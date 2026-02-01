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
        Schema::create('fin_payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained('fin_payment_transactions')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('mst_company')->cascadeOnDelete();

            // File information
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size')->comment('Size in KB');
            $table->string('file_type', 10)->comment('jpg, png, pdf');

            // Bank transfer details
            $table->string('bank_name', 100);
            $table->string('account_name', 150);
            $table->string('account_number', 50);
            $table->date('transfer_date');
            $table->decimal('transfer_amount', 15, 2);
            $table->text('notes')->nullable()->comment('Tenant notes');

            // Verification status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_payment_proofs');
    }
};
