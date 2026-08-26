<?php

declare(strict_types=1);

namespace Tests\Unit\Pricing;

use App\Domain\Pricing\Data\AccessSeatSplit;
use App\Enums\BillingCycle;
use App\Enums\PackageModality;
use App\Enums\SupervisionPackageSku;
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

    public function test_mixed_access_and_five_hundred_discount(): void
    {
        PricingSettings::query()->create([
            'unit_price_manual' => 100_000,
            'unit_price_hardware' => 200_000,
            'unit_price_supervision' => 50_000,
            'currency' => 'COP',
        ]);

        $calculator = app(PriceCalculator::class);
        $mix = $calculator->quoteAccess(new AccessSeatSplit(3, 2), BillingCycle::Monthly);
        $this->assertSame(0.10, $mix->volumeDiscountPct);
        $this->assertEquals(630_000.0, $mix->priceMonthly);

        $pack500 = $calculator->quote(PackageModality::Manual, 500, BillingCycle::Monthly);
        $this->assertSame(0.50, $pack500->volumeDiscountPct);

        $offer5 = $calculator->quoteSupervisionOffer(5, BillingCycle::Monthly);
        $this->assertNotNull($offer5);
        $this->assertSame(0.10, $offer5->volumeDiscountPct);
        $this->assertEquals(450_000.0, $offer5->priceMonthly);

        $this->assertNull($calculator->quoteSupervisionOffer(1, BillingCycle::Monthly));

        $sit100 = $calculator->quoteSupervisionSku(SupervisionPackageSku::Sit100, BillingCycle::Monthly);
        $unlimited = $calculator->quoteSupervisionSku(SupervisionPackageSku::Unlimited, BillingCycle::Monthly);
        $this->assertEquals($sit100->priceMonthly * 2, $unlimited->priceMonthly);

        $offer50 = $calculator->quoteSupervisionOffer(50, BillingCycle::Monthly);
        $this->assertNotNull($offer50);
        $this->assertSame(0.25, $offer50->volumeDiscountPct);
        $this->assertEquals(7_500_000.0, $offer50->priceMonthly);
    }
}
