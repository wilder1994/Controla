<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStructureTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var \App\Models\StructureType $type */
        $type = $this->route('structureType');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('structure_types', 'code')->ignore($type->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_unit' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
