<?php

namespace Tests;

use App\Enums\CompanyPackageSku;
use App\Support\Legal\CorpusAcceptanceRules;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PilotDemoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seed mínimo + datos piloto (empresa, conjuntos, censo, usuarios demo). */
    protected function seedWithPilot(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(PilotDemoSeeder::class);
    }

    /** @return array<string, array<string, string>> */
    protected function acceptAllCorpusDocs(?CompanyPackageSku $sku = null): array
    {
        $docs = [];
        foreach (CorpusAcceptanceRules::requiredTypeValues($sku) as $type) {
            $docs[$type] = '1';
        }

        return ['accept_docs' => $docs];
    }
}
