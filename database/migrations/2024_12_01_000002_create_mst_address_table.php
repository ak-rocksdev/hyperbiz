<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstAddressTable extends Migration
{
    public function up()
    {
        Schema::create('mst_address', function (Blueprint $table) {
            $table->id();
            $table->string('address');
            $table->string('city_id')->nullable();
            $table->string('city_name')->nullable();
            $table->string('state_name')->nullable();
            $table->string('state_id')->nullable();
            $table->string('country_id');
            $table->string('country_name');
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_address');
    }
}
