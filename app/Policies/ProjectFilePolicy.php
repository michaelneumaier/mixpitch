<?php

namespace App\Policies;

use App\Models\ProjectFile;
use App\Models\User;

class ProjectFilePolicy
{
    /**
     * Determine whether the user can view the project file.
     * This is used for general file access within the producer interface.
     */
    public function view(User $user, ProjectFile $projectFile): bool
    {
        \Log::info('ProjectFile authorization check', [
            'user_id' => $user->id,
            'project_file_id' => $projectFile->id,
            'project_id' => $projectFile->project_id,
            'project_user_id' => $projectFile->project?->user_id,
            'project_loaded' => $projectFile->relationLoaded('project'),
            'auth_result' => $user->id === $projectFile->project?->user_id,
        ]);

        // Only the project owner (producer) can view project files in the management interface
        return $user->id === $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can download the project file.
     * This is used within authenticated areas (producer interface).
     */
    public function download(User $user, ProjectFile $projectFile): bool
    {
        // Producer can download all project files (including client-uploaded files)
        return $user->id === $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can delete the project file.
     */
    public function delete(User $user, ProjectFile $projectFile): bool
    {
        // Only the project owner (producer) can delete project files
        // This includes client-uploaded files (producer manages the project)
        return $user->id === $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can create project files.
     * This is used for producer uploads to project files.
     */
    public function create(User $user, \App\Models\Project $project): bool
    {
        // Only the project owner can create project files
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can upload files to a project.
     * This is used for the project upload functionality.
     */
    public function uploadFile(User $user, \App\Models\Project $project): bool
    {
        // Only the project owner can upload project files
        if ($user->id !== $project->user_id) {
            return false;
        }

        // Block uploads for completed projects
        return ! in_array($project->status, [
            \App\Models\Project::STATUS_COMPLETED,
        ]);
    }

    /**
     * Note: Client file uploads don't use this policy since clients don't have user accounts.
     * Client uploads are handled via signed URLs in the ClientPortalController.
     */
}
