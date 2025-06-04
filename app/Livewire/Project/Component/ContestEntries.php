<?php

namespace App\Livewire\Project\Component; // Placing in a subdirectory

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class ContestEntries extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    public Project $project;
    public $selectedPitchId = null;
    public $rankToAssign = 2; // For runner-up selection

    // Listener to refresh when a winner is selected
    protected $listeners = ['contestWinnerSelected' => '$refresh'];

    public function mount($project)
    {
        $this->project = $project;
        // Ensure this component only renders for contest workflow projects
        if (!$this->project->isContest()) {
            abort(404); // Or handle differently, e.g., don't render
        }
    }

    /**
     * Select a contest winner.
     */
    public function selectWinner($pitchId, PitchWorkflowService $workflowService)
    {
        $pitch = Pitch::findOrFail($pitchId);
        $this->authorize('selectWinner', $pitch); // Use PitchPolicy

        try {
            $workflowService->selectContestWinner($pitch, auth()->user());
            Toaster::success('Contest winner selected successfully!');
            
            // Dispatch event to refresh using the correct format for the test
            $this->dispatch('contestWinnerSelected');
            
            // Optionally redirect or update UI state
        } catch (\App\Exceptions\Payment\InvoiceCreationException $e) {
            Log::error('Contest winner selection failed during invoice creation', ['error' => $e->getMessage()]);
            Toaster::error('Winner selected, but failed to create prize invoice. Please contact support.');
        } catch (\Exception $e) {
            Log::error('Error selecting contest winner', ['error' => $e->getMessage()]);
            Toaster::error('An error occurred while selecting the winner: ' . $e->getMessage());
        }
    }

    /**
     * Select a contest runner-up (Optional Feature).
     */
    public function selectRunnerUp($pitchId, PitchWorkflowService $workflowService)
    {
        $this->validate(['rankToAssign' => 'required|integer|min:2']);
        $pitch = Pitch::findOrFail($pitchId);
        $this->authorize('selectRunnerUp', $pitch); // Use PitchPolicy

        try {
            $workflowService->selectContestRunnerUp($pitch, auth()->user(), $this->rankToAssign);
            Toaster::success('Contest runner-up (Rank: ' . $this->rankToAssign . ') selected.');
            $this->rankToAssign = 2; // Reset for next potential selection
            
            // Dispatch an event for both tests and UI refresh
            $this->dispatch('contestRunnerUpSelected'); 
            $this->dispatch('contestWinnerSelected'); // Also dispatch this for the list refresh
        } catch (\Exception $e) {
            Log::error('Error selecting contest runner-up', ['error' => $e->getMessage()]);
            Toaster::error('An error occurred while selecting the runner-up: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Fetch contest entries, ordered perhaps by submission date
        $entries = $this->project->pitches()
            ->whereIn('status', [
                Pitch::STATUS_CONTEST_ENTRY,
                Pitch::STATUS_CONTEST_WINNER,
                Pitch::STATUS_CONTEST_RUNNER_UP,
                Pitch::STATUS_CONTEST_NOT_SELECTED
            ])
            ->with(['user:id,name,profile_photo_path', 'currentSnapshot']) // Eager load user info and current snapshot
            ->orderBy('created_at', 'asc')
            ->paginate(10); // Add pagination

        // Check if a winner has already been selected
        $winnerExists = $this->project->pitches()->where('status', Pitch::STATUS_CONTEST_WINNER)->exists();
        
        // Check if judging is finalized
        $isFinalized = $this->project->isJudgingFinalized();

        return view('livewire.project.component.contest-entries', [
            'entries' => $entries,
            'winnerExists' => $winnerExists,
            'isFinalized' => $isFinalized,
            'canSelectWinner' => $this->project->submission_deadline ? $this->project->submission_deadline->isPast() : true // Allow selection if deadline passed or not set
        ]);
    }
} 