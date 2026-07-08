<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthorizationStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processed => 'Procesada',
            self::Expired => 'Vencida',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
