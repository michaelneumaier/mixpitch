<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\ContestJudgingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ContestJudging extends Component
{
    public Project $project;

    public $contestEntries;

    public $contestResult;

    public $placements = []; // Array to track dropdown selections

    public $isFinalized = false;

    public $canFinalize = false;

    public $showFinalizeModal = false;

    public $finalizationNotes = '';

    protected $listeners = [
        'refreshJudging' => '$refresh',
        'placementUpdated' => '$refresh',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadJudgingData();
    }

    public function loadJudgingData()
    {
        // Load contest entries with relationships
        $this->contestEntries = $this->project->getContestEntries();

        // Load contest result
        $this->contestResult = $this->project->contestResult;

        // Check if judging is finalized
        $this->isFinalized = $this->project->isJudgingFinalized();

        // Check if judging can be finalized
        $this->canFinalize = $this->project->canFinalizeJudging();

        // Load current placements into dropdown array
        $this->loadCurrentPlacements();
    }

    protected function loadCurrentPlacements()
    {
        $this->placements = [];

        foreach ($this->contestEntries as $entry) {
            $this->placements[$entry->id] = $entry->rank ?? '';
        }
    }

    public function updatePlacement($pitchId, $placement)
    {
        try {
            // Authorize action using policy
            $this->authorize('setContestPlacements', $this->project);

            $pitch = Pitch::findOrFail($pitchId);

            // Additional authorization for the specific pitch
            $this->authorize('setContestPlacement', $pitch);

            // Check if judging is finalized
            if ($this->isFinalized) {
                Toaster::error('Cannot modify placements after judging has been finalized.');

                return;
            }

            $judgingService = app(ContestJudgingService::class);

            // Update placement
            $result = $judgingService->setPlacement(
                $this->project,
                $pitch,
                $placement ?: null
            );

            if ($result) {
                // Update local placements array
                $this->placements[$pitchId] = $placement;

                // Reload judging data to get fresh state
                $this->loadJudgingData();

                $placementLabel = $placement ? match ($placement) {
                    Pitch::RANK_FIRST => '1st Place',
                    Pitch::RANK_SECOND => '2nd Place',
                    Pitch::RANK_THIRD => '3rd Place',
                    Pitch::RANK_RUNNER_UP => 'Runner-up',
                    default => $placement
                } : 'No Placement';

                Toaster::success("Placement updated to: {$placementLabel}");

                // Emit event for other components
                $this->dispatch('placementUpdated');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to judge this contest.');
        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Toaster::error('Failed to update placement. Please try again.');
        }
    }

    public function getAvailablePlacementsForPitch($pitchId)
    {
        $pitch = $this->contestEntries->firstWhere('id', $pitchId);
        if (! $pitch) {
            return [];
        }

        $judgingService = app(ContestJudgingService::class);

        return $judgingService->getAvailablePlacementsForPitch($this->project, $pitch);
    }

    public function openFinalizeModal()
    {
        if (! $this->canFinalize) {
            Toaster::error('Contest judging cannot be finalized at this time.');

            return;
        }

        $this->showFinalizeModal = true;
        $this->finalizationNotes = '';
    }

    public function closeFinalizeModal()
    {
        $this->showFinalizeModal = false;
        $this->finalizationNotes = '';
    }

    public function finalizeJudging()
    {
        try {
            // Authorize action using policy
            $this->authorize('finalizeContestJudging', $this->project);

            $judgingService = app(ContestJudgingService::class);

            $result = $judgingService->finalizeJudging(
                $this->project,
                Auth::user(),
                $this->finalizationNotes ?: null
            );

            if ($result) {
                $this->loadJudgingData();
                $this->closeFinalizeModal();

                Toaster::success('Contest judging has been finalized! All participants have been notified.');

                // Emit event for other components
                $this->dispatch('judgingFinalized');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to finalize this contest.');
        } catch (\InvalidArgumentException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Toaster::error('Failed to finalize judging. Please try again.');
        }
    }

    public function getWinnersSummary()
    {
        if (! $this->contestResult) {
            return null;
        }

        $summary = [
            'first_place' => null,
            'second_place' => null,
            'third_place' => null,
            'runner_ups' => [],
        ];

        if ($this->contestResult->first_place_pitch_id) {
            $summary['first_place'] = $this->contestEntries->firstWhere('id', $this->contestResult->first_place_pitch_id);
        }

        if ($this->contestResult->second_place_pitch_id) {
            $summary['second_place'] = $this->contestEntries->firstWhere('id', $this->contestResult->second_place_pitch_id);
        }

        if ($this->contestResult->third_place_pitch_id) {
            $summary['third_place'] = $this->contestEntries->firstWhere('id', $this->contestResult->third_place_pitch_id);
        }

        if ($this->contestResult->runner_up_pitch_ids) {
            foreach ($this->contestResult->runner_up_pitch_ids as $runnerUpId) {
                $runnerUp = $this->contestEntries->firstWhere('id', $runnerUpId);
                if ($runnerUp) {
                    $summary['runner_ups'][] = $runnerUp;
                }
            }
        }

        return $summary;
    }

    #[On('refreshJudging')]
    public function refreshJudging()
    {
        $this->loadJudgingData();
    }

    public function render()
    {
        $winnersSummary = $this->getWinnersSummary();

        return view('livewire.project.component.contest-judging', [
            'winnersSummary' => $winnersSummary,
        ]);
    }
}
