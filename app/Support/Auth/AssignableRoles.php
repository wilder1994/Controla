<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\ClientAdminOrigin;

final class AssignableRoles
{
    public const CLIENT_ADMIN = 'client-admin';

    public const CLIENT_INSTALLATION_ADMIN = 'client-installation-admin';

    /** @return list<string> */
    public static function forPlatform(): array
    {
        return [
            'super-admin',
            'company-admin',
            self::CLIENT_ADMIN,
            self::CLIENT_INSTALLATION_ADMIN,
            'guardia',
            'supervisor',
            'resident',
            'anfitrion',
            'admin-accesos',
        ];
    }

    /** @return list<string> */
    public static function forCompany(): array
    {
        return [
            'company-admin',
            self::CLIENT_ADMIN,
            self::CLIENT_INSTALLATION_ADMIN,
            'guardia',
            'supervisor',
        ];
    }

    public static function needsEmployee(string $role, ?string $origin = null): bool
    {
        if (self::isInstallationAdmin($role)) {
            return false;
        }

        if ($role === self::CLIENT_ADMIN && $origin === ClientAdminOrigin::External->value) {
            return false;
        }

        return in_array($role, self::forEmployeeAccess(), true);
    }

    /** Roles de empresa que, si son internos, se crean sobre una ficha de empleado. */
    /** @return list<string> */
    public static function forEmployeeAccess(): array
    {
        return [
            'company-admin',
            self::CLIENT_ADMIN,
            'supervisor',
            'guardia',
        ];
    }

    /** @return list<string> */
    public static function forClient(): array
    {
        return [
            self::CLIENT_ADMIN,
            self::CLIENT_INSTALLATION_ADMIN,
        ];
    }

    /** @return list<string> */
    public static function clientFacingAdmins(): array
    {
        return [
            self::CLIENT_ADMIN,
            self::CLIENT_INSTALLATION_ADMIN,
        ];
    }

    public static function isClientFacingAdmin(string $role): bool
    {
        return in_array($role, self::clientFacingAdmins(), true);
    }

    public static function isInstallationAdmin(string $role): bool
    {
        return $role === self::CLIENT_INSTALLATION_ADMIN;
    }

    public static function isExternalClientAdmin(string $role, ?string $origin): bool
    {
        if (self::isInstallationAdmin($role)) {
            return true;
        }

        return $role === self::CLIENT_ADMIN && $origin === ClientAdminOrigin::External->value;
    }

    /** Roles que requieren al menos un cliente asignado. */
    /** @return list<string> */
    public static function requiringClientAssignment(): array
    {
        return [
            self::CLIENT_ADMIN,
            self::CLIENT_INSTALLATION_ADMIN,
            'guardia',
            'resident',
            'anfitrion',
        ];
    }

    /** Roles con exactamente un cliente (vigilante o admin externo). */
    /** @return list<string> */
    public static function requiringSingleClientAssignment(): array
    {
        return [
            'guardia',
            self::CLIENT_INSTALLATION_ADMIN,
        ];
    }

    public static function forcesSingleClient(string $role, ?string $origin = null): bool
    {
        if (in_array($role, self::requiringSingleClientAssignment(), true)) {
            return true;
        }

        return self::isExternalClientAdmin($role, $origin);
    }

    public static function label(string $role): string
    {
        return match ($role) {
            'super-admin' => 'Súper administrador',
            'company-admin' => 'Administrador empresa',
            self::CLIENT_ADMIN => 'Administrador del cliente',
            self::CLIENT_INSTALLATION_ADMIN => 'Admin instalaciones',
            'guardia' => 'Vigilante',
            'supervisor' => 'Supervisor de vigilancia',
            'resident' => 'Residente portal',
            'anfitrion' => 'Anfitrión',
            'admin-accesos' => 'Admin accesos (legacy)',
            default => $role,
        };
    }
}
