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
            // create field to generate transaction code six digit contain random number and random alphabet (ex: 123ABC)
            $table->string('transaction_code', 6)->unique(); // Transaction code
            $table->unsignedBigInteger('mst_client_id'); // Foreign key to mst_clients
            $table->decimal('grand_total', 15, 2); // Grand total of the transaction
            $table->decimal('expedition_fee', 15, 2)->nullable(); // Expedition fee
            $table->timestamp('transaction_date')->nullable(); // Transaction date and time
            $table->string('status')->nullable(); // Status
            $table->timestamps(); // Created at and updated at
            $table->unsignedBigInteger('created_by')->nullable(); // User who created the record
            $table->unsignedBigInteger('updated_by')->nullable(); // User who updated the record
        
            // Define foreign key relationships
            $table->foreign('mst_client_id')->references('id')->on('mst_client')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null'); // User reference
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null'); // User reference
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
