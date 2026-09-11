<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

final class ReassignEmployeePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('update', $employee) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'supervisor_post_id' => ['required', 'integer', 'exists:supervisor_posts,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'installation_id' => 'instalación',
            'supervisor_post_id' => 'puesto',
        ];
    }
}
