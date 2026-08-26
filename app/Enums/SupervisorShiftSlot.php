<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorShiftSlot: string
{
    case Day = 'day';
    case Night = 'night';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Día',
            self::Night => 'Noche',
            self::Mixed => 'Mixto',
        };
    }

    public function scheduleLabel(): string
    {
        return match ($this) {
            self::Day => '06:00 – 18:00',
            self::Night => '18:00 – 06:00',
            self::Mixed => 'Turno extendido',
        };
    }
}
