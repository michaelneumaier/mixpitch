<?php

// app/Http/Livewire/Pitch/Component/UpdatePitchStatus.php
namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use App\Models\Pitch;
use App\Services\NotificationService;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\Toaster;

class UpdatePitchStatus extends Component
{
    public $pitch;
    public $status;
    public $hasCompletedPitch;
    public $denyReason;

    protected $listeners = [
        'confirmApproveSnapshot' => 'approveSnapshot',
        'confirmDenySnapshot' => 'denySnapshot',
        'confirmCancelSubmission' => 'cancelSubmission',
        'confirmRequestRevisions' => 'requestRevisions',
        'snapshot-status-updated' => '$refresh'
    ];

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->status = $pitch->status;
        $this->hasCompletedPitch = $pitch->project->pitches()->where('status', Pitch::STATUS_COMPLETED)->exists();
    }

    public function changeStatus($direction, $newStatus = null)
    {
        $project = $this->pitch->project;

        // Ensure the authenticated user is the owner of the project
        if (!Auth::check() || $project->user_id !== Auth::id()) {
            throw new UnauthorizedActionException('change status', 'You are not authorized to change the status of this pitch');
        }

        try {
            // Validate the status transition before proceeding
            $validationPassed = false;
            $errorMessage = '';
            
            // Determine which validation method to use based on the target status
            if ($newStatus) {
                switch ($newStatus) {
                    case Pitch::STATUS_APPROVED:
                        // Check if the pitch was completed and paid
                        if ($this->pitch->status === Pitch::STATUS_COMPLETED &&
                            $this->pitch->payment_status === Pitch::PAYMENT_STATUS_PAID) {
                            $validationPassed = false;
                            $errorMessage = 'This pitch has already been paid and cannot be returned to approved status.';
                        } else {
                            [$validationPassed, $errorMessage] = $this->pitch->canApprove($this->pitch->current_snapshot_id);
                        }
                        break;
                    case Pitch::STATUS_DENIED:
                        [$validationPassed, $errorMessage] = $this->pitch->canDeny($this->pitch->current_snapshot_id);
                        break;
                    case Pitch::STATUS_REVISIONS_REQUESTED:
                        [$validationPassed, $errorMessage] = $this->pitch->canRequestRevisions($this->pitch->current_snapshot_id);
                        break;
                    case Pitch::STATUS_IN_PROGRESS:
                        if ($this->pitch->status === Pitch::STATUS_READY_FOR_REVIEW) {
                            [$validationPassed, $errorMessage] = $this->pitch->canCancelSubmission();
                        } else {
                            $validationPassed = true;
                        }
                        break;
                    case Pitch::STATUS_COMPLETED:
                        [$validationPassed, $errorMessage] = $this->pitch->canComplete();
                        break;
                    default:
                        $validationPassed = true; // For other transitions, rely on the model's validation
                }
            } else {
                $validationPassed = true; // For automatic transitions, rely on the model's validation
            }
            
            // If validation fails, throw an exception with the error message
            if (!$validationPassed) {
                throw new InvalidStatusTransitionException(
                    $this->pitch->status, 
                    $newStatus ?? 'unknown',
                    $errorMessage
                );
            }

            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();
            
            if ($newStatus) {
                $this->pitch->changeStatus($direction, $newStatus);
            } else {
                $this->pitch->changeStatus($direction);
            }
            $this->status = $this->pitch->status;

            // Commit the transaction if everything succeeded
            DB::commit();

            // Use redirect instead of events to refresh the page
            Toaster::success('Pitch status updated successfully.');
            return redirect()->route('projects.manage', $this->pitch->project);
        } catch (InvalidStatusTransitionException $e) {
            // Rollback the transaction if anything fails
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            // Log the error for debugging
            Log::error('Invalid status transition', [
                'pitch_id' => $this->pitch->id,
                'current_status' => $e->getCurrentStatus(),
                'target_status' => $e->getTargetStatus(),
                'error' => $e->getMessage()
            ]);
            
            Toaster::error($e->getMessage());
        } catch (UnauthorizedActionException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            Log::error('Unauthorized pitch action', [
                'pitch_id' => $this->pitch->id,
                'action' => $e->getAction(),
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            // Rollback the transaction if anything fails
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            // Log the error for debugging
            Log::error('Failed to update pitch status', [
                'pitch_id' => $this->pitch->id,
                'attempted_direction' => $direction,
                'attempted_status' => $newStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Toaster::error('An unexpected error occurred while updating the pitch status.');
        }
    }

    public function reviewPitch()
    {
        // Retrieve the latest snapshot for this pitch
        $latestSnapshot = $this->pitch->snapshots()->orderBy('created_at', 'desc')->first();

        if ($latestSnapshot) {
            return redirect()->route('pitches.showSnapshot', [$this->pitch->id, $latestSnapshot->id]);
        }

        Toaster::error('No snapshots available to review.');
    }

    /**
     * Request to approve a snapshot (opens confirmation dialog)
     *
     * @param int $snapshotId
     * @return void
     */
    public function requestSnapshotApproval(int $snapshotId)
    {
        // Validate that the pitch can be approved
        [$canApprove, $errorMessage] = $this->pitch->canApprove($snapshotId);
        if (!$canApprove) {
            Toaster::error($errorMessage);
            return;
        }

        $this->dispatch('openConfirmDialog', 'approve', ['snapshotId' => $snapshotId]);
    }

    /**
     * Request to deny a snapshot (opens confirmation dialog)
     *
     * @param int $snapshotId
     * @return void
     */
    public function requestSnapshotDenial(int $snapshotId)
    {
        // Validate that the pitch can be denied
        [$canDeny, $errorMessage] = $this->pitch->canDeny($snapshotId);
        if (!$canDeny) {
            Toaster::error($errorMessage);
            return;
        }

        $this->dispatch('openConfirmDialog', 'deny', ['snapshotId' => $snapshotId]);
    }
    
    /**
     * Request to cancel pitch submission (opens confirmation dialog)
     *
     * @return void
     */
    public function requestCancelSubmission()
    {
        // Validate that the submission can be canceled
        [$canCancel, $errorMessage] = $this->pitch->canCancelSubmission();
        if (!$canCancel) {
            Toaster::error($errorMessage);
            return;
        }

        $this->dispatch('openConfirmDialog', 'cancel');
    }

    /**
     * Request to request revisions for a snapshot (opens confirmation dialog)
     *
     * @param int $snapshotId
     * @return void
     */
    public function requestRevisions($snapshotId)
    {
        // Check if permissions allow requesting revisions
        [$canRequestRevisions, $errorMessage] = $this->pitch->canRequestRevisions($snapshotId);
        if (!$canRequestRevisions) {
            $this->dispatch('showToast', message: $errorMessage, style: 'error');
            return;
        }
        
        // Prepare to open the revisions dialog
        $this->confirmAction = 'confirmRequestRevisions';
        $this->confirmTitle = 'Request Revisions';
        $this->confirmMessage = 'Please provide details about what revisions you would like to request:';
        $this->confirmButtonText = 'Submit Request';
        $this->confirmButtonClass = 'btn-info';
        $this->snapshotId = $snapshotId;
        $this->denyReason = '';
        $this->showConfirmModal = true;
    }

    /**
     * Approve a pitch snapshot
     *
     * @param int $snapshotId
     * @return void
     */
    public function approveSnapshot(int $snapshotId)
    {
        // Validate that the pitch can be approved
        [$canApprove, $errorMessage] = $this->pitch->canApprove($snapshotId);
        if (!$canApprove) {
            Toaster::error($errorMessage);
            return;
        }

        // Verify the snapshot exists
        $snapshot = $this->pitch->snapshots()->findOrFail($snapshotId);

        // Update the snapshot status
        $snapshot->status = 'accepted';
        $snapshot->save();

        // Set as the current snapshot
        $this->pitch->current_snapshot_id = $snapshot->id;

        // Update the pitch status
        $this->pitch->changeStatus(
            'forward',
            Pitch::STATUS_APPROVED,
            'Pitch snapshot #' . $snapshot->snapshot_data['version'] . ' has been approved'
        );

        // Create event for the snapshot approval
        $this->pitch->events()->create([
            'event_type' => 'snapshot_approved',
            'comment' => 'Snapshot version ' . $snapshot->snapshot_data['version'] . ' has been approved',
            'snapshot_id' => $snapshot->id,
            'created_by' => auth()->id(),
            'user_id' => auth()->id(),
        ]);
        
        // Create notification for snapshot approval
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifySnapshotApproved($snapshot);
        } catch (\Exception $e) {
            // Log notification error but don't fail the request
            Log::error('Failed to create snapshot approval notification: ' . $e->getMessage());
        }

        $this->resetState();

        $this->dispatch('snapshot-status-updated');
        $this->dispatch('pitchStatusUpdated');
        Toaster::success('Pitch has been approved successfully.');
        
        // Redirect to manage project page for a full page refresh
        return redirect()->route('projects.manage', $this->pitch->project);
    }

    /**
     * Deny a pitch snapshot
     *
     * @param int $snapshotId
     * @param string $reason
     * @return void
     */
    public function denySnapshot(int $snapshotId, string $reason = '')
    {
        // Use the reason passed from the confirmation dialog
        $this->denyReason = $reason;

        $this->validate([
            'denyReason' => 'required|min:3',
        ]);

        // Validate that the pitch can be denied
        [$canDeny, $errorMessage] = $this->pitch->canDeny($snapshotId);
        if (!$canDeny) {
            Toaster::error($errorMessage);
            return;
        }

        // Verify the snapshot exists
        $snapshot = $this->pitch->snapshots()->findOrFail($snapshotId);

        // Update the snapshot status - standardize on 'denied' over 'declined'
        $snapshot->status = 'denied';
        $snapshot->save();

        // Set as the current snapshot
        $this->pitch->current_snapshot_id = $snapshot->id;

        // Update the pitch status
        $this->pitch->changeStatus(
            'backward',
            Pitch::STATUS_DENIED,
            'Pitch snapshot #' . $snapshot->snapshot_data['version'] . ' has been denied: ' . $this->denyReason
        );

        // Create event for the snapshot denial
        $this->pitch->events()->create([
            'event_type' => 'snapshot_denied',
            'comment' => 'Snapshot denied. Reason: ' . $this->denyReason,
            'snapshot_id' => $snapshot->id,
            'created_by' => auth()->id(),
            'user_id' => auth()->id(),
        ]);

        // Add a comment with the denial reason
        $this->pitch->addComment('This pitch was denied: ' . $this->denyReason);
        
        // Create notification for snapshot denial
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifySnapshotDenied($snapshot, $this->denyReason);
        } catch (\Exception $e) {
            // Log notification error but don't fail the request
            Log::error('Failed to create snapshot denial notification: ' . $e->getMessage());
        }

        $this->resetState();

        $this->dispatch('snapshot-status-updated');
        $this->dispatch('pitchStatusUpdated');
        Toaster::success('Pitch has been denied.');
        
        // Redirect to manage project page for a full page refresh
        return redirect()->route('projects.manage', $this->pitch->project);
    }

    /**
     * Request revisions for a pitch snapshot
     *
     * @param int $snapshotId
     * @param string $reason
     * @return void
     */
    public function requestRevisions(int $snapshotId, string $reason = '')
    {
        // Use the reason passed from the confirmation dialog as the changes requested
        $this->denyReason = $reason;

        $this->validate([
            'denyReason' => 'required|min:3',
        ]);

        // Validate that revisions can be requested for the pitch
        [$canRequestRevisions, $errorMessage] = $this->pitch->canRequestRevisions($snapshotId);
        if (!$canRequestRevisions) {
            Toaster::error($errorMessage);
            return;
        }

        // Verify the snapshot exists
        $snapshot = $this->pitch->snapshots()->findOrFail($snapshotId);

        // Update the snapshot status - mark as pending revisions
        $snapshot->status = 'revisions_requested';
        $snapshot->save();

        // Set as the current snapshot
        $this->pitch->current_snapshot_id = $snapshot->id;

        // Update the pitch status to revisions requested
        $this->pitch->changeStatus(
            'forward',
            Pitch::STATUS_REVISIONS_REQUESTED,
            'Revisions requested for pitch snapshot #' . $snapshot->snapshot_data['version'] . ': ' . $this->denyReason
        );

        // Create event for the revisions request
        $this->pitch->events()->create([
            'event_type' => 'snapshot_revisions_requested',
            'comment' => 'Revisions requested. Reason: ' . $this->denyReason,
            'snapshot_id' => $snapshot->id,
            'created_by' => auth()->id(),
            'user_id' => auth()->id(),
        ]);

        // Add a comment with the revisions requested reason
        $this->pitch->addComment('Revisions requested for this pitch: ' . $this->denyReason);
        
        // Create notification for revisions requested
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifySnapshotRevisionsRequested($snapshot, $this->denyReason);
        } catch (\Exception $e) {
            // Log notification error but don't fail the request
            Log::error('Failed to create revisions requested notification: ' . $e->getMessage());
        }

        $this->resetState();

        $this->dispatch('snapshot-status-updated');
        $this->dispatch('pitchStatusUpdated');
        Toaster::success('Changes have been requested for this pitch.');
        
        // Redirect to manage project page for a full page refresh
        return redirect()->route('projects.manage', $this->pitch->project);
    }

    /**
     * Cancel a pitch submission
     *
     * @return void
     */
    public function cancelSubmission()
    {
        // Validate that the submission can be canceled
        [$canCancel, $errorMessage] = $this->pitch->canCancelSubmission();
        if (!$canCancel) {
            Toaster::error($errorMessage);
            return;
        }

        try {
            $this->pitch->changeStatus('backward', Pitch::STATUS_IN_PROGRESS, 'Pitch submission was canceled');
            $this->status = $this->pitch->status;

            // Create notification for pitch cancellation
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyPitchCancellation($this->pitch);
            } catch (\Exception $e) {
                // Log notification error but don't fail the request
                Log::error('Failed to create pitch cancellation notification: ' . $e->getMessage());
            }

            $this->resetState();
            $this->dispatch('pitchStatusUpdated');

            Toaster::success('Pitch submission has been canceled.');
            return redirect()->route('projects.manage', $this->pitch->project);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }

    /**
     * Return a pitch to ready for review status
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnToReadyForReview()
    {
        try {
            // Start a transaction for atomicity
            DB::beginTransaction();
            
            // Log the current state before changes
            Log::info('Return to ready for review - Starting', [
                'pitch_id' => $this->pitch->id,
                'current_status' => $this->pitch->status,
                'current_snapshot_id' => $this->pitch->current_snapshot_id
            ]);
            
            // Force update the snapshot directly
            if ($this->pitch->status === Pitch::STATUS_DENIED && $this->pitch->current_snapshot_id) {
                // Update the snapshot status to pending
                DB::table('pitch_snapshots')
                    ->where('id', $this->pitch->current_snapshot_id)
                    ->update(['status' => 'pending']);
                
                Log::info('Snapshot status updated directly', [
                    'snapshot_id' => $this->pitch->current_snapshot_id
                ]);
            }
            
            // Now use the changeStatus method for the pitch itself
            $result = $this->changeStatus('backward', Pitch::STATUS_READY_FOR_REVIEW);
            
            // Make sure to commit the transaction
            DB::commit();
            
            // Log the final state after all changes
            $snapshot = DB::table('pitch_snapshots')
                ->where('id', $this->pitch->current_snapshot_id)
                ->first();
                
            Log::info('Return to ready for review - Completed', [
                'pitch_id' => $this->pitch->id,
                'new_status' => $this->pitch->fresh()->status,
                'snapshot_id' => $this->pitch->current_snapshot_id,
                'snapshot_status' => $snapshot ? $snapshot->status : 'unknown'
            ]);
            
            return $result;
        } catch (\Exception $e) {
            // Rollback if there's an error
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            Log::error('Failed to return pitch to ready for review status', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Toaster::error('An error occurred while returning the pitch to review status: ' . $e->getMessage());
            return redirect()->route('projects.manage', $this->pitch->project);
        }
    }

    /**
     * Reset the component state
     */
    private function resetState()
    {
        $this->pitch->refresh();
        $this->status = $this->pitch->status;
        $this->denyReason = '';
    }

    public function render()
    {
        return view('livewire.pitch.component.update-pitch-status');
    }
}
