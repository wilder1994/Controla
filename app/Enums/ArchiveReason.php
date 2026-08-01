<?php

declare(strict_types=1);

namespace App\Enums;

enum ArchiveReason: string
{
    case Cancelled = 'cancelled';
    case NonPayment = 'non_payment';

    public function label(): string
    {
        return match ($this) {
            self::Cancelled => 'Baja voluntaria',
            self::NonPayment => 'Falta de pago',
        };
    }
}
