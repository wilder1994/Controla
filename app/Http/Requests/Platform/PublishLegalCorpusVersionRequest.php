<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class PublishLegalCorpusVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.documents.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:20'],
            'effective_from' => ['nullable', 'date'],
        ];
    }
}
