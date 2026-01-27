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
        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->decimal('reorder_level', 15, 3)->default(0)->after('quantity_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });
    }
};
