<?php

declare(strict_types=1);

namespace App\Support\Geo;

final class GeoAddressRules
{
    /** @return array<string, mixed> */
    public static function optional(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
