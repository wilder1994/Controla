<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesEmployee;
use App\Models\SecurityCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreCompanyFirstAdminRequest extends FormRequest
{
    use ValidatesEmployee;

    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareEmployeeBooleans();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $company = $this->route('company');
        $companyId = $company instanceof SecurityCompany ? (int) $company->id : 0;

        return array_merge(
            $this->employeeFieldRules($companyId),
            [
                'username' => [
                    'required',
                    'string',
                    'max:64',
                    'regex:/^[a-z]+\.[a-z]+\.\d{4}$/',
                    Rule::unique('users', 'username'),
                ],
                'password' => ['required', Password::defaults()],
            ],
        );
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return array_merge($this->employeeAttributes(), [
            'username' => 'usuario de acceso',
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->employeeMessages();
    }
}
