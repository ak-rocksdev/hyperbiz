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
        Schema::create('mst_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 50)->unique()->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->unsignedBigInteger('mst_product_category_id');
            $table->unsignedBigInteger('mst_brand_id')->nullable();
            $table->unsignedBigInteger('mst_client_id')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->text('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by');

            $table->foreign('mst_product_category_id')->references('id')->on('mst_product_categories')->onDelete('cascade');
            $table->foreign('mst_brand_id')->references('id')->on('mst_brands')->onDelete('set null');
            $table->foreign('mst_client_id')->references('id')->on('mst_client')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_products');
    }
};
