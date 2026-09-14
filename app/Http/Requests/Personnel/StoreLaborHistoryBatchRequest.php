<?php

namespace App\Http\Requests\Personnel;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLaborHistoryBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company.documents.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }
}
