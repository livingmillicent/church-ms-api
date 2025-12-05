<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Define roles
        $roles = [
            'admin',
            'pastor',
            'member',
        ];

        // Create roles if they don't exist
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }

        /**
         * Define permissions
         * (You can customize this list)
         */
        $permissions = [
            'view members',
            'create members',
            'edit members',
            'delete members',

            'view services',
            'create services',
            'edit services',
            'delete services',

            'manage finances',
        ];

        // Create permissions if not exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // Assign permissions to roles
        $admin = Role::where('name', 'admin')->first();
        $pastor = Role::where('name', 'pastor')->first();
        $member = Role::where('name', 'member')->first();

        // Admin gets ALL permissions
        $admin->syncPermissions(Permission::all());

        // Pastor gets most permissions except financial management
        $pastor->syncPermissions([
            'view members',
            'create members',
            'edit members',

            'view services',
            'create services',
            'edit services',
        ]);

        // Member gets minimal permissions
        $member->syncPermissions([
            'view services',
        ]);
    }
}
