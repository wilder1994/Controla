<?php

namespace App\Http\Requests\Personnel;

use Illuminate\Foundation\Http\FormRequest;

final class MarkLaborHistoryNaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company.settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string'],
        ];
    }
}
