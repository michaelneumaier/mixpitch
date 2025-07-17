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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('pitch_id')->constrained()->onDelete('cascade');
            $table->foreignId('payout_schedule_id')->constrained()->onDelete('cascade');

            // Request details
            $table->string('requested_by_email');
            $table->text('reason');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['requested', 'approved', 'denied', 'processed', 'expired'])->default('requested');

            // Response details
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('denied_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('denied_at')->nullable();
            $table->text('denial_reason')->nullable();

            // Processing details
            $table->string('stripe_refund_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();

            // Indexes
            $table->index(['status', 'expires_at']);
            $table->index('requested_by_email');
            $table->index('pitch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
