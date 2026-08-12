<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\PartyType;
use App\Models\SecurityCompany;
use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SecurityCompany $company */
        $company = $this->route('company');

        return $this->user()?->can('updateProfile', $company) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var SecurityCompany $company */
        $company = $this->route('company');

        $taxIdRule = $company->hasCompletedAcceptance()
            ? ['prohibited']
            : ['required', 'string', 'max:40', Rule::unique('security_companies', 'tax_id')->ignore($company->id)];

        return [
            'legal_name' => ['required', 'string', 'max:160'],
            'trade_name' => ['nullable', 'string', 'max:160'],
            'tax_id' => $taxIdRule,
            'party_type' => ['required', Rule::enum(PartyType::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            ...GeoAddressRules::optional(),
        ];
    }
}
