<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Enums\BloodGroup;
use App\Enums\Sex;
use App\Support\Geo\ColombiaDivipola;
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
            ...ColombiaDivipola::placeRules('birth_department', 'birth_city'),
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
            'has_disability' => ['sometimes', 'boolean'],
            ...ColombiaDivipola::placeRules('document_issue_department', 'document_issue_city'),
            'document_issued_at' => ['nullable', 'date'],
            'same_cost_center' => ['nullable', 'boolean'],
            'education' => ['nullable', 'string', 'max:120'],
            'marital_status' => ['nullable', 'string', 'max:80'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'residence_city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'engagement_type' => ['nullable', 'string', 'max:80'],
            'contributor_type' => ['nullable', 'string', 'max:80'],
            'labor_contract_type' => ['nullable', 'string', 'max:80'],
            'hired_on' => ['nullable', 'date'],
            'labor_contract_ends_on' => ['nullable', 'date'],
            'left_on' => ['nullable', 'date'],
            'eps_code' => ['nullable', 'string', 'max:40'],
            'eps_name' => ['nullable', 'string', 'max:120'],
            'afp_code' => ['nullable', 'string', 'max:40'],
            'afp_name' => ['nullable', 'string', 'max:120'],
            'compensation_fund' => ['nullable', 'string', 'max:120'],
            'arl_name' => ['nullable', 'string', 'max:120'],
            'arl_risk_level' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
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
            'birth_department' => 'departamento de nacimiento',
            'birth_city' => 'municipio de nacimiento',
            'document_issue_department' => 'departamento de expedición',
            'document_issue_city' => 'municipio de expedición',
            'document_issued_at' => 'fecha de expedición',
            'education' => 'escolaridad',
            'marital_status' => 'estado civil',
            'children_count' => 'número de hijos',
            'phone' => 'teléfono',
            'residence_city' => 'lugar de residencia',
            'address' => 'dirección',
            'engagement_type' => 'tipo de vinculación',
            'contributor_type' => 'tipo de cotizante',
            'labor_contract_type' => 'tipo de contrato',
            'hired_on' => 'fecha de ingreso',
            'labor_contract_ends_on' => 'vencimiento de contrato',
            'left_on' => 'fecha de retiro',
            'eps_code' => 'código EPS',
            'eps_name' => 'EPS',
            'afp_code' => 'código AFP',
            'afp_name' => 'pensión',
            'compensation_fund' => 'caja de compensación',
            'arl_name' => 'ARL',
            'arl_risk_level' => 'nivel de riesgo ARL',
            'photo' => 'foto',
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
