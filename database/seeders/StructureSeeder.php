<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Services\Structure\MigrateLegacyStructuresService;
use App\Services\Structure\SeedPilotStructuresService;
use Illuminate\Database\Seeder;

final class StructureSeeder extends Seeder
{
    public function run(): void
    {
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();

        if ($palmas === null) {
            return;
        }

        app(MigrateLegacyStructuresService::class)->executeForClient($palmas);
        app(SeedPilotStructuresService::class)->execute($palmas);
    }
}
