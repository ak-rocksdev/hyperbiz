<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds UoM conversion tracking to PO/SO items.
     * This allows transactions to use any UoM while tracking the base quantity for inventory.
     *
     * Example:
     * - User orders: 10 Karung 50kg
     * - Stored as: quantity=10, uom_conversion_factor=50, base_quantity=500
     * - Inventory effect: 500 KG
     */
    public function up(): void
    {
        // Purchase Order Items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Conversion factor at time of order (snapshot for audit trail)
            // 1 = same as base unit, 50 = 1 unit equals 50 base units
            $table->decimal('uom_conversion_factor', 18, 6)->default(1)->after('uom_id');

            // Quantity converted to base unit (for inventory calculations)
            // base_quantity = quantity * uom_conversion_factor
            $table->decimal('base_quantity', 15, 3)->nullable()->after('quantity');

            // Track which UoM is the base for this product (snapshot)
            $table->unsignedBigInteger('base_uom_id')->nullable()->after('uom_conversion_factor');

            $table->foreign('base_uom_id')->references('id')->on('mst_uom')->nullOnDelete();
        });

        // Sales Order Items
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->decimal('uom_conversion_factor', 18, 6)->default(1)->after('uom_id');
            $table->decimal('base_quantity', 15, 3)->nullable()->after('quantity');
            $table->unsignedBigInteger('base_uom_id')->nullable()->after('uom_conversion_factor');

            $table->foreign('base_uom_id')->references('id')->on('mst_uom')->nullOnDelete();
        });

        // Update existing records: set base_quantity = quantity (since conversion_factor defaults to 1)
        \DB::statement('UPDATE purchase_order_items SET base_quantity = quantity WHERE base_quantity IS NULL');
        \DB::statement('UPDATE sales_order_items SET base_quantity = quantity WHERE base_quantity IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['base_uom_id']);
            $table->dropColumn(['uom_conversion_factor', 'base_quantity', 'base_uom_id']);
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['base_uom_id']);
            $table->dropColumn(['uom_conversion_factor', 'base_quantity', 'base_uom_id']);
        });
    }
};
