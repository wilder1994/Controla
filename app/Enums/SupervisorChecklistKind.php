<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorChecklistKind: string
{
    case Ppe = 'ppe';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::Ppe => 'EPP',
            self::Vehicle => 'Vehículo',
        };
    }
}
