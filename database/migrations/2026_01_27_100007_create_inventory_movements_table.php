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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->timestamp('movement_date');
            $table->unsignedBigInteger('product_id');
            // For future multi-warehouse: $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->enum('movement_type', [
                'purchase_in',      // Stock in from PO receiving
                'sales_out',        // Stock out from SO shipment/delivery
                'purchase_return',  // Stock out (returned to supplier)
                'sales_return',     // Stock in (returned by customer)
                'adjustment_in',    // Manual adjustment increase
                'adjustment_out',   // Manual adjustment decrease
                'opening_stock',    // Initial stock entry
            ]);
            $table->string('reference_type', 50)->nullable(); // 'purchase_receiving', 'sales_order', 'purchase_return', 'sales_return', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the source document
            $table->decimal('quantity', 15, 3); // Positive for IN, Negative for OUT
            $table->decimal('unit_cost', 15, 2)->nullable(); // Cost at movement time (for COGS)
            $table->decimal('quantity_before', 15, 3); // Stock before movement
            $table->decimal('quantity_after', 15, 3); // Stock after movement
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for common queries
            $table->index(['product_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
