<?php

declare(strict_types=1);

namespace App\Support\Supervision;

use Carbon\CarbonInterface;

final class SupervisorPresence
{
    public const ONLINE = 'online';

    public const SCREEN_OFF = 'screen_off';

    public const NO_SIGNAL = 'no_signal';

    public const FRESH_SECONDS = 90;

    /**
     * @return array{online: bool, signal: string, online_label: string}
     */
    public static function from(?CarbonInterface $lastAt, ?bool $screenOn, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $fresh = $lastAt instanceof CarbonInterface
            && $lastAt->diffInSeconds($now, true) <= self::FRESH_SECONDS;

        if (! $fresh) {
            return [
                'online' => false,
                'signal' => self::NO_SIGNAL,
                'online_label' => 'Sin señal',
            ];
        }

        if ($screenOn === false) {
            return [
                'online' => true,
                'signal' => self::SCREEN_OFF,
                'online_label' => 'Pantalla apagada',
            ];
        }

        return [
            'online' => true,
            'signal' => self::ONLINE,
            'online_label' => 'En línea',
        ];
    }
}
