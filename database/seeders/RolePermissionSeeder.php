<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $admin = Role::findOrCreate('admin', 'admin');
        $customer = Role::findOrCreate('customer', 'web');

        // Define permissions for admin
        $adminPermissions = [
            'manage products',
            'manage orders',
            'manage categories',
        ];

        // Define permissions for admin
        $customerPermissions = [
            'view products',
            'place orders',
        ];

        // Create database records for admin permissions
        foreach ($adminPermissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        // Create database records for customer permissions
        foreach ($customerPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Give desired permission to admin and customer from permission arrays
        $admin->givePermissionTo($adminPermissions);
        $customer->givePermissionTo($customerPermissions);
    }
}
