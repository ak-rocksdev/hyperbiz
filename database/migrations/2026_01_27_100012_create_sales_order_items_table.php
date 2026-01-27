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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_shipped', 15, 3)->default(0); // For partial shipping
            $table->decimal('unit_price', 15, 2); // Selling price
            $table->decimal('unit_cost', 15, 2)->nullable(); // COGS (calculated based on costing method)
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('mst_uom')->onDelete('set null');

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
