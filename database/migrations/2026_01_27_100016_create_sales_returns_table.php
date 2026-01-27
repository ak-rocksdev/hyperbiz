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
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 20)->unique(); // Auto: SR-2025-00001
            $table->unsignedBigInteger('sales_order_id')->nullable(); // Can be linked to SO or standalone
            $table->unsignedBigInteger('customer_id');
            $table->date('return_date');

            // Currency support
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);

            $table->enum('status', ['draft', 'confirmed', 'completed'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('refund_method', ['credit_note', 'cash_refund', 'replacement', 'none'])->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mst_client')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('return_date');
            $table->index('status');
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_return_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2); // Return at original selling price
            $table->decimal('unit_cost', 15, 2)->nullable(); // For COGS reversal
            $table->decimal('subtotal', 15, 2);
            $table->string('reason', 255)->nullable(); // Per-item reason
            $table->enum('condition', ['good', 'damaged', 'expired'])->default('good'); // Affects restock decision
            $table->boolean('restock')->default(true); // Whether to add back to inventory
            $table->timestamps();

            $table->foreign('sales_return_id')->references('id')->on('sales_returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('mst_products')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('mst_uom')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
