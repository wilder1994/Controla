<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class WeaponInspectionPhotos
{
    /** @var list<string> */
    public const IDENTIFICATION = ['right', 'left', 'serial', 'brand', 'imprint'];

    public const CLEANING = 'cleaning';

    /** @var list<string> */
    public const SLOTS = ['right', 'left', 'serial', 'brand', 'imprint', 'cleaning'];

    /** @return list<array{key: string, label: string}> */
    public static function identificationSlots(): array
    {
        return [
            ['key' => 'right', 'label' => 'Lado derecho'],
            ['key' => 'left', 'label' => 'Lado izquierdo'],
            ['key' => 'serial', 'label' => 'Serie'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'imprint', 'label' => 'Imprenta'],
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public static function cleaningSlots(): array
    {
        return [
            ['key' => self::CLEANING, 'label' => 'Aseo'],
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public static function slots(): array
    {
        return array_merge(self::identificationSlots(), self::cleaningSlots());
    }

    /**
     * @return list<string>
     */
    public static function requiredKeys(bool $cleaned): array
    {
        if ($cleaned) {
            return [...self::IDENTIFICATION, self::CLEANING];
        }

        return self::IDENTIFICATION;
    }
}
