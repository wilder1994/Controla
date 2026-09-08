<?php

declare(strict_types=1);

namespace Tests\Unit\Supervision;

use App\Services\Company\SnapSupervisorTrailToRoadsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SnapSupervisorTrailToRoadsTest extends TestCase
{
    public function test_empty_without_api_key(): void
    {
        config(['google-maps.api_key' => null, 'google-maps.server_api_key' => null]);

        $path = app(SnapSupervisorTrailToRoadsService::class)->execute([
            ['lat' => 3.45, 'lng' => -76.53],
            ['lat' => 3.46, 'lng' => -76.54],
        ]);

        $this->assertSame([], $path);
    }

    public function test_maps_snapped_points(): void
    {
        config(['google-maps.server_api_key' => 'test-key']);
        Http::fake([
            'roads.googleapis.com/*' => Http::response([
                'snappedPoints' => [
                    ['location' => ['latitude' => 3.1, 'longitude' => -76.1]],
                    ['location' => ['latitude' => 3.2, 'longitude' => -76.2]],
                ],
            ], 200),
        ]);

        $path = app(SnapSupervisorTrailToRoadsService::class)->execute([
            ['lat' => 3.45, 'lng' => -76.53],
            ['lat' => 3.46, 'lng' => -76.54],
        ]);

        $this->assertSame([
            ['lat' => 3.1, 'lng' => -76.1],
            ['lat' => 3.2, 'lng' => -76.2],
        ], $path);
    }
}
