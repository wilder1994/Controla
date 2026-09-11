<?php

declare(strict_types=1);

namespace App\Enums;

enum InstallationKind: string
{
    case Colegio = 'colegio';
    case Conjunto = 'conjunto';
    case Bodega = 'bodega';
    case Oficina = 'oficina';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Colegio => 'Colegio',
            self::Conjunto => 'Conjunto / PH',
            self::Bodega => 'Bodega',
            self::Oficina => 'Oficina',
            self::Otro => 'Otro',
        };
    }

    public function requiresOfficialCode(): bool
    {
        return $this === self::Colegio;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
