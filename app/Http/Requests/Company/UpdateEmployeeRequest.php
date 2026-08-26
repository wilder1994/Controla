<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesEmployee;
use App\Models\Employee;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEmployeeRequest extends FormRequest
{
    use ValidatesEmployee;

    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('update', $employee) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareEmployeeBooleans();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($this->user());
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : null;
        $currentJobTitleId = $employee instanceof Employee ? (int) $employee->job_title_id : null;
        $currentCollaboratorTypeId = $employee instanceof Employee ? (int) $employee->collaborator_type_id : null;

        return $this->employeeFieldRules($companyId, $employeeId, $currentJobTitleId, $currentCollaboratorTypeId);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return $this->employeeAttributes();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->employeeMessages();
    }
}
