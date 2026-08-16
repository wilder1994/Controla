<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed mínimo para pruebas reales / entorno limpio:
 * roles+permisos, normoteca+TRD, súper admin.
 *
 * Datos piloto (empresa, conjuntos, censo, otros usuarios):
 * php artisan db:seed --class=PilotDemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            PlatformDocumentsSeeder::class,
            IdentityDocumentTypeSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
