<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSubscriptionAcceptanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.documents.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'representative_name' => ['required', 'string', 'max:120'],
            'representative_role' => ['required', 'string', 'max:80'],
            'representative_document_type' => ['required', 'string', 'max:20'],
            'representative_document_number' => ['required', 'string', 'max:40'],
            'accept_contract' => ['accepted'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
        ];
    }
}
