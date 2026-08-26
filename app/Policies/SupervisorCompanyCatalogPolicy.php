<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class SupervisorCompanyCatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company.settings.manage')
            || $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, object $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, object $model): bool
    {
        return $this->owns($user, $model);
    }

    private function owns(User $user, object $model): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.settings.manage')
            && (int) $user->security_company_id === (int) $model->security_company_id;
    }
}
