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
use Illuminate\Validation\Rule;

class CompletePitch extends Component
{
    public $pitch;
    public $feedback = '';
    public $hasOtherApprovedPitches = false;
    public $otherApprovedPitchesCount = 0;
    public $showCompletionModal = false;
    public $finalComments = '';
    public $rating = null;
    public $hasCompletedPitch = false;

    protected $rules = [
        'feedback' => 'nullable|string|max:5000',
        'rating' => ['required', 'integer', 'between:1,5'],
    ];

    protected $messages = [
        'rating.required' => 'Please provide a rating for the producer.',
        'rating.integer' => 'Rating must be a whole number.',
        'rating.between' => 'Rating must be between 1 and 5 stars.',
    ];

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
            'pitch_id' => $this->pitch->id,
            'rating' => $this->rating,
            'feedback' => $this->feedback
        ]);

        $this->validate();

        try {
            // Authorize the action using PitchPolicy
            $this->authorize('complete', $this->pitch);

            // Call the service to handle the completion logic, passing rating and feedback
            $completedPitch = $pitchCompletionService->completePitch(
                $this->pitch,
                auth()->user(),
                $this->feedback ?: null,
                $this->rating
            );

            // Refresh local pitch model state after service call
            $this->pitch->refresh();

            // Success feedback
            Toaster::success('Pitch has been completed successfully!');
            $this->dispatch('pitchStatusUpdated'); // Notify parent/other components

            // For paid projects, redirect to payment overview if needed
            if ($this->pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING) {
                Log::info('Redirecting to payment overview for pitch.', ['pitch_id' => $this->pitch->id]);
                $this->closeCompletionModal();
                // Use RouteHelpers for safer redirect generation
                return redirect(RouteHelpers::getPaymentOverviewUrl($this->pitch->project, $this->pitch));
            }

            // Close the modal after successful completion if not redirecting
            $this->closeCompletionModal();
            
            // No automatic redirect here anymore, let the page refresh or stay

        } catch (AuthorizationException | UnauthorizedActionException $e) {
            Log::warning('Unauthorized pitch completion attempt', ['pitch_id' => $this->pitch->id, 'user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $this->closeCompletionModal();
            Toaster::error('You are not authorized to complete this pitch.');
        } catch (CompletionValidationException $e) {
            Log::warning('Pitch completion validation failed', ['pitch_id' => $this->pitch->id, 'error' => $e->getMessage()]);
            $this->closeCompletionModal();
            Toaster::error($e->getMessage()); // Show specific validation error
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically handled by Livewire, but log them
            Log::warning('Pitch completion validation failed (Livewire)', ['pitch_id' => $this->pitch->id, 'errors' => $e->errors()]);
            // Optionally show a generic toaster message if needed, but errors should appear near fields
            // Toaster::error('Please correct the errors below.');
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
