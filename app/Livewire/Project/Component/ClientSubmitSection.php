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
     * Get producer-uploaded files (pitch files)
     */
    #[Computed]
    public function producerFiles()
    {
        return $this->pitch->files()->with('pitch')->get();
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
     * Recall submission and return to in progress status
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
                // Update pitch status
                $this->pitch->status = Pitch::STATUS_IN_PROGRESS;
                $this->pitch->save();

                // Create event to track the recall
                $this->pitch->events()->create([
                    'created_by' => Auth::id(),
                    'event_type' => 'submission_recalled',
                    'status' => Pitch::STATUS_IN_PROGRESS,
                    'comment' => 'Producer recalled submission to make changes',
                ]);
            });

            Toaster::success('Submission recalled successfully. You can now make changes and resubmit.');
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

    public function render()
    {
        return view('livewire.project.component.client-submit-section');
    }
}
