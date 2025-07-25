<?php

// app/Http/Livewire/Pitch/Component/UpdatePitchStatus.php

namespace App\Livewire\Pitch\Component;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\SnapshotException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class UpdatePitchStatus extends Component
{
    use AuthorizesRequests;

    public $pitch;

    public $status;

    public $hasCompletedPitch;

    public $denyReason = '';

    public $revisionFeedback = '';

    public $currentSnapshotIdToActOn;

    protected $listeners = [
        'confirmApproveSnapshot' => 'approveSnapshot',
        'confirmDenySnapshot' => 'denySnapshot',
        'confirmCancelSubmission' => 'cancelSubmission',
        'confirmRequestRevisions' => 'requestRevisionsAction',
        'snapshot-status-updated' => '$refresh',
    ];

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->status = $pitch->status;
        $this->hasCompletedPitch = $pitch->project->pitches()->where('status', Pitch::STATUS_COMPLETED)->exists();
    }

    public function reviewPitch()
    {
        // Retrieve the latest snapshot for this pitch
        $latestSnapshot = $this->pitch->snapshots()->orderBy('created_at', 'desc')->first();

        if ($latestSnapshot) {
            return redirect()->route('projects.pitches.snapshots.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug, 'snapshot' => $latestSnapshot->id]);
        }

        Toaster::error('No snapshots available to review.');
    }

    /**
     * Request to approve a snapshot (opens confirmation dialog)
     *
     * @param  array  $data  Contains snapshotId
     * @return void
     */
    public function requestSnapshotApproval(array $data)
    {
        $snapshotId = $data['snapshotId'] ?? null;
        if (! $snapshotId) {
            return;
        }

        $this->currentSnapshotIdToActOn = $snapshotId;
        $this->dispatch('openConfirmDialog', 'approve');
    }

    /**
     * Request to deny a snapshot (opens confirmation dialog)
     *
     * @param  array  $data  Contains snapshotId
     * @return void
     */
    public function requestSnapshotDenial(array $data)
    {
        $snapshotId = $data['snapshotId'] ?? null;
        if (! $snapshotId) {
            return;
        }

        $this->currentSnapshotIdToActOn = $snapshotId;
        $this->denyReason = '';
        $this->dispatch('openConfirmDialog', 'deny');
    }

    /**
     * Request to cancel pitch submission (opens confirmation dialog)
     *
     * @return void
     */
    public function requestCancelSubmission()
    {
        $this->dispatch('openConfirmDialog', 'cancel');
    }

    /**
     * Request to request revisions for a snapshot (opens confirmation dialog)
     *
     * @param  array  $data  Contains snapshotId
     * @return void
     */
    public function requestRevisions(array $data)
    {
        $snapshotId = $data['snapshotId'] ?? null;
        if (! $snapshotId) {
            return;
        }

        $this->currentSnapshotIdToActOn = $snapshotId;
        $this->revisionFeedback = '';
        $this->dispatch('openConfirmDialog', 'revisions');
    }

    /**
     * Approve an initial PENDING pitch.
     *
     * @return void
     */
    public function approveInitialPitch(PitchWorkflowService $pitchWorkflowService)
    {
        try {
            // Authorization check
            $this->authorize('approveInitial', $this->pitch);

            $pitchWorkflowService->approveInitialPitch(
                $this->pitch,
                auth()->user()
            );

            Toaster::success('Initial pitch approved successfully!');
            $this->dispatch('pitchStatusUpdated'); // Dispatch generic event
            $this->status = Pitch::STATUS_IN_PROGRESS;
            $this->pitch->refresh(); // Restore this call
        } catch (UnauthorizedActionException|\Illuminate\Auth\Access\AuthorizationException $e) { // Catch specific auth exceptions
            Toaster::error('You are not authorized to approve this pitch.');
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error approving initial pitch via Livewire', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while approving the initial pitch.');
        }
    }

    /**
     * Approve a submitted snapshot.
     * Called via listener after confirmation.
     *
     * @return void
     */
    public function approveSnapshot(PitchWorkflowService $pitchWorkflowService)
    {
        if (! $this->currentSnapshotIdToActOn) {
            Log::warning('approveSnapshot called without currentSnapshotIdToActOn.', ['pitch_id' => $this->pitch->id]);
            Toaster::error('An error occurred. Please try again.');

            return;
        }

        try {
            $this->authorize('approveSubmission', $this->pitch);

            $pitchWorkflowService->approveSubmittedPitch(
                $this->pitch,
                $this->currentSnapshotIdToActOn,
                auth()->user()
            );

            Toaster::success('Pitch approved successfully!');

            // Clean up experimental code and use simple event dispatch
            $this->dispatch('pitchStatusUpdated');
            $this->dispatch('snapshot-status-updated');
            $this->currentSnapshotIdToActOn = null;
            $this->status = Pitch::STATUS_APPROVED;
            $this->pitch->refresh(); // Restore this call
        } catch (UnauthorizedActionException $e) {
            Toaster::error('You are not authorized to approve this pitch.');
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error approving pitch via Livewire', ['pitch_id' => $this->pitch->id, 'snapshot_id' => $this->currentSnapshotIdToActOn, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while approving the pitch.');
        } finally {
            $this->currentSnapshotIdToActOn = null;
        }
    }

    /**
     * Deny a submitted snapshot.
     * Called via listener after confirmation.
     * Requires $this->denyReason to be set by the dialog.
     *
     * @return void
     */
    public function denySnapshot(PitchWorkflowService $pitchWorkflowService)
    {
        if (! $this->currentSnapshotIdToActOn) {
            Log::warning('denySnapshot called without currentSnapshotIdToActOn.', ['pitch_id' => $this->pitch->id]);
            Toaster::error('An error occurred. Please try again.');

            return;
        }

        try {
            $this->authorize('denySubmission', $this->pitch);

            $pitchWorkflowService->denySubmittedPitch(
                $this->pitch,
                $this->currentSnapshotIdToActOn,
                auth()->user(),
                $this->denyReason
            );

            Toaster::success('Pitch denied successfully.');

            // Clean up experimental code
            $this->dispatch('pitchStatusUpdated');
            $this->dispatch('snapshot-status-updated');
            $this->status = Pitch::STATUS_DENIED;
            $this->pitch->refresh(); // Restore this call
        } catch (UnauthorizedActionException $e) {
            Toaster::error('You are not authorized to deny this pitch.');
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error denying pitch via Livewire', ['pitch_id' => $this->pitch->id, 'snapshot_id' => $this->currentSnapshotIdToActOn, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while denying the pitch.');
        } finally {
            $this->currentSnapshotIdToActOn = null;
            $this->denyReason = '';
        }
    }

    /**
     * Request revisions for a submitted snapshot.
     * Called via listener after confirmation.
     * Requires $this->revisionFeedback to be set by the dialog.
     *
     * @return void
     */
    public function requestRevisionsAction(PitchWorkflowService $pitchWorkflowService)
    {
        if (! $this->currentSnapshotIdToActOn) {
            Log::warning('requestRevisionsAction called without currentSnapshotIdToActOn.', ['pitch_id' => $this->pitch->id]);
            Toaster::error('An error occurred. Please try again.');

            return;
        }

        // Validate feedback is not empty - Service also validates, but good for quick UI feedback
        if (trim($this->revisionFeedback) === '') {
            Toaster::error('Revision feedback cannot be empty.');

            // Optionally focus the input field
            return;
        }

        try {
            // Use 'requestRevisions' policy method name from guide
            $this->authorize('requestRevisions', $this->pitch);

            $pitchWorkflowService->requestPitchRevisions(
                $this->pitch,
                $this->currentSnapshotIdToActOn,
                auth()->user(),
                $this->revisionFeedback
            );

            Toaster::success('Revisions requested successfully.');

            $this->dispatch('pitchStatusUpdated');
            $this->dispatch('snapshot-status-updated');
            $this->status = Pitch::STATUS_REVISIONS_REQUESTED;
            $this->pitch->refresh();
        } catch (UnauthorizedActionException|\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to request revisions for this pitch.');
        } catch (InvalidStatusTransitionException|SnapshotException|\InvalidArgumentException $e) {
            // Catch specific exceptions from the service
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error requesting revisions via Livewire', ['pitch_id' => $this->pitch->id, 'snapshot_id' => $this->currentSnapshotIdToActOn, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while requesting revisions.');
        } finally {
            $this->currentSnapshotIdToActOn = null;
            $this->revisionFeedback = '';
        }
    }

    /**
     * Cancel pitch submission.
     * Called via listener after confirmation.
     * NOTE: This action is typically performed by the PITCH CREATOR.
     * It might be better placed in ManagePitch component.
     *
     * @return void
     */
    public function cancelSubmission(PitchWorkflowService $pitchWorkflowService)
    {
        // Ensure this component is used in a context where the logged-in user
        // could potentially be the pitch creator (might not be the case if
        // this component is strictly for project owner actions).
        // If not, this method might always fail authorization.

        try {
            // Use 'cancelSubmission' policy method name from guide
            $this->authorize('cancelSubmission', $this->pitch);

            $pitchWorkflowService->cancelPitchSubmission(
                $this->pitch,
                auth()->user()
            );

            Toaster::success('Pitch submission cancelled successfully.');

            $this->dispatch('pitchStatusUpdated');
            $this->dispatch('snapshot-status-updated');
            $this->status = Pitch::STATUS_IN_PROGRESS;
            $this->pitch->refresh();
        } catch (UnauthorizedActionException|\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to cancel this submission.');
        } catch (InvalidStatusTransitionException|SnapshotException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error cancelling submission via Livewire', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            Toaster::error('An unexpected error occurred while cancelling the submission.');
        }
    }

    /**
     * Change the status of a pitch (direct status change for simple transitions)
     *
     * @param  string  $direction  The direction of the transition ('forward' or 'backward')
     * @param  string  $newStatus  The new status to set
     * @return void
     */
    public function changeStatus(string $direction, string $newStatus)
    {
        try {
            // Change the authorization check based on the status transition
            if ($this->status === Pitch::STATUS_PENDING && $newStatus === Pitch::STATUS_IN_PROGRESS) {
                // For approving initial pitch, use the same permission as approveInitialPitch
                $this->authorize('approveInitial', $this->pitch);
            } elseif ($direction === 'backward' &&
                      ($this->status === Pitch::STATUS_IN_PROGRESS ||
                       $this->status === Pitch::STATUS_PENDING_REVIEW)) {
                // For revoking access, project owners should be able to do this
                $this->authorize('manageAccess', $this->pitch);
            } elseif ($direction === 'backward' &&
                      ($this->status === Pitch::STATUS_APPROVED ||
                       $this->status === Pitch::STATUS_READY_FOR_REVIEW)) {
                // For returning approved/ready for review pitches, project owners should have access
                $this->authorize('manageReview', $this->pitch);
            } else {
                // Default fallback to update permission
                $this->authorize('update', $this->pitch);
            }

            // Get available transitions for this direction
            $availableTransitions = Pitch::$transitions[$direction][$this->status] ?? null;

            // Check if this is a valid transition
            $isValidTransition = false;
            if (is_array($availableTransitions)) {
                $isValidTransition = in_array($newStatus, $availableTransitions);
            } else {
                $isValidTransition = $availableTransitions === $newStatus;
            }

            if (! $isValidTransition) {
                throw new InvalidStatusTransitionException(
                    $this->status,
                    $newStatus,
                    'Invalid status transition'
                );
            }

            // Update the status
            $this->pitch->status = $newStatus;
            $this->pitch->save();

            // Record this event
            PitchEvent::createStatusChangeEvent(
                $this->pitch,
                auth()->user(),
                $this->status,
                $newStatus
            );

            // Update component state
            $this->status = $newStatus;

            // Dispatch events
            $this->dispatch('pitchStatusUpdated');

            Toaster::success('Pitch status updated successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to change this pitch status.');
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error changing pitch status', [
                'pitch_id' => $this->pitch->id,
                'direction' => $direction,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An unexpected error occurred while changing the pitch status.');
        }
    }

    /**
     * Helper method to return a denied pitch back to 'ready for review' status
     *
     * @return void
     */
    public function returnToReadyForReview()
    {
        // This is essentially a wrapper around changeStatus but provides
        // a more descriptive method name for the specific transition
        try {
            $this->authorize('manageReview', $this->pitch);

            if ($this->status !== Pitch::STATUS_DENIED) {
                throw new InvalidStatusTransitionException(
                    $this->status,
                    Pitch::STATUS_READY_FOR_REVIEW,
                    'This action is only valid for denied pitches'
                );
            }

            // Update the status
            $this->pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
            $this->pitch->save();

            // Record this event
            PitchEvent::createStatusChangeEvent(
                $this->pitch,
                auth()->user(),
                $this->status,
                Pitch::STATUS_READY_FOR_REVIEW
            );

            // Update component state
            $this->status = Pitch::STATUS_READY_FOR_REVIEW;

            // Dispatch events
            $this->dispatch('pitchStatusUpdated');

            Toaster::success('Pitch returned to review successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to return this pitch to review.');
        } catch (InvalidStatusTransitionException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error returning pitch to review', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An unexpected error occurred while returning the pitch to review.');
        }
    }

    private function resetState()
    {
        $this->denyReason = '';
        $this->revisionFeedback = '';
        $this->currentSnapshotIdToActOn = null;
    }

    public function render()
    {
        $this->hasCompletedPitch = $this->pitch->project->pitches()->where('status', Pitch::STATUS_COMPLETED)->exists();

        $currentSnapshot = $this->pitch->currentSnapshot()->first();

        return view('livewire.pitch.component.update-pitch-status', [
            'currentSnapshot' => $currentSnapshot,
        ]);
    }

    /**
     * Generate a slug for the pitch if it doesn't have one
     */
    private function generateSlugForPitch()
    {
        $baseSlug = ! empty($this->pitch->title)
            ? \Illuminate\Support\Str::slug($this->pitch->title)
            : 'pitch-'.$this->pitch->id;

        $slug = $baseSlug;
        $count = 1;

        while (
            Pitch::where('project_id', $this->pitch->project_id)
                ->where('id', '!=', $this->pitch->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        $this->pitch->slug = $slug;
        $this->pitch->save();

        Log::info('Generated slug for pitch during status change in Livewire component', [
            'pitch_id' => $this->pitch->id,
            'generated_slug' => $slug,
        ]);
    }
}
