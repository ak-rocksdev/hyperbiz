<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Master tables that need company_id for multi-tenancy.
     */
    private array $tables = [
        'mst_products',
        'mst_client',
        'mst_product_categories',
        'mst_brands',
        'mst_expense_categories',
        'mst_uom_categories',
        'mst_uom',
        'mst_product_uoms',
        'mst_client_type',
        'mst_address',
        'mst_currencies',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('mst_company')->nullOnDelete();
                    $table->index('company_id');
                });
            }
        }

        // Update unique constraints to include company_id
        // mst_products: sku must be unique per company
        if (Schema::hasTable('mst_products')) {
            // Drop existing unique if exists using raw SQL (Laravel 11 compatible)
            $this->dropIndexIfExists('mst_products', 'mst_products_sku_unique');

            Schema::table('mst_products', function (Blueprint $table) {
                $table->unique(['company_id', 'sku'], 'mst_products_company_sku_unique');
            });
        }

        // mst_currencies: code must be unique per company
        if (Schema::hasTable('mst_currencies')) {
            $this->dropIndexIfExists('mst_currencies', 'mst_currencies_code_unique');

            Schema::table('mst_currencies', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'mst_currencies_company_code_unique');
            });
        }
    }

    public function down(): void
    {
        // Restore original unique constraints
        if (Schema::hasTable('mst_products') && Schema::hasColumn('mst_products', 'company_id')) {
            $this->dropIndexIfExists('mst_products', 'mst_products_company_sku_unique');

            Schema::table('mst_products', function (Blueprint $table) {
                $table->unique('sku', 'mst_products_sku_unique');
            });
        }

        if (Schema::hasTable('mst_currencies') && Schema::hasColumn('mst_currencies', 'company_id')) {
            $this->dropIndexIfExists('mst_currencies', 'mst_currencies_company_code_unique');

            Schema::table('mst_currencies', function (Blueprint $table) {
                $table->unique('code', 'mst_currencies_code_unique');
            });
        }

        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }

    /**
     * Drop an index if it exists (Laravel 11 compatible).
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))->pluck('Key_name')->unique()->toArray();

        if (in_array($indexName, $indexes)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }
};
