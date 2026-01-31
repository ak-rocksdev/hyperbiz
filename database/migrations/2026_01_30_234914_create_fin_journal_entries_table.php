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
        Schema::create('fin_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 30)->unique();
            $table->date('entry_date');
            $table->unsignedBigInteger('fiscal_period_id')->nullable();
            $table->enum('entry_type', [
                'manual',
                'auto_sales',
                'auto_purchase',
                'auto_payment',
                'auto_expense',
                'closing',
                'opening',
                'adjustment'
            ])->default('manual');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('memo', 255)->nullable();
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->unsignedBigInteger('reversed_by_id')->nullable();
            $table->unsignedBigInteger('reverses_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('fiscal_period_id')
                ->references('id')
                ->on('fin_fiscal_periods')
                ->onDelete('set null');

            $table->foreign('reversed_by_id')
                ->references('id')
                ->on('fin_journal_entries')
                ->onDelete('set null');

            $table->foreign('reverses_id')
                ->references('id')
                ->on('fin_journal_entries')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('posted_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('voided_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index(['entry_date', 'status']);
            $table->index(['entry_type', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_journal_entries');
    }
};
