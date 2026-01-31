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
        Schema::create('fin_supplier_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('currency_code', 3)->default('IDR');

            // Totals
            $table->decimal('total_purchases', 18, 2)->default(0);
            $table->decimal('total_payments', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);

            // Aging buckets
            $table->decimal('current_0_30', 18, 2)->default(0);
            $table->decimal('current_31_60', 18, 2)->default(0);
            $table->decimal('current_61_90', 18, 2)->default(0);
            $table->decimal('current_over_90', 18, 2)->default(0);

            // Statistics
            $table->date('last_purchase_date')->nullable();
            $table->date('last_payment_date')->nullable();

            $table->timestamps();

            // Foreign key to mst_client
            $table->foreign('supplier_id')
                ->references('id')
                ->on('mst_client')
                ->onDelete('cascade');

            // Unique constraint
            $table->unique(['supplier_id', 'currency_code'], 'supplier_currency_unique');

            // Index
            $table->index('current_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_supplier_balances');
    }
};
