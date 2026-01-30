<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
                            {--skip-backup : Skip the mysqldump backup step}
                            {--skip-truncate : Skip truncating existing data}
                            {--backup-only : Only create backup, do not seed}';

    protected $description = 'Backup database, clear transaction data, and seed comprehensive demo data';

    // Tables to preserve (auth, users, roles, permissions related)
    protected array $preserveTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'teams',
        'team_user',
        'team_invitations',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'migrations',
        'failed_jobs',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'mst_company',
        'company_settings',
    ];

    // Tables to truncate in order (respecting foreign key constraints)
    protected array $truncateOrder = [
        // Inventory movements first (references many things)
        'inventory_movements',

        // Payment records
        'payments',

        // Sales return items and returns
        'sales_return_items',
        'sales_returns',

        // Sales shipment items and shipments
        'sales_shipment_items',
        'sales_shipments',

        // Sales order items and orders
        'sales_order_items',
        'sales_orders',

        // Purchase return items and returns
        'purchase_return_items',
        'purchase_returns',

        // Purchase receiving items and receivings
        'purchase_receiving_items',
        'purchase_receivings',

        // Purchase order items and orders
        'purchase_order_items',
        'purchase_orders',

        // Legacy transactions
        'transactions_log',
        'transaction_details',
        'transactions',

        // Inventory stock
        'inventory_stock',

        // System logs (audit trail)
        'system_logs',

        // Products
        'mst_products',

        // Product dependencies
        'mst_brands',
        'mst_product_categories',

        // Clients (customers and suppliers)
        'mst_client',
        'mst_address',
        'mst_client_type',

        // Currencies and UOMs will be reseeded
        'mst_currencies',
        'mst_uom',
    ];

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('   HyperBiz Demo Data Seeder');
        $this->info('========================================');
        $this->newLine();

        // Step 1: Create mysqldump backup
        if (!$this->option('skip-backup')) {
            if (!$this->createBackup()) {
                return Command::FAILURE;
            }
        } else {
            $this->warn('Skipping database backup (--skip-backup flag used)');
        }

        if ($this->option('backup-only')) {
            $this->info('Backup completed. Exiting (--backup-only flag used)');
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->option('skip-truncate')) {
            $this->newLine();
            $this->warn('WARNING: This will DELETE all existing transaction data!');
            $this->warn('The following data will be preserved:');
            $this->info('  - Users and authentication data');
            $this->info('  - Roles and permissions');
            $this->info('  - Company settings');
            $this->newLine();

            if (!$this->confirm('Do you want to proceed?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Step 2: Truncate tables
        if (!$this->option('skip-truncate')) {
            if (!$this->truncateTables()) {
                return Command::FAILURE;
            }
        } else {
            $this->warn('Skipping table truncation (--skip-truncate flag used)');
        }

        // Step 3: Run seeders
        if (!$this->runSeeders()) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('   Demo Data Seeding Complete!');
        $this->info('========================================');

        return Command::SUCCESS;
    }

    protected function createBackup(): bool
    {
        $this->info('Step 1: Creating database backup...');

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Create backup directory
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupFile = "{$backupDir}/{$dbName}_backup_{$timestamp}.sql";

        // Build mysqldump command
        $mysqldumpPath = $this->findMysqldump();
        if (!$mysqldumpPath) {
            $this->error('mysqldump not found. Please ensure MySQL is installed and in PATH.');
            $this->warn('You can skip backup with --skip-backup flag if needed.');
            return false;
        }

        $command = sprintf(
            '"%s" -h %s -P %d -u %s %s %s > "%s"',
            $mysqldumpPath,
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            $backupFile
        );

        // Execute backup
        $this->line("  Creating backup: {$backupFile}");

        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($backupFile)) {
            $this->error('Backup failed!');
            $this->error(implode("\n", $output));
            return false;
        }

        $fileSize = $this->formatBytes(filesize($backupFile));
        $this->info("  Backup created successfully ({$fileSize})");
        $this->info("  Location: {$backupFile}");
        $this->newLine();

        return true;
    }

    protected function findMysqldump(): ?string
    {
        // Common paths for mysqldump
        $paths = [
            'mysqldump', // In PATH
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-5.7.40-winx64\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];

        // Also check common Laragon MySQL directories
        $laragonMysqlDir = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonMysqlDir)) {
            $dirs = glob($laragonMysqlDir . '\\mysql-*\\bin\\mysqldump.exe');
            foreach ($dirs as $dir) {
                $paths[] = $dir;
            }
        }

        foreach ($paths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function commandExists(string $command): bool
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // Check if it's a full path that exists
            if (file_exists($command)) {
                return true;
            }
            // Check using where command
            exec("where " . escapeshellarg($command) . " 2>nul", $output, $returnVar);
            return $returnVar === 0;
        } else {
            exec("which " . escapeshellarg($command) . " 2>/dev/null", $output, $returnVar);
            return $returnVar === 0;
        }
    }

    protected function truncateTables(): bool
    {
        $this->info('Step 2: Truncating transaction tables...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $truncatedCount = 0;
            foreach ($this->truncateOrder as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::table($table)->truncate();
                        $this->line("  Truncated: {$table} ({$count} records)");
                        $truncatedCount++;
                    }
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            if ($truncatedCount === 0) {
                $this->info('  No tables needed truncation.');
            } else {
                $this->info("  Truncated {$truncatedCount} tables.");
            }

            $this->newLine();
            return true;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Error truncating tables: ' . $e->getMessage());
            return false;
        }
    }

    protected function runSeeders(): bool
    {
        $this->info('Step 3: Running seeders...');
        $this->newLine();

        try {
            // Run CurrencySeeder first
            $this->line('  Running CurrencySeeder...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\CurrencySeeder',
                '--force' => true,
            ]);

            // Run UomSeeder
            $this->line('  Running UomSeeder...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\UomSeeder',
                '--force' => true,
            ]);

            // Run ClientTypeUpdateSeeder if exists
            $this->line('  Running ClientTypeUpdateSeeder...');
            try {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\ClientTypeUpdateSeeder',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            // Run main demo data seeder
            $this->newLine();
            $this->line('  Running DemoDataSeeder...');
            $this->newLine();

            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoDataSeeder',
                '--force' => true,
            ], $this->output);

            return true;

        } catch (\Exception $e) {
            $this->error('Error running seeders: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return false;
        }
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
