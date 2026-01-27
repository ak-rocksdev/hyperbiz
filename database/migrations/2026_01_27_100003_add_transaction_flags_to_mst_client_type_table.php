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
        Schema::table('mst_client_type', function (Blueprint $table) {
            $table->boolean('can_purchase')->default(false)->after('client_type');
            $table->boolean('can_sell')->default(false)->after('can_purchase');
            $table->text('description')->nullable()->after('can_sell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_client_type', function (Blueprint $table) {
            $table->dropColumn(['can_purchase', 'can_sell', 'description']);
        });
    }
};
