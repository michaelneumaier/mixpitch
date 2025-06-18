<?php

namespace App\Livewire\Project\Component;

use Livewire\Component;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Services\ContestJudgingService;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;
use Illuminate\Auth\Access\AuthorizationException;

class ContestSnapshotJudging extends Component
{
    public Project $project;
    public Pitch $pitch;
    public PitchSnapshot $snapshot;
    public $currentPlacement;
    public $availablePlacements = [];
    public $isFinalized = false;
    public $canJudge = false;

    protected $listeners = [
        'placementUpdated' => 'refreshPlacement'
    ];

    public function mount(Project $project, Pitch $pitch, PitchSnapshot $snapshot)
    {
        \Log::info('ContestSnapshotJudging component mounting', [
            'project_id' => $project->id,
            'pitch_id' => $pitch->id,
            'snapshot_id' => $snapshot->id,
            'user_id' => Auth::id()
        ]);

        $this->project = $project;
        $this->pitch = $pitch;
        $this->snapshot = $snapshot;
        
        $this->loadJudgingData();
    }

    public function loadJudgingData()
    {
        try {
            // Check if user can judge this contest using policy
            $this->canJudge = Auth::check() && 
                             $this->project->isContest() && 
                             Auth::user()->can('judgeContest', $this->project);
            
            // Check if judging is finalized
            $this->isFinalized = $this->project->isJudgingFinalized();
            
            // Load current placement
            $this->currentPlacement = $this->pitch->rank ?? '';
            
            // Load available placements if user can judge
            if ($this->canJudge && !$this->isFinalized) {
                $judgingService = app(ContestJudgingService::class);
                $this->availablePlacements = $judgingService->getAvailablePlacementsForPitch($this->project, $this->pitch);
            }

            \Log::info('ContestSnapshotJudging data loaded', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'can_judge' => $this->canJudge,
                'is_finalized' => $this->isFinalized,
                'current_placement' => $this->currentPlacement,
                'available_placements' => $this->availablePlacements
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading ContestSnapshotJudging data', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Toaster::error('Error loading judging data. Please refresh the page.');
        }
    }

    public function updatePlacement($placement)
    {
        try {
            \Log::info('ContestSnapshotJudging updatePlacement called', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'placement' => $placement,
                'user_id' => Auth::id()
            ]);

            // Authorize action using policy
            $this->authorize('setContestPlacement', $this->pitch);

            // Check if judging is finalized
            if ($this->isFinalized) {
                Toaster::error('Cannot modify placements after judging has been finalized.');
                return;
            }

            // Additional validation
            if (!$this->canJudge) {
                Toaster::error('You are not authorized to judge this contest entry.');
                return;
            }

            $judgingService = app(ContestJudgingService::class);
            
            // Update placement
            $result = $judgingService->setPlacement(
                $this->project, 
                $this->pitch, 
                $placement ?: null
            );

            if ($result) {
                // Update local state
                $this->currentPlacement = $placement;
                $this->loadJudgingData(); // Refresh available placements
                
                $placementLabel = $placement ? match($placement) {
                    \App\Models\Pitch::RANK_FIRST => '1st Place',
                    \App\Models\Pitch::RANK_SECOND => '2nd Place',
                    \App\Models\Pitch::RANK_THIRD => '3rd Place',
                    \App\Models\Pitch::RANK_RUNNER_UP => 'Runner-up',
                    default => $placement
                } : 'No Placement';
                
                Toaster::success("Placement updated to: {$placementLabel}");
                
                // Emit event for other components
                $this->dispatch('placementUpdated', [
                    'pitch_id' => $this->pitch->id,
                    'pitch_slug' => $this->pitch->slug,
                    'placement' => $placement,
                    'placement_label' => $placementLabel
                ]);

                \Log::info('ContestSnapshotJudging placement updated successfully', [
                    'project_id' => $this->project->id,
                    'pitch_id' => $this->pitch->id,
                    'placement' => $placement,
                    'placement_label' => $placementLabel
                ]);
            } else {
                Toaster::error('Failed to update placement. Please try again.');
                \Log::warning('ContestSnapshotJudging placement update failed', [
                    'project_id' => $this->project->id,
                    'pitch_id' => $this->pitch->id,
                    'placement' => $placement
                ]);
            }
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::warning('ContestSnapshotJudging authorization failed', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            Toaster::error('You are not authorized to judge this contest entry.');
        } catch (\InvalidArgumentException $e) {
            \Log::error('ContestSnapshotJudging invalid argument', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('ContestSnapshotJudging unexpected error', [
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('Failed to update placement. Please try again.');
        }
    }

    public function refreshPlacement()
    {
        $this->pitch->refresh();
        $this->loadJudgingData();
    }

    public function getPlacementBadgeClass()
    {
        return match($this->currentPlacement) {
            '1st' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            '2nd' => 'bg-gray-100 text-gray-800 border-gray-200',
            '3rd' => 'bg-orange-100 text-orange-800 border-orange-200',
            'runner-up' => 'bg-blue-100 text-blue-800 border-blue-200',
            default => 'bg-gray-100 text-gray-600 border-gray-200'
        };
    }

    public function getPlacementIcon()
    {
        return match($this->currentPlacement) {
            '1st' => '🥇',
            '2nd' => '🥈',
            '3rd' => '🥉',
            'runner-up' => '🏅',
            default => '⭕'
        };
    }

    public function render()
    {
        return view('livewire.project.component.contest-snapshot-judging');
    }
}
