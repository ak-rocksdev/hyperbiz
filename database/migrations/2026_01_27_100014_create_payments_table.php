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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 20)->unique(); // Auto: PAY-2025-00001
            $table->enum('payment_type', ['purchase', 'sales']); // Which module
            $table->string('reference_type', 50); // 'purchase_order', 'sales_order'
            $table->unsignedBigInteger('reference_id'); // PO or SO ID
            $table->date('payment_date');

            // Currency support
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_base', 15, 2)->nullable(); // Converted to base currency

            // Payment method details
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'credit_card',
                'debit_card',
                'cheque',
                'giro',
                'e_wallet',
                'other'
            ])->default('cash');
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('reference_number', 100)->nullable(); // Check number, transfer ref, etc.

            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['reference_type', 'reference_id']);
            $table->index('payment_date');
            $table->index('payment_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
