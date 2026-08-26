<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\SupervisorFieldModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSupervisorFieldLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('supervisor') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'module' => ['required', Rule::enum(SupervisorFieldModule::class)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'payload' => ['required', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }
}
