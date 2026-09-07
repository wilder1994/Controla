<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\SupervisorShiftLocation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BuildSupervisorTrailService
{
    private const SIMPLIFY_METERS = 28.0;

    private const STOP_METERS = 45.0;

    private const STOP_SECONDS = 120;

    /**
     * @param  Collection<int, SupervisorShiftLocation>  $locations
     * @return array{
     *     path: list<array{lat: float, lng: float, at: ?string}>,
     *     start: ?array{lat: float, lng: float, at: ?string},
     *     end: ?array{lat: float, lng: float, at: ?string},
     *     stops: list<array{lat: float, lng: float, minutes: int, from: ?string, to: ?string}>,
     *     parked: ?array{lat: float, lng: float, minutes: int}
     * }
     */
    public function execute(Collection $locations, bool $shiftOpen = false): array
    {
        $points = $locations
            ->map(fn (SupervisorShiftLocation $loc) => [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'at' => $loc->recorded_at,
            ])
            ->values();

        if ($points->isEmpty()) {
            return [
                'path' => [],
                'start' => null,
                'end' => null,
                'stops' => [],
                'parked' => null,
            ];
        }

        $path = [];
        $stops = [];
        $cluster = [];

        foreach ($points as $point) {
            if ($cluster === []) {
                $cluster[] = $point;
                $path[] = $this->point($point);
                continue;
            }

            $anchor = $cluster[0];
            if ($this->meters($anchor['lat'], $anchor['lng'], $point['lat'], $point['lng']) <= self::STOP_METERS) {
                $cluster[] = $point;
                continue;
            }

            $this->flushStop($cluster, $stops);
            $cluster = [$point];
            $lastPath = $path[array_key_last($path)];
            if ($this->meters($lastPath['lat'], $lastPath['lng'], $point['lat'], $point['lng']) >= self::SIMPLIFY_METERS) {
                $path[] = $this->point($point);
            }
        }

        $parked = $this->flushStop($cluster, $stops);
        if (! $shiftOpen) {
            $parked = null;
        }

        $first = $points->first();
        $last = $points->last();

        return [
            'path' => $path,
            'start' => $this->point($first),
            'end' => $this->point($last),
            'stops' => $stops,
            'parked' => $parked,
        ];
    }

    /**
     * @param  list<array{lat: float, lng: float, at: ?CarbonInterface}>  $cluster
     * @param  list<array{lat: float, lng: float, minutes: int, from: ?string, to: ?string}>  $stops
     * @return array{lat: float, lng: float, minutes: int}|null
     */
    private function flushStop(array $cluster, array &$stops): ?array
    {
        if (count($cluster) < 2) {
            return null;
        }

        $firstAt = $cluster[0]['at'];
        $lastAt = $cluster[array_key_last($cluster)]['at'];
        if (! $firstAt instanceof CarbonInterface || ! $lastAt instanceof CarbonInterface) {
            return null;
        }

        $seconds = $firstAt->diffInSeconds($lastAt, true);
        if ($seconds < self::STOP_SECONDS) {
            return null;
        }

        $minutes = max(2, (int) round($seconds / 60));
        $lat = array_sum(array_column($cluster, 'lat')) / count($cluster);
        $lng = array_sum(array_column($cluster, 'lng')) / count($cluster);
        $stop = [
            'lat' => $lat,
            'lng' => $lng,
            'minutes' => $minutes,
            'from' => $firstAt->toIso8601String(),
            'to' => $lastAt->toIso8601String(),
        ];
        $stops[] = $stop;

        return [
            'lat' => $lat,
            'lng' => $lng,
            'minutes' => $minutes,
        ];
    }

    /**
     * @param  array{lat: float, lng: float, at: mixed}  $point
     * @return array{lat: float, lng: float, at: ?string}
     */
    private function point(array $point): array
    {
        $at = $point['at'] ?? null;

        return [
            'lat' => $point['lat'],
            'lng' => $point['lng'],
            'at' => $at instanceof CarbonInterface ? $at->toIso8601String() : null,
        ];
    }

    private function meters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1.0, sqrt($a)));
    }
}
