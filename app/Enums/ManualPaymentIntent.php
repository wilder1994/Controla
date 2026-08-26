<?php

declare(strict_types=1);

namespace App\Enums;

enum ManualPaymentIntent: string
{
    case Renew = 'renew';
    case Anticipate = 'anticipate';
    case Reactivate = 'reactivate';
    case PlanChange = 'plan_change';
    case SupervisionChange = 'supervision_change';

    public function label(): string
    {
        return match ($this) {
            self::Renew => 'Renovar periodo vencido/por vencer',
            self::Anticipate => 'Anticipar próximo periodo',
            self::Reactivate => 'Reactivar membresía',
            self::PlanChange => 'Cambio de plan (diferido)',
            self::SupervisionChange => 'Supervisión Pro',
        };
    }
}
