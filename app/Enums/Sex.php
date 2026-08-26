<?php

declare(strict_types=1);

namespace App\Enums;

enum Sex: string
{
    case Male = 'hombre';
    case Female = 'mujer';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Hombre',
            self::Female => 'Mujer',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public static function tryParse(string $raw): ?self
    {
        $value = mb_strtolower(trim($raw));

        return match ($value) {
            'hombre', 'masculino', 'male', self::Male->value => self::Male,
            'mujer', 'femenino', 'female', self::Female->value => self::Female,
            default => null,
        };
    }
}
