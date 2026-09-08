<?php

declare(strict_types=1);

namespace App\Services\Company;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SnapSupervisorTrailToRoadsService
{
    private const CHUNK = 100;

    private const OVERLAP = 5;

    /**
     * @param  list<array{lat: float, lng: float}>  $points
     * @return list<array{lat: float, lng: float}>
     */
    public function execute(array $points): array
    {
        if (count($points) < 2) {
            return [];
        }

        $key = (string) (config('google-maps.server_api_key') ?: config('google-maps.api_key'));
        if ($key === '') {
            return [];
        }

        $snapped = [];
        $offset = 0;
        $total = count($points);

        while ($offset < $total) {
            $chunk = array_slice($points, $offset, self::CHUNK);
            $mapped = $this->request($chunk, $key);
            if ($mapped === null) {
                return [];
            }
            if ($offset > 0 && $mapped !== []) {
                array_shift($mapped);
            }
            foreach ($mapped as $point) {
                $snapped[] = $point;
            }
            if ($offset + self::CHUNK >= $total) {
                break;
            }
            $offset += self::CHUNK - self::OVERLAP;
        }

        return $snapped;
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $chunk
     * @return list<array{lat: float, lng: float}>|null
     */
    private function request(array $chunk, string $key): ?array
    {
        $path = implode('|', array_map(
            fn (array $p) => $p['lat'].','.$p['lng'],
            $chunk,
        ));

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Referer' => rtrim((string) config('app.url'), '/').'/',
                ])
                ->get('https://roads.googleapis.com/v1/snapToRoads', [
                    'path' => $path,
                    'interpolate' => 'true',
                    'key' => $key,
                ]);
        } catch (Throwable $e) {
            Log::warning('Roads snap failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('Roads snap HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $points = [];
        foreach ($response->json('snappedPoints') ?? [] as $row) {
            $lat = $row['location']['latitude'] ?? null;
            $lng = $row['location']['longitude'] ?? null;
            if ($lat === null || $lng === null) {
                continue;
            }
            $points[] = ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        return $points;
    }
}
