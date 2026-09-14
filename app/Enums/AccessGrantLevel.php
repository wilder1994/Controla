<?php

declare(strict_types=1);

namespace App\Enums;

enum AccessGrantLevel: string
{
    case View = 'view';
    case Manage = 'manage';

    public function rank(): int
    {
        return match ($this) {
            self::View => 1,
            self::Manage => 2,
        };
    }

    public function allows(self $needed): bool
    {
        return $this->rank() >= $needed->rank();
    }
}
