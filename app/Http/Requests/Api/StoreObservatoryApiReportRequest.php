<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\ObservatoryReportKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreObservatoryApiReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('observatory.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer'],
            'kind' => ['required', 'string', Rule::enum(ObservatoryReportKind::class)],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'reporter_name' => ['nullable', 'string', 'max:120'],
            'reporter_phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'installation_id' => 'colegio',
            'kind' => 'tipo',
            'body' => 'descripción',
        ];
    }
}
