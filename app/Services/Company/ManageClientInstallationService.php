<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Validation\ValidationException;

final class ManageClientInstallationService
{
    /** @param array{name: string, is_client_site?: bool, is_active?: bool} $data */
    public function create(Client $client, array $data): Installation
    {
        $name = trim($data['name']);
        $this->assertUniqueName($client, $name);
        $isClientSite = (bool) ($data['is_client_site'] ?? false);

        if ($isClientSite) {
            $this->clearClientSiteFlag($client);
        }

        return Installation::query()->create([
            'client_id' => $client->id,
            'name' => $name,
            'is_client_site' => $isClientSite,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /** @param array{name?: string, is_client_site?: bool, is_active?: bool} $data */
    public function update(Installation $installation, array $data): Installation
    {
        $client = $installation->client;
        abort_unless($client instanceof Client, 404);

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            $this->assertUniqueName($client, $name, $installation->id);
            $installation->name = $name;
        }

        if (array_key_exists('is_client_site', $data)) {
            $isClientSite = (bool) $data['is_client_site'];
            if ($isClientSite) {
                $this->clearClientSiteFlag($client, $installation->id);
            }
            $installation->is_client_site = $isClientSite;
        }

        if (array_key_exists('is_active', $data)) {
            $installation->is_active = (bool) $data['is_active'];
        }

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
