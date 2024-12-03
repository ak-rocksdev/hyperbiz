<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstClientTypeTable extends Migration
{
    public function up()
    {
        Schema::create('mst_client_type', function (Blueprint $table) {
            $table->id();
            $table->string('client_type'); // e.g. 'Importir', 'Distibutor', 'Retailer', 'Wholesaler', 'Agent', 'Supplier', 'Manufacturer', etc.
            $table->timestamps();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mst_client_type');
    }
}
