<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos piloto opcionales (empresa SJ, Palmas/Torres, censo, usuarios demo).
 * No corre en `db:seed` por defecto — solo tests o:
 *   php artisan db:seed --class=PilotDemoSeeder
 */
final class PilotDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StructureTypeSeeder::class,
            LocationSeeder::class,
            TenantSeeder::class,
            PilotUsersSeeder::class,
            StructureSeeder::class,
        ]);
    }
}
