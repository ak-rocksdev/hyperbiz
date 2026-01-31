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
        Schema::create('fin_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_account_id')->constrained('fin_chart_of_accounts')->onDelete('restrict');
            $table->string('bank_name', 100);
            $table->string('account_name', 100);
            $table->string('account_number', 50);
            $table->string('currency_code', 3)->default('IDR');
            $table->string('swift_code', 20)->nullable();
            $table->string('branch', 100)->nullable();
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->decimal('last_reconciled_balance', 20, 2)->nullable();
            $table->date('last_reconciled_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('bank_name');
            $table->index('is_active');
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_bank_accounts');
    }
};
