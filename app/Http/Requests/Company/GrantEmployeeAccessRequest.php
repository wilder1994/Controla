<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Employee;
use App\Support\Auth\AssignableRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class GrantEmployeeAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('grantAccess', $employee) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $requiresClient = in_array($this->input('role'), AssignableRoles::requiringClientAssignment(), true);

        return [
            'role' => ['required', 'string', Rule::in(AssignableRoles::forEmployeeAccess())],
            'password' => ['required', 'confirmed', Password::defaults()],
            'client_ids' => [$requiresClient ? 'required' : 'nullable', 'array'],
            'client_ids.*' => ['integer', 'exists:clients,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'role' => 'rol',
            'client_ids' => 'conjunto',
        ];
    }
}
