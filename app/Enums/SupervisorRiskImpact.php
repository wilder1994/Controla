<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorRiskImpact: string
{
    case Insignificant = '1';
    case Minor = '2';
    case Moderate = '3';
    case Major = '4';
    case Catastrophic = '5';

    public function label(): string
    {
        return match ($this) {
            self::Insignificant => 'Insignificante',
            self::Minor => 'Menor',
            self::Moderate => 'Moderado',
            self::Major => 'Mayor',
            self::Catastrophic => 'Catastrófico',
        };
    }

    public function score(): int
    {
        return (int) $this->value;
    }
}
