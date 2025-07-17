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
        Schema::create('stripe_transactions', function (Blueprint $table) {
            $table->id();

            // Stripe identifiers
            $table->string('stripe_transaction_id')->unique();
            $table->string('type'); // payment_intent, transfer, refund, payout, invoice, subscription
            $table->string('status'); // pending, succeeded, failed, canceled, requires_action, processing

            // Financial details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('fee_amount', 10, 2)->nullable();

            // Related records
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pitch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payout_schedule_id')->nullable()->constrained()->onDelete('set null');

            // Stripe metadata
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('payment_method_id')->nullable();

            // Additional information
            $table->text('description')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('stripe_metadata')->nullable();

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'payout_schedule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_transactions');
    }
};
