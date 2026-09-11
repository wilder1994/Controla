<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SecurityCompany;
use App\Models\User;

final class SecurityCompanyPolicy
{
    public function view(User $actor, SecurityCompany $company): bool
    {
        if ($actor->can('platform.companies.view')) {
            return true;
        }

        return $actor->hasRole('company-admin')
            && (int) $actor->security_company_id === (int) $company->id;
    }

    public function updateProfile(User $actor, SecurityCompany $company): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }

        if ($actor->hasPermissionTo('platform.companies.manage')) {
            return true;
        }

        $sameCompany = (int) ($actor->security_company_id ?? 0) === (int) $company->id;

        return $sameCompany
            && ($actor->hasRole('company-admin') || $actor->hasPermissionTo('company.settings.manage'));
    }
}
