<?php

declare(strict_types=1);

return [
    /*
    | Días de uso tras la fecha de corte sin pago (acceso sigue permitido).
    | Día de corte + grace_days = suspensión.
    */
    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 5),

    /*
    | Días antes de la fecha de corte para recordatorio de pago.
    | (Canal de notificación: pendiente de implementación.)
    */
    'reminder_days_before_cutoff' => (int) env('SUBSCRIPTION_REMINDER_DAYS', 5),

    /*
    | Días en suspensión sin reactivar antes de archivar por falta de pago.
    | Configurable: ajustar según política comercial / normativa interna.
    */
    'archive_after_suspended_days' => (int) env('SUBSCRIPTION_ARCHIVE_AFTER_SUSPENDED_DAYS', 90),

    /*
    | Día de corte máximo permitido (evita problemas con meses cortos).
    */
    'billing_day_max' => 28,
];
