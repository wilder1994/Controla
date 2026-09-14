<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupervisorObservatoryReportRequest extends FormRequest
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
            'kind' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'photos' => ['required_without:photo', 'array', 'min:1', 'max:3'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'photo' => ['required_without:photos', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'client_event_id' => ['nullable', 'string', 'max:80'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'photos' => 'fotos',
            'photos.*' => 'foto',
            'photo' => 'foto',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photos.required_without' => 'Tome al menos una foto.',
            'photos.min' => 'Tome al menos una foto.',
            'photos.max' => 'Puede adjuntar hasta tres fotos.',
            'photo.required_without' => 'Tome al menos una foto.',
        ];
    }
}
