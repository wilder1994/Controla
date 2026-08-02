<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Enums\PartyType;
use App\Models\SecurityCompany;
use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = SecurityCompany::query()->find($this->user()?->security_company_id);

        return $company !== null && ($this->user()?->can('updateProfile', $company) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $company = SecurityCompany::query()->findOrFail($this->user()->security_company_id);

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
