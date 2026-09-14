<?php

declare(strict_types=1);

namespace App\Enums;

enum PanicAttentionStatus: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }
}
