<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated El árbol instalación → acceso vive en TenantSeeder (ficha del cliente).
 */
final class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: las puertas de Accesos se crean a mano o en el seed piloto del cliente.
    }
}
