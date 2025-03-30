<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Project\ProjectCreationException;
use App\Exceptions\Project\ProjectUpdateException;
use App\Exceptions\File\FileUploadException;
use App\Exceptions\File\StorageLimitException;
use Illuminate\Support\Facades\Log;

class ProjectManagementService
{
    /**
     * Create a new project.
     *
     * @param User $user The user creating the project.
     * @param array $validatedData Validated data from the request.
     * @param ?UploadedFile $projectImage Optional uploaded project image.
     * @return Project The newly created project.
     * @throws ProjectCreationException
     */
    public function createProject(User $user, array $validatedData, ?UploadedFile $projectImage): Project
    {
        try {
            return DB::transaction(function () use ($user, $validatedData, $projectImage) {
                $project = new Project($validatedData);
                $project->user_id = $user->id;
                $project->status = Project::STATUS_UNPUBLISHED; // Ensure default

                if ($projectImage) {
                    // Consider moving path generation logic here or keep in model if simple
                    $path = $projectImage->store('project_images', 's3');
                    $project->image_path = $path;
                    Log::info('Project image uploaded to S3', ['path' => $path, 'project_name' => $project->name]);
                }

                $project->save();
                // Dispatch ProjectCreated event if needed
                // event(new ProjectCreated($project));
                return $project;
            });
        } catch (\Exception $e) {
            Log::error('Error creating project in service', ['error' => $e->getMessage(), 'data' => $validatedData]);
            // TODO: Ensure App\Exceptions\Project\ProjectCreationException exists
            throw new ProjectCreationException('Failed to create project: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing project.
     *
     * @param Project $project The project to update.
     * @param array $validatedData Validated data from the request.
     * @param ?UploadedFile $newProjectImage Optional new project image.
     * @param bool $deleteExistingImage Flag to delete existing image if new one is uploaded.
     * @return Project The updated project.
     * @throws ProjectUpdateException
     */
    public function updateProject(Project $project, array $validatedData, ?UploadedFile $newProjectImage, bool $deleteExistingImage = true): Project
    {
         try {
            return DB::transaction(function () use ($project, $validatedData, $newProjectImage, $deleteExistingImage) {
                $oldImagePath = $project->image_path;

                $project->fill($validatedData);

                if ($newProjectImage) {
                    // Delete old image first (if requested and exists)
                    if ($deleteExistingImage && $oldImagePath && Storage::disk('s3')->exists($oldImagePath)) {
                         try {
                            Storage::disk('s3')->delete($oldImagePath);
                            Log::info('Old project image deleted from S3', ['path' => $oldImagePath, 'project_id' => $project->id]);
                        } catch (\Exception $e) {
                            Log::error('Failed to delete old project image', ['path' => $oldImagePath, 'project_id' => $project->id, 'error' => $e->getMessage()]);
                            // Decide if this should halt the update or just log
                        }
                    }
                    // Store new image
                    $path = $newProjectImage->store('project_images', 's3');
                    Log::debug('Service: Stored new image', ['new_path' => $path, 'project_id' => $project->id]); // Log new path
                    $project->image_path = $path;
                    Log::info('New project image uploaded to S3', ['path' => $path, 'project_id' => $project->id]);
                }

                Log::debug('Service: Before save', [
                    'project_id' => $project->id,
                    'old_image_path_var' => $oldImagePath,
                    'current_image_path_prop' => $project->image_path,
                    'is_dirty' => $project->isDirty('image_path')
                ]);
                $project->save();
                // Dispatch ProjectUpdated event if needed
                // event(new ProjectUpdated($project));
                return $project;
            });
        } catch (\Exception $e) {
            Log::error('Error updating project in service', ['project_id' => $project->id, 'error' => $e->getMessage(), 'data' => $validatedData]);
            // TODO: Ensure App\Exceptions\Project\ProjectUpdateException exists
            throw new ProjectUpdateException('Failed to update project: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Publish a project.
     *
     * @param Project $project
     * @return Project
     */
    public function publishProject(Project $project): Project
    {
        // Policy check should happen before calling the service
        // TODO: Review if Project::publish() should be kept or logic moved here
        $project->publish(); // Keep simple status logic in model or move here if complex
         // Dispatch ProjectPublished event if needed
         // event(new ProjectPublished($project));
        return $project;
    }

    /**
     * Unpublish a project.
     *
     * @param Project $project
     * @return Project
     */
    public function unpublishProject(Project $project): Project
    {
        // Policy check should happen before calling the service
        // TODO: Review if Project::unpublish() should be kept or logic moved here
        $project->unpublish(); // Keep simple status logic in model or move here if complex
         // Dispatch ProjectUnpublished event if needed
         // event(new ProjectUnpublished($project));
        return $project;
    }

     /**
     * Mark a project as completed.
     * Typically called by the PitchCompletionService.
     *
     * @param Project $project
     * @return Project
     */
    public function completeProject(Project $project): Project
    {
        // Ensure this is idempotent or called correctly
        // TODO: Ensure Project model has STATUS_COMPLETED constant
        if ($project->status !== Project::STATUS_COMPLETED) {
            $project->status = Project::STATUS_COMPLETED;
            $project->completed_at = now();
            $project->save();
             // Dispatch ProjectCompleted event if needed
            // event(new ProjectCompleted($project));
        }
        return $project;
    }

    // Add other methods as needed (e.g., deleteProject)
} 