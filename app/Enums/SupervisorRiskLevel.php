<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Extreme = 'extreme';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bajo',
            self::Medium => 'Medio',
            self::High => 'Alto',
            self::Extreme => 'Extremo',
        };
    }
}
