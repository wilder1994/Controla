<?php

declare(strict_types=1);

namespace App\Enums;

enum ObservatoryEventStatus: string
{
    case Nuevo = 'nuevo';
    case EnAtencion = 'en_atencion';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::EnAtencion => 'En atención',
            self::Cerrado => 'Cerrado',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Nuevo => $next === self::EnAtencion,
            self::EnAtencion => $next === self::Cerrado,
            self::Cerrado => false,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
