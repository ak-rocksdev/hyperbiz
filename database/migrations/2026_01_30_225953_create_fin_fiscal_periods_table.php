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
        Schema::create('fin_fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fiscal_year_id');
            $table->string('name', 50); // e.g., 'January 2026'
            $table->unsignedTinyInteger('period_number'); // 1-12 (or 1-13 for 13-period)
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'adjusting', 'locked'])->default('open');
            $table->boolean('is_adjusting_period')->default(false); // Year-end adjustments
            $table->timestamps();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('fiscal_year_id')
                ->references('id')
                ->on('fin_fiscal_years')
                ->onDelete('cascade');
            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(['fiscal_year_id', 'period_number']);
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fin_fiscal_periods');
    }
};
