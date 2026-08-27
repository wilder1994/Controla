<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientSupervisorPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && $client->has_supervision
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'vista' => ['nullable', 'in:accesos,supervision'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'installation_id' => 'instalación',
            'name' => 'nombre',
        ];
    }
}
