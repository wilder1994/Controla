<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Enums\LegalCorpusType;
use App\Models\LegalCorpusVersion;
use App\Models\User;
use Carbon\Carbon;

final class MinorPersonalData
{
    public const AGE_OF_MAJORITY = 18;

    public const RESERVED = 'Dato reservado';

    public const NOTICE = 'Por la Ley 1581 de 2012 (art. 7), el Decreto 1377 de 2013 y el Código de Infancia y Adolescencia (Ley 1098 de 2006), los datos personales de niños, niñas y adolescentes tienen protección especial. El registro requiere autorización del representante legal. Se tratarán conforme a la política de datos del responsable. En portería solo se muestra el nombre. No se incluyen en descargas ni listados compartidos. El acceso a los demás datos queda reservado a los administradores autorizados.';

    public static function notice(): string
    {
        $published = trim((string) (LegalCorpusVersion::currentGlobal(LegalCorpusType::MinorsDataPolicy)?->content ?? ''));

        return $published !== '' ? $published : self::NOTICE;
    }

    /** @var list<string> */
    public const CENSUS_ADMIN_ROLES = [
        'super-admin',
        'company-admin',
        'client-admin',
        'client-installation-admin',
    ];

    public static function isMinor(mixed $birthDate): bool
    {
        $date = self::asDate($birthDate);
        if ($date === null) {
            return false;
        }

        return $date->age < self::AGE_OF_MAJORITY;
    }

    public static function age(mixed $birthDate): ?int
    {
        $date = self::asDate($birthDate);

        return $date?->age;
    }

    public static function canViewFullPii(?User $user): bool
    {
        return $user?->hasAnyRole(self::CENSUS_ADMIN_ROLES) ?? false;
    }

    public static function inPorteria(): bool
    {
        $route = request()->route();
        if ($route === null) {
            return false;
        }

        return $route->named('access.*');
    }

    public static function shouldRevealPii(?User $user, mixed $birthDate): bool
    {
        if (! self::isMinor($birthDate)) {
            return true;
        }

        if (self::inPorteria()) {
            return false;
        }

        return self::canViewFullPii($user);
    }

    public static function documentForDisplay(?User $user, mixed $birthDate, ?string $type, ?string $number): string
    {
        if (! self::shouldRevealPii($user, $birthDate)) {
            return self::RESERVED;
        }

        $label = trim(($type ?? '').' '.($number ?? ''));

        return $label !== '' ? $label : '—';
    }

    public static function contactForDisplay(?User $user, mixed $birthDate, ?string $value): string
    {
        if (! self::shouldRevealPii($user, $birthDate)) {
            return self::RESERVED;
        }

        return $value !== null && $value !== '' ? $value : '—';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function redactIdentityArray(array $row, mixed $birthDate, ?User $user = null): array
    {
        if (self::shouldRevealPii($user ?? auth()->user(), $birthDate)) {
            return $row;
        }

        foreach (['document_type', 'document_number', 'phone', 'phone_primary', 'phone_secondary', 'email', 'photo_path', 'blood_type', 'notes', 'access_code'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = in_array($key, ['document_type', 'document_number'], true)
                    ? self::RESERVED
                    : null;
            }
        }

        return $row;
    }

    private static function asDate(mixed $birthDate): ?Carbon
    {
        if ($birthDate === null || $birthDate === '') {
            return null;
        }

        try {
            return $birthDate instanceof Carbon
                ? $birthDate
                : Carbon::parse($birthDate);
        } catch (\Throwable) {
            return null;
        }
    }
}
