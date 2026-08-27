<?php

declare(strict_types=1);

return [
    /*
    | Datos operativos (censo, visitantes, logs): retención tras retiro o archivo.
    | Orientación Ley 1581 — suprimir cuando la finalidad del servicio concluyó.
    */
    'census_retention_days' => (int) env('RETENTION_CENSUS_DAYS', 365),

    /*
    | Metadatos comerciales / facturación de empresas archivadas (estatuto tributario ~5 años).
    */
    'commercial_retention_years' => (int) env('RETENTION_COMMERCIAL_YEARS', 5),

    /*
    | Tablas con client_id a purgar (orden: dependientes primero).
    */
    'purge_tables' => [
        'supervisor_field_logs',
        'supervisor_recommendations',
        'supervisor_shift_reviews',
        'supervisor_posts',
        'access_logs',
        'correspondence',
        'pre_authorizations',
        'visitor_pre_authorizations',
        'guard_logs',
        'blocklist',
        'structure_app_users',
        'structure_pets',
        'structure_members',
        'vehicles',
        'structures',
        'visitors',
        'residents',
        'housing_units',
        'buildings',
        'locations',
        'installations',
        'client_user_assignments',
    ],
];
