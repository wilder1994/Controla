<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserScopeResolver
{
    public function scopedQuery(User $actor): Builder
    {
        if ($actor->hasRole('super-admin')) {
            return User::query();
        }

        if ($actor->hasRole('company-admin') && $actor->security_company_id) {
            $companyId = (int) $actor->security_company_id;

            return User::query()
                ->where(function (Builder $query) use ($companyId): void {
                    $query->where('security_company_id', $companyId)
                        ->orWhereHas('clients', function (Builder $clientQuery) use ($companyId): void {
                            $clientQuery->where('security_company_id', $companyId);
                        });
                })
                ->whereDoesntHave('roles', function (Builder $roleQuery): void {
                    $roleQuery->where('name', 'super-admin');
                });
        }

        if ($actor->hasAnyRole(['client-admin', 'admin-accesos'])) {
            $clientId = $this->resolveClientTenantId($actor);

            if ($clientId === null) {
                return User::query()->whereKey($actor->id);
            }

            return User::query()->where(function (Builder $query) use ($actor, $clientId): void {
                $query->whereKey($actor->id)
                    ->orWhere(function (Builder $scoped) use ($clientId): void {
                        $scoped->whereHas('clients', function (Builder $clientQuery) use ($clientId): void {
                            $clientQuery->where('clients.id', $clientId);
                        })->whereHas('roles', function (Builder $roleQuery): void {
                            $roleQuery->whereIn('name', [
                                'resident',
                                'anfitrion',
                                'guardia',
                                'supervisor',
                            ]);
                        });
                    });
            });
        }

        return User::query()->whereKey($actor->id);
    }

    public function canManage(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        return $this->scopedQuery($actor)->whereKey($target->id)->exists();
    }

    public function resolveClientTenantId(User $actor): ?int
    {
        if ($actor->primary_client_id) {
            return (int) $actor->primary_client_id;
        }

        $assignment = $actor->clients()->orderByDesc('client_user_assignments.is_primary')->first();

        return $assignment ? (int) $assignment->id : null;
    }

    public function companyIdForActor(User $actor): ?int
    {
        if ($actor->security_company_id) {
            return (int) $actor->security_company_id;
        }

        $clientId = $this->resolveClientTenantId($actor);

        if ($clientId === null) {
            return null;
        }

        return (int) Client::query()->whereKey($clientId)->value('security_company_id');
    }
}
