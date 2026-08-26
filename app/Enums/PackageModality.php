<?php

declare(strict_types=1);

namespace App\Enums;

enum PackageModality: string
{
    case Manual = 'manual';
    case Hardware = 'hardware';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Sin hardware',
            self::Hardware => 'Con hardware',
            self::Mixed => 'Mixto',
        };
    }

    /** @return list<string> */
    public function features(): array
    {
        if ($this === self::Mixed) {
            return array_values(array_unique(array_merge(
                config('tenancy.features.manual', []),
                config('tenancy.features.hardware', []),
            )));
        }

        return config('tenancy.features.'.$this->value, []);
    }

    public function allows(string $feature): bool
    {
        return in_array($feature, $this->features(), true);
    }

    /** @return list<string> */
    public function featureLabels(): array
    {
        $labels = config('tenancy.feature_labels', []);

        return array_values(array_map(
            static fn (string $key) => $labels[$key] ?? str_replace('_', ' ', $key),
            $this->features()
        ));
    }
}
