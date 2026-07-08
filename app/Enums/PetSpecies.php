<?php

declare(strict_types=1);

namespace App\Enums;

enum PetSpecies: string
{
    case Dog = 'dog';
    case Cat = 'cat';
    case Bird = 'bird';
    case ExoticOther = 'exotic_other';

    public function label(): string
    {
        return match ($this) {
            self::Dog => 'Perro',
            self::Cat => 'Gato',
            self::Bird => 'Ave',
            self::ExoticOther => 'Otro / exótico',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $species) => [$species->value => $species->label()])
            ->all();
    }
}
