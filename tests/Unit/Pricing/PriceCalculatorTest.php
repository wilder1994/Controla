<?php

declare(strict_types=1);

namespace Tests\Unit\Pricing;

use App\Enums\BillingCycle;
use App\Enums\PackageModality;
use App\Models\PricingSettings;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_volume_and_annual_discounts_apply(): void
    {
        PricingSettings::query()->create([
            'unit_price_manual' => 100_000,
            'unit_price_hardware' => 200_000,
            'currency' => 'COP',
        ]);

        $calculator = app(PriceCalculator::class);
        $monthly = $calculator->quote(PackageModality::Manual, 10, BillingCycle::Monthly);
        $annual = $calculator->quote(PackageModality::Manual, 10, BillingCycle::Annual);

        $this->assertSame(0.15, $monthly->volumeDiscountPct);
        $this->assertEquals(850_000.0, $monthly->priceMonthly); // 100k*10*0.85
        $this->assertGreaterThan(0, $annual->annualSavings);
        $this->assertEqualsWithDelta($monthly->priceMonthly * 12 * 0.83, $annual->priceAnnual, 1.0);
    }

    public function test_supervision_matrix_uses_same_volume_discounts(): void
    {
        PricingSettings::query()->create([
            'unit_price_manual' => 100_000,
            'unit_price_hardware' => 200_000,
            'unit_price_supervision' => 50_000,
            'currency' => 'COP',
        ]);

        $calculator = app(PriceCalculator::class);
        $quote = $calculator->quoteSupervision(10, BillingCycle::Monthly);

        $this->assertSame(0.15, $quote->volumeDiscountPct);
        $this->assertEquals(425_000.0, $quote->priceMonthly);
    }
}
