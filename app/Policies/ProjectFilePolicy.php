<?php

namespace App\Policies;

use App\Models\ProjectFile;
use App\Models\User;

class ProjectFilePolicy
{
    /**
     * Determine whether the user can view the project file.
     * This is used for general file access within the producer interface and client portal.
     */
    public function view(User $user, ProjectFile $projectFile): bool
    {
        \Log::info('ProjectFile authorization check', [
            'user_id' => $user->id,
            'project_file_id' => $projectFile->id,
            'project_id' => $projectFile->project_id,
            'project_user_id' => $projectFile->project?->user_id,
            'project_loaded' => $projectFile->relationLoaded('project'),
        ]);

        // Project owner (producer) can always view project files
        if ((int) $user->id === (int) $projectFile->project->user_id) {
            \Log::info('ProjectFile authorization: allowed (project owner)');

            return true;
        }

        // For client management projects, allow the client to view their own reference files
        if ($projectFile->project->isClientManagement()) {
            $isClient = (int) $projectFile->project->client_user_id === (int) $user->id ||
                       $projectFile->project->client_email === $user->email;

            \Log::info('ProjectFile authorization check (client management)', [
                'is_client_management' => true,
                'client_user_id' => $projectFile->project->client_user_id,
                'user_id' => $user->id,
                'client_email' => $projectFile->project->client_email,
                'user_email' => $user->email,
                'auth_result' => $isClient,
            ]);

            return $isClient;
        }

        \Log::info('ProjectFile authorization: denied');

        return false;
    }

    /**
     * Determine whether the user can download the project file.
     * This is used within authenticated areas (producer interface).
     */
    public function download(User $user, ProjectFile $projectFile): bool
    {
        // Producer can download all project files (including client-uploaded files)
        return (int) $user->id === (int) $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can delete the project file.
     */
    public function delete(User $user, ProjectFile $projectFile): bool
    {
        // Only the project owner (producer) can delete project files
        // This includes client-uploaded files (producer manages the project)
        return (int) $user->id === (int) $projectFile->project->user_id;
    }

    /**
     * Determine whether the user can create project files.
     * This is used for producer uploads to project files.
     */
    public function create(User $user, \App\Models\Project $project): bool
    {
        // Only the project owner can create project files
        return (int) $user->id === (int) $project->user_id;
    }

    /**
     * Determine whether the user can upload files to a project.
     * This is used for the project upload functionality.
     */
    public function uploadFile(User $user, \App\Models\Project $project): bool
    {
        // Only the project owner can upload project files
        if ((int) $user->id !== (int) $project->user_id) {
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
