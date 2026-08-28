<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Employee\Data\SaveEmployeeData;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use Illuminate\Validation\ValidationException;

final class ManageEmployeeService
{
    public function create(SaveEmployeeData $data): Employee
    {
        $this->assertJobTitle($data);
        $this->assertCollaboratorType($data);
        $this->assertUniqueDocument($data);
        $this->assertUniqueEmail($data);

        return Employee::query()->create($this->attributes($data) + [
            'is_active' => true,
            'ceased_at' => null,
        ]);
    }

    public function update(Employee $employee, SaveEmployeeData $data): Employee
    {
        $this->assertJobTitle($data, $employee);
        $this->assertCollaboratorType($data, $employee);
        $this->assertUniqueDocument($data, $employee->id);
        $this->assertUniqueEmail($data, $employee->id);

        $employee->update($this->attributes($data));

        return $employee->refresh();
    }

    public function archive(Employee $employee): Employee
    {
        if (! $employee->is_active) {
            return $employee;
        }

        $employee->update([
            'is_active' => false,
            'ceased_at' => now()->toDateString(),
        ]);

        $employee->user?->update(['is_active' => false]);

        return $employee->refresh();
    }

    public function restore(Employee $employee): Employee
    {
        $employee->update([
            'is_active' => true,
            'ceased_at' => null,
        ]);

        return $employee->refresh();
    }

    /** @return array<string, mixed> */
    private function attributes(SaveEmployeeData $data): array
    {
        return [
            'security_company_id' => $data->securityCompanyId,
            'job_title_id' => $data->jobTitleId,
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'last_name_paternal' => $data->lastNamePaternal,
            'last_name_maternal' => $data->lastNameMaternal,
            'first_names' => $data->firstNames,
            'sex' => $data->sex,
            'birth_date' => $data->birthDate,
            'collaborator_type_id' => $data->collaboratorTypeId,
            'email' => $data->email,
            'nationality' => $data->nationality,
            'blood_group' => $data->bloodGroup,
            'birth_department' => $data->birthDepartment,
            'birth_city' => $data->birthCity,
            'emergency_phone' => $data->emergencyPhone,
            'emergency_contact' => $data->emergencyContact,
            'has_disability' => $data->hasDisability,
            'document_issue_department' => $data->documentIssueDepartment,
            'document_issue_city' => $data->documentIssueCity,
            'document_issued_at' => $data->documentIssuedAt,
            'same_cost_center' => $data->sameCostCenter,
        ];
    }

    private function assertJobTitle(SaveEmployeeData $data, ?Employee $employee = null): void
    {
        $ok = CompanyJobTitle::query()
            ->whereKey($data->jobTitleId)
            ->where('security_company_id', $data->securityCompanyId)
            ->where(function ($query) use ($employee): void {
                $query->where('is_active', true);
                if ($employee !== null) {
                    $query->orWhere('id', $employee->job_title_id);
                }
            })
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'job_title_id' => 'Selecciona un cargo activo de la empresa.',
            ]);
        }
    }

    private function assertCollaboratorType(SaveEmployeeData $data, ?Employee $employee = null): void
    {
        $ok = CompanyCollaboratorType::query()
            ->whereKey($data->collaboratorTypeId)
            ->where('security_company_id', $data->securityCompanyId)
            ->where(function ($query) use ($employee): void {
                $query->where('is_active', true);
                if ($employee !== null) {
                    $query->orWhere('id', $employee->collaborator_type_id);
                }
            })
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'collaborator_type_id' => 'Selecciona un tipo de colaborador activo de la empresa.',
            ]);
        }
    }

    private function assertUniqueDocument(SaveEmployeeData $data, ?int $ignoreId = null): void
    {
        $exists = Employee::query()
            ->where('security_company_id', $data->securityCompanyId)
            ->where('document_number', $data->documentNumber)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'document_number' => 'Ya existe un empleado con este documento en la empresa.',
            ]);
        }
    }

    private function assertUniqueEmail(SaveEmployeeData $data, ?int $ignoreId = null): void
    {
        $exists = Employee::query()
            ->where('security_company_id', $data->securityCompanyId)
            ->where('email', $data->email)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'Ya existe un empleado con este correo en la empresa.',
            ]);
        }
    }
}
