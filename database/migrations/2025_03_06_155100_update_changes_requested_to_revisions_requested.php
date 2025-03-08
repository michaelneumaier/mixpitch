<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update pitch status from changes_requested to revisions_requested
        DB::table('pitches')
            ->where('status', 'changes_requested')
            ->update(['status' => 'revisions_requested']);
            
        // Update pitch_snapshots status from changes_requested to revisions_requested
        DB::table('pitch_snapshots')
            ->where('status', 'changes_requested')
            ->update(['status' => 'revisions_requested']);
            
        // Update pitch_events event_type from changes_requested to snapshot_revisions_requested
        DB::table('pitch_events')
            ->where('event_type', 'changes_requested')
            ->update(['event_type' => 'snapshot_revisions_requested']);
            
        // Update comments text in pitch_events
        DB::table('pitch_events')
            ->where('comment', 'like', 'Changes requested%')
            ->update([
                'comment' => DB::raw("REPLACE(comment, 'Changes requested', 'Revisions requested')")
            ]);
            
        // Update notifications type from snapshot_changes_requested to snapshot_revisions_requested
        DB::table('notifications')
            ->where('type', 'snapshot_changes_requested')
            ->update(['type' => 'snapshot_revisions_requested']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update pitch status from revisions_requested to changes_requested
        DB::table('pitches')
            ->where('status', 'revisions_requested')
            ->update(['status' => 'changes_requested']);
            
        // Update pitch_snapshots status from revisions_requested to changes_requested
        DB::table('pitch_snapshots')
            ->where('status', 'revisions_requested')
            ->update(['status' => 'changes_requested']);
            
        // Update pitch_events event_type from snapshot_revisions_requested to changes_requested
        DB::table('pitch_events')
            ->where('event_type', 'snapshot_revisions_requested')
            ->update(['event_type' => 'changes_requested']);
            
        // Update comments text in pitch_events
        DB::table('pitch_events')
            ->where('comment', 'like', 'Revisions requested%')
            ->update([
                'comment' => DB::raw("REPLACE(comment, 'Revisions requested', 'Changes requested')")
            ]);
            
        // Update notifications type from snapshot_revisions_requested to snapshot_changes_requested
        DB::table('notifications')
            ->where('type', 'snapshot_revisions_requested')
            ->update(['type' => 'snapshot_changes_requested']);
    }
};
