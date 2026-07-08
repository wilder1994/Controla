<?php

declare(strict_types=1);

return [
    'session' => [
        'active_client_key' => 'tenancy.active_client_id',
        'active_company_key' => 'tenancy.active_company_id',
    ],

    'plan_tiers' => [
        'economic' => ['label' => 'Económico', 'max_structures' => 20],
        'deluxe' => ['label' => 'Deluxe', 'max_structures' => 50],
        'pro' => ['label' => 'Pro', 'max_structures' => 100],
        'ultimate' => ['label' => 'Ultimate', 'max_structures' => 200],
    ],

    'tenant_scoped_tables' => [
        'locations',
        'buildings',
        'housing_units',
        'residents',
        'visitors',
        'vehicles',
        'access_logs',
        'pre_authorizations',
        'correspondence',
        'guard_logs',
        'structures',
        'structure_members',
        'structure_pets',
        'visitor_pre_authorizations',
        'structure_app_users',
    ],
];
