<?php

namespace App\Observers;

use App\Models\ContestResult;
use App\Models\Pitch;
use Illuminate\Support\Facades\Log;

class ContestResultObserver
{
    /**
     * Handle the ContestResult "updated" event.
     * This ensures runner-up arrays don't contain invalid pitch IDs.
     */
    public function updating(ContestResult $contestResult): void
    {
        // Clean up runner-up pitch IDs before saving
        if ($contestResult->isDirty('runner_up_pitch_ids')) {
            $runnerUpIds = $contestResult->runner_up_pitch_ids ?? [];
            
            if (!empty($runnerUpIds)) {
                // Verify all runner-up pitch IDs still exist
                $validPitchIds = Pitch::whereIn('id', $runnerUpIds)->pluck('id')->toArray();
                $orphanedIds = array_diff($runnerUpIds, $validPitchIds);
                
                if (!empty($orphanedIds)) {
                    Log::info('ContestResultObserver: Cleaning orphaned runner-up pitch IDs', [
                        'contest_result_id' => $contestResult->id,
                        'orphaned_ids' => $orphanedIds,
                        'valid_ids' => $validPitchIds
                    ]);
                    
                    $contestResult->runner_up_pitch_ids = $validPitchIds;
                }
            }
        }
    }
    
    /**
     * Handle cleanup when a pitch is deleted from the system.
     * This method will be called by the PitchObserver.
     */
    public static function cleanupDeletedPitch(int $pitchId): void
    {
        Log::info('ContestResultObserver: Starting cleanup for deleted pitch', [
            'pitch_id' => $pitchId
        ]);

        // Find all contest results that might reference this pitch
        $contestResults = ContestResult::where(function($query) use ($pitchId) {
            $query->where('first_place_pitch_id', $pitchId)
                  ->orWhere('second_place_pitch_id', $pitchId)
                  ->orWhere('third_place_pitch_id', $pitchId)
                  ->orWhereJsonContains('runner_up_pitch_ids', $pitchId);
        })->get();
        
        foreach ($contestResults as $contestResult) {
            $changes = [];
            $wasChanged = false;
            
            // Check and clean individual placements
            if ($contestResult->first_place_pitch_id === $pitchId) {
                $changes['first_place_pitch_id'] = ['from' => $pitchId, 'to' => null];
                $contestResult->first_place_pitch_id = null;
                $wasChanged = true;
            }
            
            if ($contestResult->second_place_pitch_id === $pitchId) {
                $changes['second_place_pitch_id'] = ['from' => $pitchId, 'to' => null];
                $contestResult->second_place_pitch_id = null;
                $wasChanged = true;
            }
            
            if ($contestResult->third_place_pitch_id === $pitchId) {
                $changes['third_place_pitch_id'] = ['from' => $pitchId, 'to' => null];
                $contestResult->third_place_pitch_id = null;
                $wasChanged = true;
            }
            
            // Check and clean runner-ups
            $runnerUpIds = $contestResult->runner_up_pitch_ids ?? [];
            if (in_array($pitchId, $runnerUpIds)) {
                $originalCount = count($runnerUpIds);
                
                // Remove the deleted pitch ID from the array
                $cleanedIds = array_values(array_filter($runnerUpIds, function($id) use ($pitchId) {
                    return $id !== $pitchId;
                }));
                
                $changes['runner_up_pitch_ids'] = [
                    'from' => $runnerUpIds,
                    'to' => $cleanedIds,
                    'removed_pitch_id' => $pitchId
                ];
                
                $contestResult->runner_up_pitch_ids = empty($cleanedIds) ? null : $cleanedIds;
                $wasChanged = true;
            }
            
            if ($wasChanged) {
                $contestResult->save();
                
                Log::info('ContestResultObserver: Cleaned contest result references', [
                    'contest_result_id' => $contestResult->id,
                    'project_id' => $contestResult->project_id,
                    'removed_pitch_id' => $pitchId,
                    'changes' => $changes,
                    'is_finalized' => $contestResult->isFinalized()
                ]);
            }
        }
    }
    
    /**
     * Validate contest result integrity and clean up any orphaned pitch references.
     */
    public static function validateAndCleanup(ContestResult $contestResult): bool
    {
        $needsUpdate = false;
        $changes = [];
        
        // Check first place
        if ($contestResult->first_place_pitch_id && !Pitch::find($contestResult->first_place_pitch_id)) {
            $changes['first_place_pitch_id'] = ['from' => $contestResult->first_place_pitch_id, 'to' => null];
            $contestResult->first_place_pitch_id = null;
            $needsUpdate = true;
        }
        
        // Check second place
        if ($contestResult->second_place_pitch_id && !Pitch::find($contestResult->second_place_pitch_id)) {
            $changes['second_place_pitch_id'] = ['from' => $contestResult->second_place_pitch_id, 'to' => null];
            $contestResult->second_place_pitch_id = null;
            $needsUpdate = true;
        }
        
        // Check third place
        if ($contestResult->third_place_pitch_id && !Pitch::find($contestResult->third_place_pitch_id)) {
            $changes['third_place_pitch_id'] = ['from' => $contestResult->third_place_pitch_id, 'to' => null];
            $contestResult->third_place_pitch_id = null;
            $needsUpdate = true;
        }
        
        // Check runner-ups
        $runnerUpIds = $contestResult->runner_up_pitch_ids ?? [];
        if (!empty($runnerUpIds)) {
            $validIds = Pitch::whereIn('id', $runnerUpIds)->pluck('id')->toArray();
            $orphanedIds = array_diff($runnerUpIds, $validIds);
            
            if (!empty($orphanedIds)) {
                $changes['runner_up_pitch_ids'] = [
                    'from' => $runnerUpIds,
                    'to' => $validIds,
                    'orphaned' => $orphanedIds
                ];
                $contestResult->runner_up_pitch_ids = empty($validIds) ? null : array_values($validIds);
                $needsUpdate = true;
            }
        }
        
        if ($needsUpdate) {
            $contestResult->save();
            
            Log::warning('ContestResultObserver: Cleaned up orphaned pitch references', [
                'contest_result_id' => $contestResult->id,
                'project_id' => $contestResult->project_id,
                'changes' => $changes,
                'is_finalized' => $contestResult->isFinalized()
            ]);
        }
        
        return $needsUpdate;
    }
} 