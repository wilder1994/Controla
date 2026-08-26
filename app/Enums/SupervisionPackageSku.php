<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisionPackageSku: string
{
    case Sit1 = 'sup_1';
    case Sit5 = 'sup_5';
    case Sit10 = 'sup_10';
    case Sit50 = 'sup_50';
    case Sit100 = 'sup_100';

    public function size(): int
    {
        return (int) explode('_', $this->value)[1];
    }

    public function label(): string
    {
        $size = $this->size();
        $sites = $size === 1 ? '1 sitio' : "{$size} sitios";

        return "{$sites} · Supervisión Pro";
    }

    public static function fromSize(int $size): self
    {
        return self::from('sup_'.$size);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
