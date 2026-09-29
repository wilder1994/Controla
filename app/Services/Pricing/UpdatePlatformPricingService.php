<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Enums\CommercialMetal;
use App\Enums\CommercialProduct;
use App\Models\PricingSettings;
use App\Models\User;

final class UpdatePlatformPricingService
{
    /** @param array<string, mixed> $payload */
    public function execute(array $payload, ?User $actor = null): PricingSettings
    {
        $settings = PricingSettings::current();
        $units = $payload['units'] ?? [];

        $discounts = [];
        foreach ([CommercialProduct::Access, CommercialProduct::Supervision, CommercialProduct::Indexing] as $product) {
            foreach (CommercialMetal::cases() as $metal) {
                $pct = (float) data_get($payload, "discounts.{$product->value}.{$metal->value}", 0);
                $discounts[$product->value][$metal->value] = round($pct / 100, 4);
            }
        }

        $modules = [];
        foreach (CommercialProduct::cases() as $product) {
            $mods = $payload['modules'][$product->value] ?? [];
            $modules[$product->value] = array_values(array_unique(array_filter($mods)));
        }

        $observatory = [];
        foreach (CommercialMetal::cases() as $metal) {
            $observatory[$metal->value] = (float) data_get($payload, "observatory.{$metal->value}", 0);
        }

        $settings->update([
            'unit_price_manual' => (float) ($units['access'] ?? $settings->unit_price_manual),
            'unit_price_hardware' => (float) ($settings->unit_price_hardware ?: 150_000),
            'unit_price_supervision' => (float) ($units['supervision'] ?? $settings->unit_price_supervision),
            'catalog' => [
                'units' => [
                    'access' => (float) ($units['access'] ?? 80_000),
                    'supervision' => (float) ($units['supervision'] ?? 80_000),
                    'indexing' => (float) ($units['indexing'] ?? 40_000),
                    'indexing_addon' => (float) ($units['indexing_addon'] ?? 15_000),
                ],
                'discounts' => $discounts,
                'observatory' => $observatory,
                'modules' => $modules,
            ],
            'updated_by' => $actor?->id,
        ]);

        return $settings->fresh();
    }
}
