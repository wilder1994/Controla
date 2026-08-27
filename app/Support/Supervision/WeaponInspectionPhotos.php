<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class WeaponInspectionPhotos
{
    /** @var list<string> */
    public const SLOTS = ['right', 'left', 'serial', 'brand', 'imprint', 'cleaning'];

    /** @return list<array{key: string, label: string}> */
    public static function slots(): array
    {
        return [
            ['key' => 'right', 'label' => 'Lado derecho'],
            ['key' => 'left', 'label' => 'Lado izquierdo'],
            ['key' => 'serial', 'label' => 'Serie'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'imprint', 'label' => 'Imprenta'],
            ['key' => 'cleaning', 'label' => 'Aseo'],
        ];
    }
}
