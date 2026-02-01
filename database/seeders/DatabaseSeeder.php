<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, run roles and permissions (creates platform admin)
        $this->call(RolesPermissionSeeder::class);

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('  HyperBiz Database Seeded Successfully!');
        $this->command->info('=================================================');
        $this->command->info('');
        $this->command->info('Platform Admin Login:');
        $this->command->info('  Email: platform@hyperbiz.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('This is a fresh multi-tenant setup.');
        $this->command->info('Register new users via the app to create companies.');
        $this->command->info('');
    }
}
