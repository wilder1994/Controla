<?php

declare(strict_types=1);

namespace Tests\Unit\Geo;

use App\Support\Geo\ColombiaDivipola;
use Tests\TestCase;

final class ColombiaDivipolaTest extends TestCase
{
    public function test_catalog_includes_departments_and_bogota_capital(): void
    {
        $this->assertTrue(ColombiaDivipola::hasDepartment('Antioquia'));
        $this->assertTrue(ColombiaDivipola::hasMunicipality('Antioquia', 'Medellín'));
        $this->assertTrue(ColombiaDivipola::hasDepartment('Bogotá D.C.'));
        $this->assertTrue(ColombiaDivipola::hasMunicipality('Bogotá D.C.', 'Bogotá D.C.'));
        $this->assertFalse(ColombiaDivipola::hasMunicipality('Cundinamarca', 'Bogotá'));
        $this->assertFalse(ColombiaDivipola::hasMunicipality('Antioquia', 'Cali'));
    }
}
