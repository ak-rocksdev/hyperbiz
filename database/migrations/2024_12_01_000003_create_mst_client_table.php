<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstClientTable extends Migration
{
    public function up()
    {
        Schema::create('mst_client', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->unsignedBigInteger('mst_address_id')->nullable();
            $table->string('client_phone_number');
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_phone_number')->nullable();
            $table->unsignedBigInteger('mst_client_type_id');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('mst_address_id')->references('id')->on('mst_address')->onDelete('cascade');
            $table->foreign('mst_client_type_id')->references('id')->on('mst_client_type')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_client');
    }
}
