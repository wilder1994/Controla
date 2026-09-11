<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientAdminOrigin: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Interno (empleado de la empresa)',
            self::External => 'Externo (administrador del cliente)',
        };
    }
}
