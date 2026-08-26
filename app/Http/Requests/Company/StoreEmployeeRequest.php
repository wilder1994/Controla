<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesEmployee;
use App\Models\Employee;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEmployeeRequest extends FormRequest
{
    use ValidatesEmployee;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareEmployeeBooleans();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($this->user());

        return $this->employeeFieldRules($companyId);
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
