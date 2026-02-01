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
        Schema::create('fin_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('mst_company')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->enum('status', ['pending', 'awaiting_verification', 'paid', 'failed', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date');
            $table->enum('payment_method', ['stripe', 'bank_transfer'])->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_invoices');
    }
};
