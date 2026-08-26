<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyJobTitle;
use App\Models\User;

final class CompanyJobTitlePolicy
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

    public function update(User $user, CompanyJobTitle $jobTitle): bool
    {
        return $this->owns($user, $jobTitle);
    }

    public function delete(User $user, CompanyJobTitle $jobTitle): bool
    {
        return $this->owns($user, $jobTitle);
    }

    private function owns(User $user, CompanyJobTitle $jobTitle): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.settings.manage')
            && (int) $user->security_company_id === (int) $jobTitle->security_company_id;
    }
}
