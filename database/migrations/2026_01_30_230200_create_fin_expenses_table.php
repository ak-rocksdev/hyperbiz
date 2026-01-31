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
        Schema::create('fin_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 30)->unique();
            $table->date('expense_date');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('paid_from_account_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('payee_name', 100)->nullable();
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('amount', 18, 2);
            $table->decimal('amount_in_base', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'check', 'other'])->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->enum('status', ['draft', 'approved', 'posted', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('mst_expense_categories')
                ->onDelete('restrict');

            $table->foreign('account_id')
                ->references('id')
                ->on('fin_chart_of_accounts')
                ->onDelete('restrict');

            $table->foreign('paid_from_account_id')
                ->references('id')
                ->on('fin_chart_of_accounts')
                ->onDelete('set null');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('mst_client')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Journal entry will be added later when journal table exists
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_expenses');
    }
};
