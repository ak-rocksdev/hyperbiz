<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Transaction tables that need company_id for multi-tenancy.
     */
    private array $tables = [
        'purchase_orders',
        'purchase_order_items',
        'purchase_receivings',
        'purchase_receiving_items',
        'purchase_returns',
        'purchase_return_items',
        'sales_orders',
        'sales_order_items',
        'sales_shipments',
        'sales_shipment_items',
        'sales_returns',
        'sales_return_items',
        'payments',
        'transactions',
        'transaction_details',
        'transactions_log',
        'inventory_stock',
        'inventory_movements',
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

        // Update unique constraints: po_number/so_number must be unique per company
        // purchase_orders uses po_number, sales_orders uses so_number
        $orderTables = [
            'purchase_orders' => ['old_index' => 'purchase_orders_po_number_unique', 'column' => 'po_number'],
            'sales_orders' => ['old_index' => 'sales_orders_so_number_unique', 'column' => 'so_number'],
        ];

        foreach ($orderTables as $tableName => $config) {
            if (Schema::hasTable($tableName)) {
                $this->dropIndexIfExists($tableName, $config['old_index']);

                $column = $config['column'];
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
                    $table->unique(['company_id', $column], $tableName . '_company_' . $column . '_unique');
                });
            }
        }

        // payments: payment_number unique per company
        if (Schema::hasTable('payments')) {
            $this->dropIndexIfExists('payments', 'payments_payment_number_unique');

            Schema::table('payments', function (Blueprint $table) {
                $table->unique(['company_id', 'payment_number'], 'payments_company_payment_number_unique');
            });
        }

        // inventory_stock: product unique per company
        // Must handle FK constraint: drop FK first, drop unique, add regular index, add compound unique, re-add FK
        if (Schema::hasTable('inventory_stock')) {
            // Step 1: Drop the foreign key that uses product_id
            $this->dropForeignIfExists('inventory_stock', 'inventory_stock_product_id_foreign');

            // Step 2: Drop the unique constraint
            $this->dropIndexIfExists('inventory_stock', 'inventory_stock_product_id_unique');

            // Step 3: Add a regular index on product_id (for FK to use)
            Schema::table('inventory_stock', function (Blueprint $table) {
                $table->index('product_id', 'inventory_stock_product_id_index');
            });

            // Step 4: Add compound unique constraint
            Schema::table('inventory_stock', function (Blueprint $table) {
                $table->unique(['company_id', 'product_id'], 'inventory_stock_company_product_unique');
            });

            // Step 5: Re-add the foreign key
            Schema::table('inventory_stock', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // Restore original unique constraints for inventory_stock
        if (Schema::hasTable('inventory_stock') && Schema::hasColumn('inventory_stock', 'company_id')) {
            // Step 1: Drop the foreign key
            $this->dropForeignIfExists('inventory_stock', 'inventory_stock_product_id_foreign');

            // Step 2: Drop the compound unique constraint
            $this->dropIndexIfExists('inventory_stock', 'inventory_stock_company_product_unique');

            // Step 3: Drop the regular index we added
            $this->dropRegularIndexIfExists('inventory_stock', 'inventory_stock_product_id_index');

            // Step 4: Restore original unique constraint
            Schema::table('inventory_stock', function (Blueprint $table) {
                $table->unique('product_id', 'inventory_stock_product_id_unique');
            });

            // Step 5: Re-add the foreign key (the unique constraint serves as the index)
            Schema::table('inventory_stock', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'company_id')) {
            $this->dropIndexIfExists('payments', 'payments_company_payment_number_unique');

            Schema::table('payments', function (Blueprint $table) {
                $table->unique('payment_number', 'payments_payment_number_unique');
            });
        }

        $orderTables = [
            'sales_orders' => 'so_number',
            'purchase_orders' => 'po_number',
        ];
        foreach ($orderTables as $tableName => $column) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                $this->dropIndexIfExists($tableName, $tableName . '_company_' . $column . '_unique');

                Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
                    $table->unique($column, $tableName . '_' . $column . '_unique');
                });
            }
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

    /**
     * Drop a foreign key if it exists (Laravel 11 compatible).
     */
    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        $foreignKeys = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table]
        ))->pluck('CONSTRAINT_NAME')->toArray();

        if (in_array($foreignName, $foreignKeys)) {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        }
    }

    /**
     * Drop a regular index if it exists (Laravel 11 compatible).
     */
    private function dropRegularIndexIfExists(string $table, string $indexName): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))->pluck('Key_name')->unique()->toArray();

        if (in_array($indexName, $indexes)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
