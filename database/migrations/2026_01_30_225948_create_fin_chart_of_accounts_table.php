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
        Schema::create('fin_chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('account_name', 100);
            $table->enum('account_type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'cogs',
                'expense',
                'other_income',
                'other_expense',
            ]);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedTinyInteger('level')->default(1); // 1=Category, 2=Group, 3=Detail, 4=Sub-detail
            $table->boolean('is_header')->default(false); // Header accounts (non-postable)
            $table->boolean('is_bank_account')->default(false); // For bank reconciliation
            $table->boolean('is_system')->default(false); // System-created, non-deletable
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('parent_id')
                ->references('id')
                ->on('fin_chart_of_accounts')
                ->onDelete('restrict');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('account_type');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_chart_of_accounts');
    }
};
