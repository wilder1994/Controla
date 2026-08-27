<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientInstallationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'is_client_site' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'vista' => ['nullable', 'in:accesos,supervision'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nombre'];
    }
}
