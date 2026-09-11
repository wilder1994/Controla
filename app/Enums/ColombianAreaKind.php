<?php

declare(strict_types=1);

namespace App\Enums;

enum ColombianAreaKind: string
{
    case Comuna = 'comuna';
    case Localidad = 'localidad';
    case Vereda = 'vereda';
    case Corregimiento = 'corregimiento';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Comuna => 'Comuna',
            self::Localidad => 'Localidad',
            self::Vereda => 'Vereda',
            self::Corregimiento => 'Corregimiento',
            self::None => 'Área',
        };
    }

    public function applies(): bool
    {
        return $this !== self::None;
    }
}
