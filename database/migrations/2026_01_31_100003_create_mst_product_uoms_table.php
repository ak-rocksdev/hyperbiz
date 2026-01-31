<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product-specific UoM configuration table.
     * Allows products to have multiple units of measure with custom conversion factors.
     *
     * Example for "Kedelai Premium" (Soybeans):
     * - KG (base): factor=1, purchase=yes, sales=yes
     * - Karung 25kg: factor=25, purchase=yes, sales=yes
     * - Karung 50kg: factor=50, purchase=yes, sales=no
     * - Ton: factor=1000, purchase=yes, sales=no
     */
    public function up(): void
    {
        Schema::create('mst_product_uoms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');

            // Flags
            $table->boolean('is_base_uom')->default(false);      // TRUE = inventory tracking unit
            $table->boolean('is_purchase_uom')->default(true);   // Can use for purchasing
            $table->boolean('is_sales_uom')->default(true);      // Can use for selling
            $table->boolean('is_default_purchase')->default(false); // Default UoM for new PO lines
            $table->boolean('is_default_sales')->default(false);    // Default UoM for new SO lines

            // Product-specific conversion factor to BASE unit
            // Example: If base=KG, and this is "Karung 50kg", factor=50
            $table->decimal('conversion_factor', 18, 6)->default(1);

            // Optional: different default price for this UoM (for quick entry)
            // Example: Price per Karung might be pre-set
            $table->decimal('default_purchase_price', 18, 2)->nullable();
            $table->decimal('default_sales_price', 18, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('mst_products')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('mst_uom')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Unique constraint: each product can only have each UoM once
            $table->unique(['product_id', 'uom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_product_uoms');
    }
};
