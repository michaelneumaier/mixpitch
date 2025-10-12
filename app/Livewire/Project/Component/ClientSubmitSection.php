<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ClientSubmitSection extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public Pitch $pitch;

    public $workflowColors;

    // Workflow management
    public $responseToFeedback = '';

    // Watermarking controls
    public $watermarkingEnabled = false;

    public $showWatermarkingInfo = false;

    protected $listeners = [
        'filesUploaded' => '$refresh',
        'fileDeleted' => '$refresh',
        'fileVersionChanged' => '$refresh',
    ];

    protected $rules = [
        'responseToFeedback' => 'nullable|string|max:5000',
    ];

    public function mount(Project $project, Pitch $pitch, $workflowColors)
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->workflowColors = $workflowColors;

        // Initialize watermarking preference
        $this->watermarkingEnabled = $this->pitch->watermarking_enabled ?? false;
    }

    /**
     * Get producer-uploaded files based on pitch status
     * - READY_FOR_REVIEW: Show files from current snapshot (already submitted)
     * - IN_PROGRESS/REVISIONS: Show working version files (will be submitted)
     */
    #[Computed]
    public function producerFiles()
    {
        // If already submitted for review, show files from the current snapshot
        if ($this->pitch->status === Pitch::STATUS_READY_FOR_REVIEW && $this->pitch->currentSnapshot) {
            $snapshotFileIds = $this->pitch->currentSnapshot->snapshot_data['file_ids'] ?? [];

            return $this->pitch->files()->whereIn('id', $snapshotFileIds)->with('pitch')->get();
        }

        // Otherwise, show working version files (files that will be included in next submission)
        return $this->pitch->files()->inWorkingVersion()->with('pitch')->get();
    }

    /**
     * Get audio files that would be affected by watermarking
     */
    #[Computed]
    public function audioFiles()
    {
        return $this->producerFiles->filter(function ($file) {
            return in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']);
        });
    }

    /**
     * Submit pitch for client review
     */
    public function submitForReview(PitchWorkflowService $pitchWorkflowService)
    {
        $this->authorize('submitForReview', $this->pitch);
        $this->validateOnly('responseToFeedback');

        try {
            // Update watermarking preference before submission
            $this->pitch->update([
                'watermarking_enabled' => $this->watermarkingEnabled,
            ]);

            $pitchWorkflowService->submitPitchForReview($this->pitch, Auth::user(), $this->responseToFeedback);

            Toaster::success('Pitch submitted for client review successfully.');
            $this->responseToFeedback = '';
            $this->pitch->refresh();

            // Emit event to refresh parent component
            $this->dispatch('pitchStatusChanged');

        } catch (\Exception $e) {
            Log::warning('Pitch submission failed', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            Toaster::error($e->getMessage());
        }
    }

    /**
     * Recall submission and restore to previous state
     * Deletes the snapshot since the client hasn't reviewed it yet
     * Smart restoration: Returns pitch to the state it was in before this submission
     */
    public function recallSubmission()
    {
        // Authorization check
        $this->authorize('recallSubmission', $this->pitch);

        // Validation: Can only recall from READY_FOR_REVIEW status
        if ($this->pitch->status !== Pitch::STATUS_READY_FOR_REVIEW) {
            Toaster::error('Can only recall submissions that are ready for review.');

            return;
        }

        try {
            DB::transaction(function () {
                $snapshot = $this->pitch->currentSnapshot;

                // Determine what status to restore to
                $previousStatus = $this->determinePreviousStatus($snapshot);
                $previousSnapshotId = $this->findPreviousSnapshotId($snapshot);

                // Store snapshot data for audit trail
                $snapshotData = $snapshot ? [
                    'snapshot_id' => $snapshot->id,
                    'version' => $snapshot->version,
                    'file_ids' => $snapshot->snapshot_data['file_ids'] ?? [],
                    'file_count' => count($snapshot->snapshot_data['file_ids'] ?? []),
                    'restored_status' => $previousStatus,
                    'restored_snapshot_id' => $previousSnapshotId,
                ] : null;

                // Restore pitch to previous state
                $this->pitch->status = $previousStatus;
                $this->pitch->current_snapshot_id = $previousSnapshotId;
                $this->pitch->save();

                // Delete the recalled snapshot (client never reviewed it)
                if ($snapshot) {
                    $snapshot->delete();
                    Log::info('Snapshot deleted during recall, state restored', [
                        'pitch_id' => $this->pitch->id,
                        'snapshot_id' => $snapshot->id,
                        'version' => $snapshot->version,
                        'restored_status' => $previousStatus,
                        'restored_snapshot_id' => $previousSnapshotId,
                    ]);
                }

                // Create event to track the recall and restoration
                $this->pitch->events()->create([
                    'created_by' => Auth::id(),
                    'event_type' => 'submission_recalled',
                    'status' => $previousStatus,
                    'comment' => "Producer recalled submission. Restored to previous state: {$previousStatus}",
                    'metadata' => $snapshotData,
                ]);
            });

            // User feedback based on restored state
            $message = match ($this->pitch->status) {
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED => 'Submission recalled. Restored to revision request state.',
                Pitch::STATUS_COMPLETED => 'Submission recalled. Previous version remains approved.',
                default => 'Submission recalled successfully. You can now make changes and resubmit.',
            };

            Toaster::success($message);
            $this->pitch->refresh();

            // Emit event to refresh parent component
            $this->dispatch('pitchStatusChanged');

        } catch (\Exception $e) {
            Log::error('Failed to recall submission', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to recall submission. Please try again.');
        }
    }

    /**
     * Determine what status the pitch should be restored to after recall
     * Looks at event history to find the status before the recalled submission
     */
    private function determinePreviousStatus(?object $snapshot): string
    {
        if (! $snapshot) {
            return Pitch::STATUS_IN_PROGRESS;
        }

        // Get all events for this snapshot (there may be multiple if it was edited)
        $snapshotEventIds = $this->pitch->events()
            ->where('snapshot_id', $snapshot->id)
            ->pluck('id')
            ->toArray();

        if (empty($snapshotEventIds)) {
            Log::warning('No events found for snapshot during recall', [
                'pitch_id' => $this->pitch->id,
                'snapshot_id' => $snapshot->id,
            ]);

            return Pitch::STATUS_IN_PROGRESS;
        }

        // Find the earliest event ID for this snapshot
        $earliestSnapshotEventId = min($snapshotEventIds);

        // Find the last status-changing event BEFORE any events related to this snapshot
        $previousEvent = $this->pitch->events()
            ->where('id', '<', $earliestSnapshotEventId)
            ->whereIn('event_type', ['status_change', 'client_revisions_requested'])
            ->orderBy('id', 'desc')
            ->first();

        if ($previousEvent) {
            Log::info('Found previous status from event history', [
                'pitch_id' => $this->pitch->id,
                'previous_event_id' => $previousEvent->id,
                'previous_status' => $previousEvent->status,
                'snapshot_id' => $snapshot->id,
                'earliest_snapshot_event_id' => $earliestSnapshotEventId,
            ]);

            return $previousEvent->status;
        }

        // No previous event = this was the first submission
        Log::info('No previous status found, restoring to IN_PROGRESS', [
            'pitch_id' => $this->pitch->id,
            'snapshot_id' => $snapshot->id,
        ]);

        return Pitch::STATUS_IN_PROGRESS;
    }

    /**
     * Find the ID of the snapshot that should become current after recall
     */
    private function findPreviousSnapshotId(?object $snapshot): ?int
    {
        if (! $snapshot) {
            return null;
        }

        // Check if snapshot data has previous_snapshot_id
        $previousSnapshotId = $snapshot->snapshot_data['previous_snapshot_id'] ?? null;

        if ($previousSnapshotId) {
            // Verify the snapshot still exists
            $previousSnapshot = $this->pitch->snapshots()->find($previousSnapshotId);
            if ($previousSnapshot) {
                Log::info('Found previous snapshot from snapshot data', [
                    'pitch_id' => $this->pitch->id,
                    'previous_snapshot_id' => $previousSnapshot->id,
                ]);

                return $previousSnapshot->id;
            }
        }

        // Fallback: Find the most recent snapshot before this one
        $previousSnapshot = $this->pitch->snapshots()
            ->where('id', '<', $snapshot->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($previousSnapshot) {
            Log::info('Found previous snapshot by ID/date', [
                'pitch_id' => $this->pitch->id,
                'previous_snapshot_id' => $previousSnapshot->id,
            ]);

            return $previousSnapshot->id;
        }

        return null;
    }

    public function render()
    {
        return view('livewire.project.component.client-submit-section');
    }
}
