<?php

namespace App\Livewire\Project;

use App\Exceptions\Pitch\CompletionValidationException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Models\Pitch;
use App\Services\PitchCompletionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CompletePitchModal extends Component
{
    public $showModal = false;
    public $pitch = null;
    public $pitchId = null;
    public $pitchTitle = '';
    public $projectTitle = '';
    public $hasOtherApprovedPitches = false;
    public $otherApprovedPitchesCount = 0;
    public $projectBudget = 0;
    public $feedback = '';
    public $rating = null;

    protected $listeners = [
        'openCompletePitchModal' => 'openModal',
        'pitchStatusUpdated' => 'handlePitchUpdated'
    ];

    protected $rules = [
        'feedback' => 'nullable|string|max:5000',
        'rating' => ['required', 'integer', 'between:1,5'],
    ];

    protected $messages = [
        'rating.required' => 'Please provide a rating for the producer.',
        'rating.integer' => 'Rating must be a whole number.',
        'rating.between' => 'Rating must be between 1 and 5 stars.',
    ];

    public function openModal($data)
    {
        $this->pitchId = $data['pitchId'];
        $this->pitchTitle = $data['pitchTitle'];
        $this->projectTitle = $data['projectTitle'];
        $this->hasOtherApprovedPitches = $data['hasOtherApprovedPitches'];
        $this->otherApprovedPitchesCount = $data['otherApprovedPitchesCount'];
        $this->projectBudget = $data['projectBudget'];
        
        // Load the pitch model
        $this->pitch = Pitch::findOrFail($this->pitchId);
        
        // Reset form data
        $this->feedback = '';
        $this->rating = null;
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['pitch', 'pitchId', 'pitchTitle', 'projectTitle', 'hasOtherApprovedPitches', 'otherApprovedPitchesCount', 'projectBudget', 'feedback', 'rating']);
    }

    public function completePitch(PitchCompletionService $pitchCompletionService)
    {
        $this->validate();

        try {
            // Authorize the action using PitchPolicy
            $this->authorize('complete', $this->pitch);

            // Call the service to handle the completion logic
            $completedPitch = $pitchCompletionService->completePitch(
                $this->pitch,
                auth()->user(),
                $this->feedback ?: null,
                $this->rating
            );

            // Success feedback
            Toaster::success('Pitch has been completed successfully!');

            // For paid projects, redirect to payment overview if needed
            if ($completedPitch->payment_status === Pitch::PAYMENT_STATUS_PENDING) {
                return redirect()->route('projects.pitches.payment.overview', [
                    'project' => $completedPitch->project,
                    'pitch' => $completedPitch
                ]);
            }

            // For all other cases, refresh the current page to update all components
            return redirect()->route('projects.manage', $completedPitch->project);

        } catch (AuthorizationException | UnauthorizedActionException $e) {
            Log::warning('Unauthorized pitch completion attempt', [
                'pitch_id' => $this->pitchId, 
                'user_id' => auth()->id(), 
                'error' => $e->getMessage()
            ]);
            $this->closeModal();
            Toaster::error('You are not authorized to complete this pitch.');
        } catch (CompletionValidationException $e) {
            Log::warning('Pitch completion validation failed', [
                'pitch_id' => $this->pitchId, 
                'error' => $e->getMessage()
            ]);
            $this->closeModal();
            Toaster::error($e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let Livewire handle validation errors
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error completing pitch', [
                'pitch_id' => $this->pitchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->closeModal();
            Toaster::error('An unexpected error occurred while completing the pitch.');
        }
    }

    public function handlePitchUpdated()
    {
        // Refresh the pitch data if modal is open
        if ($this->showModal && $this->pitch) {
            $this->pitch->refresh();
        }
    }

    public function render()
    {
        return view('livewire.project.complete-pitch-modal');
    }
} 