<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientAccessPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && $client->has_access
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer', 'exists:installations,id'],
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'vista' => ['nullable', 'in:accesos,supervision'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'installation_id' => 'instalación',
            'code' => 'código',
            'name' => 'nombre',
        ];
    }
}
