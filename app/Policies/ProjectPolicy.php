<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
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
        return true;
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
        return true;
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
        return true;
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
        Log::debug("[ProjectPolicy] unpublish check result: " . ($isOwner ? 'true' : 'false'));
        return $isOwner;
    }
}