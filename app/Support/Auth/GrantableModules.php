<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Domain\User\AccessGrantData;
use App\Enums\AccessGrantLevel;
use App\Enums\AccessGrantScope;
use App\Models\User;

final class GrantableModules
{
    /**
     * @return array<string, array{label: string, view: list<string>, manage: list<string>, route?: string}>
     */
    public static function forScope(AccessGrantScope $scope): array
    {
        return match ($scope) {
            AccessGrantScope::Company => [
                'dashboard' => [
                    'label' => 'Mi empresa',
                    'view' => ['company.dashboard'],
                    'manage' => ['company.dashboard'],
                    'route' => 'company.dashboard',
                ],
                'billing' => [
                    'label' => 'Facturación',
                    'view' => ['company.billing.manage'],
                    'manage' => ['company.billing.manage'],
                    'route' => 'company.billing.index',
                ],
                'clients' => [
                    'label' => 'Clientes',
                    'view' => ['company.clients.view'],
                    'manage' => ['company.clients.view', 'company.clients.manage'],
                    'route' => 'company.clients.index',
                ],
                'installations' => [
                    'label' => 'Instalaciones',
                    'view' => ['company.installations.view'],
                    'manage' => ['company.installations.view', 'company.installations.manage'],
                    'route' => 'company.installations.index',
                ],
                'observatory' => [
                    'label' => 'Observatorio',
                    'view' => ['observatory.view'],
                    'manage' => ['observatory.view', 'observatory.events.update'],
                    'route' => 'company.observatory.events.index',
                ],
                'supervision' => [
                    'label' => 'Supervisión',
                    'view' => ['company.supervision.view'],
                    'manage' => ['company.supervision.view'],
                    'route' => 'company.supervision.index',
                ],
                'panics' => [
                    'label' => 'Atención de pánicos',
                    'view' => ['ops.panic.attend'],
                    'manage' => ['ops.panic.attend'],
                    'route' => 'company.panics.index',
                ],
                'downloads' => [
                    'label' => 'Descargas',
                    'view' => ['company.downloads.view'],
                    'manage' => ['company.downloads.view'],
                    'route' => 'company.downloads.index',
                ],
                'employees' => [
                    'label' => 'Empleados',
                    'view' => ['company.employees.view'],
                    'manage' => ['company.employees.view', 'company.employees.manage'],
                    'route' => 'company.employees.index',
                ],
                'documents' => [
                    'label' => 'Documentos',
                    'view' => ['company.documents.view'],
                    'manage' => ['company.documents.view', 'company.documents.manage'],
                    'route' => 'company.personnel-documents.index',
                ],
                'users' => [
                    'label' => 'Usuarios',
                    'view' => ['company.users.view'],
                    'manage' => ['company.users.view', 'company.users.assign'],
                    'route' => 'company.users.index',
                ],
                'profile' => [
                    'label' => 'Mis datos',
                    'view' => ['company.profile.manage'],
                    'manage' => ['company.profile.manage'],
                    'route' => 'company.settings.edit',
                ],
                'settings' => [
                    'label' => 'Ajustes',
                    'view' => ['company.settings.view'],
                    'manage' => ['company.settings.view', 'company.settings.manage'],
                    'route' => 'company.job-titles.index',
                ],
            ],
            AccessGrantScope::Client, AccessGrantScope::Installation => [
                'sig' => [
                    'label' => 'Resumen (tablero SIG)',
                    'view' => ['ops.sig.view'],
                    'manage' => ['ops.sig.view'],
                    'route' => 'client.dashboard',
                ],
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

    public static function usesMatrix(string $role): bool
    {
        return in_array($role, ['colaborador', 'company-admin', 'client-admin', 'client-installation-admin'], true);
    }

    public static function usesCompanyMatrix(string $role): bool
    {
        return in_array($role, ['colaborador', 'company-admin'], true);
    }

    public static function usesClientMatrix(string $role): bool
    {
        return in_array($role, ['colaborador', 'client-admin'], true);
    }

    public static function usesInstallationMatrix(string $role): bool
    {
        return in_array($role, ['colaborador', 'client-installation-admin'], true);
    }

    /**
     * @return list<AccessGrantData>
     */
    public static function defaultCompanyManageGrants(int $companyId): array
    {
        return self::defaultManageGrants(AccessGrantScope::Company, $companyId);
    }

    /**
     * @return list<AccessGrantData>
     */
    public static function defaultScopedManageGrants(AccessGrantScope $scope, int $scopeId): array
    {
        return self::defaultManageGrants($scope, $scopeId);
    }

    /**
     * @return list<AccessGrantData>
     */
    private static function defaultManageGrants(AccessGrantScope $scope, int $scopeId): array
    {
        $grants = [];
        foreach (self::keys($scope) as $module) {
            $grants[] = new AccessGrantData($scope, $scopeId, $module, AccessGrantLevel::Manage);
        }

        return $grants;
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

    public static function firstCompanyHome(User $user): string
    {
        foreach (self::forScope(AccessGrantScope::Company) as $row) {
            $route = $row['route'] ?? null;
            $permission = $row['view'][0] ?? null;
            if ($route === null || $permission === null) {
                continue;
            }
            if ($user->can($permission)) {
                return route($route);
            }
        }

        return route('profile.edit');
    }
}
