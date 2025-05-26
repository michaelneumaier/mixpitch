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
            // Log input data
            Log::info('ProjectManagementService: Creating project', [
                'user_id' => $user->id,
                'data' => $validatedData,
                'has_image' => !is_null($projectImage)
            ]);
            
            // Start a transaction to ensure all related operations succeed or fail together
            return DB::transaction(function () use ($user, $validatedData, $projectImage) {
                // Create the project
                $project = new Project($validatedData);
                
                // Populate project with data
                $project->user_id = $user->id;
                $project->status = Project::STATUS_UNPUBLISHED; // Ensure default
                
                // Handle image upload if provided
                if ($projectImage) {
                    $path = $projectImage->store('project_images', 's3');
                    $project->image_path = $path;
                    Log::info('Project image uploaded to S3', ['path' => $path, 'project_name' => $project->name]);
                }
                
                // Save to get ID
                $project->save();
                
                Log::info('Project created in database', [
                    'project_id' => $project->id,
                    'project_data' => $project->toArray()
                ]);
                
                // Dispatch ProjectCreated event if needed
                // event(new ProjectCreated($project));
                return $project;
            });
        } catch (\Exception $e) {
            Log::error('ProjectManagementService: Error creating project', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            // Ensure we are working with the latest db state of the project
            $project = $project->fresh();
            $originalDataForLog = $project->toArray(); // For logging

            return DB::transaction(function () use ($project, $validatedData, $newProjectImage, $deleteExistingImage, $originalDataForLog) {
                $oldImagePath = $project->image_path;
                $newImagePath = null;

                // Prepare data for fill, excluding image_path if new one is coming
                $fillData = $validatedData;
                if ($newProjectImage) {
                    unset($fillData['image_path']);
                } else {
                    // Also unset if no new image, to prevent accidental nulling if key exists in $validatedData
                    unset($fillData['image_path']);
                }

                // Remove workflow_type from fillable data for updates to prevent accidental change
                unset($fillData['workflow_type']);

                // Save project_type value from input before fill() operation
                $projectType = $fillData['project_type'] ?? null;
                $budget = $fillData['budget'] ?? null;

                // Add explicit logging for critical fields
                Log::debug('ProjectManagementService: Critical field values before fill', [
                    'project_type_in_validatedData' => $validatedData['project_type'] ?? 'NOT SET',
                    'budget_in_validatedData' => $validatedData['budget'] ?? 'NOT SET',
                    'project_type_in_fillData' => $fillData['project_type'] ?? 'NOT SET',
                    'budget_in_fillData' => $fillData['budget'] ?? 'NOT SET',
                    'project_type_current' => $project->project_type,
                    'budget_current' => $project->budget
                ]);

                Log::debug('ProjectManagementService: Data before fill', [
                    'validatedData' => $validatedData, // Original validated data
                    'fillData' => $fillData,       // Data used for fill()
                    'project_before_fill' => $originalDataForLog
                ]);

                // ---> Add logging here <---
                Log::debug('ProjectManagementService: Filling project with data', [
                    'project_id' => $project->id,
                    'fillData_keys' => array_keys($fillData),
                    'project_type_in_fillData' => $fillData['project_type'] ?? 'NOT SET',
                    'budget_in_fillData' => $fillData['budget'] ?? 'NOT SET'
                ]);
                // ---> End logging <---

                // Fill the model with prepared data
                $project->fill($fillData);

                // Manually set project_type after fill if provided
                if ($projectType !== null) {
                    Log::debug('ProjectManagementService: Manually setting project_type', [
                        'project_type_before' => $project->project_type,
                        'project_type_to_set' => $projectType
                    ]);
                    $project->project_type = $projectType;
                }

                // Add logging after fill to see if data was applied
                Log::debug('ProjectManagementService: Project state after fill', [
                    'project_type' => $project->project_type,
                    'budget' => $project->budget,
                    'isDirty' => $project->isDirty(),
                    'dirtyFields' => $project->getDirty()
                ]);

                // Handle new image upload
                if ($newProjectImage) {
                    // Store new image
                    $newImagePath = $newProjectImage->store('project_images', 's3');
                    Log::info('New project image uploaded', ['path' => $newImagePath, 'project_id' => $project->id]);
                    // Set the new path on the model
                    $project->image_path = $newImagePath;
                }

                Log::debug('ProjectManagementService: Project state before save', [
                    'project_attributes' => $project->getAttributes(),
                    'is_dirty' => $project->isDirty(),
                    'dirty_fields' => $project->getDirty()
                ]);

                // Save all changes
                $project->save();

                Log::debug('ProjectManagementService: Project state after save', [
                    'project_attributes' => $project->fresh()->getAttributes(), // Log state from DB
                    'project_type' => $project->fresh()->project_type,
                    'budget' => $project->fresh()->budget
                ]);

                // Delete old image only after successful save of new state
                if ($newProjectImage && $deleteExistingImage && $oldImagePath && $oldImagePath !== $newImagePath && Storage::disk('s3')->exists($oldImagePath)) {
                    try {
                        Storage::disk('s3')->delete($oldImagePath);
                        Log::info('Old project image deleted', ['path' => $oldImagePath, 'project_id' => $project->id]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete old project image', ['path' => $oldImagePath, 'error' => $e->getMessage()]);
                        // Non-critical, don't rollback transaction
                    }
                }

                return $project; // Return the saved project instance
            });
        } catch (\Exception $e) {
            Log::error('Error updating project in service', ['project_id' => $project->id ?? null, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

    /**
     * Reopen a completed project, setting its status back to open.
     * Called when a completed pitch is returned to an active status.
     *
     * @param Project $project
     * @return Project
     */
    public function reopenProject(Project $project): Project
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            Log::info('Reopening project', ['project_id' => $project->id, 'current_status' => $project->status]);
            $project->status = Project::STATUS_OPEN; // Or appropriate previous status if needed
            $project->completed_at = null;
            $project->save();
            Log::info('Project reopened successfully', ['project_id' => $project->id, 'new_status' => $project->status]);
            // Dispatch ProjectReopened event if needed
            // event(new ProjectReopened($project));
        } else {
            Log::warning('Attempted to reopen a project that was not completed', ['project_id' => $project->id, 'status' => $project->status]);
        }
        return $project;
    }

    // Add other methods as needed (e.g., deleteProject)
}