<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\StructureMember;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof StructureMember
            && ($this->user()?->can('update', $member) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'assigned_location_ids' => ['nullable', 'array'],
            'assigned_location_ids.*' => [
                'integer',
                Rule::exists('locations', 'id')->where('client_id', $clientId),
            ],
        ];
    }
}
