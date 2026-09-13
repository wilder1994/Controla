<?php

declare(strict_types=1);

namespace Tests\Unit\Geo;

use App\Support\Geo\CaliComunaLayer;
use App\Support\Geo\PointInPolygon;
use Tests\TestCase;

final class CaliComunaLayerTest extends TestCase
{
    public function test_point_in_polygon_handles_square_and_hole(): void
    {
        $pip = new PointInPolygon;
        $outer = [[0.0, 0.0], [0.0, 2.0], [2.0, 2.0], [2.0, 0.0], [0.0, 0.0]];
        $hole = [[0.75, 0.75], [0.75, 1.25], [1.25, 1.25], [1.25, 0.75], [0.75, 0.75]];

        $this->assertTrue($pip->ringContains(1.0, 1.0, $outer));
        $this->assertFalse($pip->ringContains(3.0, 3.0, $outer));
        $this->assertTrue($pip->polygonContains(0.2, 0.2, [$outer, $hole]));
        $this->assertFalse($pip->polygonContains(1.0, 1.0, [$outer, $hole]));
    }

    public function test_locates_cali_urban_comunas_and_rejects_outside(): void
    {
        $layer = app(CaliComunaLayer::class);

        $this->assertSame('09', $layer->locate(3.43722, -76.5225)['code'] ?? null);
        $this->assertSame('03', $layer->locate(3.4516, -76.532)['code'] ?? null);
        $this->assertNull($layer->locate(4.60971, -74.08175));
        $this->assertSame('09', $layer->normalize('9'));
        $this->assertSame('fuera', $layer->normalize('fuera'));
        $this->assertSame('', $layer->normalize('99'));
        $this->assertCount(22, $layer->catalog());
    }
}
