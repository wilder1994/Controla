<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorAlarmKind: string
{
    case Test = 'test';
    case Response = 'response';

    public function label(): string
    {
        return match ($this) {
            self::Test => 'Prueba',
            self::Response => 'Atención',
        };
    }
}
