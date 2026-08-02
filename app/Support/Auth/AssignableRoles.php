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

    /** @return list<string> */
    public static function forClient(): array
    {
        return [
            'resident',
            'anfitrion',
            'guardia',
            'supervisor',
        ];
    }

    /** @return list<string> */
    public static function requiringClientAssignment(): array
    {
        return [
            'client-admin',
            'guardia',
            'supervisor',
            'resident',
            'anfitrion',
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            'super-admin' => 'Súper administrador',
            'company-admin' => 'Administrador empresa',
            'client-admin' => 'Administrador conjunto',
            'guardia' => 'Guardia portería',
            'supervisor' => 'Supervisor portería',
            'resident' => 'Residente portal',
            'anfitrion' => 'Anfitrión',
            'admin-accesos' => 'Admin accesos (legacy)',
            default => $role,
        };
    }
}
