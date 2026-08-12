<?php

declare(strict_types=1);

return [
    'api_key' => env('GOOGLE_MAPS_API_KEY'),
    'default_center' => [
        'lat' => (float) env('GOOGLE_MAPS_DEFAULT_LAT', 4.5709),
        'lng' => (float) env('GOOGLE_MAPS_DEFAULT_LNG', -74.2973),
    ],
    'default_zoom' => (int) env('GOOGLE_MAPS_DEFAULT_ZOOM', 6),
];
