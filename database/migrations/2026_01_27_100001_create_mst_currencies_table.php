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
        Schema::create('mst_currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique(); // ISO 4217: 'IDR', 'USD', 'SGD'
            $table->string('name', 100); // 'Indonesian Rupiah', 'US Dollar'
            $table->string('symbol', 10); // 'Rp', '$', 'S$'
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Rate to base currency
            $table->boolean('is_base')->default(false); // Base currency for conversion
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_currencies');
    }
};
