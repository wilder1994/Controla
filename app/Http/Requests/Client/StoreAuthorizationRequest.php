<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\VisitorCategory;
use App\Models\VisitorPreAuthorization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('client.authorizations.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'member_id' => ['nullable', 'integer', Rule::exists('structure_members', 'id')->where('client_id', $clientId)],
            'visitor_name' => ['required', 'string', 'max:150'],
            'visitor_document' => ['nullable', 'string', 'max:30'],
            'visitor_category' => ['required', Rule::enum(VisitorCategory::class)],
            'valid_for_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
