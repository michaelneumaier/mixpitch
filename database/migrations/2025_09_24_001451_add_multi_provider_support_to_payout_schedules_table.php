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
        Schema::table('payout_schedules', function (Blueprint $table) {
            // Add provider-specific fields
            $table->string('payout_provider')->default('stripe')->after('workflow_type');
            $table->string('provider_account_id')->nullable()->after('producer_stripe_account_id');
            $table->string('provider_transfer_id')->nullable()->after('stripe_transfer_id');
            $table->json('provider_metadata')->nullable()->after('metadata');

            // Add provider fees tracking
            $table->decimal('provider_fee_percentage', 5, 4)->default(0)->after('commission_amount');
            $table->decimal('provider_fee_fixed', 10, 2)->default(0)->after('provider_fee_percentage');
            $table->decimal('provider_fee_total', 10, 2)->default(0)->after('provider_fee_fixed');

            // Add indexes for performance
            $table->index('payout_provider');
            $table->index('provider_account_id');
            $table->index('provider_transfer_id');

            // Composite indexes for common queries
            $table->index(['payout_provider', 'status']);
            $table->index(['producer_user_id', 'payout_provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payout_schedules', function (Blueprint $table) {
            $table->dropIndex(['producer_user_id', 'payout_provider']);
            $table->dropIndex(['payout_provider', 'status']);
            $table->dropIndex(['provider_transfer_id']);
            $table->dropIndex(['provider_account_id']);
            $table->dropIndex(['payout_provider']);

            $table->dropColumn([
                'payout_provider',
                'provider_account_id',
                'provider_transfer_id',
                'provider_metadata',
                'provider_fee_percentage',
                'provider_fee_fixed',
                'provider_fee_total',
            ]);
        });
    }
};
