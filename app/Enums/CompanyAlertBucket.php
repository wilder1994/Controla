<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyAlertBucket: string
{
    case Current = 'current';
    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Al día',
            self::DueSoon => 'Por vencer',
            self::Overdue => 'Vencidos',
            self::Suspended => 'Suspendidas',
            self::Archived => 'Archivadas',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
