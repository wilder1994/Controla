<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyCollaboratorType;
use App\Models\User;

final class CompanyCollaboratorTypePolicy
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

    public function update(User $user, CompanyCollaboratorType $collaboratorType): bool
    {
        return $this->owns($user, $collaboratorType);
    }

    public function delete(User $user, CompanyCollaboratorType $collaboratorType): bool
    {
        return $this->owns($user, $collaboratorType);
    }

    private function owns(User $user, CompanyCollaboratorType $collaboratorType): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.settings.manage')
            && (int) $user->security_company_id === (int) $collaboratorType->security_company_id;
    }
}
