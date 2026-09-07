<?php

declare(strict_types=1);

namespace App\Enums;

enum BloodGroup: string
{
    case OPositive = 'O+';
    case ONegative = 'O-';
    case APositive = 'A+';
    case ANegative = 'A-';
    case BPositive = 'B+';
    case BNegative = 'B-';
    case AbPositive = 'AB+';
    case AbNegative = 'AB-';
    case Pending = 'Pendiente';

    public function label(): string
    {
        return $this->value;
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
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s*\(.*\)\s*/u', '', $value) ?? $value;
        $value = strtoupper(str_replace([' ', '–', '—'], ['', '-', '-'], trim($value)));

        if ($value === 'PENDIENTE') {
            return self::Pending;
        }

        foreach (self::cases() as $case) {
            if ($case === self::Pending) {
                continue;
            }
            if (strtoupper($case->value) === $value) {
                return $case;
            }
        }

        return null;
    }
}
