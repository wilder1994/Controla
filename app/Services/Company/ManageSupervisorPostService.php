<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Client;
use App\Models\Installation;
use App\Models\SupervisorPost;
use Illuminate\Validation\ValidationException;

final class ManageSupervisorPostService
{
    /** @param array{installation_id: int, name: string, is_active?: bool} $data */
    public function create(Client $client, array $data): SupervisorPost
    {
        abort_unless($client->has_supervision, 403);

        $installation = $this->installationOfClient($client, (int) $data['installation_id']);
        $name = trim($data['name']);
        $this->assertUniqueName($installation, $name);

        return SupervisorPost::query()->create([
            'client_id' => $client->id,
            'installation_id' => $installation->id,
            'name' => $name,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /** @param array{installation_id?: int, name?: string, is_active?: bool} $data */
    public function update(SupervisorPost $post, array $data): SupervisorPost
    {
        $client = $post->client;
        abort_unless($client instanceof Client, 404);

        if (isset($data['installation_id'])) {
            $installation = $this->installationOfClient($client, (int) $data['installation_id']);
            $post->installation_id = $installation->id;
        }

        if (isset($data['name'])) {
            $name = trim($data['name']);
            $installation = $post->installation;
            abort_unless($installation instanceof Installation, 404);
            $this->assertUniqueName($installation, $name, $post->id);
            $post->name = $name;
        }

        if (array_key_exists('is_active', $data)) {
            $post->is_active = (bool) $data['is_active'];
        }

        $post->save();

        return $post->refresh();
    }

    public function delete(SupervisorPost $post): void
    {
        if ($post->reviews()->exists()) {
            throw ValidationException::withMessages([
                'post' => 'No se puede eliminar: hay revistas de Supervisión en este puesto.',
            ]);
        }

        $post->delete();
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

    private function assertUniqueName(Installation $installation, string $name, ?int $ignoreId = null): void
    {
        $exists = SupervisorPost::query()
            ->withoutGlobalScopes()
            ->where('installation_id', $installation->id)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un puesto con ese nombre en esta instalación.',
            ]);
        }
    }
}
