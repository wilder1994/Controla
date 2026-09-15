<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\SupervisorPostModality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSupervisorPostModalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->can('company.settings.manage') || $user->hasRole('super-admin'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = (int) ($this->user()?->security_company_id ?? 0);
        $current = $this->route('postModality');
        $ignoreId = $current instanceof SupervisorPostModality ? $current->id : null;

        return [
            'hours' => [
                'required',
                'integer',
                'min:1',
                'max:24',
                Rule::unique('supervisor_post_modalities', 'hours')
                    ->where(fn ($q) => $q->where('security_company_id', $companyId))
                    ->ignore($ignoreId),
            ],
            'name' => ['nullable', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'hours' => 'horas',
            'name' => 'nombre',
        ];
    }
}
