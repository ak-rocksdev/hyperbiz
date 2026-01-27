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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id')->nullable(); // Unit of measure at order time
            $table->decimal('quantity', 15, 3); // Support fractional (kg, liter)
            $table->decimal('quantity_received', 15, 3)->default(0); // For partial receiving
            $table->decimal('unit_cost', 15, 2); // Cost at purchase time
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2); // (quantity * unit_cost) - discount
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
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
        Schema::dropIfExists('purchase_order_items');
    }
};
