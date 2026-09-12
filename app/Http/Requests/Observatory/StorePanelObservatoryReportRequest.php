<?php

declare(strict_types=1);

namespace App\Http\Requests\Observatory;

use App\Enums\ObservatoryReportKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePanelObservatoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'integer'],
            'kind' => ['required', 'string', Rule::enum(ObservatoryReportKind::class)],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
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
            'body' => 'qué pasó',
            'photo' => 'foto',
        ];
    }
}
