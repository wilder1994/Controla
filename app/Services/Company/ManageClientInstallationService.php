<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Geo\GeoAddressData;
use App\Models\Client;
use App\Models\Installation;
use Illuminate\Validation\ValidationException;

final class ManageClientInstallationService
{
    /**
     * @param  array{name: string, is_client_site?: bool, is_active?: bool, geo?: ?GeoAddressData}  $data
     */
    public function create(Client $client, array $data): Installation
    {
        $isClientSite = (bool) ($data['is_client_site'] ?? false);
        $name = $isClientSite ? trim((string) $client->name) : trim($data['name']);
        $this->assertUniqueName($client, $name);

        if ($isClientSite) {
            $this->clearClientSiteFlag($client);
        }

        return Installation::query()->create(array_merge([
            'client_id' => $client->id,
            'name' => $name,
            'is_client_site' => $isClientSite,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ], $this->geoAttributes($client, $isClientSite, $data['geo'] ?? null)));
    }

    /**
     * @param  array{name?: string, is_client_site?: bool, is_active?: bool, geo?: ?GeoAddressData}  $data
     */
    public function update(Installation $installation, array $data): Installation
    {
        $client = $installation->client;
        abort_unless($client instanceof Client, 404);

        $isClientSite = array_key_exists('is_client_site', $data)
            ? (bool) $data['is_client_site']
            : $installation->is_client_site;

        $name = $isClientSite
            ? trim((string) $client->name)
            : (isset($data['name']) ? trim((string) $data['name']) : $installation->name);

        $this->assertUniqueName($client, $name, $installation->id);
        $installation->name = $name;

        if ($isClientSite) {
            $this->clearClientSiteFlag($client, $installation->id);
        }
        $installation->is_client_site = $isClientSite;

        if (array_key_exists('is_active', $data)) {
            $installation->is_active = (bool) $data['is_active'];
        }

        $installation->fill($this->geoAttributes($client, $isClientSite, $data['geo'] ?? null));
        $installation->save();

        return $installation->refresh();
    }

    public function delete(Installation $installation): void
    {
        if ($installation->locations()->exists()) {
            throw ValidationException::withMessages([
                'installation' => 'No se puede eliminar: tiene puntos de acceso. Elimínelos o muévalos antes.',
            ]);
        }

        if ($installation->supervisorPosts()->exists()) {
            throw ValidationException::withMessages([
                'installation' => 'No se puede eliminar: tiene puestos de Supervisión. Elimínelos antes.',
            ]);
        }

        $installation->delete();
    }

    private function geoAttributes(Client $client, bool $isClientSite, ?GeoAddressData $geo): array
    {
        if ($isClientSite) {
            if ($client->latitude === null || $client->longitude === null) {
                throw ValidationException::withMessages([
                    'is_client_site' => 'La ficha del cliente no tiene ubicación. Complétala o crea la instalación con el mapa.',
                ]);
            }

            return [
                'address' => $client->address,
                'city' => $client->city,
                'department' => $client->department,
                'latitude' => $client->latitude,
                'longitude' => $client->longitude,
            ];
        }

        if ($geo === null || $geo->latitude === null || $geo->longitude === null) {
            throw ValidationException::withMessages([
                'latitude' => 'Fija la ubicación de la instalación en el mapa.',
            ]);
        }

        return $geo->toModelAttributes();
    }

    private function assertUniqueName(Client $client, string $name, ?int $ignoreId = null): void
    {
        $exists = Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe una instalación con ese nombre en este cliente.',
            ]);
        }
    }

    private function clearClientSiteFlag(Client $client, ?int $ignoreId = null): void
    {
        Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('is_client_site', true)
            ->update(['is_client_site' => false]);
    }
}
