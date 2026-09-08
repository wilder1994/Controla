<?php

declare(strict_types=1);

namespace Tests\Unit\Supervision;

use App\Models\SupervisorShiftLocation;
use App\Services\Company\BuildSupervisorTrailService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class BuildSupervisorTrailTest extends TestCase
{
    public function test_stationary_pings_become_a_stop(): void
    {
        $start = CarbonImmutable::parse('2026-09-05 10:00:00');
        $locations = new Collection([
            $this->point(3.4516, -76.5320, $start),
            $this->point(3.45162, -76.53202, $start->addMinutes(2)),
            $this->point(3.45161, -76.53201, $start->addMinutes(6)),
            $this->point(3.4600, -76.5400, $start->addMinutes(9)),
        ]);

        $trail = app(BuildSupervisorTrailService::class)->execute($locations, true);

        $this->assertNotEmpty($trail['path']);
        $this->assertCount(1, $trail['stops']);
        $this->assertGreaterThanOrEqual(5, $trail['stops'][0]['minutes']);
        $this->assertNotNull($trail['start']);
        $this->assertNotNull($trail['end']);
        $this->assertNull(app(BuildSupervisorTrailService::class)->execute($locations, false)['parked']);
    }

    public function test_current_parked_grows_with_clock_and_is_not_a_past_stop(): void
    {
        $now = CarbonImmutable::parse('2026-09-05 10:20:00');
        $start = CarbonImmutable::parse('2026-09-05 10:00:00');
        $locations = new Collection([
            $this->point(3.4516, -76.5320, $start),
            $this->point(3.45161, -76.53201, $start->addMinutes(3)),
        ]);

        $trail = app(BuildSupervisorTrailService::class)->execute($locations, true, $now);

        $this->assertNotNull($trail['parked']);
        $this->assertTrue($trail['parked']['current']);
        $this->assertGreaterThanOrEqual(18, $trail['parked']['minutes']);
        $this->assertSame([], $trail['stops']);
        $this->assertFalse($trail['online']);
    }

    private function point(float $lat, float $lng, CarbonImmutable $at): SupervisorShiftLocation
    {
        $location = new SupervisorShiftLocation();
        $location->latitude = $lat;
        $location->longitude = $lng;
        $location->recorded_at = $at;

        return $location;
    }
}
