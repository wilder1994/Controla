<?php

declare(strict_types=1);

namespace App\Enums;

enum SignupIntentStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'En progreso',
            self::AwaitingPayment => 'Pendiente de pago',
            self::Completed => 'Completado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Expirado',
        };
    }
}
