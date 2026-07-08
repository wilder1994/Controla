<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientPlanTier: string
{
    case Economic = 'economic';
    case Deluxe = 'deluxe';
    case Pro = 'pro';
    case Ultimate = 'ultimate';

    public function label(): string
    {
        return config("tenancy.plan_tiers.{$this->value}.label", $this->value);
    }

    public function maxStructures(): int
    {
        return (int) config("tenancy.plan_tiers.{$this->value}.max_structures", 20);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tier) => [$tier->value => $tier->label()])
            ->all();
    }
}
