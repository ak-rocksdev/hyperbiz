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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 20)->unique(); // Auto: PO-2025-00001
            $table->unsignedBigInteger('supplier_id'); // FK to mst_client (where type.can_purchase = true)
            $table->date('order_date');
            $table->date('expected_date')->nullable();

            // Status tracking
            $table->enum('status', ['draft', 'confirmed', 'partial', 'received', 'cancelled'])->default('draft');

            // Currency support
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Rate at transaction time

            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0); // Calculated

            // Tax (optional)
            $table->boolean('tax_enabled')->default(false);
            $table->string('tax_name', 50)->nullable(); // 'PPN', 'VAT', etc.
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('grand_total', 15, 2)->default(0);

            // Payment tracking
            $table->string('payment_terms', 50)->nullable(); // 'Net 30', 'COD', etc.
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('amount_paid', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('supplier_id')->references('id')->on('mst_client')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('order_date');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
