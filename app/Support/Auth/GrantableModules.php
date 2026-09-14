<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\AccessGrantLevel;
use App\Enums\AccessGrantScope;

final class GrantableModules
{
    /**
     * @return array<string, array{label: string, view: list<string>, manage: list<string>}>
     */
    public static function forScope(AccessGrantScope $scope): array
    {
        return match ($scope) {
            AccessGrantScope::Company => [
                'clients' => [
                    'label' => 'Clientes',
                    'view' => ['company.clients.view'],
                    'manage' => ['company.clients.view', 'company.clients.manage'],
                ],
                'installations' => [
                    'label' => 'Instalaciones',
                    'view' => ['company.clients.view'],
                    'manage' => ['company.clients.view', 'company.clients.manage'],
                ],
                'observatory' => [
                    'label' => 'Observatorio',
                    'view' => ['observatory.view'],
                    'manage' => ['observatory.view', 'observatory.events.update'],
                ],
                'supervision' => [
                    'label' => 'Supervisión',
                    'view' => ['company.supervision.view'],
                    'manage' => ['company.supervision.view'],
                ],
                'panics' => [
                    'label' => 'Atención de pánicos',
                    'view' => ['ops.panic.attend'],
                    'manage' => ['ops.panic.attend'],
                ],
                'employees' => [
                    'label' => 'Empleados',
                    'view' => ['company.employees.view'],
                    'manage' => ['company.employees.view', 'company.employees.manage'],
                ],
                'documents' => [
                    'label' => 'Documentos',
                    'view' => ['company.documents.view'],
                    'manage' => ['company.documents.view', 'company.documents.manage'],
                ],
                'users' => [
                    'label' => 'Usuarios',
                    'view' => ['company.users.view'],
                    'manage' => ['company.users.view', 'company.users.assign'],
                ],
                'settings' => [
                    'label' => 'Ajustes',
                    'view' => ['company.settings.view'],
                    'manage' => ['company.settings.view', 'company.settings.manage'],
                ],
            ],
            AccessGrantScope::Client, AccessGrantScope::Installation => [
                'observatory' => [
                    'label' => 'Observatorio',
                    'view' => ['observatory.view'],
                    'manage' => ['observatory.view', 'observatory.events.update'],
                ],
                'census' => [
                    'label' => 'Censo / estructura',
                    'view' => ['client.structures.manage', 'client.members.manage'],
                    'manage' => [
                        'client.structures.manage',
                        'client.members.manage',
                        'client.pets.manage',
                        'client.vehicles.manage',
                        'client.authorizations.manage',
                    ],
                ],
            ],
        };
    }

    /** @return list<string> */
    public static function keys(AccessGrantScope $scope): array
    {
        return array_keys(self::forScope($scope));
    }

    /** @return list<string> */
    public static function permissionsFor(AccessGrantScope $scope, string $module, AccessGrantLevel $level): array
    {
        $catalog = self::forScope($scope)[$module] ?? null;
        if ($catalog === null) {
            return [];
        }

        return $level === AccessGrantLevel::Manage ? $catalog['manage'] : $catalog['view'];
    }
}
