<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',

            'view_tickets',
            'create_tickets',
            'edit_tickets',
            'delete_tickets',

            'view_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $mantenimientoRole = Role::firstOrCreate(['name' => 'Mantenimiento', 'guard_name' => 'web']);
        $mantenimientoRole->syncPermissions([
            'view_tickets',
            'create_tickets',
            'edit_tickets',
            'view_logs',
        ]);

        $soporteRole = Role::firstOrCreate(['name' => 'Soporte', 'guard_name' => 'web']);
        $soporteRole->syncPermissions([
            'view_tickets',
            'create_tickets',
            'view_users',
        ]);
    }
}
