<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions using dot notation: {module}.{action}
        $permissions = [
            // Roles management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Users management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Customers management
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Transactions management
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'transactions.delete',

            // Products management
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Brands management
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            // Product Categories management
            'product-categories.view',
            'product-categories.create',
            'product-categories.edit',
            'product-categories.delete',

            // Company management
            'company.view',
            'company.edit',

            // System Logs
            'logs.view',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create superadmin role (will bypass all permissions via Gate::before)
        $roleSuperadmin = Role::firstOrCreate(['name' => 'superadmin']);

        // Create admin role with specific permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
            'customers.view',
            'customers.create',
            'customers.edit',
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'products.view',
            'products.create',
            'products.edit',
            'brands.view',
            'brands.create',
            'brands.edit',
            'product-categories.view',
            'product-categories.create',
            'product-categories.edit',
            'company.view',
        ]);

        // Create staff role with limited permissions
        $roleStaff = Role::firstOrCreate(['name' => 'staff']);
        $roleStaff->syncPermissions([
            'customers.view',
            'transactions.view',
            'transactions.create',
            'products.view',
            'brands.view',
            'product-categories.view',
        ]);

        // Assign superadmin role to user ID 1 (if exists)
        $user1 = User::find(1);
        if ($user1) {
            $user1->assignRole($roleSuperadmin);
        }

        // Assign admin role to user ID 2 (if exists)
        $user2 = User::find(2);
        if ($user2) {
            $user2->assignRole($roleAdmin);
        }
    }
}
