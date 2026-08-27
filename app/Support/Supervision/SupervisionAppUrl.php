<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class SupervisionAppUrl
{
    public static function pwa(): string
    {
        $configured = trim((string) config('supervision.pwa_url'));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $app = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($app, PHP_URL_HOST) ?: '');
        if (str_starts_with($host, 'controla.')) {
            return (string) preg_replace('#://controla\.#i', '://controla_supervision.', $app, 1);
        }

        return $app;
    }
}
