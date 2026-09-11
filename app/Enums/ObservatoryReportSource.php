<?php

declare(strict_types=1);

namespace App\Enums;

enum ObservatoryReportSource: string
{
    case Comunidad = 'comunidad';

    public function label(): string
    {
        return match ($this) {
            self::Comunidad => 'Comunidad educativa',
        };
    }
}
