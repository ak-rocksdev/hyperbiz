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
        Schema::create('transactions_log', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null'); // FK to transactions
            $table->enum('action', ['create', 'read', 'update', 'delete']); // Action type
            $table->json('changed_fields')->nullable(); // JSON of changed fields
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK to users
            $table->string('actor_role', 50)->nullable(); // Nullable role for flexibility
            $table->string('ip_address', 45)->nullable(); // User's IP address
            $table->text('user_agent')->nullable(); // User's agent details
            $table->timestamp('action_timestamp')->useCurrent(); // Action timestamp
            $table->timestamps(); // Created at and updated at
            $table->text('remarks')->nullable(); // Additional remarks
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions_log');
    }
};
