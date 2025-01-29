<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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


        $permissions = [
            "role-list",
            "role-create",
            "role-edit",
            "role-delete",
            "user-list",
            "user-create",
            "user-edit",
            "user-delete",
        ];

        $permissions_admin_package_1 = [
            "user-list",
            "user-edit",
        ];

        foreach ($permissions as $key => $permission) {
            Permission::create(['name' => $permission]);
        }

        $role_superadmin = Role::create(['name' => 'superadmin']);
        $role_superadmin->syncPermissions($permissions);

        $role_admin_package_1 = Role::create(['name' => 'admin']);
        $role_admin_package_1->syncPermissions($permissions_admin_package_1);

        // It Should be move UserSeeder for first setup
        $create_user_role_1 = User::findOrFail(1);
        $create_user_role_1->assignRole($role_superadmin);

        $create_user_role_2 = User::findOrFail(2);
        $create_user_role_2->assignRole($role_admin_package_1);
    }
}
