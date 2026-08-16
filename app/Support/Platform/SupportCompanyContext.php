<?php

declare(strict_types=1);

namespace App\Support\Platform;

final class SupportCompanyContext
{
    public const SESSION_KEY = 'support.acting_company_id';

    public static function enter(int $companyId): void
    {
        session([self::SESSION_KEY => $companyId]);
    }

    public static function exit(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function isActive(): bool
    {
        $id = session(self::SESSION_KEY);

        return $id !== null && (int) $id > 0;
    }

    public static function companyId(): ?int
    {
        if (! self::isActive()) {
            return null;
        }

        return (int) session(self::SESSION_KEY);
    }
}
