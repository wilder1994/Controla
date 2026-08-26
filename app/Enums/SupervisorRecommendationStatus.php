<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorRecommendationStatus: string
{
    case Open = 'open';
    case Progress = 'progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Progress => 'En proceso',
            self::Closed => 'Cerrada',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Open => $next === self::Progress || $next === self::Closed,
            self::Progress => $next === self::Closed,
            self::Closed => false,
        };
    }
}
