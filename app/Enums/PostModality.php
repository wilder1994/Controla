<?php

declare(strict_types=1);

namespace App\Enums;

enum PostModality: int
{
    case Hours8 = 8;
    case Hours12 = 12;
    case Hours24 = 24;

    public function label(): string
    {
        return $this->value.' h';
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
