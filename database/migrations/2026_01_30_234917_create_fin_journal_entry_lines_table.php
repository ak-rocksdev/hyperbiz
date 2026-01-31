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
        Schema::create('fin_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->integer('line_number')->default(1);
            $table->string('description', 255)->nullable();
            $table->decimal('debit_amount', 18, 2)->default(0);
            $table->decimal('credit_amount', 18, 2)->default(0);
            $table->decimal('debit_amount_base', 18, 2)->default(0);
            $table->decimal('credit_amount_base', 18, 2)->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('fin_journal_entries')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')
                ->on('fin_chart_of_accounts')
                ->onDelete('restrict');

            $table->foreign('customer_id')
                ->references('id')
                ->on('mst_client')
                ->onDelete('set null');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('mst_client')
                ->onDelete('set null');

            $table->foreign('product_id')
                ->references('id')
                ->on('mst_products')
                ->onDelete('set null');

            $table->foreign('expense_id')
                ->references('id')
                ->on('fin_expenses')
                ->onDelete('set null');

            // Indexes
            $table->index(['journal_entry_id', 'line_number']);
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_journal_entry_lines');
    }
};
