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
        Schema::create('user_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider'); // stripe, paypal, wise, etc.
            $table->string('account_id'); // provider-specific account ID
            $table->string('status')->default('pending'); // pending, active, restricted, disabled
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            // Provider-specific data
            $table->json('account_data')->nullable(); // Store provider-specific account details
            $table->json('capabilities')->nullable(); // What the account can do
            $table->json('requirements')->nullable(); // What's needed to complete setup
            $table->json('metadata')->nullable(); // Additional metadata

            // Tracking fields
            $table->string('created_by')->nullable(); // user, admin, system
            $table->timestamp('setup_completed_at')->nullable();
            $table->timestamp('last_status_check')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'provider']);
            $table->index(['provider', 'status']);
            $table->index(['user_id', 'is_primary']);
            $table->index('account_id');
            $table->index('status');

            // Constraints
            $table->unique(['user_id', 'provider', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payout_accounts');
    }
};
