<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\ClientAdminOrigin;
use App\Models\Client;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use App\Support\Platform\ActingCompanyResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

final class UserScopeResolver
{
    public function scopedQuery(User $actor): Builder
    {
        if ($actor->hasRole('super-admin')) {
            $supportCompanyId = app(ActingCompanyResolver::class)->id($actor);

            if ($supportCompanyId !== null) {
                return User::query()
                    ->where(function (Builder $query) use ($supportCompanyId): void {
                        $query->where('security_company_id', $supportCompanyId)
                            ->orWhereHas('clients', function (Builder $clientQuery) use ($supportCompanyId): void {
                                $clientQuery->where('security_company_id', $supportCompanyId);
                            });
                    })
                    ->whereDoesntHave('roles', function (Builder $roleQuery): void {
                        $roleQuery->where('name', 'super-admin');
                    });
            }

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

        if ($actor->hasAnyRole(['client-admin', 'client-installation-admin', 'admin-accesos'])) {
            $clientId = $this->resolveClientTenantId($actor);

            if ($clientId === null) {
                return User::query()->whereKey($actor->id);
            }

            return $this->clientPanelQuery($clientId);
        }

        return User::query()->whereKey($actor->id);
    }

    /** Admins externos de un cliente. No incluye personal de la empresa. */
    public function clientPanelQuery(int $clientId): Builder
    {
        return User::query()
            ->whereHas('clients', function (Builder $clientQuery) use ($clientId): void {
                $clientQuery->where('clients.id', $clientId);
            })
            ->whereHas('roles', function (Builder $roleQuery): void {
                $roleQuery->whereIn('name', AssignableRoles::clientFacingAdmins());
            })
            ->where(function (Builder $originQuery): void {
                $originQuery
                    ->where('admin_origin', ClientAdminOrigin::External->value)
                    ->orWhere(function (Builder $legacy): void {
                        $legacy->whereNull('admin_origin')->whereNull('employee_id');
                    });
            });
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
        $tenantId = app(TenantContext::class)->clientId();
        if ($tenantId !== null && $tenantId > 0) {
            return $tenantId;
        }

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
