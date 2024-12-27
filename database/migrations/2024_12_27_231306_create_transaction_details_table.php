<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('mst_product_id');
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->timestamps();

            // Foreign keys
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('mst_product_id')->references('id')->on('mst_products')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_details');
    }
}

