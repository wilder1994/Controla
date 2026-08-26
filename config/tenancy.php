<?php

declare(strict_types=1);

return [
    'session' => [
        'active_client_key' => 'tenancy.active_client_id',
        'active_company_key' => 'tenancy.active_company_id',
    ],

    /*
    | Legacy Axesa-style tiers (kept for DB columns on clients; not sold commercially).
    */
    'plan_tiers' => [
        'economic' => ['label' => 'Económico', 'max_structures' => 20],
        'deluxe' => ['label' => 'Deluxe', 'max_structures' => 50],
        'pro' => ['label' => 'Pro', 'max_structures' => 100],
        'ultimate' => ['label' => 'Ultimate', 'max_structures' => 200],
    ],

    /*
    | Commercial package sizes (client seats). Unit prices live in pricing_settings (DB);
    | volume/annual discounts below drive the auto-calculated price matrix.
    */
    'package_sizes' => [1, 5, 10, 50, 100],

    'pricing' => [
        'currency' => 'COP',
        'default_unit_manual' => 80_000,
        'default_unit_hardware' => 150_000,
        'default_unit_supervision' => 0,
        'volume_discounts' => [
            1 => 0.0,
            5 => 0.10,
            10 => 0.15,
            50 => 0.25,
            100 => 0.30,
        ],
        'annual_discount' => 0.17,
    ],

    'features' => [
        'manual' => [
            'census',
            'manual_gate',
            'resident_web',
        ],
        'hardware' => [
            'census',
            'manual_gate',
            'resident_web',
            'barcode_reader',
            'id_reader',
            'lpr',
            'rfid',
            'facial',
            'app_device_bridge',
        ],
    ],

    'feature_labels' => [
        'census' => 'Censo ilimitado por conjunto',
        'manual_gate' => 'Portería y registro manual',
        'resident_web' => 'Portal web residente',
        'barcode_reader' => 'Lector código de barras',
        'id_reader' => 'Lector de cédula / documento',
        'lpr' => 'Reconocimiento de placas (LPR)',
        'rfid' => 'Tarjetas y chips RFID',
        'facial' => 'Reconocimiento facial',
        'app_device_bridge' => 'App residente ↔ dispositivos',
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
