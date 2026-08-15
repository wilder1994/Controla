<?php

declare(strict_types=1);

namespace Database\Seeders;

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

        foreach (Role::all() as $role) {
            if (! array_key_exists($role->name, config('access.roles', []))) {
                $role->users()->sync([]);
                $role->permissions()->sync([]);
                $role->delete();
            }
        }

        $superAdmin = Role::findByName('super-admin');
        $superAdmin->syncPermissions(Permission::all());
    }
}
