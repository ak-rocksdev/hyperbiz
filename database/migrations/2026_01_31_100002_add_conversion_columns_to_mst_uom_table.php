<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds category, base unit reference, and conversion factor to UoM table.
     * This enables standard/global unit conversions (e.g., 1 KG = 1000 G).
     */
    public function up(): void
    {
        Schema::table('mst_uom', function (Blueprint $table) {
            // Category grouping (Weight, Volume, Length, Count, Packaging)
            $table->unsignedBigInteger('category_id')->nullable()->after('description');

            // Reference to base unit within same category (e.g., G -> KG, ML -> LTR)
            // NULL means this IS the base unit for its category
            $table->unsignedBigInteger('base_uom_id')->nullable()->after('category_id');

            // Standard conversion factor to base unit
            // Example: Gram has factor 0.001 (to convert to KG)
            // Example: Ton has factor 1000 (to convert to KG)
            // NULL or 1 means this IS the base unit
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('base_uom_id');

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('mst_uom_categories')->nullOnDelete();
            $table->foreign('base_uom_id')->references('id')->on('mst_uom')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_uom', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['base_uom_id']);
            $table->dropColumn(['category_id', 'base_uom_id', 'conversion_factor']);
        });
    }
};
