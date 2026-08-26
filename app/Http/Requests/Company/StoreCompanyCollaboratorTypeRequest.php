<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\CompanyCollaboratorType;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCompanyCollaboratorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CompanyCollaboratorType::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nombre'];
    }
}
