<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_company', function (Blueprint $table) {
            $table->string('subscription_status')->default('trial')->after('logo');
            $table->unsignedBigInteger('subscription_plan_id')->nullable()->after('subscription_status');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('subscription_starts_at')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_starts_at');
            $table->string('billing_cycle')->nullable()->after('subscription_ends_at');
            $table->unsignedInteger('max_users')->nullable()->after('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('mst_company', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'subscription_plan_id',
                'trial_ends_at',
                'subscription_starts_at',
                'subscription_ends_at',
                'billing_cycle',
                'max_users',
            ]);
        });
    }
};
