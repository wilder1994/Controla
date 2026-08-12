<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Gateway = 'gateway';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Gateway => 'Pasarela (sandbox/live)',
            self::Manual => 'Registro manual',
        };
    }
}
