<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PartyType;
use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:160'],
            'trade_name' => ['nullable', 'string', 'max:160'],
            'tax_id' => ['required', 'string', 'max:40', Rule::unique('security_companies', 'tax_id')],
            'party_type' => ['required', Rule::enum(PartyType::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'package_sku' => ['nullable', Rule::enum(CompanyPackageSku::class)],
            'billing_cycle' => ['nullable', Rule::enum(BillingCycle::class)],
            ...GeoAddressRules::optional(),
        ];
    }
}
