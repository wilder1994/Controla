<?php

declare(strict_types=1);

namespace App\Http\Requests\Observatory;

use App\Enums\ObservatoryReporterRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePublicObservatoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $anonymous = $this->boolean('is_anonymous');

        return [
            'installation_id' => ['required', 'integer'],
            'kind' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'reporter_role' => ['required', 'string', Rule::in(array_keys(ObservatoryReporterRole::publicOptions()))],
            'reporter_name' => [$anonymous ? 'nullable' : 'required', 'string', 'max:120'],
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
            'body' => 'qué pasó',
            'reporter_role' => 'quién eres',
            'reporter_name' => 'nombre',
            'reporter_phone' => 'teléfono',
            'photo' => 'foto',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
        ];
    }
}
