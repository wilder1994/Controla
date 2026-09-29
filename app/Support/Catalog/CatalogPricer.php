<?php

declare(strict_types=1);

namespace App\Support\Catalog;

use App\Enums\BillingCycle;
use App\Enums\CommercialMetal;
use App\Enums\CommercialProduct;

final class CatalogPricer
{
    public function __construct(private readonly CatalogSettings $catalog)
    {
    }

    public static function make(): self
    {
        return new self(CatalogSettings::current());
    }

    public function packMonthly(CommercialProduct $product, CommercialMetal $metal, bool $indexingAddon = false): float
    {
        $unitKey = $product === CommercialProduct::Indexing && $indexingAddon
            ? 'indexing_addon'
            : $product->value;
        $unit = $this->catalog->unit($unitKey);
        $discount = $this->catalog->discount($product, $metal);

        return round($unit * $metal->packSize() * (1 - $discount), 2);
    }

    public function seatsMonthly(CommercialProduct $product, int $seats, bool $indexingAddon = false): float
    {
        if ($seats <= 0) {
            return 0.0;
        }

        $parts = CommercialMetal::decompose($seats);
        $total = 0.0;
        if ($parts['metal'] instanceof CommercialMetal) {
            $total += $this->packMonthly($product, $parts['metal'], $indexingAddon);
        }

        $unitKey = $product === CommercialProduct::Indexing && $indexingAddon
            ? 'indexing_addon'
            : $product->value;
        $total += $this->catalog->unit($unitKey) * $parts['extras'];

        return round($total, 2);
    }

    public function applyCycle(float $monthly, BillingCycle $cycle): float
    {
        if ($cycle !== BillingCycle::Annual) {
            return $monthly;
        }

        $annualPct = (float) config('tenancy.pricing.annual_discount', 0.17);

        return round($monthly * 12 * (1 - $annualPct), 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function matrix(CommercialProduct $product, BillingCycle $cycle, bool $indexingAddon = false): array
    {
        $unitKey = $product === CommercialProduct::Indexing && $indexingAddon
            ? 'indexing_addon'
            : $product->value;
        $unit = $this->catalog->unit($unitKey);
        $rows = [];

        foreach (CommercialMetal::cases() as $metal) {
            $monthly = $this->packMonthly($product, $metal, $indexingAddon);
            $discount = $this->catalog->discount($product, $metal);
            $list = $unit * $metal->packSize();
            $rows[] = [
                'metal' => $metal->value,
                'label' => $metal->label(),
                'range' => $metal->rangeLabel(),
                'pack' => $metal->packSize(),
                'employees' => $metal->employeeCap(),
                'discount' => $discount,
                'unit' => $unit,
                'list_monthly' => $list,
                'price_monthly' => $monthly,
                'price_annual' => $this->applyCycle($monthly, BillingCycle::Annual),
                'amount' => $this->applyCycle($monthly, $cycle),
                'savings' => max(0, $list - $monthly),
                'modules' => $this->catalog->modules($product),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function observatoryMatrix(BillingCycle $cycle): array
    {
        $rows = [];
        foreach (CommercialMetal::cases() as $metal) {
            $monthly = $this->catalog->observatoryPrice($metal);
            $rows[] = [
                'metal' => $metal->value,
                'label' => $metal->label(),
                'price_monthly' => $monthly,
                'price_annual' => $this->applyCycle($monthly, BillingCycle::Annual),
                'amount' => $this->applyCycle($monthly, $cycle),
            ];
        }

        return $rows;
    }

    public function catalog(): CatalogSettings
    {
        return $this->catalog;
    }
}
