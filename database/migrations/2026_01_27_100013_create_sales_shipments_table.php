<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: Sales shipments are OPTIONAL. Sales orders can be completed
     * without creating shipment records (simple status change).
     * This table is for businesses that need detailed shipment tracking.
     */
    public function up(): void
    {
        Schema::create('sales_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 20)->unique(); // Auto: SHP-2025-00001
            $table->unsignedBigInteger('sales_order_id');
            $table->date('shipment_date');
            $table->string('courier', 100)->nullable(); // 'JNE', 'J&T', 'SiCepat', etc.
            $table->string('tracking_number', 100)->nullable();
            $table->enum('status', ['draft', 'shipped', 'in_transit', 'delivered'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('shipment_date');
            $table->index('status');
        });

        Schema::create('sales_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_shipment_id');
            $table->unsignedBigInteger('sales_order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_shipped', 15, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_shipment_id')->references('id')->on('sales_shipments')->onDelete('cascade');
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_shipment_items');
        Schema::dropIfExists('sales_shipments');
    }
};
