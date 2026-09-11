<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\User\CreateUserData;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\AllocateLoginUsername;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\UserManagementContext;
use Illuminate\Validation\ValidationException;

final class GrantEmployeeAccessService
{
    public function __construct(
        private readonly ManageScopedUserService $manageScopedUserService,
        private readonly AllocateLoginUsername $usernames,
    ) {}

    /**
     * @return array{username: string, password: string}
     */
    public function previewCredentials(Employee $employee): array
    {
        $this->assertCanGrant($employee);

        return [
            'username' => $this->usernames->forEmployee($employee),
            'password' => $this->usernames->randomPassword(),
        ];
    }

    /**
     * @param  list<int>  $clientIds
     */
    public function execute(
        Employee $employee,
        User $actor,
        string $role,
        string $password,
        array $clientIds = [],
        ?string $username = null,
        ?string $jobTitle = null,
    ): User {
        $this->assertCanGrant($employee);

        return $this->manageScopedUserService->create(
            new CreateUserData(
                name: $employee->fullName(),
                username: filled($username) ? (string) $username : $this->usernames->forEmployee($employee),
                email: null,
                password: $password,
                role: $role,
                securityCompanyId: (int) $employee->security_company_id,
                clientIds: $clientIds,
                isActive: true,
                jobTitle: $jobTitle ?: $employee->jobTitle?->name,
                employeeId: $employee->id,
                mustChangePassword: true,
                adminOrigin: $role === 'client-admin' ? \App\Enums\ClientAdminOrigin::Internal : null,
                documentNumber: $employee->document_number,
            ),
            $actor,
            UserManagementContext::Company,
        );
    }

    private function assertCanGrant(Employee $employee): void
    {
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
    }
}
