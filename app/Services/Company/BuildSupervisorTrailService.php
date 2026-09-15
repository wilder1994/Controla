<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\SupervisorShiftLocation;
use App\Support\Supervision\SupervisorPresence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BuildSupervisorTrailService
{
    private const SIMPLIFY_METERS = 28.0;

    private const STOP_METERS = 75.0;

    private const STOP_SECONDS = 120;

    /** Saltos mayores se descartan si el siguiente ping no los confirma (picos GPS). */
    private const SPIKE_METERS = 140.0;

    /** ~130 km/h: una moto de patrulla no cubre más en un ping. */
    private const MAX_SPEED_MPS = 36.0;

    private const MAX_ACCURACY_METERS = 150.0;

    public const OFFLINE_SECONDS = SupervisorPresence::FRESH_SECONDS;

    /**
     * @param  Collection<int, SupervisorShiftLocation>  $locations
     * @return array{
     *     path: list<array{lat: float, lng: float, at: ?string}>,
     *     start: ?array{lat: float, lng: float, at: ?string},
     *     end: ?array{lat: float, lng: float, at: ?string},
     *     stops: list<array{lat: float, lng: float, minutes: int, from: ?string, to: ?string, current: bool, label: string}>,
     *     parked: ?array{lat: float, lng: float, minutes: int, from: ?string, to: ?string, current: bool, label: string},
     *     km: float,
     *     online: bool,
     *     signal: string,
     *     online_label: string
     * }
     */
    public function execute(Collection $locations, bool $shiftOpen = false, ?CarbonInterface $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $rawLast = $locations->last();
        $points = $locations
            ->map(fn (SupervisorShiftLocation $loc) => [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'at' => $loc->recorded_at,
                'accuracy' => $loc->accuracy !== null ? (float) $loc->accuracy : null,
            ])
            ->values();

        if ($points->isEmpty()) {
            return [
                'path' => [],
                'start' => null,
                'end' => null,
                'stops' => [],
                'parked' => null,
                'km' => 0.0,
                ...SupervisorPresence::from(null, null, $now),
            ];
        }

        $points = collect($this->filterGpsNoise($points->all()))->values();
        if ($points->isEmpty()) {
            $fallback = $locations->first();
            $points = collect([[
                'lat' => (float) $fallback->latitude,
                'lng' => (float) $fallback->longitude,
                'at' => $fallback->recorded_at,
                'accuracy' => $fallback->accuracy !== null ? (float) $fallback->accuracy : null,
            ]]);
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

            $closed = $this->clusterStop($cluster, false, $cluster[array_key_last($cluster)]['at']);
            if ($closed !== null) {
                $stops[] = $closed;
            }
            $cluster = [$point];
            $lastPath = $path[array_key_last($path)];
            if ($this->meters($lastPath['lat'], $lastPath['lng'], $point['lat'], $point['lng']) >= self::SIMPLIFY_METERS) {
                $path[] = $this->point($point);
            }
        }

        $parked = null;
        if ($shiftOpen) {
            $parked = $this->clusterStop($cluster, true, $now);
        } else {
            $closed = $this->clusterStop($cluster, false, $cluster[array_key_last($cluster)]['at'] ?? null);
            if ($closed !== null) {
                $stops[] = $closed;
            }
        }

        $first = $points->first();
        $last = $points->last();
        $screenOn = $rawLast instanceof SupervisorShiftLocation && $rawLast->screen_on !== null
            ? (bool) $rawLast->screen_on
            : null;

        return [
            'path' => $path,
            'start' => $this->point($first),
            'end' => $this->point($last),
            'stops' => $stops,
            'parked' => $parked,
            'km' => $this->km($path),
            ...SupervisorPresence::from(
                $rawLast instanceof SupervisorShiftLocation ? $rawLast->recorded_at : ($last['at'] ?? null),
                $screenOn,
                $now,
            ),
        ];
    }

    /**
     * @param  list<array{lat: float, lng: float, at: mixed}>  $cluster
     * @return array{lat: float, lng: float, minutes: int, from: ?string, to: ?string, current: bool, label: string}|null
     */
    private function clusterStop(array $cluster, bool $current, mixed $until): ?array
    {
        if ($cluster === [] || ! $until instanceof CarbonInterface) {
            return null;
        }

        $firstAt = $cluster[0]['at'];
        if (! $firstAt instanceof CarbonInterface) {
            return null;
        }

        if (! $current && count($cluster) < 2) {
            return null;
        }

        $lastPing = $cluster[array_key_last($cluster)]['at'];
        if (! $lastPing instanceof CarbonInterface) {
            $lastPing = $firstAt;
        }

        $spanNow = $firstAt->diffInSeconds($until, true);
        $spanPings = $firstAt->diffInSeconds($lastPing, true);
        $stale = $lastPing->diffInSeconds($until, true) > self::OFFLINE_SECONDS;

        if ($spanNow < self::STOP_SECONDS) {
            return null;
        }
        if ($current && $stale && $spanPings < self::STOP_SECONDS) {
            return null;
        }
        if (! $current && $spanPings < self::STOP_SECONDS) {
            return null;
        }

        $minutes = max(2, (int) round(($current ? $spanNow : $spanPings) / 60));
        $lat = array_sum(array_column($cluster, 'lat')) / count($cluster);
        $lng = array_sum(array_column($cluster, 'lng')) / count($cluster);
        $from = $firstAt->toIso8601String();
        $to = $until->toIso8601String();
        $fromLabel = $this->clock($firstAt);
        $toLabel = $this->clock($until);

        return [
            'lat' => $lat,
            'lng' => $lng,
            'minutes' => $minutes,
            'from' => $from,
            'to' => $to,
            'current' => $current,
            'label' => $current
                ? 'Se detuvo a las '.$fromLabel.' · lleva '.$minutes.' min'
                : 'Se detuvo de '.$fromLabel.' a '.$toLabel.' ('.$minutes.' min)',
        ];
    }

    /**
     * Descarta precisión pésima, saltos más rápidos que una moto y picos aislados
     * (el siguiente ping vuelve hacia el punto anterior).
     *
     * @param  list<array{lat: float, lng: float, at: mixed, accuracy: ?float}>  $points
     * @return list<array{lat: float, lng: float, at: mixed, accuracy: ?float}>
     */
    private function filterGpsNoise(array $points): array
    {
        $kept = [];
        $n = count($points);

        for ($i = 0; $i < $n; $i++) {
            $cur = $points[$i];
            if (! $this->coordsOk($cur) || $this->tooInaccurate($cur)) {
                continue;
            }
            if ($kept === []) {
                $kept[] = $cur;

                continue;
            }

            $prev = $kept[array_key_last($kept)];
            $dist = $this->meters($prev['lat'], $prev['lng'], $cur['lat'], $cur['lng']);
            $dt = $this->secondsBetween($prev['at'] ?? null, $cur['at'] ?? null);

            if ($dist <= self::SPIKE_METERS) {
                $kept[] = $cur;

                continue;
            }

            if (! $this->speedPlausible($dist, $dt)) {
                continue;
            }

            $next = $points[$i + 1] ?? null;
            if ($next === null) {
                $kept[] = $cur;

                continue;
            }
            if (! $this->coordsOk($next) || $this->tooInaccurate($next)) {
                continue;
            }

            $dPrevNext = $this->meters($prev['lat'], $prev['lng'], $next['lat'], $next['lng']);
            $dCurNext = $this->meters($cur['lat'], $cur['lng'], $next['lat'], $next['lng']);
            if ($dCurNext + 40.0 < $dPrevNext) {
                $kept[] = $cur;
            }
        }

        return $kept;
    }

    /** @param  array{lat: float, lng: float, accuracy?: ?float}  $point */
    private function coordsOk(array $point): bool
    {
        $lat = $point['lat'];
        $lng = $point['lng'];
        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }

    /** @param  array{accuracy?: ?float}  $point */
    private function tooInaccurate(array $point): bool
    {
        $accuracy = $point['accuracy'] ?? null;

        return $accuracy !== null && $accuracy > self::MAX_ACCURACY_METERS;
    }

    private function speedPlausible(float $meters, int $seconds): bool
    {
        $dt = max(1, $seconds);

        return ($meters / $dt) <= self::MAX_SPEED_MPS;
    }

    private function secondsBetween(mixed $from, mixed $to): int
    {
        if (! $from instanceof CarbonInterface || ! $to instanceof CarbonInterface) {
            return 15;
        }

        return max(0, (int) $from->diffInSeconds($to, false));
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

    /** @param  list<array{lat: float, lng: float}>  $path */
    private function km(array $path): float
    {
        $total = 0.0;
        for ($i = 1, $n = count($path); $i < $n; $i++) {
            $total += $this->meters($path[$i - 1]['lat'], $path[$i - 1]['lng'], $path[$i]['lat'], $path[$i]['lng']);
        }

        return round($total / 1000, 1);
    }

    private function clock(CarbonInterface $at): string
    {
        return $at->timezone((string) config('app.timezone'))->format('H:i');
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
