<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\Employee;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreUserRequest extends FormRequest
{
    use ValidatesManagedUser;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($this->user());

        return array_merge(
            $this->roleRule(AssignableRoles::forCompany()),
            $this->clientIdsRule(),
            [
                'employee_id' => ['required', 'integer', 'exists:employees,id'],
                'job_title' => [
                    'required',
                    'string',
                    'max:80',
                    Rule::exists('company_job_titles', 'name')->where('security_company_id', $companyId),
                ],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
                'username' => [
                    'required',
                    'string',
                    'max:64',
                    'regex:/^[a-z]+\.[a-z]+\.\d{4}$/',
                    Rule::unique('users', 'username'),
                ],
                'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
                'is_active' => ['sometimes', 'boolean'],
            ],
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $employeeId = (int) $this->input('employee_id');
            if ($employeeId === 0) {
                return;
            }

            $companyId = app(ActingCompanyResolver::class)->requireId($this->user());
            $employee = Employee::query()->find($employeeId);

            if ($employee === null || (int) $employee->security_company_id !== $companyId) {
                $validator->errors()->add('employee_id', 'Seleccione un empleado de esta empresa.');

                return;
            }

            if (! $employee->is_active || $employee->ceased_at !== null) {
                $validator->errors()->add('employee_id', 'El empleado no está activo.');
            }

            if ($employee->user()->exists()) {
                $validator->errors()->add('employee_id', 'Este empleado ya tiene un usuario de acceso.');
            }
        });
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'employee_id' => 'empleado',
            'username' => 'usuario de acceso',
            'job_title' => 'cargo',
            'role' => 'rol',
            'client_ids' => 'conjunto',
        ];
    }
}
