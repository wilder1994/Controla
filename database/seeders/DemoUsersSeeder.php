<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Único usuario del seed mínimo: súper administrador.
 * El resto de usuarios se crea desde la UI.
 *
 * Local: PLATFORM_ADMIN_* opcionales (cae a admin@control-acceso.test / Admin123!).
 * Producción: ambas variables son obligatorias y viven solo en el .env del servidor.
 */
final class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('access.platform_admin_email', ''));
        $password = (string) config('access.platform_admin_password', '');

        if (app()->environment('production')) {
            if ($email === '' || $password === '') {
                throw new RuntimeException(
                    'Define PLATFORM_ADMIN_EMAIL y PLATFORM_ADMIN_PASSWORD en el .env de producción.'
                );
            }
        } else {
            $email = $email !== '' ? $email : 'admin@control-acceso.test';
            $password = $password !== '' ? $password : 'Admin123!';
        }

        $payload = [
            'name' => 'Súper Administrador',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
            'must_change_password' => false,
        ];

        $superAdmin = User::query()->role('super-admin')->first()
            ?? User::query()->where('email', $email)->first();

        if ($superAdmin === null) {
            $superAdmin = User::query()->create($payload);
        } else {
            $superAdmin->fill($payload)->save();
        }

        $superAdmin->syncRoles(['super-admin']);
    }
}
