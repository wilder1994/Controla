<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Domain\Pricing\Data\PriceQuote;
use App\Enums\BillingCycle;
use App\Enums\PackageModality;
use App\Models\PricingSettings;

final class PriceCalculator
{
    public function quote(
        PackageModality $modality,
        int $size,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): PriceQuote {
        $settings ??= PricingSettings::current();
        $unitPrice = $modality === PackageModality::Hardware
            ? (float) $settings->unit_price_hardware
            : (float) $settings->unit_price_manual;

        return $this->quoteFromUnit($unitPrice, $size, $cycle, $modality, $settings);
    }

    public function quoteSupervision(
        int $size,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): PriceQuote {
        $settings ??= PricingSettings::current();

        return $this->quoteFromUnit(
            (float) $settings->unit_price_supervision,
            $size,
            $cycle,
            PackageModality::Manual,
            $settings,
        );
    }

    /** @return list<array<string, mixed>> */
    public function matrixSupervision(BillingCycle $cycle, ?PricingSettings $settings = null): array
    {
        $settings ??= PricingSettings::current();
        $sizes = config('tenancy.package_sizes', [1, 5, 10, 50, 100]);
        $rows = [];

        foreach ($sizes as $size) {
            $size = (int) $size;
            $quote = $this->quoteSupervision($size, $cycle, $settings);
            $rows[] = [
                'size' => $size,
                'volume_discount_pct' => $quote->volumeDiscountPct,
                'quote' => $quote->toArray(),
            ];
        }

        return $rows;
    }

    private function quoteFromUnit(
        float $unitPrice,
        int $size,
        BillingCycle $cycle,
        PackageModality $modality,
        PricingSettings $settings,
    ): PriceQuote {
        $volumeDiscount = $this->volumeDiscountFor($size);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);

        $listMonthly = $unitPrice * $size;
        $priceMonthly = round($listMonthly * (1 - $volumeDiscount), 2);
        $annualIfPaidMonthly = round($priceMonthly * 12, 2);
        $priceAnnual = round($annualIfPaidMonthly * (1 - $annualDiscount), 2);
        $effectiveUnit = $size > 0 ? round($priceMonthly / $size, 2) : 0.0;

        return new PriceQuote(
            modality: $modality,
            size: $size,
            cycle: $cycle,
            unitPrice: $unitPrice,
            volumeDiscountPct: $volumeDiscount,
            annualDiscountPct: $annualDiscount,
            priceMonthly: $priceMonthly,
            priceAnnual: $priceAnnual,
            effectiveUnitMonthly: $effectiveUnit,
            listMonthlyWithoutVolume: round($listMonthly, 2),
            annualIfPaidMonthly: $annualIfPaidMonthly,
            annualSavings: round($annualIfPaidMonthly - $priceAnnual, 2),
            currency: (string) $settings->currency,
        );
    }

    /** @return list<array<string, mixed>> */
    public function matrix(BillingCycle $cycle, ?PricingSettings $settings = null): array
    {
        $settings ??= PricingSettings::current();
        $sizes = config('tenancy.package_sizes', [1, 5, 10, 50, 100]);
        $rows = [];

        foreach ($sizes as $size) {
            $size = (int) $size;
            $manual = $this->quote(PackageModality::Manual, $size, $cycle, $settings);
            $hardware = $this->quote(PackageModality::Hardware, $size, $cycle, $settings);

            $rows[] = [
                'size' => $size,
                'volume_discount_pct' => $manual->volumeDiscountPct,
                'manual' => $manual->toArray(),
                'hardware' => $hardware->toArray(),
            ];
        }

        return $rows;
    }

    private function volumeDiscountFor(int $size): float
    {
        /** @var array<int|string, float|int> $map */
        $map = config('tenancy.pricing.volume_discounts', []);

        return (float) ($map[$size] ?? $map[(string) $size] ?? 0.0);
    }
}
