<?php

declare(strict_types=1);

namespace App\Support\Geo;

final class PointInPolygon
{
    /**
     * @param  list<list{float, float}>  $ring  GeoJSON ring [lng, lat]
     */
    public function ringContains(float $lat, float $lng, array $ring): bool
    {
        $count = count($ring);
        if ($count < 3) {
            return false;
        }

        $inside = false;
        $j = $count - 1;
        for ($i = 0; $i < $count; $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];
            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersects) {
                $inside = ! $inside;
            }
            $j = $i;
        }

        return $inside;
    }

    /**
     * @param  list<list<list{float, float}>>  $polygon  GeoJSON Polygon rings
     */
    public function polygonContains(float $lat, float $lng, array $polygon): bool
    {
        $outer = $polygon[0] ?? [];
        if (! $this->ringContains($lat, $lng, $outer)) {
            return false;
        }

        foreach (array_slice($polygon, 1) as $hole) {
            if ($this->ringContains($lat, $lng, $hole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $coordinates
     */
    public function geometryContains(float $lat, float $lng, string $type, array $coordinates): bool
    {
        if ($type === 'Polygon') {
            return $this->polygonContains($lat, $lng, $coordinates);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                if (is_array($polygon) && $this->polygonContains($lat, $lng, $polygon)) {
                    return true;
                }
            }
        }

        return false;
    }
}
