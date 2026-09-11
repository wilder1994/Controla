<?php

declare(strict_types=1);

namespace App\Enums;

enum ObservatoryReportKind: string
{
    case Amenaza = 'amenaza';
    case Rina = 'rina';
    case Hurto = 'hurto';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Amenaza => 'Amenaza',
            self::Rina => 'Riña',
            self::Hurto => 'Hurto',
            self::Otro => 'Otro',
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
