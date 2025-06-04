<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if contest_results table exists before attempting cleanup
        if (!Schema::hasTable('contest_results')) {
            return;
        }
        
        // Find all contest results with potential orphaned references
        $contestResults = DB::table('contest_results')
            ->whereNotNull('runner_up_pitch_ids')
            ->get();
        
        $cleanupCount = 0;
        $totalProcessed = 0;
        
        foreach ($contestResults as $contestResult) {
            $totalProcessed++;
            $runnerUpIds = json_decode($contestResult->runner_up_pitch_ids, true);
            
            if (empty($runnerUpIds) || !is_array($runnerUpIds)) {
                continue;
            }
            
            // Find which pitch IDs still exist
            $validPitchIds = DB::table('pitches')
                ->whereIn('id', $runnerUpIds)
                ->pluck('id')
                ->toArray();
            
            $orphanedIds = array_diff($runnerUpIds, $validPitchIds);
            
            if (!empty($orphanedIds)) {
                $cleanupCount++;
                
                // Log the cleanup for audit purposes
                Log::info('Migration: Cleaning orphaned contest pitch references', [
                    'contest_result_id' => $contestResult->id,
                    'project_id' => $contestResult->project_id,
                    'original_runner_ups' => $runnerUpIds,
                    'valid_pitch_ids' => $validPitchIds,
                    'orphaned_ids' => $orphanedIds
                ]);
                
                // Update the contest result
                $newRunnerUpIds = empty($validPitchIds) ? null : json_encode(array_values($validPitchIds));
                
                DB::table('contest_results')
                    ->where('id', $contestResult->id)
                    ->update([
                        'runner_up_pitch_ids' => $newRunnerUpIds,
                        'updated_at' => now()
                    ]);
            }
        }
        
        // Log summary
        Log::info("Migration: Processed {$totalProcessed} contest results, cleaned {$cleanupCount} with orphaned references");
        
        // Also check for orphaned individual placement references (though FK constraints should handle these)
        $orphanedFirstPlace = DB::table('contest_results')
            ->whereNotNull('first_place_pitch_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pitches')
                    ->whereColumn('pitches.id', 'contest_results.first_place_pitch_id');
            })
            ->count();
            
        $orphanedSecondPlace = DB::table('contest_results')
            ->whereNotNull('second_place_pitch_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pitches')
                    ->whereColumn('pitches.id', 'contest_results.second_place_pitch_id');
            })
            ->count();
            
        $orphanedThirdPlace = DB::table('contest_results')
            ->whereNotNull('third_place_pitch_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pitches')
                    ->whereColumn('pitches.id', 'contest_results.third_place_pitch_id');
            })
            ->count();
        
        if ($orphanedFirstPlace || $orphanedSecondPlace || $orphanedThirdPlace) {
            Log::warning("Migration: Found orphaned individual placements", [
                'first_place' => $orphanedFirstPlace,
                'second_place' => $orphanedSecondPlace,
                'third_place' => $orphanedThirdPlace
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only cleans data, no schema changes to reverse
    }
}; 