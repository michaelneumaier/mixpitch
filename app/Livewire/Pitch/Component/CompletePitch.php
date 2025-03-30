<?php

namespace App\Livewire\Pitch\Component;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use Livewire\Component;
use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\Toaster;
use App\Services\NotificationService;
use App\Models\Project;
use App\Models\PitchFeedback;
use App\Services\PitchCompletionService;
use Illuminate\Auth\Access\AuthorizationException;
use App\Exceptions\Pitch\CompletionValidationException;
use App\Services\PitchWorkflowService;
use App\Helpers\RouteHelpers;

class CompletePitch extends Component
{
    public $pitch;
    public $feedback = '';
    public $errors = [];
    public $hasOtherApprovedPitches = false;
    public $otherApprovedPitchesCount = 0;
    public $showCompletionModal = false;
    public $finalComments = '';
    public $rating = null;
    public $hasCompletedPitch = false;

    public function mount(Pitch $pitch, bool $hasCompletedPitch = false)
    {
        $this->pitch = $pitch;
        $this->project = $pitch->project;
        $this->hasCompletedPitch = $hasCompletedPitch;
        $this->checkForOtherApprovedPitches();
    }

    /**
     * Check if there are other approved pitches for this project
     */
    protected function checkForOtherApprovedPitches()
    {
        $this->otherApprovedPitchesCount = $this->pitch->project->pitches()
            ->where('status', Pitch::STATUS_APPROVED)
            ->where('id', '!=', $this->pitch->id)
            ->count();

        $this->hasOtherApprovedPitches = $this->otherApprovedPitchesCount > 0;
    }

    /**
     * Check if the user is authorized to complete the pitch
     *
     * @throws UnauthorizedActionException
     * @return bool
     */
    public function isAuthorized()
    {
        $isAuthorized = Auth::check() &&
            $this->pitch->project->user_id === Auth::id() &&
            $this->pitch->status === Pitch::STATUS_APPROVED;

        if (!$isAuthorized) {
            throw new UnauthorizedActionException(
                'complete',
                'You are not authorized to complete this pitch'
            );
        }

        return true;
    }

    /**
     * Open the completion modal
     */
    public function openCompletionModal()
    {
        try {
            // Log that the method was called
            \Log::info('openCompletionModal called', [
                'pitch_id' => $this->pitch->id,
                'current_modal_state' => $this->showCompletionModal
            ]);

            // Check if the user is authorized
            $this->isAuthorized();

            // Check if the pitch can be completed
            $this->pitch->canComplete();

            // Update our local properties based on what canComplete() found
            $this->checkForOtherApprovedPitches();

            // Show the modal directly - ensure we're setting this to true
            $this->showCompletionModal = true;

            // Log the final state
            \Log::info('Modal should be open now', [
                'modal_state' => $this->showCompletionModal
            ]);
        } catch (UnauthorizedActionException $e) {
            Toaster::error($e->getMessage());
            Log::error('Unauthorized attempt to complete pitch', [
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            Toaster::error($e->getMessage());
            Log::error('Invalid pitch completion attempt', [
                'pitch_id' => $this->pitch->id,
                'status' => $this->pitch->status,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Toaster::error('An unexpected error occurred');
            Log::error('Error in pitch completion request', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Close the completion modal
     */
    public function closeCompletionModal()
    {
        $this->showCompletionModal = false;
    }

    /**
     * Complete a pitch directly (used by the button in the modal)
     * Refactored to use PitchCompletionService.
     */
    public function debugComplete(PitchCompletionService $pitchCompletionService)
    {
        Log::info('CompletePitch::debugComplete called', [
            'pitch_id' => $this->pitch->id
        ]);

        try {
            // Authorize the action using PitchPolicy
            $this->authorize('complete', $this->pitch);

            // Call the service to handle the completion logic
            $completedPitch = $pitchCompletionService->completePitch(
                $this->pitch,
                auth()->user(),
                $this->feedback ?: null // Pass feedback, or null if empty
            );

            // Refresh local pitch model state after service call
            $this->pitch->refresh();

            // Success feedback
            Toaster::success('Pitch has been completed successfully!');
            $this->dispatch('pitchStatusUpdated'); // Notify parent/other components

            // For paid projects, dispatch an event to open the payment modal
            // Check the status set by the service
            if ($this->pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING) {
                 Log::info('Dispatching openPaymentModal event for pitch.', ['pitch_id' => $this->pitch->id]);
                $this->dispatch('openPaymentModal');
            }

            // Close the modal after successful completion or if payment modal is triggered
            $this->closeCompletionModal();

            // Redirect back to the pitch show page (optional, could stay on manage page)
             return redirect()->route('projects.pitches.show', [
                 'project' => $this->pitch->project->slug,
                 'pitch' => $this->pitch->slug
             ]);

        } catch (AuthorizationException | UnauthorizedActionException $e) {
            Log::warning('Unauthorized pitch completion attempt', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $this->closeCompletionModal();
            Toaster::error('You are not authorized to complete this pitch.');
        } catch (CompletionValidationException $e) {
            Log::warning('Pitch completion validation failed', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            $this->closeCompletionModal();
            Toaster::error($e->getMessage()); // Show specific validation error
        } catch (\Exception $e) {
            Log::error('Error completing pitch via Livewire', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Optional for detailed debugging
            ]);
            $this->closeCompletionModal();
            Toaster::error('An unexpected error occurred while completing the pitch. Please try again.');
        }
    }

    /*
    protected function validateCompletionRequirements()
    {
        // Check if the user is authorized
        $this->isAuthorized();

        // Check if the pitch can be completed
        $this->pitch->canComplete();
    }
    */

    /**
     * Mark this pitch as completed
     */
    protected function markAsCompleted($feedback)
    {
        DB::beginTransaction();

        try {
            // Update pitch status and store completion feedback and date
            $this->pitch->update([
                'status' => Pitch::STATUS_COMPLETED,
                'completed_at' => now(),
                'completion_feedback' => $feedback,
                'completion_date' => now(),
                'payment_status' => $this->isFreeProject() ? Pitch::PAYMENT_STATUS_PAID : Pitch::PAYMENT_STATUS_PENDING,
            ]);

            // If this is a free project, mark as paid immediately with no payment required
            if ($this->isFreeProject()) {
                $this->pitch->update([
                    'final_invoice_id' => 'free_project',
                    'payment_amount' => 0,
                    'payment_completed_at' => now(),
                ]);
            }

            // Process the feedback if it exists - add as a comment for the audit trail
            if (!empty($feedback)) {
                $this->pitch->addComment('Completion feedback: ' . $feedback);
            }

            // Add debug logging to verify feedback is stored correctly
            \Log::info('Completion feedback stored', [
                'pitch_id' => $this->pitch->id,
                'feedback' => $feedback,
                'stored_feedback' => $this->pitch->refresh()->completion_feedback
            ]);

            // Send notifications
            $notificationService = app(NotificationService::class);

            // Notify the pitch creator that their pitch was completed
            $notificationService->notifyPitchCompleted(
                $this->pitch,
                'Your pitch for ' . $this->pitch->project->name . ' has been marked as completed.'
            );

            // Notify project owner if pitch creator completed the pitch
            if (auth()->id() === $this->pitch->user_id && auth()->id() !== $this->pitch->project->user_id) {
                $notificationService->notifyPitchCompleted(
                    $this->pitch,
                    $this->pitch->user->name . ' marked their pitch for ' . $this->pitch->project->name . ' as completed.'
                );
            }

            DB::commit();

            // Session message
            session()->flash('success', 'The pitch has been marked as completed.');

            // Reload the page to show the completed state
            return redirect()->route('projects.pitches.show', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking pitch as completed: ' . $e->getMessage(), [
                'pitch_id' => $this->pitch->id,
                'exception' => $e,
            ]);

            // Show error message
            Toaster::error('There was an error completing the pitch. Please try again.');
        }

        $this->showCompletionModal = false;
    }

    /**
     * Close other active pitches (Step 3)
     */
    protected function closeOtherPitches()
    {
        DB::beginTransaction();
        try {
            // Re-get the project to ensure it's fresh
            $project = Project::find($this->pitch->project_id);

            // Get IDs of pitches to be closed for logging
            $pitchesToClose = $project->pitches()
                ->where('id', '!=', $this->pitch->id)
                ->whereNotIn('status', [Pitch::STATUS_CLOSED, Pitch::STATUS_COMPLETED])
                ->pluck('id');

            \Log::info('About to close other pitches', [
                'pitch_being_completed' => $this->pitch->id,
                'pitches_to_close' => $pitchesToClose,
                'other_approved_count' => $this->otherApprovedPitchesCount
            ]);

            // Update the pitches directly in the database to avoid race conditions
            $updateResult = DB::table('pitches')
                ->where('project_id', $project->id)
                ->where('id', '!=', $this->pitch->id)
                ->whereNotIn('status', [Pitch::STATUS_CLOSED, Pitch::STATUS_COMPLETED])
                ->update(['status' => Pitch::STATUS_CLOSED]);

            \Log::info('Closed other pitches', [
                'update_result' => $updateResult
            ]);

            // Refresh to get updated data
            $project->refresh();

            // Process the closed pitches for events and snapshots
            foreach ($project->pitches()->where('id', '!=', $this->pitch->id)->get() as $otherPitch) {
                if ($otherPitch->status === Pitch::STATUS_CLOSED) {
                    // Create a status change event
                    $otherPitch->events()->create([
                        'event_type' => 'status_change',
                        'comment' => 'Pitch automatically closed because another pitch was completed',
                        'status' => Pitch::STATUS_CLOSED,
                        'created_by' => auth()->id(),
                    ]);

                    // Find and decline pending snapshots
                    $pendingSnapshots = $otherPitch->snapshots()->where('status', 'pending')->get();
                    foreach ($pendingSnapshots as $pendingSnapshot) {
                        $pendingSnapshot->status = 'denied';
                        $pendingSnapshot->save();

                        // Create an event for the declined snapshot
                        $otherPitch->events()->create([
                            'event_type' => 'snapshot_status_change',
                            'comment' => 'Snapshot automatically declined because the pitch was closed',
                            'status' => 'denied',
                            'snapshot_id' => $pendingSnapshot->id,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            DB::commit();
            \Log::info('Other pitches closed successfully', [
                'pitch_id' => $this->pitch->id,
                'closed_count' => $updateResult
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error closing other pitches', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw to be handled by the calling method
        }
    }

    /**
     * Send completion notifications (Step 5)
     */
    protected function sendCompletionNotifications($feedback)
    {
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->notifyPitchCompleted($this->pitch, $feedback);

            \Log::info('Completion notifications sent', [
                'pitch_id' => $this->pitch->id
            ]);
        } catch (\Exception $e) {
            // Log notification error but don't fail the process
            Log::error('Failed to create pitch completion notification', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        \Log::info('CompletePitch::render', [
            'pitch_id' => $this->pitch->id,
            'showCompletionModal' => $this->showCompletionModal,
            'hasOtherApprovedPitches' => $this->hasOtherApprovedPitches
        ]);

        return view('livewire.pitch.component.complete-pitch');
    }
}
