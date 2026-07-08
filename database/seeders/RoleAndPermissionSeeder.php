<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (config('access.permissions', []) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (config('access.roles', []) as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (! empty($rolePermissions)) {
                $role->syncPermissions($rolePermissions);
            }
        }

        $superAdmin = Role::findByName('super-admin');
        $superAdmin->syncPermissions(Permission::all());

        $admin = User::firstOrCreate(
            ['email' => 'admin@control-acceso.test'],
            [
                'name' => 'Súper Administrador',
                'password' => bcrypt('Admin123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super-admin');

        $guardia = User::firstOrCreate(
            ['email' => 'guardia@control-acceso.test'],
            [
                'name' => 'Guardia Portero',
                'password' => bcrypt('Guardia123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $guardia->assignRole('guardia');

        $anfitrion = User::firstOrCreate(
            ['email' => 'anfitrion@control-acceso.test'],
            [
                'name' => 'Residente Ejemplo',
                'password' => bcrypt('Anfitrion123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $anfitrion->assignRole('resident');
    }
}
