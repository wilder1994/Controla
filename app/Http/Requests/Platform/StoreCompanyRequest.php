<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PartyType;
use App\Enums\SupervisionPackageSku;
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
        $access = CompanyPackageSku::tryFrom((string) $this->input('package_sku', ''));
        $allowsSupervision = $access?->allowsSupervision() ?? false;

        return [
            'legal_name' => ['required', 'string', 'max:160'],
            'trade_name' => ['required', 'string', 'max:160'],
            'tax_id' => ['required', 'string', 'max:40', Rule::unique('security_companies', 'tax_id')],
            'party_type' => ['required', Rule::enum(PartyType::class)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'package_sku' => ['required', Rule::enum(CompanyPackageSku::class)],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'supervision_package_sku' => [
                Rule::requiredIf($allowsSupervision),
                Rule::prohibitedIf(! $allowsSupervision),
                'nullable',
                Rule::enum(SupervisionPackageSku::class),
            ],
            ...GeoAddressRules::required(),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'legal_name' => 'razón social',
            'trade_name' => 'nombre comercial',
            'tax_id' => 'NIT',
            'party_type' => 'tipo de suscriptor',
            'email' => 'email comercial',
            'phone' => 'teléfono',
            'package_sku' => 'paquete de Accesos',
            'billing_cycle' => 'ciclo',
            'supervision_package_sku' => 'paquete de Supervisión',
            'address' => 'dirección',
            'city' => 'ciudad',
            'department' => 'departamento',
            'latitude' => 'coordenadas',
            'longitude' => 'coordenadas',
        ];
    }
}
