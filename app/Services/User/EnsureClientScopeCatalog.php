<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Domain\User\AccessGrantData;
use App\Enums\AccessGrantScope;
use App\Models\User;
use App\Models\UserModuleGrant;
use App\Support\Auth\GrantableModules;

final class EnsureClientScopeCatalog
{
    public function __construct(
        private readonly SyncCollaboratorAccessService $syncCollaboratorAccess,
    ) {}

    public function execute(User $user): void
    {
        $companyId = (int) ($user->security_company_id ?? 0);
        if ($companyId < 1) {
            return;
        }

        if ($user->hasRole('client-admin')) {
            $this->syncScope($user, $companyId, AccessGrantScope::Client, $user->clients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all());

            return;
        }

        if ($user->hasRole('client-installation-admin')) {
            $this->syncScope(
                $user,
                $companyId,
                AccessGrantScope::Installation,
                $user->assignedInstallations()->pluck('installations.id')->map(fn ($id) => (int) $id)->all(),
            );
        }
    }

    public function backfillAll(): int
    {
        $count = 0;
        User::query()->role('client-admin')->get()->each(function (User $user) use (&$count): void {
            $this->execute($user);
            $count++;
        });
        User::query()->role('client-installation-admin')->get()->each(function (User $user) use (&$count): void {
            $this->execute($user);
            $count++;
        });

        return $count;
    }

    /** @param  list<int>  $scopeIds */
    private function syncScope(User $user, int $companyId, AccessGrantScope $scope, array $scopeIds): void
    {
        if ($scopeIds === []) {
            return;
        }

        $existing = UserModuleGrant::query()
            ->where('user_id', $user->id)
            ->where('scope', $scope->value)
            ->get();

        if ($existing->isNotEmpty()) {
            $grants = [];
            foreach ($existing as $row) {
                $grants[] = new AccessGrantData(
                    $row->scope,
                    (int) $row->scope_id,
                    $row->module,
                    $row->level,
                );
            }
            $this->syncCollaboratorAccess->sync($user, $user, $grants, $companyId, true);

            return;
        }

        $grants = [];
        foreach ($scopeIds as $scopeId) {
            if ($scopeId < 1) {
                continue;
            }
            $grants = array_merge($grants, GrantableModules::defaultScopedManageGrants($scope, $scopeId));
        }
        if ($grants === []) {
            return;
        }

        $this->syncCollaboratorAccess->sync($user, $user, $grants, $companyId, true);
    }
}
