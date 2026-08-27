<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Location;
use Illuminate\Validation\ValidationException;

final class ManageClientAccessPointService
{
    /**
     * @param  array{
     *     installation_id: int,
     *     code: string,
     *     name: string,
     *     address?: string|null,
     *     phone?: string|null,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     geo_radius_m?: int|null,
     *     is_active?: bool
     * }  $data
     */
    public function create(Client $client, array $data): Location
    {
        $installation = $this->installationOfClient($client, (int) $data['installation_id']);
        $code = strtoupper(trim($data['code']));
        $this->assertUniqueCode($client, $code);

        return Location::query()->create([
            'client_id' => $client->id,
            'installation_id' => $installation->id,
            'code' => $code,
            'name' => trim($data['name']),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'geo_radius_m' => $data['geo_radius_m'] ?? 250,
            'type' => 'access_point',
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array{
     *     installation_id?: int,
     *     code?: string,
     *     name?: string,
     *     address?: string|null,
     *     phone?: string|null,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     geo_radius_m?: int|null,
     *     is_active?: bool
     * }  $data
     */
    public function update(Location $location, array $data): Location
    {
        $client = $location->client;
        abort_unless($client instanceof Client, 404);

        if (isset($data['installation_id'])) {
            $installation = $this->installationOfClient($client, (int) $data['installation_id']);
            $location->installation_id = $installation->id;
        }

        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            $this->assertUniqueCode($client, $code, $location->id);
            $location->code = $code;
        }

        if (isset($data['name'])) {
            $location->name = trim($data['name']);
        }

        foreach (['address', 'phone', 'latitude', 'longitude'] as $field) {
            if (array_key_exists($field, $data)) {
                $location->{$field} = $data[$field];
            }
        }

        if (array_key_exists('geo_radius_m', $data) && $data['geo_radius_m'] !== null) {
            $location->geo_radius_m = (int) $data['geo_radius_m'];
        }

        if (array_key_exists('is_active', $data)) {
            $location->is_active = (bool) $data['is_active'];
        }

        $location->type = 'access_point';
        $location->save();

        return $location->refresh();
    }

    public function delete(Location $location): void
    {
        $location->delete();
    }

    private function installationOfClient(Client $client, int $installationId): Installation
    {
        $installation = Installation::query()
            ->withoutGlobalScopes()
            ->where('id', $installationId)
            ->where('client_id', $client->id)
            ->first();

        if ($installation === null) {
            throw ValidationException::withMessages([
                'installation_id' => 'La instalación no pertenece a este cliente.',
            ]);
        }

        return $installation;
    }

    private function assertUniqueCode(Client $client, string $code, ?int $ignoreId = null): void
    {
        $exists = Location::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Ya existe un acceso con ese código en este cliente.',
            ]);
        }
    }
}
