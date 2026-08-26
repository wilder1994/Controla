<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\BillingCycle;
use App\Enums\SupervisionPackageSku;
use App\Models\SecurityCompany;
use App\Services\Pricing\PriceCalculator;

final class AssignCompanySupervisionPackageService
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function execute(
        SecurityCompany $company,
        ?SupervisionPackageSku $sku,
        ?float $volumeDiscountOverride = null,
    ): SecurityCompany {
        if ($sku === null) {
            $company->update([
                'supervision_package_sku' => null,
                'supervision_package_size' => 0,
                'supervision_unlimited' => false,
                'max_supervision_clients' => 0,
                'supervision_unit_price_snapshot' => null,
                'supervision_package_price_monthly' => null,
                'supervision_package_price_annual' => null,
            ]);

            return $company->fresh();
        }

        $cycle = $company->billing_cycle ?? BillingCycle::Monthly;
        $accessSize = (int) ($company->package_size ?: 0);
        $offer = SupervisionPackageSku::offerForAccessSize($accessSize);
        $discount = $volumeDiscountOverride
            ?? ($offer === $sku
                ? $this->priceCalculator->volumeDiscountFor($accessSize)
                : $this->priceCalculator->volumeDiscountFor($sku->isUnlimited() ? 100 : $sku->size()));

        $quote = $this->priceCalculator->quoteSupervisionSku($sku, $cycle, null, $discount);

        $company->update([
            'supervision_package_sku' => $sku,
            'supervision_package_size' => $sku->isUnlimited() ? 0 : $sku->size(),
            'supervision_unlimited' => $sku->isUnlimited(),
            'max_supervision_clients' => $sku->seatCap(),
            'supervision_unit_price_snapshot' => $quote->unitPrice,
            'supervision_package_price_monthly' => $quote->priceMonthly,
            'supervision_package_price_annual' => $quote->priceAnnual,
        ]);

        return $company->fresh();
    }
}
