<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\StructureMember;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FinalizeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StructureMember::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'has_app_access' => ['boolean'],
            'is_active' => ['boolean'],
            'assigned_location_ids' => ['nullable', 'array'],
            'assigned_location_ids.*' => [
                'integer',
                Rule::exists('locations', 'id')->where('client_id', $clientId),
            ],
        ];
    }
}
