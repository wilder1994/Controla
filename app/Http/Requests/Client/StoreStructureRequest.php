<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Structure;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Structure::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'structure_type_id' => [
                'required',
                'integer',
                Rule::exists('structure_types', 'id')->where('is_active', true),
            ],
            'max_occupancy' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
