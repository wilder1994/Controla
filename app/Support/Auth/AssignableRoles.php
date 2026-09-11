<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class AssignableRoles
{
    /** @return list<string> */
    public static function forPlatform(): array
    {
        return [
            'super-admin',
            'company-admin',
            'client-admin',
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
            'client-admin',
            'guardia',
            'supervisor',
        ];
    }

    public static function needsEmployee(string $role): bool
    {
        return in_array($role, self::forEmployeeAccess(), true);
    }

    /** Roles de empresa: siempre se crean sobre una ficha de empleado. */
    /** @return list<string> */
    public static function forEmployeeAccess(): array
    {
        return [
            'company-admin',
            'client-admin',
            'supervisor',
            'guardia',
        ];
    }

    /** @return list<string> */
    public static function forClient(): array
    {
        return [
            'client-admin',
        ];
    }

    /** Roles que requieren al menos un cliente asignado. */
    /** @return list<string> */
    public static function requiringClientAssignment(): array
    {
        return [
            'client-admin',
            'guardia',
            'resident',
            'anfitrion',
        ];
    }

    /** Roles con exactamente un cliente (vigilante). */
    /** @return list<string> */
    public static function requiringSingleClientAssignment(): array
    {
        return [
            'guardia',
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            'super-admin' => 'Súper administrador',
            'company-admin' => 'Administrador empresa',
            'client-admin' => 'Administrador del cliente',
            'guardia' => 'Vigilante',
            'supervisor' => 'Supervisor de vigilancia',
            'resident' => 'Residente portal',
            'anfitrion' => 'Anfitrión',
            'admin-accesos' => 'Admin accesos (legacy)',
            default => $role,
        };
    }
}
