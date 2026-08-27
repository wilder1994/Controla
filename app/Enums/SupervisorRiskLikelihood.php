<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorRiskLikelihood: string
{
    case VeryLow = '1';
    case Low = '2';
    case Medium = '3';
    case High = '4';
    case VeryHigh = '5';

    public function label(): string
    {
        return match ($this) {
            self::VeryLow => 'Muy baja',
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::VeryHigh => 'Muy alta',
        };
    }

    public function score(): int
    {
        return (int) $this->value;
    }
}
