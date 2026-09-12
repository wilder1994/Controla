<?php

declare(strict_types=1);

namespace App\Enums;

enum ObservatoryReportSource: string
{
    case Comunidad = 'comunidad';
    case Panel = 'panel';
    case Campo = 'campo';
    case Porteria = 'porteria';

    public function label(): string
    {
        return match ($this) {
            self::Comunidad => 'Comunidad',
            self::Panel => 'Panel',
            self::Campo => 'App de patrulla',
            self::Porteria => 'Portería',
        };
    }
}
