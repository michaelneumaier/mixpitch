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
        Schema::create('payout_schedules', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('pitch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('contest_prize_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('producer_user_id')->constrained('users')->onDelete('cascade'); // Winner/recipient
            $table->string('producer_stripe_account_id')->nullable(); // Winner's Stripe Connect account

            // Payout details
            $table->decimal('gross_amount', 10, 2); // Original prize amount
            $table->decimal('commission_rate', 4, 2); // Platform commission rate
            $table->decimal('commission_amount', 10, 2); // Platform commission
            $table->decimal('net_amount', 10, 2); // Amount paid to winner
            $table->string('currency', 3)->default('USD');

            // Scheduling
            $table->timestamp('hold_release_date'); // When payout can be processed
            $table->enum('status', ['scheduled', 'processing', 'completed', 'disputed', 'cancelled', 'failed', 'reversed'])->default('scheduled');

            // Processing timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            // Stripe integration
            $table->string('stripe_payment_intent_id')->nullable(); // Original payment
            $table->string('stripe_transfer_id')->nullable(); // Payout transfer
            $table->string('stripe_reversal_id')->nullable(); // If reversed
            $table->text('failure_reason')->nullable(); // If failed

            // Workflow context
            $table->string('workflow_type', 50)->nullable(); // standard, contest, client_management
            $table->string('subscription_plan', 20)->nullable(); // Plan at time of payout
            $table->string('subscription_tier', 20)->nullable(); // Tier at time of payout

            // Metadata
            $table->json('metadata')->nullable(); // Additional context

            $table->timestamps();

            // Indexes
            $table->index(['status', 'hold_release_date']);
            $table->index('producer_user_id');
            $table->index('workflow_type');
            $table->index('project_id');

            // Note: Check constraint for pitch_id/contest_prize_id exclusivity
            // will be enforced at the application level in the model
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_schedules');
    }
};
