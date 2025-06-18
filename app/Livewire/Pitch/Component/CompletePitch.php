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
    public $hasOtherApprovedPitches = false;
    public $otherApprovedPitchesCount = 0;
    public $hasCompletedPitch = false;



    public function mount(Pitch $pitch, bool $hasCompletedPitch = false)
    {
        $this->pitch = $pitch;
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
     * Open the completion modal by dispatching a global event
     */
    public function openCompletionModal()
    {
        try {
            // Check if the user is authorized
            $this->isAuthorized();

            // Update our local properties based on what canComplete() found
            $this->checkForOtherApprovedPitches();

            // Dispatch global event to open the modal with pitch data
            $this->dispatch('openCompletePitchModal', [
                'pitchId' => $this->pitch->id,
                'pitchTitle' => $this->pitch->title ?? 'Untitled Pitch',
                'projectTitle' => $this->pitch->project->title,
                'hasOtherApprovedPitches' => $this->hasOtherApprovedPitches,
                'otherApprovedPitchesCount' => $this->otherApprovedPitchesCount,
                'projectBudget' => $this->pitch->project->budget ?? 0
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



    public function render()
    {
        return view('livewire.pitch.component.complete-pitch');
    }
}
