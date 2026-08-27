<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorWeaponPermitKind: string
{
    case Sport = 'deporte';
    case Possession = 'tenencia';

    public function label(): string
    {
        return match ($this) {
            self::Sport => 'Permiso de deporte',
            self::Possession => 'Permiso de tenencia',
        };
    }
}
