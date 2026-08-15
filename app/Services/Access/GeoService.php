<?php
namespace App\Services\Access;

use App\Models\Location;

class GeoService
{
    public function distanceMeters(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function validateAgainstLocation(Location $location, ?float $lat, ?float $lng): array
    {
        $errors = [];

        if ($lat === null || $lng === null) {
            $errors[] = 'La ubicación GPS es obligatoria.';
            return $errors;
        }

        if ($location->latitude !== null && $location->longitude !== null) {
            $distance = $this->distanceMeters($lat, $lng, (float) $location->latitude, (float) $location->longitude);
            $radius = (int) ($location->geo_radius_m ?: 250);

            if ($distance !== null && $distance > $radius) {
                $errors[] = "La posición capturada está a {$distance} m de la ubicación configurada (radio permitido: {$radius} m).";
            }
        }

        return $errors;
    }
}