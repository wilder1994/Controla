<?php

declare(strict_types=1);

namespace App\Enums;

enum StructureType: string
{
    case GeneralArea = 'general_area';
    case Block = 'block';
    case Apartment = 'apartment';
    case House = 'house';
    case Office = 'office';
    case CommercialStore = 'commercial_store';

    public function label(): string
    {
        return match ($this) {
            self::GeneralArea => 'Conjunto / Zona',
            self::Block => 'Torre / Bloque',
            self::Apartment => 'Apartamento',
            self::House => 'Casa',
            self::Office => 'Oficina',
            self::CommercialStore => 'Local comercial',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
