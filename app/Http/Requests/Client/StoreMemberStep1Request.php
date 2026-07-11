<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\MemberType;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMemberStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('client.members.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_number' => ['required', 'string', 'max:30'],
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'member_type' => ['required', Rule::enum(MemberType::class)],
            'phone_primary' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }
}
