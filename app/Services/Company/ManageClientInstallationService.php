<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Geo\GeoAddressData;
use App\Models\Client;
use App\Models\ClientUserInstallationAssignment;
use App\Models\Installation;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use App\Support\Geo\ColombianArea;
use Illuminate\Validation\ValidationException;

final class ManageClientInstallationService
{
    /**
     * @param  array{name: string, is_client_site?: bool, is_active?: bool, code?: ?string, commune?: ?string, rector_user_id?: ?int, geo?: ?GeoAddressData}  $data
     */
    public function create(Client $client, array $data): Installation
    {
        $isClientSite = (bool) ($data['is_client_site'] ?? false);
        $name = $isClientSite ? trim((string) $client->name) : trim($data['name']);
        $this->assertUniqueName($client, $name);

        if ($isClientSite) {
            $this->clearClientSiteFlag($client);
        }

        $geo = $this->geoAttributes($client, $isClientSite, $data['geo'] ?? null);
        $area = $this->areaAttributes($data['commune'] ?? null, $geo['city'] ?? $client->city);

        $installation = Installation::query()->create(array_merge([
            'client_id' => $client->id,
            'name' => $name,
            'code' => $this->resolveCode($client, $data['code'] ?? null),
            'is_client_site' => $isClientSite,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ], $geo, $area));

        $this->syncRector($installation, isset($data['rector_user_id']) ? (int) $data['rector_user_id'] ?: null : null);

        return $installation->refresh();
    }

    /**
     * @param  array{name?: string, is_client_site?: bool, is_active?: bool, code?: ?string, commune?: ?string, rector_user_id?: ?int, geo?: ?GeoAddressData}  $data
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

        if (array_key_exists('code', $data)) {
            $installation->code = $this->resolveCode($client, $data['code'] ?? null, $installation->id);
        }

        $installation->fill($this->geoAttributes($client, $isClientSite, $data['geo'] ?? null));

        if (array_key_exists('commune', $data)) {
            $installation->fill($this->areaAttributes($data['commune'] ?? null, $installation->city));
        }

        $installation->save();

        if (array_key_exists('rector_user_id', $data)) {
            $this->syncRector($installation, $data['rector_user_id'] !== null ? (int) $data['rector_user_id'] ?: null : null);
        }

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

    private function resolveCode(Client $client, ?string $code, ?int $ignoreId = null): string
    {
        $code = $this->nullableString($code);
        if ($code === null) {
            $code = $this->nextCode($client, $ignoreId);
        }

        $exists = Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Ya existe una instalación con ese código en este cliente.',
            ]);
        }

        return $code;
    }

    private function nextCode(Client $client, ?int $ignoreId = null): string
    {
        return Installation::nextAvailableCode($client, $ignoreId);
    }

    private function syncRector(Installation $installation, ?int $rectorUserId): void
    {
        $installation->rector_user_id = $rectorUserId;

        if ($rectorUserId === null) {
            $installation->save();

            return;
        }

        $user = User::query()->find($rectorUserId);
        if ($user === null || ! $user->hasRole(AssignableRoles::CLIENT_INSTALLATION_ADMIN)) {
            throw ValidationException::withMessages([
                'rector_user_id' => 'El rector debe ser un admin de instalaciones.',
            ]);
        }

        if (! $user->canAccessClient((int) $installation->client_id)) {
            throw ValidationException::withMessages([
                'rector_user_id' => 'El rector no pertenece a este cliente.',
            ]);
        }

        ClientUserInstallationAssignment::query()->firstOrCreate([
            'user_id' => $user->id,
            'installation_id' => $installation->id,
        ]);

        $installation->save();
    }

    private function areaAttributes(mixed $commune, mixed $city): array
    {
        $kind = ColombianArea::classify(is_string($commune) ? $commune : null, is_string($city) ? $city : null);

        return [
            'commune' => ColombianArea::persistableValue(is_string($commune) ? $commune : null, is_string($city) ? $city : null),
            'area_kind' => $kind->value,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
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
