<?php

declare(strict_types=1);

namespace Tests\Unit\Pricing;

use App\Enums\CommercialMetal;
use App\Enums\CommercialProduct;
use App\Models\PricingSettings;
use App\Support\Catalog\CatalogPricer;
use App\Support\Catalog\CatalogSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogPricerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pack_applies_discount_and_extras_are_full_unit(): void
    {
        $settings = PricingSettings::current();
        $settings->update([
            'catalog' => [
                'units' => [
                    'access' => 100_000,
                    'supervision' => 80_000,
                    'indexing' => 40_000,
                    'indexing_addon' => 10_000,
                ],
                'discounts' => [
                    'access' => ['bronce' => 0.05, 'plata' => 0.10, 'oro' => 0.10, 'platino' => 0.12],
                ],
            ],
        ]);

        $pricer = new CatalogPricer(new CatalogSettings($settings->fresh()));
        $bronce = $pricer->packMonthly(CommercialProduct::Access, CommercialMetal::Bronce);
        $this->assertEquals(475_000.0, $bronce);

        $six = $pricer->seatsMonthly(CommercialProduct::Access, 6);
        $this->assertEquals(575_000.0, $six);
    }
}
