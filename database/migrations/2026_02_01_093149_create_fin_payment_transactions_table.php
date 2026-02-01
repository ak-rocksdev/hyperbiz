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
        Schema::create('fin_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('mst_company')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('fin_invoices')->cascadeOnDelete();
            $table->string('transaction_id', 100)->nullable()->comment('Gateway transaction ID or manual reference');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('payment_method', ['stripe', 'bank_transfer']);
            $table->enum('status', ['pending', 'awaiting_verification', 'success', 'failed', 'expired', 'rejected'])->default('pending');
            $table->json('gateway_response')->nullable()->comment('Stripe response data');
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_payment_transactions');
    }
};
