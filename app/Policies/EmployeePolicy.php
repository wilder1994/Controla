<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

final class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company.employees.view')
            || $user->can('company.employees.manage')
            || $user->hasRole('super-admin');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->sameCompany($user, $employee)
            && ($user->can('company.employees.view')
                || $user->can('company.employees.manage')
                || $user->hasRole('super-admin'));
    }

    public function create(User $user): bool
    {
        return $user->can('company.employees.manage')
            || $user->hasRole('super-admin');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->canMutate($user, $employee);
    }

    public function archive(User $user, Employee $employee): bool
    {
        return $this->canMutate($user, $employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->canMutate($user, $employee);
    }

    public function grantAccess(User $user, Employee $employee): bool
    {
        return $this->sameCompany($user, $employee)
            && ($user->can('company.users.assign') || $user->hasRole('super-admin'));
    }

    private function canMutate(User $user, Employee $employee): bool
    {
        return $this->sameCompany($user, $employee)
            && ($user->can('company.employees.manage') || $user->hasRole('super-admin'));
    }

    private function sameCompany(User $user, Employee $employee): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return (int) $user->security_company_id === (int) $employee->security_company_id;
    }
}
