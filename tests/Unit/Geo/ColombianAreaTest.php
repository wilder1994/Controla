<?php

declare(strict_types=1);

namespace Tests\Unit\Geo;

use App\Enums\ColombianAreaKind;
use App\Support\Geo\ColombianArea;
use Tests\TestCase;

final class ColombianAreaTest extends TestCase
{
    public function test_classifies_named_settlements_and_city_rules(): void
    {
        $this->assertSame(ColombianAreaKind::Comuna, ColombianArea::classify('Comuna 17', 'Cali'));
        $this->assertSame(ColombianAreaKind::Localidad, ColombianArea::classify('Kennedy', 'Bogotá'));
        $this->assertSame(ColombianAreaKind::Vereda, ColombianArea::classify('Vereda El Cerrito', 'Jamundí'));
        $this->assertSame(ColombianAreaKind::Corregimiento, ColombianArea::classify('Corregimiento Pance', 'Cali'));
        $this->assertSame(ColombianAreaKind::None, ColombianArea::classify('Centro', 'Jamundí'));
        $this->assertSame(ColombianAreaKind::None, ColombianArea::classify(null, 'Cali'));
    }

    public function test_drops_barrio_when_settlement_does_not_apply(): void
    {
        $this->assertNull(ColombianArea::persistableValue('Centro', 'Jamundí'));
        $this->assertSame('Comuna 17', ColombianArea::persistableValue('Comuna 17', 'Cali'));
    }
}
