<?php

declare(strict_types=1);

namespace App\Enums;

enum PartyType: string
{
    case LegalEntity = 'legal_entity';
    case NaturalPerson = 'natural_person';

    public function label(): string
    {
        return match ($this) {
            self::LegalEntity => 'Persona jurídica',
            self::NaturalPerson => 'Persona natural',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
