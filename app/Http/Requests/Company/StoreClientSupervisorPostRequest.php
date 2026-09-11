<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Enums\PostModality;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientSupervisorPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($client->has_access || $client->has_supervision)
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'name' => ['required', 'string', 'max:120'],
            'modality' => ['required', 'integer', Rule::enum(PostModality::class)],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'is_active' => ['sometimes', 'boolean'],
            'vista' => ['nullable', 'in:sitio,puertas,accesos,supervision'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'installation_id' => 'instalación',
            'name' => 'nombre',
            'modality' => 'modalidad',
            'employee_ids' => 'vigilantes',
        ];
    }
}
