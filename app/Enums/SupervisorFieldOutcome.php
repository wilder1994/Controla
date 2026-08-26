<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorFieldOutcome: string
{
    case Ok = 'ok';
    case Attention = 'attention';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Attention => 'Atención',
            self::Critical => 'Crítico',
        };
    }
}
