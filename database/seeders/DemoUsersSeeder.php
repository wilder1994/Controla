<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Único usuario del seed mínimo: súper administrador.
 * El resto de usuarios se crea desde la UI.
 */
final class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@control-acceso.test'],
            [
                'name' => 'Súper Administrador',
                'password' => 'Admin123!',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);
    }
}
