<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Geo\GeoAddressData;
use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PartyType;
use App\Models\SecurityCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateCompanyService
{
    public function __construct(
        private readonly AssignCompanyPackageService $assignCompanyPackageService,
    ) {}

    /**
     * @param  array{
     *     legal_name: string,
     *     trade_name?: string|null,
     *     tax_id: string,
     *     party_type: string|PartyType,
     *     email?: string|null,
     *     phone?: string|null,
     *     package_sku?: string|null,
     *     billing_cycle?: string|null,
     * }  $attributes
     */
    public function execute(array $attributes, GeoAddressData $geo): SecurityCompany
    {
        if (SecurityCompany::query()->where('tax_id', $attributes['tax_id'])->exists()) {
            throw ValidationException::withMessages([
                'tax_id' => 'Ya existe una empresa con este identificador fiscal.',
            ]);
        }

        return DB::transaction(function () use ($attributes, $geo): SecurityCompany {
            $partyType = $attributes['party_type'] instanceof PartyType
                ? $attributes['party_type']
                : PartyType::from((string) $attributes['party_type']);

            $company = SecurityCompany::query()->create([
                'legal_name' => $attributes['legal_name'],
                'trade_name' => $attributes['trade_name'] ?: $attributes['legal_name'],
                'tax_id' => $attributes['tax_id'],
                'party_type' => $partyType,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                ...$geo->toModelAttributes(),
                'is_active' => true,
            ]);

            $skuValue = $attributes['package_sku'] ?? null;
            if ($skuValue) {
                $sku = CompanyPackageSku::from((string) $skuValue);
                $cycle = BillingCycle::tryFrom((string) ($attributes['billing_cycle'] ?? 'monthly'))
                    ?? BillingCycle::Monthly;
                $this->assignCompanyPackageService->execute($company, $sku, $cycle);
            }

            return $company->fresh();
        });
    }
}
