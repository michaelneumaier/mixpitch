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
        Schema::table('transactions', function (Blueprint $table) {
            // Add producer details for payout tracking
            $table->foreignId('producer_user_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            $table->string('producer_stripe_account_id')->nullable()->after('producer_user_id');

            // Add workflow context
            $table->string('workflow_type', 50)->nullable()->after('user_subscription_tier');

            // Add payout schedule relationship
            $table->foreignId('payout_schedule_id')->nullable()->after('pitch_id')->constrained()->onDelete('set null');

            // Add indexes for better performance
            $table->index('producer_user_id');
            $table->index('workflow_type');
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['producer_user_id']);
            $table->dropForeign(['payout_schedule_id']);
            $table->dropIndex(['producer_user_id']);
            $table->dropIndex(['workflow_type']);
            $table->dropIndex(['status', 'type']);

            $table->dropColumn([
                'producer_user_id',
                'producer_stripe_account_id',
                'workflow_type',
                'payout_schedule_id',
            ]);
        });
    }
};
