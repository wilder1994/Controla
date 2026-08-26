<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyPackageSku: string
{
    case Pack1Manual = 'pack_1_manual';
    case Pack5Manual = 'pack_5_manual';
    case Pack10Manual = 'pack_10_manual';
    case Pack50Manual = 'pack_50_manual';
    case Pack100Manual = 'pack_100_manual';
    case Pack500Manual = 'pack_500_manual';
    case Pack1Hardware = 'pack_1_hardware';
    case Pack5Hardware = 'pack_5_hardware';
    case Pack10Hardware = 'pack_10_hardware';
    case Pack50Hardware = 'pack_50_hardware';
    case Pack100Hardware = 'pack_100_hardware';
    case Pack500Hardware = 'pack_500_hardware';

    public function size(): int
    {
        return (int) explode('_', $this->value)[1];
    }

    public function modality(): PackageModality
    {
        return PackageModality::from(explode('_', $this->value)[2]);
    }

    public function label(): string
    {
        $size = $this->size();
        $clients = $size === 1 ? '1 cliente' : "{$size} clientes";

        return "{$clients} · {$this->modality()->label()}";
    }

    public function allowsSupervision(): bool
    {
        return $this->size() >= 5;
    }

    public static function fromParts(int $size, PackageModality $modality): self
    {
        $stored = $modality === PackageModality::Mixed
            ? PackageModality::Manual
            : $modality;

        return self::from("pack_{$size}_{$stored->value}");
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $sku) => [$sku->value => $sku->label()])
            ->all();
    }

    /** @return list<int> */
    public static function sizes(): array
    {
        return array_values(array_unique(array_map(
            static fn (self $sku) => $sku->size(),
            self::cases()
        )));
    }
}
