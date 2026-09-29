<?php

declare(strict_types=1);

namespace App\Support\Catalog;

use App\Enums\CommercialMetal;
use App\Enums\CommercialProduct;
use App\Models\PricingSettings;

final class CatalogSettings
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(private readonly PricingSettings $settings)
    {
        $stored = $this->settings->catalog;
        $this->data = is_array($stored) ? $stored : [];
    }

    public static function current(): self
    {
        return new self(PricingSettings::current());
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        $fromConfig = config('catalog');
        if (is_array($fromConfig) && isset($fromConfig['default_units'])) {
            return $fromConfig;
        }

        return require base_path('config/catalog.php');
    }

    public function unit(string $key): float
    {
        $defaults = self::defaults()['default_units'] ?? [];

        return (float) ($this->data['units'][$key] ?? $defaults[$key] ?? 0);
    }

    public function discount(CommercialProduct $product, CommercialMetal $metal): float
    {
        $defaults = self::defaults()['default_discounts'][$product->value] ?? [];
        $value = $this->data['discounts'][$product->value][$metal->value] ?? $defaults[$metal->value] ?? 0;

        return max(0, min(0.9, (float) $value));
    }

    public function observatoryPrice(CommercialMetal $metal): float
    {
        $defaults = self::defaults()['default_observatory'] ?? [];

        return (float) ($this->data['observatory'][$metal->value] ?? $defaults[$metal->value] ?? 0);
    }

    /** @return list<string> */
    public function modules(CommercialProduct $product): array
    {
        $defaults = self::defaults()['default_modules'][$product->value] ?? [];
        $stored = $this->data['modules'][$product->value] ?? null;
        if (! is_array($stored) || $stored === []) {
            return $defaults;
        }

        $allowed = array_keys(self::defaults()['module_options'] ?? []);

        return array_values(array_intersect($stored, $allowed));
    }

    /**
     * @return array{
     *   units: array<string, float>,
     *   discounts: array<string, array<string, float>>,
     *   observatory: array<string, float>,
     *   modules: array<string, list<string>>
     * }
     */
    public function toForm(): array
    {
        $units = [];
        foreach (array_keys(self::defaults()['default_units'] ?? []) as $key) {
            $units[$key] = $this->unit($key);
        }

        $discounts = [];
        foreach ([CommercialProduct::Access, CommercialProduct::Supervision, CommercialProduct::Indexing] as $product) {
            foreach (CommercialMetal::cases() as $metal) {
                $discounts[$product->value][$metal->value] = $this->discount($product, $metal);
            }
        }

        $observatory = [];
        foreach (CommercialMetal::cases() as $metal) {
            $observatory[$metal->value] = $this->observatoryPrice($metal);
        }

        $modules = [];
        foreach (CommercialProduct::cases() as $product) {
            $modules[$product->value] = $this->modules($product);
        }

        return compact('units', 'discounts', 'observatory', 'modules');
    }
}
