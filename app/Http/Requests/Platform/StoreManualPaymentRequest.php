<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class StoreManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.documents.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:80'],
        ];
    }
}
