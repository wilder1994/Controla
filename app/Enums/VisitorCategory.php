<?php

declare(strict_types=1);

namespace App\Enums;

enum VisitorCategory: string
{
    case Visitor = 'visitor';
    case Contractor = 'contractor';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Visitante',
            self::Contractor => 'Contratista',
            self::Delivery => 'Domicilio / mensajería',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category) => [$category->value => $category->label()])
            ->all();
    }
}
