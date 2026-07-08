<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\StructureAppUser;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('client.app_users.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'member_id' => ['nullable', 'integer', Rule::exists('structure_members', 'id')->where('client_id', $clientId)],
            'username' => ['required', 'string', 'max:80', 'alpha_dash'],
            'email' => ['nullable', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }
}
