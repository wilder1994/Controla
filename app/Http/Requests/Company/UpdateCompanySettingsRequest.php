<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Enums\PartyType;
use App\Models\SecurityCompany;
use App\Support\Geo\GeoAddressRules;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $companyId = app(ActingCompanyResolver::class)->id($user);

        return $companyId !== null && $user->can('company.profile.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($this->user());
        $company = SecurityCompany::query()->findOrFail($companyId);

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
            'field_sheet_intro' => ['nullable', 'string', 'max:4000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            ...GeoAddressRules::optional(),
        ];
    }
}
