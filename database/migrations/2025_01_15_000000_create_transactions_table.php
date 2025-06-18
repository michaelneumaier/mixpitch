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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // User and project relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('pitch_id')->nullable()->constrained()->onDelete('set null');
            
            // Transaction details
            $table->decimal('amount', 10, 2); // Total transaction amount
            $table->decimal('commission_rate', 4, 2); // Commission rate at time of transaction
            $table->decimal('commission_amount', 10, 2); // Calculated commission amount
            $table->decimal('net_amount', 10, 2); // Amount after commission
            
            // Transaction metadata
            $table->enum('type', ['payment', 'refund', 'adjustment', 'bonus'])->default('payment');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->string('external_transaction_id')->nullable(); // Stripe payment intent ID, etc.
            
            // User subscription context at time of transaction
            $table->string('user_subscription_plan', 20); // free, pro
            $table->string('user_subscription_tier', 20); // basic, artist, engineer
            
            // Additional metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store additional context
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'type']);
            $table->index(['created_at', 'status']);
            $table->index('external_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
