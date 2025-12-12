<?php

namespace App\Livewire\Concerns;

use App\Exceptions\File\FileDeletionException;
use App\Models\BulkDownload;
use App\Models\ProjectFile;
use App\Services\BulkDownloadService;
use App\Services\FileManagementService;
use Flux\Flux;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

/**
 * Trait for managing project file operations.
 * Used by ManageStandardProject, ManageContestProject, and similar components.
 */
trait ManagesProjectFiles
{
    public $fileToDelete = null;

    public array $filesToDelete = [];

    public bool $isDeleting = false;

    public array $bulkDownloadStatus = [];

    /**
     * Get the file management service
     */
    protected function getFileService(): FileManagementService
    {
        return app(FileManagementService::class);
    }

    /**
     * Set delete modal state
     */
    public function confirmDeleteFile($fileId): void
    {
        $this->fileToDelete = $fileId;
        $this->dispatch('modal-show', name: 'delete-file');
    }

    /**
     * Cancel file deletion
     */
    public function cancelDeleteFile(): void
    {
        $this->fileToDelete = null;
        $this->dispatch('modal-close', name: 'delete-file');
    }

    /**
     * Delete a persisted Project File.
     */
    public function deleteFile($fileId = null): void
    {
        $this->isDeleting = true;

        $idToDelete = $fileId ?? $this->fileToDelete;

        Log::debug('Starting file deletion process', [
            'file_id' => $idToDelete,
            'is_file_id_null' => is_null($fileId),
            'is_file_to_delete_null' => is_null($this->fileToDelete),
        ]);

        if (! $idToDelete) {
            $this->isDeleting = false;
            Toaster::error('No file selected for deletion.');

            return;
        }

        try {
            $projectFile = ProjectFile::findOrFail($idToDelete);
            Log::debug('Found file to delete', [
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name,
            ]);

            // Authorization: Use Policy
            $this->authorize('delete', $projectFile);
            Log::debug('Authorization passed');

            // Get service via protected method
            $fileManager = $this->getFileService();
            Log::debug('File service resolved');

            // Store the file size for logging
            $fileSize = $projectFile->size;
            Log::debug('File to be deleted size', ['size' => $fileSize]);

            // Delete the file
            $fileManager->deleteProjectFile($projectFile);
            Log::debug('File deleted successfully');

            // Important: Refresh the project model first to get the latest data
            $this->project->refresh();
            Log::debug('Project model refreshed', [
                'user_total_storage_used' => $this->project->user->total_storage_used,
            ]);

            Toaster::success("File '{$projectFile->file_name}' deleted successfully.");
            $this->dispatch('file-deleted'); // Notify UI to refresh file list
            $this->dispatch('storageUpdated'); // Notify sidebar to update storage info

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('File not found for deletion', ['file_id' => $idToDelete]);
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Log::error('Authorization failed for file deletion', ['file_id' => $idToDelete, 'user_id' => auth()->id()]);
            Toaster::error('You are not authorized to delete this file.');
        } catch (FileDeletionException $e) {
            Log::warning('Project file deletion failed via Livewire', ['file_id' => $idToDelete, 'error' => $e->getMessage()]);
            Toaster::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error deleting project file via Livewire', [
                'file_id' => $idToDelete,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Toaster::error('An unexpected error occurred while deleting the file: '.$e->getMessage());
        } finally {
            $this->dispatch('modal-close', name: 'delete-file');
            $this->fileToDelete = null;
            $this->isDeleting = false;
        }
    }

    /**
     * Generate and dispatch a temporary download URL for a file.
     */
    public function getDownloadUrl($fileId): void
    {
        try {
            $projectFile = ProjectFile::findOrFail($fileId);

            // Authorization: Use Policy
            $this->authorize('download', $projectFile);

            // Get service from helper method
            $fileManagementService = $this->getFileService();

            // Get URL from service (force download by default)
            $url = $fileManagementService->getTemporaryDownloadUrl($projectFile);
            $filename = $projectFile->original_file_name ?: $projectFile->file_name;

            // Dispatch event for JavaScript to handle opening the URL
            $this->dispatch('open-url', url: $url, filename: $filename);
            Toaster::info('Your download will begin shortly...');

            // Prevent unnecessary re-render
            $this->skipRender();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
            $this->skipRender();
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download this file.');
            $this->skipRender();
        } catch (\Exception $e) {
            Log::error('Error getting project file download URL via Livewire', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('Could not generate download link: '.$e->getMessage());
            $this->skipRender();
        }
    }

    /**
     * Play a project file in the global audio player.
     */
    public function playProjectFile($fileId): void
    {
        try {
            $file = ProjectFile::findOrFail($fileId);

            Log::info('ManageProject playProjectFile debug', [
                'file_id' => $fileId,
                'file_project_id' => $file->project_id,
                'component_project_id' => $this->project->id,
                'user_id' => auth()->id(),
                'project_user_id' => $this->project->user_id,
                'match' => $file->project_id === $this->project->id,
            ]);

            // Verify the file belongs to this project (use loose comparison to handle type differences)
            if ($file->project_id != $this->project->id) {
                throw new AuthorizationException('File does not belong to this project.');
            }

            // Check if it's an audio file
            if (! $file->isAudioFile()) {
                Toaster::error('This file is not an audio file.');

                return;
            }

            // Dispatch event to play in global player
            $this->dispatch('playProjectFile', projectFileId: $file->id);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Toaster::error('File not found.');
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to play this file.');
        } catch (\Exception $e) {
            Log::error('Error playing project file', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            Toaster::error('Could not play file: '.$e->getMessage());
        }
    }

    /**
     * Handle file actions dispatched from the FileList component
     */
    #[On('fileAction')]
    public function handleFileAction($data): void
    {
        $action = $data['action'] ?? null;
        $fileId = $data['fileId'] ?? null;
        $modelType = $data['modelType'] ?? null;
        $modelId = $data['modelId'] ?? null;

        if (! $action || ! $fileId) {
            return;
        }

        // Route the action to the appropriate method
        switch ($action) {
            case 'playProjectFile':
                $this->playProjectFile($fileId);
                break;
            case 'getDownloadUrl':
                $this->getDownloadUrl($fileId);
                break;
            case 'confirmDeleteFile':
                $this->confirmDeleteFile($fileId);
                break;
            default:
                // For any other action, try to call it directly if it exists
                if (method_exists($this, $action)) {
                    $this->$action($fileId);
                }
                break;
        }
    }

    /**
     * Handle bulk file actions dispatched from the FileList component
     */
    #[On('bulkFileAction')]
    public function handleBulkFileAction($data): void
    {
        $action = $data['action'] ?? null;
        $fileIds = $data['fileIds'] ?? [];
        $modelType = $data['modelType'] ?? null;
        $modelId = $data['modelId'] ?? null;

        if (! $action || empty($fileIds)) {
            return;
        }

        // Route the action to the appropriate method
        switch ($action) {
            case 'confirmBulkDeleteFiles':
                $this->confirmBulkDeleteFiles($fileIds);
                break;
            case 'bulkDeleteFiles':
                $this->bulkDeleteFiles($fileIds);
                break;
            case 'bulkDownloadFiles':
                $this->bulkDownloadFiles($fileIds);
                break;
            default:
                // For any other action, try to call it directly if it exists
                if (method_exists($this, $action)) {
                    $this->$action($fileIds);
                }
                break;
        }
    }

    /**
     * Confirm bulk deletion of files
     */
    public function confirmBulkDeleteFiles(array $fileIds): void
    {
        // Get files and validate they belong to this project
        $projectFiles = ProjectFile::whereIn('id', $fileIds)
            ->where('project_id', $this->project->id)
            ->get();

        if ($projectFiles->isEmpty()) {
            Toaster::error('No valid files found for deletion.');

            return;
        }

        // Check authorization for each file
        try {
            foreach ($projectFiles as $file) {
                $this->authorize('delete', $file);
            }

            // Store file IDs for confirmation
            $this->filesToDelete = $fileIds;
            $this->dispatch('modal-show', name: 'bulk-delete-files');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to delete these files.');
        }
    }

    /**
     * Cancel bulk file deletion
     */
    public function cancelBulkDeleteFiles(): void
    {
        $this->filesToDelete = [];
        $this->dispatch('modal-close', name: 'bulk-delete-files');
    }

    /**
     * Bulk delete project files
     */
    public function bulkDeleteFiles(?array $fileIds = null): void
    {
        try {
            // Use provided fileIds or fall back to filesToDelete property
            $idsToDelete = $fileIds ?? $this->filesToDelete;

            if (empty($idsToDelete)) {
                Toaster::error('No files selected for deletion.');

                return;
            }

            // Get files and validate they belong to this project
            $projectFiles = ProjectFile::whereIn('id', $idsToDelete)
                ->where('project_id', $this->project->id)
                ->get();

            if ($projectFiles->isEmpty()) {
                Toaster::error('No valid files found for deletion.');

                return;
            }

            // Check authorization for each file
            foreach ($projectFiles as $file) {
                $this->authorize('delete', $file);
            }

            // Get file management service
            $fileManagementService = $this->getFileService();
            $deletedCount = 0;
            $errors = [];

            // Delete each file
            foreach ($projectFiles as $file) {
                try {
                    $fileManagementService->deleteProjectFile($file);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$file->file_name}: ".$e->getMessage();
                    Log::error('Bulk delete file error', [
                        'file_id' => $file->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Refresh project data
            $this->refreshProjectData();

            // Notify FileList component to refresh
            if ($deletedCount > 0) {
                $this->dispatch('file-deleted');
            }

            // Provide feedback
            if ($deletedCount > 0) {
                $message = $deletedCount === 1
                    ? 'File deleted successfully.'
                    : "{$deletedCount} files deleted successfully.";
                Toaster::success($message);
            }

            if (! empty($errors)) {
                foreach ($errors as $error) {
                    Toaster::error($error);
                }
            }

            // Close modal and clear selection
            $this->dispatch('modal-close', name: 'bulk-delete-files');
            $this->filesToDelete = [];

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to delete these files.');
        } catch (\Exception $e) {
            Log::error('Bulk file deletion error', [
                'file_ids' => $idsToDelete ?? [],
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An error occurred while deleting files.');
        } finally {
            // Ensure cleanup happens regardless of success/failure
            $this->dispatch('modal-close', name: 'bulk-delete-files');
            $this->filesToDelete = [];
        }
    }

    /**
     * Download selected files as a ZIP archive
     */
    public function bulkDownloadAsZip(array $fileIds): void
    {
        try {
            // Rate limiting: 3 ZIP requests per 5 minutes
            if (! $this->checkBulkDownloadRateLimit()) {
                return;
            }

            $bulkDownloadService = app(BulkDownloadService::class);

            // Check for existing pending/processing download
            if ($bulkDownloadService->hasActiveBulkDownload(auth()->id())) {
                Toaster::error('You already have a ZIP download in progress. Please wait for it to complete.');

                return;
            }

            // Get files and validate they belong to this project
            $projectFiles = ProjectFile::whereIn('id', $fileIds)
                ->where('project_id', $this->project->id)
                ->get();

            if ($projectFiles->isEmpty()) {
                Toaster::error('No valid files found for download.');

                return;
            }

            // Check authorization for each file
            foreach ($projectFiles as $file) {
                $this->authorize('download', $file);
            }

            // Create ZIP archive via Cloudflare Worker
            $archiveId = $bulkDownloadService->requestBulkDownload($fileIds, 'project');

            // Show permanent toast until ZIP is ready
            Flux::toast(
                heading: 'Preparing ZIP Download',
                text: 'Please wait while we create your archive. Don\'t navigate away.',
                variant: 'warning',
                duration: 0
            );

            // Dispatch event for Alpine.js to start polling
            $this->dispatch('bulk-download-started', archiveId: $archiveId);

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download these files.');
        } catch (\Exception $e) {
            Log::error('Bulk ZIP download error', [
                'file_ids' => $fileIds,
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An error occurred while preparing ZIP download.');
        }
    }

    /**
     * Download selected files individually
     */
    public function bulkDownloadIndividual(array $fileIds): void
    {
        try {
            // Per-file rate limiting: same file can only be downloaded once per 30 seconds
            $rateLimitResult = $this->checkPerFileRateLimit($fileIds, 'project');

            if (empty($rateLimitResult['allowed'])) {
                Toaster::error('These files were recently downloaded. Please wait a moment.');

                return;
            }

            if (! empty($rateLimitResult['blocked'])) {
                Toaster::warning(count($rateLimitResult['blocked']).' file(s) skipped (recently downloaded).');
            }

            // Get files and validate they belong to this project
            $projectFiles = ProjectFile::whereIn('id', $rateLimitResult['allowed'])
                ->where('project_id', $this->project->id)
                ->get();

            if ($projectFiles->isEmpty()) {
                Toaster::error('No valid files found for download.');

                return;
            }

            // Check authorization for each file
            foreach ($projectFiles as $file) {
                $this->authorize('download', $file);
            }

            // Trigger individual downloads for each file
            foreach ($projectFiles as $file) {
                $this->dispatch('download-file', [
                    'url' => $file->downloadUrl,
                    'filename' => $file->filename,
                ]);
            }

            $count = count($projectFiles);
            Toaster::success($count.' file'.($count !== 1 ? 's' : '').' will download shortly.');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to download these files.');
        } catch (\Exception $e) {
            Log::error('Individual file download error', [
                'file_ids' => $fileIds,
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An error occurred while preparing downloads.');
        }
    }

    /**
     * Legacy method for backwards compatibility
     *
     * @deprecated Use bulkDownloadAsZip or bulkDownloadIndividual instead
     */
    public function bulkDownloadFiles(array $fileIds): void
    {
        // Default to ZIP download for backwards compatibility
        $this->bulkDownloadAsZip($fileIds);
    }

    /**
     * Track bulk download that was started
     */
    public function trackBulkDownload(string $archiveId): void
    {
        $this->bulkDownloadStatus[$archiveId] = 'processing';
    }

    /**
     * Check status of bulk download (called from Alpine.js polling)
     */
    public function checkBulkDownloadStatus(string $archiveId): void
    {
        $download = BulkDownload::find($archiveId);

        if (! $download) {
            return;
        }

        if ($download->isCompleted()) {
            $this->bulkDownloadStatus[$archiveId] = 'completed';
            Toaster::success('Your download is ready!');

            // Dispatch event for Alpine.js to initiate download
            $this->dispatch('bulk-download-ready', url: route('bulk-download.download', $archiveId));

        } elseif ($download->isFailed()) {
            $this->bulkDownloadStatus[$archiveId] = 'failed';
            Toaster::error('Download preparation failed: '.$download->error_message);
        }
    }

    /**
     * Check per-file rate limit for individual downloads.
     * Same file can only be downloaded once per 30 seconds per user.
     *
     * @return array{allowed: array, blocked: array}
     */
    protected function checkPerFileRateLimit(array $fileIds, string $fileType): array
    {
        $blockedFiles = [];
        $allowedFiles = [];

        foreach ($fileIds as $fileId) {
            $key = "download:{$fileType}:{$fileId}:".auth()->id();

            if (RateLimiter::tooManyAttempts($key, 1)) {
                $blockedFiles[] = $fileId;
            } else {
                RateLimiter::hit($key, 30); // 30 second cooldown per file
                $allowedFiles[] = $fileId;
            }
        }

        return ['allowed' => $allowedFiles, 'blocked' => $blockedFiles];
    }

    /**
     * Check rate limit for bulk ZIP downloads.
     * 3 ZIP requests per 5 minutes per user.
     */
    protected function checkBulkDownloadRateLimit(): bool
    {
        $key = 'bulk-download:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            Toaster::error("Too many ZIP requests. Please wait {$seconds} seconds.");

            return false;
        }

        RateLimiter::hit($key, 300); // 5 minute window

        return true;
    }

    /**
     * Refresh project file data.
     * This method should be implemented by the using class.
     */
    public function refreshProjectData(): void
    {
        $this->project->refresh();
        $this->project->unsetRelation('files');
        $this->project->load('files');
    }
}
