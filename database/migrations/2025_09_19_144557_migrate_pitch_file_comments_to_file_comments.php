<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if we have the old table and the new table exists
        if (Schema::hasTable('pitch_file_comments') && Schema::hasTable('file_comments')) {
            // Copy all data from pitch_file_comments to file_comments
            DB::table('pitch_file_comments')->orderBy('id')->chunk(100, function ($comments) {
                foreach ($comments as $comment) {
                    DB::table('file_comments')->insert([
                        'id' => $comment->id, // Preserve original IDs to maintain parent relationships
                        'commentable_type' => 'App\\Models\\PitchFile',
                        'commentable_id' => $comment->pitch_file_id,
                        'user_id' => $comment->user_id,
                        'parent_id' => $comment->parent_id,
                        'comment' => $comment->comment,
                        'timestamp' => $comment->timestamp,
                        'resolved' => $comment->resolved,
                        'client_email' => $comment->client_email,
                        'is_client_comment' => $comment->is_client_comment,
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                    ]);
                }
            });
            
            // Reset the auto-increment to start after the last migrated ID
            $maxId = DB::table('file_comments')->max('id');
            if ($maxId) {
                // For SQLite
                if (config('database.default') === 'sqlite') {
                    DB::statement("UPDATE sqlite_sequence SET seq = ? WHERE name = 'file_comments'", [$maxId]);
                }
                // For MySQL
                elseif (config('database.default') === 'mysql') {
                    DB::statement("ALTER TABLE file_comments AUTO_INCREMENT = ?", [$maxId + 1]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete only the records that were migrated from pitch_file_comments
        if (Schema::hasTable('file_comments')) {
            DB::table('file_comments')
                ->where('commentable_type', 'App\\Models\\PitchFile')
                ->delete();
        }
    }
};
