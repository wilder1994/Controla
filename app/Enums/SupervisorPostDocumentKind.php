<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorPostDocumentKind: string
{
    case Minute = 'minuta';
    case Novelties = 'novedades';
    case Correspondence = 'correspondencia';
    case Other = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Minute => 'Minuta',
            self::Novelties => 'Novedades',
            self::Correspondence => 'Correspondencia de puesto',
            self::Other => 'Otro',
        };
    }
}
