<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

final class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company.settings.manage')
            || $user->hasRole('super-admin');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->owns($user, $employee);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->owns($user, $employee);
    }

    public function archive(User $user, Employee $employee): bool
    {
        return $this->owns($user, $employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->owns($user, $employee);
    }

    public function grantAccess(User $user, Employee $employee): bool
    {
        return $this->owns($user, $employee)
            && ($user->can('company.users.assign') || $user->hasRole('super-admin'));
    }

    private function owns(User $user, Employee $employee): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.settings.manage')
            && (int) $user->security_company_id === (int) $employee->security_company_id;
    }
}
