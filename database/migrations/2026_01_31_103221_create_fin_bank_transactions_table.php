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
        Schema::create('fin_bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('fin_bank_accounts')->onDelete('cascade');
            $table->date('transaction_date');
            $table->string('reference', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'fee', 'interest', 'adjustment'])->default('deposit');
            $table->decimal('amount', 20, 2);
            $table->decimal('running_balance', 20, 2)->default(0);
            $table->string('source_type', 50)->nullable(); // payment, receipt, expense, transfer, manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('fin_journal_entries')->onDelete('set null');
            $table->enum('reconciliation_status', ['unreconciled', 'matched', 'reconciled', 'cleared'])->default('unreconciled');
            $table->foreignId('reconciliation_id')->nullable()->constrained('fin_bank_reconciliations')->onDelete('set null');
            $table->string('payee', 100)->nullable();
            $table->string('check_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index('reconciliation_status');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_bank_transactions');
    }
};
