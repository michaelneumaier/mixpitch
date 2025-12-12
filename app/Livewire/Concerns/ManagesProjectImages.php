<?php

namespace App\Livewire\Concerns;

use App\Exceptions\File\FileUploadException;
use App\Services\Project\ProjectImageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use Masmerise\Toaster\Toaster;

/**
 * Trait for managing project image uploads.
 * Used by ManageStandardProject, ManageContestProject, and similar components.
 *
 * Note: The using class must also use WithFileUploads trait.
 */
trait ManagesProjectImages
{
    public bool $showImageUploadModal = false;

    public $newProjectImage;

    public $uploadingImage = false;

    public $imagePreviewUrl = null;

    /**
     * Show the image upload modal
     */
    public function showImageUpload(): void
    {
        $this->showImageUploadModal = true;
        $this->newProjectImage = null;
        $this->imagePreviewUrl = null;
    }

    /**
     * Hide the image upload modal
     */
    public function hideImageUpload(): void
    {
        $this->showImageUploadModal = false;
        $this->newProjectImage = null;
        $this->imagePreviewUrl = null;
        $this->uploadingImage = false;
    }

    /**
     * Handle image file selection and show preview
     */
    public function updatedNewProjectImage(): void
    {
        if ($this->newProjectImage) {
            try {
                // Validate file on frontend
                $this->validate([
                    'newProjectImage' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
                ]);

                // Generate preview URL
                $this->imagePreviewUrl = $this->newProjectImage->temporaryUrl();
            } catch (\Exception $e) {
                $this->imagePreviewUrl = null;
                Toaster::error('Invalid image file. Please select a valid image (JPG, PNG, GIF, or WebP) under 5MB.');
                $this->newProjectImage = null;
            }
        }
    }

    /**
     * Upload the new project image
     */
    public function uploadProjectImage(ProjectImageService $imageService): void
    {
        if (! $this->newProjectImage) {
            Toaster::error('Please select an image to upload.');

            return;
        }

        try {
            $this->authorize('update', $this->project);

            $this->uploadingImage = true;

            // Validate image file
            $this->validate([
                'newProjectImage' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            ]);

            // Use the ProjectImageService to handle upload
            if ($this->project->image_path) {
                // Update existing image
                $path = $imageService->updateProjectImage($this->project, $this->newProjectImage);
                $message = 'Project image updated successfully!';
            } else {
                // Upload new image
                $path = $imageService->uploadProjectImage($this->project, $this->newProjectImage);
                $message = 'Project image added successfully!';
            }

            // Refresh project to get new image
            $this->project->refresh();

            // Close modal and show success
            $this->hideImageUpload();
            Toaster::success($message);

            // Dispatch event for any listening components
            $this->dispatch('project-image-updated');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (FileUploadException $e) {
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error uploading project image', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('An error occurred while uploading the image. Please try again.');
        } finally {
            $this->uploadingImage = false;
        }
    }

    /**
     * Remove the project image
     */
    public function removeProjectImage(ProjectImageService $imageService): void
    {
        try {
            $this->authorize('update', $this->project);

            // Use the ProjectImageService to handle deletion
            $success = $imageService->deleteProjectImage($this->project);

            if ($success) {
                // Refresh project to remove image reference
                $this->project->refresh();

                Toaster::success('Project image removed successfully!');

                // Dispatch event for any listening components
                $this->dispatch('project-image-updated');
            } else {
                Toaster::error('Failed to remove project image. Please try again.');
            }

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Exception $e) {
            Log::error('Error removing project image', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('An error occurred while removing the image. Please try again.');
        }
    }
}
