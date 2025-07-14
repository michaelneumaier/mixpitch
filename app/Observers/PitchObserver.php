<?php

namespace App\Observers;

use App\Models\Pitch;
use App\Observers\ContestResultObserver;
use Illuminate\Support\Facades\Log;

class PitchObserver
{
    /**
     * Handle the Pitch "created" event.
     */
    public function created(Pitch $pitch): void
    {
        // When a pitch is created, update the project status if needed
        $this->syncProjectStatus($pitch);
        // Increment monthly pitch count for non-client management pitches
        if ($pitch->user) {
            $pitch->user->incrementMonthlyPitchCount($pitch);
        }
    }

    /**
     * Handle the Pitch "updated" event.
     */
    public function updated(Pitch $pitch): void
    {
        // Only sync project status if the pitch status has changed
        if ($pitch->isDirty('status') || $pitch->wasChanged('status')) {
            Log::info('Pitch status changed, syncing project status', [
                'pitch_id' => $pitch->id,
                'old_status' => $pitch->getOriginal('status'),
                'new_status' => $pitch->status
            ]);
            
            $this->syncProjectStatus($pitch);
        }
    }

    /**
     * Handle the Pitch "deleting" event.
     * This is called before the pitch is actually deleted.
     */
    public function deleting(Pitch $pitch): void
    {
        // Handle contest-specific cleanup before deletion
        $this->handleContestCleanup($pitch);
    }

    /**
     * Handle the Pitch "deleted" event.
     */
    public function deleted(Pitch $pitch): void
    {
        // When a pitch is deleted, update the project status
        $this->syncProjectStatus($pitch);
        
        // Additional cleanup for contest results (as backup to deleting event)
        ContestResultObserver::cleanupDeletedPitch($pitch->id);
    }

    /**
     * Handle the Pitch "restored" event.
     */
    public function restored(Pitch $pitch): void
    {
        // When a pitch is restored, update the project status
        $this->syncProjectStatus($pitch);
    }

    /**
     * Handle the Pitch "force deleted" event.
     */
    public function forceDeleted(Pitch $pitch): void
    {
        // When a pitch is force deleted, update the project status
        $this->syncProjectStatus($pitch);
        
        // Ensure contest cleanup happens even on force delete
        ContestResultObserver::cleanupDeletedPitch($pitch->id);
    }
    
    /**
     * Handle contest-specific cleanup when a pitch is being deleted
     */
    private function handleContestCleanup(Pitch $pitch): void
    {
        // Check if this pitch is part of any contest
        if ($pitch->isContestEntry() || $pitch->isContestWinner() || $pitch->rank) {
            Log::info('PitchObserver: Contest pitch being deleted, initiating cleanup', [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project_id,
                'status' => $pitch->status,
                'rank' => $pitch->rank
            ]);
            
            // The actual cleanup will happen in the deleted/forceDeleted events
            // This is just for logging and any pre-deletion validation
            
            // Check if this pitch is in a finalized contest
            if ($pitch->project && $pitch->project->isContest() && $pitch->project->isJudgingFinalized()) {
                Log::warning('PitchObserver: Deleting pitch from finalized contest', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'rank' => $pitch->rank,
                    'finalized_at' => $pitch->project->judging_finalized_at
                ]);
            }
        }
    }
    
    /**
     * Synchronize the project status with its pitches using ProjectManagementService
     */
    private function syncProjectStatus(Pitch $pitch): void
    {
        try {
            // Get the project associated with this pitch
            $project = $pitch->project;
            
            if ($project) {
                // Use ProjectManagementService to handle status updates
                $projectManagementService = app(\App\Services\Project\ProjectManagementService::class);
                
                // For client management projects, if a pitch is completed, complete the project
                if ($project->isClientManagement() && $pitch->status === \App\Models\Pitch::STATUS_COMPLETED) {
                    $projectManagementService->completeProject($project);
                    Log::info('Client management project completed after pitch completion', [
                        'project_id' => $project->id,
                        'triggered_by_pitch_id' => $pitch->id,
                    ]);
                }
                
                // For standard projects, let the PitchCompletionService handle project completion
                // For other status changes, we don't need to sync project status automatically
                // as the services handle this appropriately
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync project status after pitch change', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
