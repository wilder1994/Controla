<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Client::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = (int) $this->user()->security_company_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('clients', 'slug')->where('security_company_id', $companyId),
            ],
            'login_suffix' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9][a-z0-9\-\.]+$/i',
                Rule::unique('clients', 'login_suffix')->where('security_company_id', $companyId),
            ],
            ...GeoAddressRules::optional(),
            'access_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'login_suffix.regex' => 'El sufijo de login solo admite letras, números, guiones y puntos.',
        ];
    }
}
