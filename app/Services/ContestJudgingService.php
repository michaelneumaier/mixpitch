<?php

namespace App\Services;

use App\Models\ContestResult;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContestJudgingService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Set a pitch's placement in the contest
     */
    public function setPlacement(Project $project, Pitch $pitch, ?string $placement): bool
    {
        try {
            \Log::info('ContestJudgingService setPlacement called', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'placement' => $placement,
                'current_pitch_rank' => $pitch->rank,
            ]);

            // Validate that this is a contest project
            if (! $project->isContest()) {
                throw new \InvalidArgumentException('Project must be a contest to set placements');
            }

            // Validate that judging hasn't been finalized
            if ($project->isJudgingFinalized()) {
                throw new \InvalidArgumentException('Cannot modify placements after judging has been finalized');
            }

            // Validate placement value
            if ($placement && ! in_array($placement, [Pitch::RANK_FIRST, Pitch::RANK_SECOND, Pitch::RANK_THIRD, Pitch::RANK_RUNNER_UP])) {
                throw new \InvalidArgumentException('Invalid placement value: '.$placement);
            }

            // Validate pitch status
            $eligibleStatuses = [Pitch::STATUS_CONTEST_ENTRY, Pitch::STATUS_CONTEST_WINNER, Pitch::STATUS_CONTEST_RUNNER_UP];
            if (! in_array($pitch->status, $eligibleStatuses)) {
                throw new \InvalidArgumentException('Pitch is not eligible for contest placement. Current status: '.$pitch->status);
            }

            $result = DB::transaction(function () use ($project, $pitch, $placement) {
                // Get or create contest result
                $contestResult = $project->contestResult()->firstOrCreate([
                    'project_id' => $project->id,
                ], [
                    'show_submissions_publicly' => true,
                ]);

                \Log::info('Contest result retrieved/created', [
                    'contest_result_id' => $contestResult->id,
                    'project_id' => $project->id,
                ]);

                // If removing placement, clear it
                if (! $placement) {
                    $this->clearPlacement($contestResult, $pitch);
                    $pitch->update(['rank' => null]);

                    \Log::info('Placement cleared', [
                        'project_id' => $project->id,
                        'pitch_id' => $pitch->id,
                    ]);

                    return true;
                }

                // Handle exclusive placements (1st, 2nd, 3rd)
                if (in_array($placement, [Pitch::RANK_FIRST, Pitch::RANK_SECOND, Pitch::RANK_THIRD])) {
                    $this->setExclusivePlacement($contestResult, $pitch, $placement);
                } else {
                    // Handle runner-up placement
                    $this->setRunnerUpPlacement($contestResult, $pitch);
                }

                // Update the pitch rank
                $pitch->update(['rank' => $placement]);

                \Log::info('Placement set successfully', [
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'placement' => $placement,
                ]);

                return true;
            });

            return $result;

        } catch (\InvalidArgumentException $e) {
            \Log::error('ContestJudgingService validation error', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'placement' => $placement,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('ContestJudgingService unexpected error', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'placement' => $placement,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \InvalidArgumentException('Failed to set placement: '.$e->getMessage());
        }
    }

    /**
     * Clear a pitch's placement from contest results
     */
    protected function clearPlacement(ContestResult $contestResult, Pitch $pitch): void
    {
        $pitchId = $pitch->id;

        // Clear from exclusive placements
        if ($contestResult->first_place_pitch_id === $pitchId) {
            $contestResult->update(['first_place_pitch_id' => null]);
        }
        if ($contestResult->second_place_pitch_id === $pitchId) {
            $contestResult->update(['second_place_pitch_id' => null]);
        }
        if ($contestResult->third_place_pitch_id === $pitchId) {
            $contestResult->update(['third_place_pitch_id' => null]);
        }

        // Clear from runner-ups
        $runnerUps = $contestResult->runner_up_pitch_ids ?? [];
        if (in_array($pitchId, $runnerUps)) {
            $runnerUps = array_values(array_filter($runnerUps, fn ($id) => $id !== $pitchId));
            $contestResult->update(['runner_up_pitch_ids' => $runnerUps]);
        }
    }

    /**
     * Set an exclusive placement (1st, 2nd, 3rd)
     */
    protected function setExclusivePlacement(ContestResult $contestResult, Pitch $pitch, string $placement): void
    {
        $pitchId = $pitch->id;

        // First, clear this pitch from any existing placements
        $this->clearPlacement($contestResult, $pitch);

        // Clear any existing pitch from this placement and set the new one
        $field = match ($placement) {
            Pitch::RANK_FIRST => 'first_place_pitch_id',
            Pitch::RANK_SECOND => 'second_place_pitch_id',
            Pitch::RANK_THIRD => 'third_place_pitch_id',
        };

        // If there was a previous pitch in this placement, clear their rank
        if ($contestResult->$field) {
            $previousPitch = Pitch::find($contestResult->$field);
            if ($previousPitch) {
                $previousPitch->update(['rank' => null]);
            }
        }

        $contestResult->update([$field => $pitchId]);
    }

    /**
     * Set runner-up placement
     */
    protected function setRunnerUpPlacement(ContestResult $contestResult, Pitch $pitch): void
    {
        $pitchId = $pitch->id;

        // First, clear this pitch from any existing placements
        $this->clearPlacement($contestResult, $pitch);

        // Add to runner-ups if not already there
        $runnerUps = $contestResult->runner_up_pitch_ids ?? [];
        if (! in_array($pitchId, $runnerUps)) {
            $runnerUps[] = $pitchId;
            $contestResult->update(['runner_up_pitch_ids' => $runnerUps]);
        }
    }

    /**
     * Get available placement options for a specific pitch
     */
    public function getAvailablePlacementsForPitch(Project $project, Pitch $pitch): array
    {
        $contestResult = $project->contestResult;
        $placements = [
            '' => 'No Placement',
            Pitch::RANK_RUNNER_UP => 'Runner-up',
        ];

        if (! $contestResult) {
            // All placements available if no contest result exists yet
            $placements[Pitch::RANK_FIRST] = '1st Place';
            $placements[Pitch::RANK_SECOND] = '2nd Place';
            $placements[Pitch::RANK_THIRD] = '3rd Place';
        } else {
            // Check which exclusive placements are available
            if (! $contestResult->first_place_pitch_id || $contestResult->first_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_FIRST] = '1st Place';
            } else {
                $placements[Pitch::RANK_FIRST] = '1st Place (Already Chosen)';
            }

            if (! $contestResult->second_place_pitch_id || $contestResult->second_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_SECOND] = '2nd Place';
            } else {
                $placements[Pitch::RANK_SECOND] = '2nd Place (Already Chosen)';
            }

            if (! $contestResult->third_place_pitch_id || $contestResult->third_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_THIRD] = '3rd Place';
            } else {
                $placements[Pitch::RANK_THIRD] = '3rd Place (Already Chosen)';
            }
        }

        return $placements;
    }

    /**
     * Get only truly available placement options for a specific pitch (excludes already taken)
     */
    public function getAvailablePlacementsOnly(Project $project, Pitch $pitch): array
    {
        $contestResult = $project->contestResult;
        $placements = [
            '' => 'No Placement',
            Pitch::RANK_RUNNER_UP => 'Runner-up',
        ];

        if (! $contestResult) {
            // All placements available if no contest result exists yet
            $placements[Pitch::RANK_FIRST] = '1st Place';
            $placements[Pitch::RANK_SECOND] = '2nd Place';
            $placements[Pitch::RANK_THIRD] = '3rd Place';
        } else {
            // Only include placements that are truly available
            if (! $contestResult->first_place_pitch_id || $contestResult->first_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_FIRST] = '1st Place';
            }

            if (! $contestResult->second_place_pitch_id || $contestResult->second_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_SECOND] = '2nd Place';
            }

            if (! $contestResult->third_place_pitch_id || $contestResult->third_place_pitch_id === $pitch->id) {
                $placements[Pitch::RANK_THIRD] = '3rd Place';
            }
        }

        return $placements;
    }

    /**
     * Finalize contest judging
     */
    public function finalizeJudging(Project $project, User $judge, ?string $notes = null): bool
    {
        // Validate that this is a contest project
        if (! $project->isContest()) {
            throw new \InvalidArgumentException('Project must be a contest to finalize judging');
        }

        // Validate that judging can be finalized
        if (! $project->canFinalizeJudging()) {
            throw new \InvalidArgumentException('Contest judging cannot be finalized yet');
        }

        return DB::transaction(function () use ($project, $judge, $notes) {
            // Update project with finalization details and set status to completed
            $project->update([
                'judging_finalized_at' => now(),
                'judging_notes' => $notes,
                'status' => Project::STATUS_COMPLETED,
            ]);

            // Update contest result
            $project->load('contestResult'); // Refresh the relationship
            $contestResult = $project->contestResult;

            if ($contestResult) {
                $contestResult->update([
                    'finalized_at' => now(),
                    'finalized_by' => $judge->id,
                ]);

                // Update pitch statuses and send notifications
                $this->updatePitchStatusesAndNotify($project, $contestResult);
            }

            return true;
        });
    }

    /**
     * Update pitch statuses based on final results and send notifications
     */
    protected function updatePitchStatusesAndNotify(Project $project, ContestResult $contestResult): void
    {
        $allEntries = $project->getContestEntries();
        $placedPitchIds = $contestResult->getPlacedPitchIds();

        foreach ($allEntries as $pitch) {
            $placement = $contestResult->hasPlacement($pitch->id);

            if ($placement) {
                // Update winners and runner-ups
                if ($placement === '1st') {
                    $pitch->update([
                        'status' => Pitch::STATUS_CONTEST_WINNER,
                        'placement_finalized_at' => now(),
                    ]);

                    // Use appropriate notification method based on prize
                    $hasPrize = $project->prize_amount > 0;
                    if ($hasPrize) {
                        $this->notificationService->notifyContestWinnerSelected($pitch);
                    } else {
                        $this->notificationService->notifyContestWinnerSelectedNoPrize($pitch);
                    }
                } elseif ($placement === '2nd') {
                    $pitch->update([
                        'status' => Pitch::STATUS_CONTEST_WINNER,
                        'placement_finalized_at' => now(),
                    ]);

                    // Use appropriate notification method based on prize
                    $hasPrize = $project->prize_amount > 0;
                    if ($hasPrize) {
                        $this->notificationService->notifyContestWinnerSelected($pitch);
                    } else {
                        $this->notificationService->notifyContestWinnerSelectedNoPrize($pitch);
                    }
                } elseif ($placement === '3rd') {
                    $pitch->update([
                        'status' => Pitch::STATUS_CONTEST_WINNER,
                        'placement_finalized_at' => now(),
                    ]);

                    // Use appropriate notification method based on prize
                    $hasPrize = $project->prize_amount > 0;
                    if ($hasPrize) {
                        $this->notificationService->notifyContestWinnerSelected($pitch);
                    } else {
                        $this->notificationService->notifyContestWinnerSelectedNoPrize($pitch);
                    }
                } elseif ($placement === 'runner-up') {
                    $pitch->update([
                        'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
                        'placement_finalized_at' => now(),
                    ]);

                    // Note: This method might not exist yet, but we'll create it or use existing one
                    // For now, we'll use the not selected notification as a fallback
                    $this->notificationService->notifyContestEntryNotSelected($pitch);
                }
            } else {
                // Update non-selected entries
                $pitch->update([
                    'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
                    'placement_finalized_at' => now(),
                ]);
                $this->notificationService->notifyContestEntryNotSelected($pitch);
            }
        }
    }

    /**
     * Check if a project can have its judging reopened (admin only)
     */
    public function canReopenJudging(Project $project, User $user): bool
    {
        return $project->isJudgingFinalized() && $user->hasRole('admin');
    }

    /**
     * Reopen judging for a contest (admin only)
     */
    public function reopenJudging(Project $project, User $admin): bool
    {
        if (! $this->canReopenJudging($project, $admin)) {
            throw new \InvalidArgumentException('Cannot reopen judging for this contest');
        }

        return DB::transaction(function () use ($project) {
            // Determine appropriate status to revert to
            $revertStatus = $project->is_published ? Project::STATUS_OPEN : Project::STATUS_UNPUBLISHED;

            // Clear finalization timestamps and revert project status
            $project->update([
                'judging_finalized_at' => null,
                'judging_notes' => null,
                'status' => $revertStatus,
            ]);

            $contestResult = $project->contestResult;
            if ($contestResult) {
                $contestResult->update([
                    'finalized_at' => null,
                    'finalized_by' => null,
                ]);

                // Revert all contest entries back to contest_entry status
                $project->pitches()
                    ->whereIn('status', [
                        Pitch::STATUS_CONTEST_WINNER,
                        Pitch::STATUS_CONTEST_RUNNER_UP,
                        Pitch::STATUS_CONTEST_NOT_SELECTED,
                    ])
                    ->update([
                        'status' => Pitch::STATUS_CONTEST_ENTRY,
                        'placement_finalized_at' => null,
                    ]);
            }

            return true;
        });
    }

    /**
     * Formally announce contest results (to achieve 100% completion)
     */
    public function announceResults(Project $project, User $announcer): bool
    {
        // Validate that this is a contest project
        if (! $project->isContest()) {
            throw new \InvalidArgumentException('Project must be a contest to announce results');
        }

        // Validate that judging has been finalized
        if (! $project->isJudgingFinalized()) {
            throw new \InvalidArgumentException('Contest judging must be finalized before announcing results');
        }

        // Check if results are already announced
        if ($project->results_announced_at) {
            throw new \InvalidArgumentException('Contest results have already been announced');
        }

        return DB::transaction(function () use ($project, $announcer) {
            // Update project with announcement timestamp
            $project->update([
                'results_announced_at' => now(),
                'results_announced_by' => $announcer->id,
            ]);

            // Send final announcement notifications to all participants
            $this->sendAnnouncementNotifications($project);

            return true;
        });
    }

    /**
     * Send final announcement notifications to all contest participants
     */
    protected function sendAnnouncementNotifications(Project $project): void
    {
        $allEntries = $project->getContestEntries();

        foreach ($allEntries as $pitch) {
            try {
                // Send announcement notification based on status
                switch ($pitch->status) {
                    case Pitch::STATUS_CONTEST_WINNER:
                        $this->notificationService->notifyContestResultsAnnounced($pitch, 'winner');
                        break;
                    case Pitch::STATUS_CONTEST_RUNNER_UP:
                        $this->notificationService->notifyContestResultsAnnounced($pitch, 'runner_up');
                        break;
                    case Pitch::STATUS_CONTEST_NOT_SELECTED:
                        $this->notificationService->notifyContestResultsAnnounced($pitch, 'not_selected');
                        break;
                }
            } catch (\Exception $e) {
                // Log error but don't stop the process
                \Log::error('Failed to send announcement notification', [
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify the contest organizer
        try {
            $this->notificationService->notifyContestResultsAnnouncedOrganizer($project);
        } catch (\Exception $e) {
            \Log::error('Failed to send organizer announcement notification', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if contest results have been announced
     */
    public function areResultsAnnounced(Project $project): bool
    {
        return ! is_null($project->results_announced_at);
    }
}
