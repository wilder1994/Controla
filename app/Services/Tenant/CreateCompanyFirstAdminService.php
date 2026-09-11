<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Employee\Data\SaveEmployeeData;
use App\Domain\User\CreateUserData;
use App\Enums\BloodGroup;
use App\Enums\Sex;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\IdentityDocumentType;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Auth\AllocateLoginUsername;
use App\Services\Company\ManageEmployeeService;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\UserManagementContext;
use App\Support\Geo\ColombiaDivipola;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateCompanyFirstAdminService
{
    public function __construct(
        private readonly ManageEmployeeService $manageEmployeeService,
        private readonly ManageScopedUserService $manageScopedUserService,
        private readonly AllocateLoginUsername $usernames,
    ) {}

    /** @return array<string, mixed> */
    public function formOptions(SecurityCompany $company): array
    {
        return [
            'jobTitles' => CompanyJobTitle::query()
                ->where('security_company_id', $company->id)
                ->active()
                ->get(['id', 'name']),
            'collaboratorTypes' => CompanyCollaboratorType::query()
                ->where('security_company_id', $company->id)
                ->active()
                ->get(['id', 'name']),
            'documentTypes' => IdentityDocumentType::optionsForSelect(),
            'sexOptions' => Sex::options(),
            'bloodGroups' => BloodGroup::options(),
            'colombiaPlaces' => ColombiaDivipola::tree(),
        ];
    }

    /**
     * @return array{username: string, password: string}
     */
    public function preview(string $firstNames, string $lastPaternal, string $lastMaternal = ''): array
    {
        return [
            'username' => $this->usernames->forNames($firstNames, $lastPaternal !== '' ? $lastPaternal : $lastMaternal),
            'password' => $this->usernames->randomPassword(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{user: User, employee: Employee, password: string}
     */
    public function execute(SecurityCompany $company, User $actor, array $validated): array
    {
        if ($company->hasCompanyAdmin()) {
            throw ValidationException::withMessages([
                'first_names' => 'Esta empresa ya tiene un administrador.',
            ]);
        }

        return DB::transaction(function () use ($company, $actor, $validated): array {
            $employee = $this->manageEmployeeService->create(
                SaveEmployeeData::fromValidated($validated, (int) $company->id),
            );

            $user = $this->manageScopedUserService->create(
                new CreateUserData(
                    name: $employee->fullName(),
                    username: (string) $validated['username'],
                    email: null,
                    password: (string) $validated['password'],
                    role: 'company-admin',
                    securityCompanyId: (int) $company->id,
                    clientIds: [],
                    isActive: true,
                    jobTitle: $employee->jobTitle?->name,
                    employeeId: $employee->id,
                    mustChangePassword: true,
                ),
                $actor,
                UserManagementContext::Platform,
            );

            return [
                'user' => $user,
                'employee' => $employee,
                'password' => (string) $validated['password'],
            ];
        });
    }
}
