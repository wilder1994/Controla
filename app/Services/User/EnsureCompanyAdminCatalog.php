<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Domain\User\AccessGrantData;
use App\Enums\AccessGrantScope;
use App\Models\User;
use App\Models\UserModuleGrant;
use App\Support\Auth\GrantableModules;

final class EnsureCompanyAdminCatalog
{
    public function __construct(
        private readonly SyncCollaboratorAccessService $syncCollaboratorAccess,
    ) {}

    public function execute(User $user): void
    {
        if (! $user->hasRole('company-admin')) {
            return;
        }

        $companyId = (int) ($user->security_company_id ?? 0);
        if ($companyId < 1) {
            return;
        }

        $hasCompanyGrants = UserModuleGrant::query()
            ->where('user_id', $user->id)
            ->where('scope', AccessGrantScope::Company->value)
            ->exists();

        if ($hasCompanyGrants) {
            $user->load('moduleGrants');
            $grants = [];
            foreach ($user->moduleGrants as $row) {
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

        $this->syncCollaboratorAccess->sync(
            $user,
            $user,
            GrantableModules::defaultCompanyManageGrants($companyId),
            $companyId,
            true,
        );
    }

    public function backfillAll(): int
    {
        $count = 0;
        User::query()->role('company-admin')->get()->each(function (User $user) use (&$count): void {
            $this->execute($user);
            $count++;
        });

        return $count;
    }
}
