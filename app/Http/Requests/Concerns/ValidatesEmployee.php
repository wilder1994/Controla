<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Enums\BloodGroup;
use App\Enums\Sex;
use App\Support\Legal\CorpusAcceptanceRules;
use Illuminate\Validation\Rule;

trait ValidatesEmployee
{
    /**
     * @return array<string, mixed>
     */
    protected function employeeFieldRules(
        int $companyId,
        ?int $ignoreEmployeeId = null,
        ?int $currentJobTitleId = null,
        ?int $currentCollaboratorTypeId = null,
    ): array {
        $jobTitleRule = Rule::exists('company_job_titles', 'id')->where(function ($query) use ($companyId, $currentJobTitleId): void {
            $query->where('security_company_id', $companyId)
                ->where(function ($query) use ($currentJobTitleId): void {
                    $query->where('is_active', true);
                    if ($currentJobTitleId !== null) {
                        $query->orWhere('id', $currentJobTitleId);
                    }
                });
        });

        $collaboratorTypeRule = Rule::exists('company_collaborator_types', 'id')->where(function ($query) use ($companyId, $currentCollaboratorTypeId): void {
            $query->where('security_company_id', $companyId)
                ->where(function ($query) use ($currentCollaboratorTypeId): void {
                    $query->where('is_active', true);
                    if ($currentCollaboratorTypeId !== null) {
                        $query->orWhere('id', $currentCollaboratorTypeId);
                    }
                });
        });

        $documentUnique = Rule::unique('employees', 'document_number')->where('security_company_id', $companyId);
        $emailUnique = Rule::unique('employees', 'email')->where('security_company_id', $companyId);

        if ($ignoreEmployeeId !== null) {
            $documentUnique->ignore($ignoreEmployeeId);
            $emailUnique->ignore($ignoreEmployeeId);
        }

        return [
            'document_type' => CorpusAcceptanceRules::documentTypeRule(),
            'document_number' => ['required', 'string', 'max:40', $documentUnique],
            'last_name_paternal' => ['nullable', 'string', 'max:80', 'required_without:last_name_maternal'],
            'last_name_maternal' => ['nullable', 'string', 'max:80', 'required_without:last_name_paternal'],
            'first_names' => ['required', 'string', 'max:120'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'birth_date' => ['required', 'date', 'before:today'],
            'collaborator_type_id' => ['required', 'integer', $collaboratorTypeRule],
            'job_title_id' => ['required', 'integer', $jobTitleRule],
            'email' => ['required', 'email', 'max:150', $emailUnique],
            'nationality' => ['required', 'string', 'max:80'],
            'blood_group' => ['required', Rule::enum(BloodGroup::class)],
            'birth_department' => ['nullable', 'string', 'max:120'],
            'birth_city' => ['nullable', 'string', 'max:120'],
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
            'has_disability' => ['sometimes', 'boolean'],
            'document_issue_department' => ['nullable', 'string', 'max:120'],
            'document_issue_city' => ['nullable', 'string', 'max:120'],
            'document_issued_at' => ['nullable', 'date'],
            'same_cost_center' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function employeeAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'job_title_id' => 'cargo',
            'collaborator_type_id' => 'tipo de colaborador',
            'blood_group' => 'grupo sanguíneo',
            'first_names' => 'nombres',
            'last_name_paternal' => 'apellido paterno',
            'last_name_maternal' => 'apellido materno',
        ];
    }

    /** @return array<string, string> */
    protected function employeeMessages(): array
    {
        return [
            'last_name_paternal.required_without' => 'Indica al menos un apellido (paterno o materno).',
            'last_name_maternal.required_without' => 'Indica al menos un apellido (paterno o materno).',
        ];
    }

    protected function prepareEmployeeBooleans(): void
    {
        if ($this->input('same_cost_center') === '') {
            $this->merge(['same_cost_center' => null]);
        }
    }
}
