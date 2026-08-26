<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\User\CreateUserData;
use App\Models\Employee;
use App\Models\User;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\UserManagementContext;
use Illuminate\Validation\ValidationException;

final class GrantEmployeeAccessService
{
    public function __construct(
        private readonly ManageScopedUserService $manageScopedUserService,
    ) {}

    /**
     * @param  list<int>  $clientIds
     */
    public function execute(
        Employee $employee,
        User $actor,
        string $role,
        string $password,
        array $clientIds = [],
    ): User {
        if (! $employee->is_active) {
            throw ValidationException::withMessages([
                'role' => 'No se puede dar acceso a un empleado archivado.',
            ]);
        }

        if ($employee->user()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Este empleado ya tiene un usuario de acceso.',
            ]);
        }

        return $this->manageScopedUserService->create(
            new CreateUserData(
                name: $employee->fullName(),
                email: $employee->email,
                password: $password,
                role: $role,
                securityCompanyId: (int) $employee->security_company_id,
                clientIds: $clientIds,
                isActive: true,
                jobTitle: $employee->jobTitle?->name,
                employeeId: $employee->id,
            ),
            $actor,
            UserManagementContext::Company,
        );
    }
}
