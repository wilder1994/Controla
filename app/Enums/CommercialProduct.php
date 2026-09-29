<?php

declare(strict_types=1);

namespace App\Enums;

enum CommercialProduct: string
{
    case Access = 'access';
    case Supervision = 'supervision';
    case Indexing = 'indexing';
    case Observatory = 'observatory';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Accesos',
            self::Supervision => 'Supervisión',
            self::Indexing => 'Indexación',
            self::Observatory => 'Observatorio',
        };
    }
}
