<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('mst_client_id'); // Foreign key to mst_clients
            $table->unsignedBigInteger('mst_product_id'); // Foreign key to mst_products
            $table->decimal('value', 10, 2); // Transaction value
            $table->timestamp('transaction_date'); // Transaction date and time
            $table->enum('status', ['pending', 'completed', 'cancelled']); // Enum values in quotes
            $table->timestamps(); // Created at and updated at
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Define foreign key relationships
            $table->foreign('mst_client_id')->references('id')->on('mst_client')->onDelete('cascade');
            // $table->foreign('mst_product_id')->references('id')->on('mst_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transactions');
    }
};
