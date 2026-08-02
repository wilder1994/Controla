<?php

declare(strict_types=1);

return [
    /*
    | demo: facturas simuladas, sin llamadas a proveedor tecnológico DIAN.
    | live: emisión real vía PT (pendiente go-live legal).
    */
    'mode' => env('BILLING_MODE', 'demo'),

    'currency' => 'COP',

    'demo_invoice_prefix' => env('BILLING_DEMO_PREFIX', 'DEMO'),
];
