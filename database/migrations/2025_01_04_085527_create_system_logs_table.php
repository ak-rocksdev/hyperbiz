<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable(); // Fully qualified model class name
            $table->unsignedBigInteger('model_id')->nullable(); // Primary key of the model
            $table->unsignedBigInteger('user_id')->nullable(); // ID of the user who performed the action
            $table->string('action'); // Action performed: created, updated, deleted
            $table->json('changed_fields')->nullable(); // Stores the changes in JSON
            $table->ipAddress('ip_address')->nullable(); // IP address of the request
            $table->text('user_agent')->nullable(); // User agent string
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
