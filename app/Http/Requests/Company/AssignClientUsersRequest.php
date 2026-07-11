<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

final class AssignClientUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('company.users.assign') ?? false)
            && ($this->user()?->can('update', $client) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'Selecciona al menos un operativo para asignar.',
        ];
    }
}
