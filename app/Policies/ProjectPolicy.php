<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Models\ProjectFile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Project $project)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Check subscription limits
        if (!$user->canCreateProject()) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Project $project)
    {
        Log::debug("[ProjectPolicy] update check: User ID: {$user->id}, Project User ID: {$project->user_id}");
        $isOwner = $user->id === $project->user_id;
        Log::debug("[ProjectPolicy] update check result: " . ($isOwner ? 'true' : 'false'));
        return $isOwner;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Project $project)
    {
        // Only project owner can delete
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Project $project)
    {
        // Only project owner can restore (consider admin role later)
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Project $project)
    {
        // Only project owner can force delete (consider admin role later)
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether a user can create a pitch for the given project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function createPitch(User $user, Project $project): bool
    {
        // 1. User cannot pitch on their own project
        if ($user->id === $project->user_id) {
            return false;
        }

        // 2. Project must be open for pitches
        if (!$project->isOpenForPitches()) { // Uses the method added earlier
            return false;
        }

        // 3. User must not have already pitched for this project
        // REMOVED: Controller handles redirect if user already pitched.
        // The policy should only determine if the user is *allowed* to pitch
        // in principle (not owner, project is open).
        // if ($project->userPitch($user->id)) { // Uses existing method
        //     return false;
        // }

        // If all checks pass, user can create a pitch
        return true;
    }

    /**
     * Determine whether the user can publish the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function publish(User $user, Project $project): bool
    {
        Log::debug("[ProjectPolicy] publish check: User ID: {$user->id}, Project User ID: {$project->user_id}");
        $isOwner = $user->id === $project->user_id;
        
        // Client Management projects should never be published
        if ($project->isClientManagement()) {
            Log::debug("[ProjectPolicy] publish check: Client Management project cannot be published");
            return false;
        }
        
        Log::debug("[ProjectPolicy] publish check result: " . ($isOwner ? 'true' : 'false'));
        return $isOwner;
    }

    /**
     * Determine whether the user can unpublish the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function unpublish(User $user, Project $project): bool
    {
        Log::debug("[ProjectPolicy] unpublish check: User ID: {$user->id}, Project User ID: {$project->user_id}");
        $isOwner = $user->id === $project->user_id;
        
        // Client Management projects are already unpublished by design
        if ($project->isClientManagement()) {
            Log::debug("[ProjectPolicy] unpublish check: Client Management project is already unpublished by design");
            return false;
        }
        
        Log::debug("[ProjectPolicy] unpublish check result: " . ($isOwner ? 'true' : 'false'));
        return $isOwner;
    }

    /**
     * Determine whether the user can upload files to the project.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function uploadFile(User $user, Project $project): bool
    {
        // Only the project owner can upload files to their project
        if ($user->id !== $project->user_id) {
            return false;
        }
        
        // Block uploads for completed projects
        return ! in_array($project->status, [
            Project::STATUS_COMPLETED,
        ]);
    }

    /**
     * Determine whether the user can delete a project file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectFile  $projectFile
     * @return bool
     */
    public function deleteFile(User $user, ProjectFile $projectFile): bool
    {
        // Only the project owner can delete files from their project
        return $user->id === $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can download a project file.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProjectFile  $projectFile
     * @return bool
     */
    public function download(User $user, ProjectFile $projectFile): bool
    {
        // Allow project owner
        if ($user->id === $projectFile->project->user_id) {
            return true;
        }
        
        // Allow producer with an active pitch for this project
        $activePitchStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_PENDING_REVIEW,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_CONTEST_ENTRY, // Allow contest entries to download project files
        ];

        $hasActivePitch = Pitch::where('project_id', $projectFile->project_id)
                               ->where('user_id', $user->id)
                               ->whereIn('status', $activePitchStatuses)
                               ->exists();

        return $hasActivePitch;
    }

    // <<< PHASE 5: CONTEST JUDGING POLICIES >>>

    /**
     * Determine whether the user can judge contest entries.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function judgeContest(User $user, Project $project): bool
    {
        // Only the contest runner (project owner) can judge contest entries
        return $user->id === $project->user_id && $project->isContest();
    }

    /**
     * Determine whether the user can set contest placements.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function setContestPlacements(User $user, Project $project): bool
    {
        // Only the contest runner can set placements, and only if judging isn't finalized
        return $user->id === $project->user_id && 
               $project->isContest() && 
               !$project->isJudgingFinalized();
    }

    /**
     * Determine whether the user can finalize contest judging.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function finalizeContestJudging(User $user, Project $project): bool
    {
        // Only the contest runner can finalize judging when eligible
        return $user->id === $project->user_id && 
               $project->isContest() && 
               $project->canFinalizeJudging();
    }

    /**
     * Determine whether the user can reopen contest judging.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function reopenContestJudging(User $user, Project $project): bool
    {
        // Only the contest runner can reopen judging, and only if finalized
        // This might be restricted to admins in the future
        return $user->id === $project->user_id && 
               $project->isContest() && 
               $project->isJudgingFinalized();
    }

    /**
     * Determine whether the user can view contest results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function viewContestResults(User $user, Project $project): bool
    {
        // Contest runner can always view results
        if ($user->id === $project->user_id && $project->isContest()) {
            return true;
        }

        // Contest participants can view results if judging is finalized
        if ($project->isContest() && $project->isJudgingFinalized()) {
            // Check if user participated in the contest
            $hasEntry = $project->pitches()
                               ->where('user_id', $user->id)
                               ->where('status', 'like', '%contest%')
                               ->exists();
            
            if ($hasEntry) {
                return true;
            }
        }

        // Public can view results if contest allows public viewing and is finalized
        if ($project->isContest() && 
            $project->isJudgingFinalized() && 
            $project->show_submissions_publicly) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view contest entries.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function viewContestEntries(User $user, Project $project): bool
    {
        // Contest runner can always view entries
        if ($user->id === $project->user_id && $project->isContest()) {
            return true;
        }

        // Contest participants can view entries if submissions are public or judging is finalized
        if ($project->isContest()) {
            $hasEntry = $project->pitches()
                               ->where('user_id', $user->id)
                               ->where('status', 'like', '%contest%')
                               ->exists();
            
            if ($hasEntry && ($project->show_submissions_publicly || $project->isJudgingFinalized())) {
                return true;
            }
        }

        // Public can view entries if contest allows public viewing
        if ($project->isContest() && $project->show_submissions_publicly) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can close contest submissions early.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function closeContestEarly(User $user, Project $project): bool
    {
        // Only the contest runner can close submissions early
        return $user->id === $project->user_id && 
               $project->isContest() && 
               $project->canCloseEarly();
    }

    /**
     * Determine whether the user can reopen contest submissions.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function reopenContestSubmissions(User $user, Project $project): bool
    {
        // Only the contest runner can reopen submissions
        return $user->id === $project->user_id && 
               $project->isContest() && 
               $project->wasClosedEarly() &&
               !$project->isJudgingFinalized() &&
               (!$project->submission_deadline || !$project->submission_deadline->isPast());
    }

    /**
     * Determine whether the user can manage the project.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Project  $project
     * @return bool
     */
    public function manageProject(User $user, Project $project): bool
    {
        // Only the project owner can manage the project
        return $user->id === $project->user_id;
    }

    // <<< END PHASE 5: CONTEST JUDGING POLICIES >>>
}