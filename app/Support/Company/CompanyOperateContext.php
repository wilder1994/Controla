<?php

declare(strict_types=1);

namespace App\Support\Company;

final class CompanyOperateContext
{
    public const SESSION_CLIENT_KEY = 'company.operate_return_client_id';

    public const SESSION_MODE_KEY = 'company.operate_mode';

    public const MODE_PORTERIA = 'porteria';

    public const MODE_CLIENTE = 'cliente';

    public static function enter(int $clientId, string $mode): void
    {
        session([
            self::SESSION_CLIENT_KEY => $clientId,
            self::SESSION_MODE_KEY => $mode,
        ]);
    }

    public static function exit(): void
    {
        session()->forget([
            self::SESSION_CLIENT_KEY,
            self::SESSION_MODE_KEY,
        ]);
    }

    public static function isActive(): bool
    {
        $id = session(self::SESSION_CLIENT_KEY);

        return $id !== null && (int) $id > 0;
    }

    public static function clientId(): ?int
    {
        if (! self::isActive()) {
            return null;
        }

        return (int) session(self::SESSION_CLIENT_KEY);
    }

    public static function mode(): ?string
    {
        if (! self::isActive()) {
            return null;
        }

        $mode = session(self::SESSION_MODE_KEY);

        return is_string($mode) ? $mode : null;
    }
}
