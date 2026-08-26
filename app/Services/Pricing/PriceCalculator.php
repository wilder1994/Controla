<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Domain\Pricing\Data\AccessSeatSplit;
use App\Domain\Pricing\Data\PriceQuote;
use App\Enums\BillingCycle;
use App\Enums\PackageModality;
use App\Enums\SupervisionPackageSku;
use App\Models\PricingSettings;

final class PriceCalculator
{
    public function quote(
        PackageModality $modality,
        int $size,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): PriceQuote {
        $split = $modality === PackageModality::Hardware
            ? new AccessSeatSplit(0, $size)
            : new AccessSeatSplit($size, 0);

        return $this->quoteAccess($split, $cycle, $settings);
    }

    public function quoteAccess(
        AccessSeatSplit $split,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): PriceQuote {
        $settings ??= PricingSettings::current();
        $manualUnit = (float) $settings->unit_price_manual;
        $hardwareUnit = (float) $settings->unit_price_hardware;
        $listMonthly = ($split->manual * $manualUnit) + ($split->hardware * $hardwareUnit);
        $blendedUnit = $split->size() > 0 ? $listMonthly / $split->size() : 0.0;

        return $this->quoteFromList(
            $listMonthly,
            $split->size(),
            $cycle,
            $split->modality(),
            $this->volumeDiscountFor($split->size()),
            $settings,
            $blendedUnit,
        );
    }

    public function quoteSupervision(
        int $size,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
        ?float $volumeDiscountOverride = null,
    ): PriceQuote {
        $settings ??= PricingSettings::current();
        $unitPrice = (float) $settings->unit_price_supervision;
        $listMonthly = $unitPrice * $size;
        $discount = $volumeDiscountOverride ?? $this->volumeDiscountFor($size);

        return $this->quoteFromList(
            $listMonthly,
            $size,
            $cycle,
            PackageModality::Manual,
            $discount,
            $settings,
            $unitPrice,
        );
    }

    public function quoteSupervisionSku(
        SupervisionPackageSku $sku,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
        ?float $volumeDiscountOverride = null,
    ): PriceQuote {
        if ($sku->isUnlimited()) {
            $discount = $volumeDiscountOverride ?? $this->volumeDiscountFor(100);
            $base = $this->quoteSupervision(100, $cycle, $settings, $discount);

            return $base->multipliedBy(2);
        }

        return $this->quoteSupervision($sku->size(), $cycle, $settings, $volumeDiscountOverride);
    }

    public function quoteSupervisionForAccess(
        SupervisionPackageSku $sku,
        int $accessSize,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): PriceQuote {
        $offer = SupervisionPackageSku::offerForAccessSize($accessSize);
        $discount = $sku === $offer
            ? $this->volumeDiscountFor($accessSize)
            : null;

        return $this->quoteSupervisionSku($sku, $cycle, $settings, $discount);
    }

    /** Oferta de Supervisión atada al cupo Accesos (mismo % de volumen). */
    public function quoteSupervisionOffer(
        int $accessSize,
        BillingCycle $cycle,
        ?PricingSettings $settings = null,
    ): ?PriceQuote {
        $sku = SupervisionPackageSku::offerForAccessSize($accessSize);
        if ($sku === null) {
            return null;
        }

        return $this->quoteSupervisionForAccess($sku, $accessSize, $cycle, $settings);
    }

    public function volumeDiscountFor(int $size): float
    {
        /** @var array<int|string, float|int> $map */
        $map = config('tenancy.pricing.volume_discounts', []);

        return (float) ($map[$size] ?? $map[(string) $size] ?? 0.0);
    }

    /** @return list<array<string, mixed>> */
    public function matrixSupervision(BillingCycle $cycle, ?PricingSettings $settings = null): array
    {
        $settings ??= PricingSettings::current();
        $rows = [];

        foreach (SupervisionPackageSku::standaloneCases() as $sku) {
            $quote = $this->quoteSupervisionSku($sku, $cycle, $settings);
            $rows[] = [
                'size' => $sku->isUnlimited() ? null : $sku->size(),
                'sku' => $sku->value,
                'label' => $sku->label(),
                'unlimited' => $sku->isUnlimited(),
                'volume_discount_pct' => $quote->volumeDiscountPct,
                'quote' => $quote->toArray(),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function matrix(BillingCycle $cycle, ?PricingSettings $settings = null): array
    {
        $settings ??= PricingSettings::current();
        $sizes = config('tenancy.package_sizes', [1, 5, 10, 50, 100, 500]);
        $rows = [];

        foreach ($sizes as $size) {
            $size = (int) $size;
            $manual = $this->quote(PackageModality::Manual, $size, $cycle, $settings);
            $hardware = $this->quote(PackageModality::Hardware, $size, $cycle, $settings);
            $offerSku = SupervisionPackageSku::offerForAccessSize($size);
            $offer = $this->quoteSupervisionOffer($size, $cycle, $settings);

            $rows[] = [
                'size' => $size,
                'allows_supervision' => $size >= 5,
                'allows_mix' => $size >= 5,
                'volume_discount_pct' => $manual->volumeDiscountPct,
                'manual' => $manual->toArray(),
                'hardware' => $hardware->toArray(),
                'supervision_offer_sku' => $offerSku?->value,
                'supervision_offer_label' => $offerSku?->label(),
                'supervision_offer' => $offer?->toArray(),
                'supervision_choices' => $this->supervisionChoices($size, $cycle, $settings),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function supervisionChoices(int $accessSize, BillingCycle $cycle, PricingSettings $settings): array
    {
        if ($accessSize < 5) {
            return [];
        }

        $offer = SupervisionPackageSku::offerForAccessSize($accessSize);
        $skus = SupervisionPackageSku::standaloneCases();
        if ($offer !== null && ! in_array($offer, $skus, true)) {
            $skus[] = $offer;
        }

        usort($skus, static function (SupervisionPackageSku $a, SupervisionPackageSku $b): int {
            if ($a->isUnlimited()) {
                return 1;
            }
            if ($b->isUnlimited()) {
                return -1;
            }

            return $a->size() <=> $b->size();
        });

        $choices = [];
        foreach ($skus as $sku) {
            $quote = $this->quoteSupervisionForAccess($sku, $accessSize, $cycle, $settings);
            $choices[] = [
                'sku' => $sku->value,
                'label' => $sku->label(),
                'is_offer' => $sku === $offer,
                'amount' => $quote->amountDue(),
            ];
        }

        return $choices;
    }

    private function quoteFromList(
        float $listMonthly,
        int $size,
        BillingCycle $cycle,
        PackageModality $modality,
        float $volumeDiscount,
        PricingSettings $settings,
        float $unitPrice,
    ): PriceQuote {
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);
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
}
