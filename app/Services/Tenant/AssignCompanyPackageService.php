<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Services\Pricing\PriceCalculator;
use Carbon\CarbonImmutable;

final class AssignCompanyPackageService
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function execute(
        SecurityCompany $company,
        CompanyPackageSku $sku,
        BillingCycle $cycle = BillingCycle::Monthly,
        ?CarbonImmutable $startsAt = null,
    ): SecurityCompany {
        $quote = $this->priceCalculator->quote($sku->modality(), $sku->size(), $cycle);
        $startsAt ??= CarbonImmutable::now();
        $endsAt = $cycle === BillingCycle::Annual
            ? $startsAt->addYear()
            : $startsAt->addMonth();

        $billingDayMax = max(1, (int) config('subscription.billing_day_max', 28));
        $billingDay = min($billingDayMax, max(1, (int) $startsAt->day));

        $company->update([
            'package_sku' => $sku,
            'package_size' => $sku->size(),
            'package_modality' => $sku->modality(),
            'max_clients' => $sku->size(),
            'billing_cycle' => $cycle,
            'billing_day' => $billingDay,
            'unit_price_snapshot' => $quote->unitPrice,
            'volume_discount_pct' => $quote->volumeDiscountPct,
            'annual_discount_pct' => $quote->annualDiscountPct,
            'package_price_monthly' => $quote->priceMonthly,
            'package_price_annual' => $quote->priceAnnual,
            'package_starts_at' => $startsAt,
            'package_ends_at' => $endsAt,
            'grace_ends_at' => null,
            'suspended_at' => null,
            'archived_at' => null,
            'archive_reason' => null,
            'subscription_status' => SubscriptionStatus::Active,
            'is_active' => true,
        ]);

        return $company->fresh();
    }
}
