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
        Schema::create('notification_channel_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type')->index(); // e.g., 'pitch_submitted'
            $table->string('channel')->index(); // e.g., 'database', 'email'
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Unique constraint for user, type, and channel
            $table->unique(['user_id', 'notification_type', 'channel']);
        });

        // Optional: Migrate existing preferences from the old table?
        // If the old `notification_preferences` table only controlled database notifications,
        // we might want to copy those settings into this new table for the 'database' channel.
        // This depends on how the old table was intended to function.
        // Example migration (Run *after* creating the new table):
        /*
        if (Schema::hasTable('notification_preferences')) {
            DB::table('notification_preferences')->orderBy('id')->chunk(100, function ($preferences) {
                $channelPrefs = [];
                foreach ($preferences as $pref) {
                    $channelPrefs[] = [
                        'user_id' => $pref->user_id,
                        'notification_type' => $pref->notification_type,
                        'channel' => 'database', // Assume old table was for database channel
                        'is_enabled' => $pref->is_enabled,
                        'created_at' => $pref->created_at ?? now(),
                        'updated_at' => $pref->updated_at ?? now(),
                    ];
                }
                if (!empty($channelPrefs)) {
                    DB::table('notification_channel_preferences')->insert($channelPrefs);
                }
            });
            // Optionally, drop the old table after migration
            // Schema::dropIfExists('notification_preferences');
        }
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channel_preferences');
    }
};
