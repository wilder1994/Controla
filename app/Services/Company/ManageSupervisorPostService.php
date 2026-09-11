<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\PostModality;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Installation;
use App\Models\SupervisorPost;
use Illuminate\Validation\ValidationException;

final class ManageSupervisorPostService
{
    /**
     * @param  array{installation_id: int, name: string, modality?: int|PostModality, is_active?: bool, employee_ids?: list<int>}  $data
     */
    public function create(Client $client, array $data): SupervisorPost
    {
        $this->assertClientCanHavePosts($client);

        $installation = $this->installationOfClient($client, (int) $data['installation_id']);
        $name = trim($data['name']);
        $this->assertUniqueName($installation, $name);

        $post = SupervisorPost::query()->create([
            'client_id' => $client->id,
            'installation_id' => $installation->id,
            'name' => $name,
            'modality' => $this->modality($data['modality'] ?? 12),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->syncEmployees($client, $post, $data['employee_ids'] ?? []);

        return $post->refresh()->load('employees');
    }

    /**
     * @param  array{installation_id?: int, name?: string, modality?: int|PostModality, is_active?: bool, employee_ids?: list<int>}  $data
     */
    public function update(SupervisorPost $post, array $data): SupervisorPost
    {
        $client = $post->client;
        abort_unless($client instanceof Client, 404);
        $this->assertClientCanHavePosts($client);

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

        if (isset($data['modality'])) {
            $post->modality = $this->modality($data['modality']);
        }

        if (array_key_exists('is_active', $data)) {
            $post->is_active = (bool) $data['is_active'];
        }

        $post->save();

        if (array_key_exists('employee_ids', $data)) {
            $this->syncEmployees($client, $post, $data['employee_ids'] ?? []);
        }

        return $post->refresh()->load('employees');
    }

    public function delete(SupervisorPost $post): void
    {
        if ($post->reviews()->exists()) {
            throw ValidationException::withMessages([
                'post' => 'No se puede eliminar: hay revistas de Supervisión en este puesto.',
            ]);
        }

        $post->employees()->detach();
        $post->delete();
    }

    private function assertClientCanHavePosts(Client $client): void
    {
        if (! $client->has_access && ! $client->has_supervision) {
            abort(403);
        }
    }

    private function modality(int|PostModality $value): PostModality
    {
        return $value instanceof PostModality ? $value : PostModality::from((int) $value);
    }

    /** @param list<int|string> $employeeIds */
    private function syncEmployees(Client $client, SupervisorPost $post, array $employeeIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $employeeIds)));
        if ($ids === []) {
            $post->employees()->sync([]);

            return;
        }

        $valid = Employee::query()
            ->where('security_company_id', $client->security_company_id)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if (count($valid) !== count($ids)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Solo puedes asignar empleados activos de esta empresa.',
            ]);
        }

        $taken = Employee::query()
            ->whereIn('id', $valid)
            ->whereHas('supervisorPosts', fn ($q) => $q->whereKeyNot($post->id))
            ->with(['jobTitle', 'supervisorPosts.client'])
            ->get();

        if ($taken->isNotEmpty()) {
            $first = $taken->first();
            $other = $first->supervisorPosts->first();
            $job = $first->jobTitle?->name ?: 'empleado';
            $where = $other instanceof SupervisorPost
                ? $other->name.($other->client?->name ? ' · '.$other->client->name : '')
                : 'otro puesto';

            throw ValidationException::withMessages([
                'employee_ids' => $first->fullName().' ('.$job.') ya está en '.$where.'. Reasígnalo desde su ficha.',
            ]);
        }

        $post->employees()->sync($valid);
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
