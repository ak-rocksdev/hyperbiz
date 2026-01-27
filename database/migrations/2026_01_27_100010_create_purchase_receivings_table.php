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
        Schema::create('purchase_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number', 20)->unique(); // Auto: RCV-2025-00001
            $table->unsignedBigInteger('purchase_order_id');
            $table->date('receiving_date');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('receiving_date');
        });

        Schema::create('purchase_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_receiving_id');
            $table->unsignedBigInteger('purchase_order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_received', 15, 3);
            $table->decimal('unit_cost', 15, 2); // May differ if negotiated
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_receiving_id')->references('id')->on('purchase_receivings')->onDelete('cascade');
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receiving_items');
        Schema::dropIfExists('purchase_receivings');
    }
};
