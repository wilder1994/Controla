<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Domain\Geo\GeoAddressData;
use App\Domain\User\CreateUserData;
use App\Domain\User\UpdateUserData;
use App\Models\Client;
use App\Models\ClientUserAssignment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use App\Support\Auth\UserManagementContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageScopedUserService
{
    public function __construct(
        private readonly \App\Services\Auth\UserScopeResolver $scopeResolver,
    ) {}

    public function create(CreateUserData $data, User $actor, UserManagementContext $context): User
    {
        $this->assertRoleAllowed($data->role, $context, $actor);

        if (User::query()->where('email', $data->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Ya existe una cuenta con este email.',
            ]);
        }

        $companyId = $this->resolveCompanyId($data, $context, $actor);
        $clientIds = $this->normalizeClientIds($data->clientIds, $data->role, $companyId, $context, $actor);

        return DB::transaction(function () use ($data, $companyId, $clientIds): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => $data->isActive,
                'security_company_id' => $companyId,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$data->role]);
            $this->syncClientAssignments($user, $clientIds, $data->role);

            return $user->fresh(['roles', 'clients']);
        });
    }

    public function update(User $target, UpdateUserData $data, User $actor, UserManagementContext $context): User
    {
        if ($data->role !== null) {
            $this->assertRoleAllowed($data->role, $context, $actor);
        }

        if ($data->email !== $target->email && User::query()->where('email', $data->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Ya existe una cuenta con este email.',
            ]);
        }

        $companyId = (int) ($target->security_company_id ?? $this->scopeCompanyId($actor));
        $role = $data->role ?? $target->getRoleNames()->first() ?? '';
        $clientIds = $data->clientIds ?? $target->clients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all();
        $clientIds = $this->normalizeClientIds($clientIds, $role, $companyId, $context, $actor);

        return DB::transaction(function () use ($target, $data, $role, $clientIds): User {
            $attributes = [
                'name' => $data->name,
                'email' => $data->email,
                'is_active' => $data->isActive,
            ];

            if ($data->password !== null && $data->password !== '') {
                $attributes['password'] = $data->password;
            }

            $target->update($attributes);

            if ($data->role !== null) {
                $target->syncRoles([$data->role]);
            }

            $this->syncClientAssignments($target, $clientIds, $role);

            return $target->fresh(['roles', 'clients']);
        });
    }

    private function assertRoleAllowed(string $role, UserManagementContext $context, User $actor): void
    {
        $allowed = match ($context) {
            UserManagementContext::Platform => AssignableRoles::forPlatform(),
            UserManagementContext::Company => AssignableRoles::forCompany(),
            UserManagementContext::Client => AssignableRoles::forClient(),
        };

        if (! in_array($role, $allowed, true)) {
            throw ValidationException::withMessages([
                'role' => 'No puedes asignar este rol desde este panel.',
            ]);
        }

        if ($role === 'super-admin' && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'role' => 'No puedes asignar el rol de súper administrador.',
            ]);
        }
    }

    private function resolveCompanyId(CreateUserData $data, UserManagementContext $context, User $actor): ?int
    {
        if ($context === UserManagementContext::Platform && $data->securityCompanyId) {
            return $data->securityCompanyId;
        }

        if ($context === UserManagementContext::Company) {
            return $this->scopeCompanyId($actor);
        }

        if ($context === UserManagementContext::Client) {
            $clientId = $this->scopeResolver->resolveClientTenantId($actor);

            if ($clientId === null) {
                throw ValidationException::withMessages([
                    'client_ids' => 'No hay conjunto activo para asignar usuarios.',
                ]);
            }

            return (int) Client::query()->whereKey($clientId)->value('security_company_id');
        }

        return $data->securityCompanyId;
    }

    private function scopeCompanyId(User $actor): ?int
    {
        $id = $actor->security_company_id;

        return $id ? (int) $id : null;
    }

    /**
     * @param list<int> $clientIds
     * @return list<int>
     */
    private function normalizeClientIds(
        array $clientIds,
        string $role,
        ?int $companyId,
        UserManagementContext $context,
        User $actor,
    ): array {
        if (! in_array($role, AssignableRoles::requiringClientAssignment(), true)) {
            return [];
        }

        if ($context === UserManagementContext::Client) {
            $tenantId = $this->scopeResolver->resolveClientTenantId($actor);

            if ($tenantId === null) {
                throw ValidationException::withMessages([
                    'client_ids' => 'Conjunto no disponible.',
                ]);
            }

            return [(int) $tenantId];
        }

        $clientIds = array_values(array_unique(array_map('intval', $clientIds)));

        if ($clientIds === []) {
            throw ValidationException::withMessages([
                'client_ids' => 'Selecciona al menos un conjunto para este rol.',
            ]);
        }

        if ($companyId !== null) {
            $validCount = Client::query()
                ->where('security_company_id', $companyId)
                ->whereIn('id', $clientIds)
                ->count();

            if ($validCount !== count($clientIds)) {
                throw ValidationException::withMessages([
                    'client_ids' => 'Uno o más conjuntos no pertenecen a la empresa.',
                ]);
            }
        }

        return $clientIds;
    }

    /** @param list<int> $clientIds */
    private function syncClientAssignments(User $user, array $clientIds, string $role): void
    {
        if (! in_array($role, AssignableRoles::requiringClientAssignment(), true)) {
            ClientUserAssignment::query()->where('user_id', $user->id)->delete();
            $user->update(['primary_client_id' => null]);

            return;
        }

        ClientUserAssignment::query()->where('user_id', $user->id)->delete();

        foreach ($clientIds as $index => $clientId) {
            ClientUserAssignment::query()->create([
                'user_id' => $user->id,
                'client_id' => $clientId,
                'is_primary' => $index === 0,
                'assigned_at' => now(),
            ]);
        }

        $user->update(['primary_client_id' => $clientIds[0]]);
    }
}
