<?php

namespace App\Observers;

use App\Models\Pitch;
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
     * Handle the Pitch "deleted" event.
     */
    public function deleted(Pitch $pitch): void
    {
        // When a pitch is deleted, update the project status
        $this->syncProjectStatus($pitch);
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
    }
    
    /**
     * Synchronize the project status with its pitches
     */
    private function syncProjectStatus(Pitch $pitch): void
    {
        try {
            // Get the project associated with this pitch
            $project = $pitch->project;
            
            if ($project) {
                // Refresh the project with the latest pitches data
                $project->refresh();
                
                // Sync project status with pitches
                $statusChanged = $project->syncStatusWithPitches();
                
                if ($statusChanged) {
                    Log::info('Project status synchronized after pitch change', [
                        'project_id' => $project->id,
                        'new_project_status' => $project->status,
                        'triggered_by_pitch_id' => $pitch->id,
                        'pitch_status' => $pitch->status
                    ]);
                }
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
