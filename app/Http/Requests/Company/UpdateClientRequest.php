<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Enums\ClientPlanTier;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Client $client */
        $client = $this->route('client');

        return $this->user()?->can('update', $client) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');
        $companyId = (int) $client->security_company_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('clients', 'slug')
                    ->where('security_company_id', $companyId)
                    ->ignore($client->id),
            ],
            'login_suffix' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9][a-z0-9\-\.]+$/i',
                Rule::unique('clients', 'login_suffix')
                    ->where('security_company_id', $companyId)
                    ->ignore($client->id),
            ],
            'plan_tier' => ['required', Rule::enum(ClientPlanTier::class)],
            'access_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
