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
        Schema::create('fin_account_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('fiscal_period_id');
            $table->decimal('opening_debit', 18, 2)->default(0);
            $table->decimal('opening_credit', 18, 2)->default(0);
            $table->decimal('period_debit', 18, 2)->default(0);
            $table->decimal('period_credit', 18, 2)->default(0);
            $table->decimal('closing_debit', 18, 2)->default(0);
            $table->decimal('closing_credit', 18, 2)->default(0);
            $table->decimal('net_balance', 18, 2)->default(0);
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('fin_chart_of_accounts')
                ->onDelete('cascade');

            $table->foreign('fiscal_period_id')
                ->references('id')
                ->on('fin_fiscal_periods')
                ->onDelete('cascade');

            // Unique constraint: one record per account per period
            $table->unique(['account_id', 'fiscal_period_id'], 'account_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_account_balances');
    }
};
