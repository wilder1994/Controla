<?php

declare(strict_types=1);

return [
    'permissions' => [
        // Plataforma
        'platform.dashboard',
        'platform.companies.view',
        'platform.companies.manage',

        // Empresa de seguridad
        'company.dashboard',
        'company.clients.view',
        'company.clients.manage',
        'company.users.assign',

        // Cliente / censo (fase 1+)
        'client.structures.manage',
        'client.members.manage',
        'client.vehicles.manage',
        'client.authorizations.manage',
        'client.app_users.manage',

        // Portería operativa
        'access.dashboard',
        'access.manage.visitors',
        'access.manage.vehicles',
        'access.manage.vehicle_access',
        'access.register.entry',
        'access.register.exit',
        'access.manage.pre_authorizations',
        'access.manage.correspondence',
        'access.manage.guard_logs',
        'access.manage.locations',
        'access.manage.buildings',
        'access.manage.housing_units',
        'access.manage.residents',
        'access.view.reports',

        // Residente
        'resident.portal.access',
    ],

    'roles' => [
        'super-admin' => [
            'platform.dashboard',
            'platform.companies.view',
            'platform.companies.manage',
        ],

        'company-admin' => [
            'company.dashboard',
            'company.clients.view',
            'company.clients.manage',
            'company.users.assign',
            'access.dashboard',
            'access.view.reports',
        ],

        'client-admin' => [
            'client.structures.manage',
            'client.members.manage',
            'client.vehicles.manage',
            'client.authorizations.manage',
            'client.app_users.manage',
            'access.dashboard',
            'access.manage.locations',
            'access.manage.buildings',
            'access.manage.housing_units',
            'access.manage.residents',
            'access.manage.visitors',
            'access.manage.vehicles',
            'access.manage.vehicle_access',
            'access.register.entry',
            'access.register.exit',
            'access.manage.pre_authorizations',
            'access.manage.correspondence',
            'access.manage.guard_logs',
            'access.view.reports',
        ],

        'supervisor' => [
            'access.dashboard',
            'access.register.entry',
            'access.register.exit',
            'access.manage.visitors',
            'access.manage.vehicles',
            'access.manage.vehicle_access',
            'access.manage.residents',
            'access.manage.correspondence',
            'access.manage.guard_logs',
            'access.view.reports',
        ],

        'guardia' => [
            'access.dashboard',
            'access.register.entry',
            'access.register.exit',
            'access.manage.visitors',
            'access.manage.vehicles',
            'access.manage.vehicle_access',
            'access.manage.residents',
            'access.manage.correspondence',
            'access.manage.guard_logs',
        ],

        'resident' => [
            'resident.portal.access',
            'access.manage.pre_authorizations',
        ],

        // Compatibilidad temporal con roles existentes
        'admin-accesos' => [
            'client.structures.manage',
            'client.members.manage',
            'client.vehicles.manage',
            'client.authorizations.manage',
            'client.app_users.manage',
            'access.dashboard',
            'access.manage.locations',
            'access.manage.buildings',
            'access.manage.housing_units',
            'access.manage.residents',
            'access.manage.visitors',
            'access.manage.vehicles',
            'access.manage.vehicle_access',
            'access.register.entry',
            'access.register.exit',
            'access.manage.pre_authorizations',
            'access.manage.correspondence',
            'access.manage.guard_logs',
            'access.view.reports',
        ],

        'anfitrion' => [
            'resident.portal.access',
            'access.manage.pre_authorizations',
        ],
    ],

    'navigation' => [
        'admin' => [
            'label' => 'Panel Plataforma',
            'permission' => 'platform.dashboard',
            'items' => [
                ['label' => 'Resumen', 'route' => 'admin.dashboard', 'permission' => 'platform.dashboard'],
            ],
        ],
        'company' => [
            'label' => 'Panel Empresa',
            'permission' => 'company.dashboard',
            'items' => [
                ['label' => 'Resumen', 'route' => 'company.dashboard', 'permission' => 'company.dashboard'],
                ['label' => 'Clientes', 'route' => 'company.clients.index', 'permission' => 'company.clients.view'],
            ],
        ],
        'access' => [
            'label' => 'Control Acceso',
            'permission' => 'access.dashboard',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'access.dashboard', 'permission' => 'access.dashboard'],
                ['label' => 'Ingreso/Salida', 'route' => 'access.logs.index', 'permission' => 'access.register.entry'],
                ['label' => 'Visitantes', 'route' => 'access.visitors.index', 'permission' => 'access.manage.visitors'],
                ['label' => 'Residentes', 'route' => 'access.residents.index', 'permission' => 'access.manage.residents'],
                ['label' => 'Apartamentos', 'route' => 'access.housing_units.index', 'permission' => 'access.manage.housing_units'],
                ['label' => 'Torres/Bloques', 'route' => 'access.buildings.index', 'permission' => 'access.manage.buildings'],
                ['label' => 'Vehículos', 'route' => 'access.vehicles.index', 'permission' => 'access.manage.vehicles'],
                ['label' => 'Control Vehicular', 'route' => 'access.vehicle_access.index', 'permission' => 'access.manage.vehicle_access'],
                ['label' => 'Pre-Autorizaciones', 'route' => 'access.pre_authorizations.index', 'permission' => 'access.manage.pre_authorizations'],
                ['label' => 'Correspondencia', 'route' => 'access.correspondence.index', 'permission' => 'access.manage.correspondence'],
                ['label' => 'Minutas', 'route' => 'access.guard_logs.index', 'permission' => 'access.manage.guard_logs'],
                ['label' => 'Ubicaciones', 'route' => 'access.locations.index', 'permission' => 'access.manage.locations'],
                ['label' => 'Reportes', 'route' => 'access.reports.index', 'permission' => 'access.view.reports'],
            ],
        ],
        'client' => [
            'label' => 'Panel Conjunto',
            'permission' => 'client.structures.manage',
            'items' => [
                ['label' => 'Resumen', 'route' => 'client.dashboard', 'permission' => 'client.structures.manage'],
                ['label' => 'Residencial', 'route' => 'client.structures.index', 'permission' => 'client.structures.manage'],
                ['label' => 'Personas', 'route' => 'client.members.index', 'permission' => 'client.members.manage'],
                ['label' => 'Vehículos', 'route' => 'client.vehicles.index', 'permission' => 'client.vehicles.manage'],
                ['label' => 'Autorizaciones', 'route' => 'client.authorizations.index', 'permission' => 'client.authorizations.manage'],
                ['label' => 'Usuarios APP', 'route' => 'client.app-users.index', 'permission' => 'client.app_users.manage'],
            ],
        ],
    ],
];
