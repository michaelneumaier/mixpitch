<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds communication hub fields for read receipts, delivery status,
     * urgency flags, and threading support to pitch_events table.
     */
    public function up(): void
    {
        Schema::table('pitch_events', function (Blueprint $table) {
            // Read tracking - when was the message first read
            $table->timestamp('read_at')->nullable()->after('metadata');

            // Read by tracking - JSON array of readers with timestamps
            // Format: [{"user_id": 1, "read_at": "2024-01-01T00:00:00Z", "is_client": false}]
            $table->json('read_by')->nullable()->after('read_at');

            // Delivery status: pending, delivered, read
            $table->string('delivery_status', 20)->default('delivered')->after('read_by');

            // Urgency flag for priority messages
            $table->boolean('is_urgent')->default(false)->after('delivery_status');

            // Threading support - allows grouping related messages
            $table->foreignId('thread_id')->nullable()->after('is_urgent')
                ->constrained('pitch_events')->nullOnDelete();

            // Indexes for efficient querying
            $table->index(['pitch_id', 'read_at'], 'idx_pitch_events_unread');
            $table->index(['pitch_id', 'event_type', 'created_at'], 'idx_pitch_events_messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_events', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_pitch_events_unread');
            $table->dropIndex('idx_pitch_events_messages');

            // Drop foreign key constraint
            $table->dropForeign(['thread_id']);

            // Drop columns
            $table->dropColumn([
                'read_at',
                'read_by',
                'delivery_status',
                'is_urgent',
                'thread_id',
            ]);
        });
    }
};
