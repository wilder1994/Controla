<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\StructureMember;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StructureMember::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();
        $current = $this->route('member');
        $currentTypeId = $current instanceof StructureMember ? (int) $current->member_type_id : null;

        $typeRule = Rule::exists('member_types', 'id')->where(function ($query) use ($clientId, $currentTypeId): void {
            $query->where('client_id', $clientId)
                ->where(function ($inner) use ($currentTypeId): void {
                    $inner->where('is_active', true);
                    if ($currentTypeId !== null) {
                        $inner->orWhere('id', $currentTypeId);
                    }
                });
        });

        return [
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'member_type_id' => ['required', 'integer', $typeRule],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_number' => ['required', 'string', 'max:30'],
            'phone_primary' => ['nullable', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'has_app_access' => ['boolean'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }
}
