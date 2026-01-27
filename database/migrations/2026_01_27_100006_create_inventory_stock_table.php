<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            // For future multi-warehouse support, add: warehouse_id
            // $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('quantity_on_hand', 15, 3)->default(0); // Physical stock
            $table->decimal('quantity_reserved', 15, 3)->default(0); // Reserved for confirmed SO
            $table->decimal('quantity_available', 15, 3)->default(0); // Computed: on_hand - reserved
            $table->decimal('last_cost', 15, 2)->nullable(); // Last purchase cost
            $table->decimal('average_cost', 15, 2)->nullable(); // Weighted average cost
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('cascade');
            $table->unique('product_id'); // One record per product (single warehouse)
            // For multi-warehouse: $table->unique(['product_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
};
