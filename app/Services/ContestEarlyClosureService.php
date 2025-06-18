<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContestEarlyClosureService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Close contest submissions early
     */
    public function closeContestEarly(Project $project, User $user, string $reason = null): bool
    {
        // Comprehensive validation
        $this->validateEarlyClosure($project, $user);

        return DB::transaction(function () use ($project, $user, $reason) {
            // Record the early closure
            $project->update([
                'submissions_closed_early_at' => now(),
                'submissions_closed_early_by' => $user->id,
                'early_closure_reason' => $reason
            ]);

            // Log the action
            Log::info('Contest closed early', [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'closed_by' => $user->id,
                'closed_by_name' => $user->name,
                'reason' => $reason,
                'original_deadline' => $project->submission_deadline?->toISOString(),
                'closed_at' => now()->toISOString(),
                'entries_count' => $project->getContestEntries()->count()
            ]);

            // Notify all participants about early closure
            $this->notifyParticipantsOfEarlyClosure($project, $reason);

            return true;
        });
    }

    /**
     * Reopen contest submissions (undo early closure)
     */
    public function reopenContestSubmissions(Project $project, User $user): bool
    {
        // Validate reopening
        $this->validateReopening($project, $user);

        return DB::transaction(function () use ($project, $user) {
            // Clear early closure data
            $project->update([
                'submissions_closed_early_at' => null,
                'submissions_closed_early_by' => null,
                'early_closure_reason' => null
            ]);

            // Log the action
            Log::info('Contest submissions reopened', [
                'project_id' => $project->id,
                'project_title' => $project->title,
                'reopened_by' => $user->id,
                'reopened_by_name' => $user->name,
                'original_deadline' => $project->submission_deadline?->toISOString(),
                'reopened_at' => now()->toISOString()
            ]);

            // Notify participants that submissions are open again
            $this->notifyParticipantsOfReopening($project);

            return true;
        });
    }

    /**
     * Validate that contest can be closed early
     */
    private function validateEarlyClosure(Project $project, User $user): void
    {
        if (!$project->isContest()) {
            throw new \InvalidArgumentException('Only contest projects can be closed early');
        }

        if ($user->id !== $project->user_id) {
            throw new \InvalidArgumentException('Only the contest owner can close submissions early');
        }

        if (!$project->canCloseEarly()) {
            if ($project->getContestEntries()->isEmpty()) {
                throw new \InvalidArgumentException('Cannot close contest early with no entries');
            }
            throw new \InvalidArgumentException('Contest cannot be closed early at this time');
        }

        // Additional business logic validation
        if ($project->getContestEntries()->isEmpty()) {
            throw new \InvalidArgumentException('Cannot close contest early with no entries');
        }

        // Check if deadline is too far away (optional business rule)
        if ($project->submission_deadline && $project->submission_deadline->diffInHours(now()) < 24) {
            throw new \InvalidArgumentException('Cannot close contest early when deadline is less than 24 hours away');
        }
    }

    /**
     * Validate that contest submissions can be reopened
     */
    private function validateReopening(Project $project, User $user): void
    {
        if (!$project->isContest()) {
            throw new \InvalidArgumentException('Only contest projects can have submissions reopened');
        }

        if ($user->id !== $project->user_id) {
            throw new \InvalidArgumentException('Only the contest owner can reopen submissions');
        }

        if (!$project->wasClosedEarly()) {
            throw new \InvalidArgumentException('Contest was not closed early');
        }

        if ($project->isJudgingFinalized()) {
            throw new \InvalidArgumentException('Cannot reopen submissions after judging has been finalized');
        }

        // Check if original deadline has passed
        if ($project->submission_deadline && $project->submission_deadline->isPast()) {
            throw new \InvalidArgumentException('Cannot reopen submissions after original deadline has passed');
        }
    }

    /**
     * Notify all contest participants about early closure
     */
    private function notifyParticipantsOfEarlyClosure(Project $project, ?string $reason): void
    {
        $contestEntries = $project->getContestEntries();
        
        foreach ($contestEntries as $entry) {
            // For now, we'll use a generic notification method
            // TODO: Implement specific early closure notification methods
            try {
                if (method_exists($this->notificationService, 'notifyContestClosedEarly')) {
                    $this->notificationService->notifyContestClosedEarly($entry, $reason);
                } else {
                    // Fallback to existing notification method
                    Log::info('Contest closed early notification', [
                        'project_id' => $project->id,
                        'pitch_id' => $entry->id,
                        'user_id' => $entry->user_id,
                        'reason' => $reason
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send early closure notification', [
                    'project_id' => $project->id,
                    'pitch_id' => $entry->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Also notify users who might be working on entries but haven't submitted yet
        // This would require tracking "interested" users or those who downloaded files
        // For now, we'll just notify existing participants
    }

    /**
     * Notify all contest participants that submissions are open again
     */
    private function notifyParticipantsOfReopening(Project $project): void
    {
        $contestEntries = $project->getContestEntries();
        
        foreach ($contestEntries as $entry) {
            // For now, we'll use a generic notification method
            // TODO: Implement specific reopening notification methods
            try {
                if (method_exists($this->notificationService, 'notifyContestSubmissionsReopened')) {
                    $this->notificationService->notifyContestSubmissionsReopened($entry);
                } else {
                    // Fallback to existing notification method
                    Log::info('Contest submissions reopened notification', [
                        'project_id' => $project->id,
                        'pitch_id' => $entry->id,
                        'user_id' => $entry->user_id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send reopening notification', [
                    'project_id' => $project->id,
                    'pitch_id' => $entry->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get early closure statistics for a project
     */
    public function getEarlyClosureStats(Project $project): array
    {
        if (!$project->wasClosedEarly()) {
            return [];
        }

        $originalDeadline = $project->submission_deadline;
        $closedAt = $project->submissions_closed_early_at;
        $entriesCount = $project->getContestEntries()->count();

        return [
            'was_closed_early' => true,
            'closed_at' => $closedAt,
            'closed_by' => $project->submissionsClosedEarlyBy,
            'reason' => $project->early_closure_reason,
            'original_deadline' => $originalDeadline,
            'time_saved' => $originalDeadline ? $closedAt->diffForHumans($originalDeadline, true) : null,
            'days_early' => $originalDeadline ? $closedAt->diffInDays($originalDeadline) : null,
            'entries_at_closure' => $entriesCount,
            'effective_contest_duration' => $project->created_at->diffForHumans($closedAt, true)
        ];
    }
} 