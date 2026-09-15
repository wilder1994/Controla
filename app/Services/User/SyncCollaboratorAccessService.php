<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Domain\User\AccessGrantData;
use App\Enums\AccessGrantLevel;
use App\Enums\AccessGrantScope;
use App\Models\Client;
use App\Models\Installation;
use App\Models\User;
use App\Models\UserModuleGrant;
use App\Support\Auth\GrantableModules;
use Illuminate\Validation\ValidationException;

final class SyncCollaboratorAccessService
{
    /**
     * @param  list<AccessGrantData>  $grants
     */
    public function sync(User $user, User $actor, array $grants, int $companyId, bool $trusted = false): void
    {
        if ($grants === []) {
            throw ValidationException::withMessages([
                'grants' => 'El colaborador necesita al menos un permiso Ver o Gestionar.',
            ]);
        }

        if (! $trusted) {
            $this->assertActorCanGrant($actor, $grants, $companyId);
        }
        $this->assertScopesBelongToCompany($grants, $companyId);

        UserModuleGrant::query()->where('user_id', $user->id)->delete();

        foreach ($grants as $grant) {
            UserModuleGrant::query()->create([
                'user_id' => $user->id,
                'scope' => $grant->scope->value,
                'scope_id' => $grant->scopeId,
                'module' => $grant->module,
                'level' => $grant->level->value,
            ]);
        }

        $permissions = [];
        foreach ($grants as $grant) {
            $permissions = array_merge(
                $permissions,
                GrantableModules::permissionsFor($grant->scope, $grant->module, $grant->level),
            );
        }

        $user->syncPermissions(array_values(array_unique($permissions)));
    }

    public function clear(User $user): void
    {
        UserModuleGrant::query()->where('user_id', $user->id)->delete();
        $user->syncPermissions([]);
    }

    /**
     * @param  list<AccessGrantData>  $grants
     */
    private function assertActorCanGrant(User $actor, array $grants, int $companyId): void
    {
        if ($actor->hasAnyRole(['super-admin', 'company-admin'])) {
            return;
        }

        if (! $actor->hasRole('colaborador')) {
            throw ValidationException::withMessages([
                'grants' => 'No puedes asignar una matriz de permisos.',
            ]);
        }

        foreach ($grants as $grant) {
            $actorLevel = $this->actorLevel($actor, $grant, $companyId);
            if ($actorLevel === null || ! $actorLevel->allows($grant->level)) {
                throw ValidationException::withMessages([
                    'grants' => 'No puedes conceder más acceso del que tienes.',
                ]);
            }
        }
    }

    private function actorLevel(User $actor, AccessGrantData $grant, int $companyId): ?AccessGrantLevel
    {
        $companyWide = $actor->moduleGrants
            ->first(fn (UserModuleGrant $row) => $row->scope === AccessGrantScope::Company
                && (int) $row->scope_id === $companyId
                && $row->module === $grant->module);

        if ($companyWide !== null) {
            return $companyWide->level;
        }

        if ($grant->scope === AccessGrantScope::Company) {
            return $companyWide?->level;
        }

        $companyKey = $grant->scope === AccessGrantScope::Installation ? 'installations' : 'clients';
        $viaCompany = $actor->moduleGrants
            ->first(fn (UserModuleGrant $row) => $row->scope === AccessGrantScope::Company
                && (int) $row->scope_id === $companyId
                && $row->module === $companyKey);
        if ($viaCompany !== null) {
            return $viaCompany->level;
        }

        $same = $actor->moduleGrants
            ->first(fn (UserModuleGrant $row) => $row->scope === $grant->scope
                && (int) $row->scope_id === $grant->scopeId
                && $row->module === $grant->module);

        return $same?->level;
    }

    /**
     * @param  list<AccessGrantData>  $grants
     */
    private function assertScopesBelongToCompany(array $grants, int $companyId): void
    {
        foreach ($grants as $grant) {
            if ($grant->scope === AccessGrantScope::Company && $grant->scopeId !== $companyId) {
                throw ValidationException::withMessages([
                    'grants' => 'La matriz de empresa no coincide con la empresa actual.',
                ]);
            }

            if ($grant->scope === AccessGrantScope::Client) {
                $ok = Client::query()
                    ->whereKey($grant->scopeId)
                    ->where('security_company_id', $companyId)
                    ->exists();
                if (! $ok) {
                    throw ValidationException::withMessages([
                        'grants' => 'Un cliente de la matriz no pertenece a la empresa.',
                    ]);
                }
            }

            if ($grant->scope === AccessGrantScope::Installation) {
                $ok = Installation::query()
                    ->whereKey($grant->scopeId)
                    ->whereHas('client', fn ($q) => $q->where('security_company_id', $companyId))
                    ->exists();
                if (! $ok) {
                    throw ValidationException::withMessages([
                        'grants' => 'Una instalación de la matriz no pertenece a la empresa.',
                    ]);
                }
            }
        }
    }
}
