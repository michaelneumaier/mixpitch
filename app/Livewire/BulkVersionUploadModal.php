<?php

namespace App\Livewire;

use App\Models\Pitch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class BulkVersionUploadModal extends Component
{
    public bool $isOpen = false;

    public ?Pitch $pitch = null;

    public int $currentStep = 1;

    public array $uploadedFilesData = [];

    public array $autoMatches = [];

    public array $newFiles = [];

    public array $manualOverrides = [];

    public bool $uploading = false;

    public int $uploadProgress = 0;

    public ?string $errorMessage = null;

    // Early matching support - track files before S3 upload completes
    public array $pendingFiles = []; // Files selected but not uploaded yet (no s3_key)

    public array $fileUploadProgress = []; // Track upload progress per file ID

    public bool $isUploadingToS3 = false; // True when files are uploading to S3

    public bool $processCompleted = false; // Track if process completed successfully (vs cancelled)

    public bool $cancelling = false; // Track if cancellation is in progress (prevents race conditions)

    // Background processing support - allow modal to close before uploads complete
    public bool $isBackgroundProcessing = false; // True when processing uploads in background

    public ?string $backgroundSessionId = null; // Unique ID for localStorage key

    protected \App\Services\FileManagementService $fileManagementService;

    public function __construct()
    {
        $this->fileManagementService = app(\App\Services\FileManagementService::class);
    }

    #[On('openBulkVersionUploadModal')]
    public function openModal(int $pitchId): void
    {
        $this->pitch = Pitch::with(['files'])->findOrFail($pitchId);

        $this->authorize('uploadFile', $this->pitch);

        $this->reset([
            'currentStep',
            'uploadedFilesData',
            'autoMatches',
            'newFiles',
            'manualOverrides',
            'uploading',
            'errorMessage',
            'uploadProgress',
            'pendingFiles',
            'fileUploadProgress',
            'isUploadingToS3',
            'processCompleted',
            'cancelling',
        ]);

        $this->isOpen = true;

        // Dispatch event to clear JavaScript-side bulk uploads array
        $this->dispatch('clearBulkUploads');
    }

    /**
     * Handle file selection event (called immediately when files are selected, before upload)
     * This allows early matching while files upload in background
     */
    public function handleFileSelected(array $fileData): void
    {
        // Don't process new files during cancellation
        if ($this->cancelling) {
            return;
        }

        Log::info('File selected for bulk upload', [
            'file_id' => $fileData['id'],
            'file_name' => $fileData['name'],
            'file_size' => $fileData['size'],
        ]);

        // Add to pending files with numeric index (not file ID as key)
        // This ensures consistency with manualOverrides which uses numeric indices
        $this->pendingFiles[] = [
            'id' => $fileData['id'],
            'name' => $fileData['name'],
            'size' => $fileData['size'],
            'type' => $fileData['type'],
            'status' => 'queued',
            'progress' => 0,
        ];

        // Initialize upload progress tracking (keep ID as key for quick lookup)
        $this->fileUploadProgress[$fileData['id']] = [
            'progress' => 0,
            'status' => 'queued',
        ];

        $this->isUploadingToS3 = true;

        // If this is the first file, move to step 2 and start matching
        if (count($this->pendingFiles) === 1) {
            $this->currentStep = 2;
        }

        // Trigger preview matches with pending files
        $this->previewMatchesEarly();
    }

    /**
     * Handle upload progress update from JavaScript
     */
    public function handleUploadProgress(array $progressData): void
    {
        // Don't process progress updates during cancellation
        if ($this->cancelling) {
            return;
        }

        $fileId = $progressData['id'];

        // Update progress tracking (keyed by ID for quick lookup)
        if (isset($this->fileUploadProgress[$fileId])) {
            $this->fileUploadProgress[$fileId]['progress'] = $progressData['progress'];
            $this->fileUploadProgress[$fileId]['status'] = $progressData['status'];
        }

        // Find and update file in pendingFiles array (using numeric indices)
        foreach ($this->pendingFiles as $index => $file) {
            if ($file['id'] === $fileId) {
                $this->pendingFiles[$index]['progress'] = $progressData['progress'];
                $this->pendingFiles[$index]['status'] = $progressData['status'];
                break;
            }
        }
    }

    /**
     * Handle file uploaded event (called when S3 upload completes with s3_key)
     */
    public function handleFileUploaded(array $fileData): void
    {
        // Don't process uploads during cancellation
        if ($this->cancelling) {
            return;
        }

        $fileId = $fileData['id'];

        Log::info('File uploaded to S3', [
            'file_id' => $fileId,
            'file_name' => $fileData['name'],
            's3_key' => $fileData['key'],
        ]);

        // Find and update file in pendingFiles array (using numeric indices)
        foreach ($this->pendingFiles as $index => $file) {
            if ($file['id'] === $fileId) {
                $this->pendingFiles[$index]['s3_key'] = $fileData['key'];
                $this->pendingFiles[$index]['status'] = 'complete';
                $this->pendingFiles[$index]['progress'] = 100;
                break;
            }
        }

        // Update progress tracking (keyed by ID)
        if (isset($this->fileUploadProgress[$fileId])) {
            $this->fileUploadProgress[$fileId]['progress'] = 100;
            $this->fileUploadProgress[$fileId]['status'] = 'complete';
        }

        // Check if all files are uploaded
        $this->checkUploadCompletion();
    }

    /**
     * Check if all pending files have been uploaded (have s3_key)
     */
    protected function checkUploadCompletion(): void
    {
        $allComplete = true;
        foreach ($this->pendingFiles as $file) {
            if (! isset($file['s3_key']) || empty($file['s3_key'])) {
                $allComplete = false;
                break;
            }
        }

        if ($allComplete && count($this->pendingFiles) > 0) {
            $this->isUploadingToS3 = false;
            Log::info('All files uploaded to S3', [
                'file_count' => count($this->pendingFiles),
            ]);
        }
    }

    /**
     * Preview matches early (with pending files that may not have S3 keys yet)
     * This allows users to start matching based on file names while uploads continue
     */
    public function previewMatchesEarly(): void
    {
        try {
            // Get existing files for matching
            $existingFiles = $this->pitch->files()->latestVersions()->get();

            // pendingFiles already has numeric indices, use directly
            // No need for array_values since we maintain numeric keys from the start
            $filesToMatch = $this->pendingFiles;

            // Auto-match by name only (S3 keys will be added later)
            $matchResult = $this->fileManagementService->matchFilesByName($existingFiles, $filesToMatch);

            Log::info('Early matching results', [
                'matched_count' => count($matchResult['matched']),
                'unmatched_count' => count($matchResult['unmatched']),
                'pending_files_count' => count($this->pendingFiles),
            ]);

            // Convert matched array to use numeric indices as keys
            $autoMatches = [];
            foreach ($filesToMatch as $index => $fileData) {
                foreach ($matchResult['matched'] as $fileId => $matchedData) {
                    if ($matchedData['name'] === $fileData['name']) {
                        $matchedFile = \App\Models\PitchFile::find($fileId);
                        if ($matchedFile) {
                            // Store the matched file ID (latest version) directly
                            // Backend will automatically get root when creating version
                            $autoMatches[$index] = $matchedFile->id;
                            Log::info('Early match found', [
                                'numeric_index' => $index,
                                'file_name' => $fileData['name'],
                                'matched_file_id' => $matchedFile->id,
                                'file_version' => $matchedFile->getVersionLabel() ?? 'V1',
                            ]);
                        }
                        break;
                    }
                }
            }

            // Find indices of new files (unmatched)
            $newFiles = [];
            foreach ($filesToMatch as $index => $fileData) {
                if (! isset($autoMatches[$index])) {
                    $newFiles[] = $index;
                }
            }

            Log::info('Early matching complete', [
                'autoMatches' => $autoMatches,
                'newFiles' => $newFiles,
            ]);

            $this->autoMatches = $autoMatches;
            $this->newFiles = $newFiles;
            $this->manualOverrides = $autoMatches;

        } catch (\Exception $e) {
            Log::error('Error in early preview matching', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Note: File upload handling now uses direct property assignment from JavaScript
     * via $wire.set('uploadedFilesData', ...) followed by $wire.call('previewMatches')
     * This avoids Livewire event serialization issues that corrupted array data.
     *
     * This method is still used for the legacy flow when files are already uploaded.
     */
    public function previewMatches(): void
    {
        try {
            Log::info('Preview matches called', [
                'uploaded_files_count' => count($this->uploadedFilesData),
                'uploaded_files_data' => $this->uploadedFilesData,
            ]);

            // Get existing files for matching
            $existingFiles = $this->pitch->files()->latestVersions()->get();

            // Auto-match uploaded files to existing files
            $matchResult = $this->fileManagementService->matchFilesByName($existingFiles, $this->uploadedFilesData);

            Log::info('Service matching results', [
                'matched_count' => count($matchResult['matched']),
                'matched_structure' => $matchResult['matched'],
                'unmatched_count' => count($matchResult['unmatched']),
                'unmatched_structure' => $matchResult['unmatched'],
            ]);

            // Convert matched array to use file array indices instead of IDs as keys
            // Service returns: matched[existingFileId] = uploadedFileData
            // Store matched file ID (latest version) - backend will get root when creating version
            $autoMatches = [];
            foreach ($this->uploadedFilesData as $index => $fileData) {
                Log::info('Checking uploaded file for match', [
                    'index' => $index,
                    'file_name' => $fileData['name'],
                    's3_key' => $fileData['s3_key'],
                ]);

                foreach ($matchResult['matched'] as $fileId => $matchedData) {
                    Log::info('Comparing with matched data', [
                        'existing_file_id' => $fileId,
                        'matched_name' => $matchedData['name'],
                        'matched_s3_key' => $matchedData['s3_key'],
                        'names_match' => $matchedData['name'] === $fileData['name'],
                        's3_keys_match' => $matchedData['s3_key'] === $fileData['s3_key'],
                    ]);

                    if ($matchedData['name'] === $fileData['name'] && $matchedData['s3_key'] === $fileData['s3_key']) {
                        // Store the matched file ID (latest version) directly
                        // Backend will automatically get root when creating version
                        $matchedFile = \App\Models\PitchFile::find($fileId);
                        if ($matchedFile) {
                            $autoMatches[$index] = $matchedFile->id;
                            Log::info('Found match!', [
                                'index' => $index,
                                'matched_file_id' => $matchedFile->id,
                                'file_version' => $matchedFile->getVersionLabel() ?? 'V1',
                            ]);
                        }
                        break;
                    }
                }
            }

            // Find indices of new files (unmatched)
            $newFiles = [];
            foreach ($this->uploadedFilesData as $index => $fileData) {
                if (! isset($autoMatches[$index])) {
                    $newFiles[] = $index;
                }
            }

            Log::info('Final conversion results', [
                'autoMatches' => $autoMatches,
                'newFiles' => $newFiles,
            ]);

            $this->autoMatches = $autoMatches;
            $this->newFiles = $newFiles;

            // Initialize manualOverrides with autoMatches so dropdowns show pre-selected values
            // This allows the wire:model binding to work correctly, showing the matched file
            $this->manualOverrides = $autoMatches;

            // Move to step 2 to show preview
            $this->currentStep = 2;

        } catch (\Exception $e) {
            Log::error('Error previewing bulk version matches', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'Failed to process files: '.$e->getMessage();
            Toaster::error('Failed to process files');
        }
    }

    public function updateManualMatch(string $fileKey, $matchedFileId): void
    {
        // Handle both null and empty string as "unmatch this file"
        if ($matchedFileId === null || $matchedFileId === '' || $matchedFileId === '0') {
            unset($this->manualOverrides[$fileKey]);
        } else {
            $this->manualOverrides[$fileKey] = (int) $matchedFileId;
        }
    }

    public function confirmAndUpload(): void
    {
        // Ensure all files have been uploaded before proceeding
        if (! $this->allFilesUploaded()) {
            Toaster::error('Please wait for all files to finish uploading.');

            return;
        }

        $this->currentStep = 3;
        $this->uploading = true;
        $this->uploadProgress = 10;

        try {
            // Use pending files (which now have S3 keys) as the data source
            $filesToProcess = array_values($this->pendingFiles);

            // manualOverrides is initialized with autoMatches and modified by user
            // It already contains the complete final state, no need to merge
            $finalMatches = $this->manualOverrides;

            // Convert matches from [index => fileId] to [fileId => fileData]
            $manualMatchesForService = [];
            foreach ($finalMatches as $index => $fileId) {
                if (isset($filesToProcess[$index])) {
                    $manualMatchesForService[$fileId] = $filesToProcess[$index];
                }
            }

            $this->uploadProgress = 30;

            // Call service directly instead of making HTTP request
            $result = $this->fileManagementService->bulkUploadFileVersions(
                $this->pitch,
                $filesToProcess,
                Auth::user(),
                $manualMatchesForService
            );

            $this->uploadProgress = 80;

            $versionCount = count($result['created_versions']);
            $newFileCount = count($result['new_files']);

            $message = [];
            if ($versionCount > 0) {
                $message[] = "{$versionCount} new version".($versionCount !== 1 ? 's' : '');
            }
            if ($newFileCount > 0) {
                $message[] = "{$newFileCount} new file".($newFileCount !== 1 ? 's' : '');
            }

            $this->uploadProgress = 100;

            Toaster::success('Uploaded '.implode(' and ', $message));

            // Dispatch events to refresh file list
            // Primary event - matches pattern from other upload operations
            $this->dispatch('filesUploaded', [
                'count' => $versionCount + $newFileCount,
                'model_type' => get_class($this->pitch),
                'model_id' => $this->pitch->id,
            ]);

            // Legacy events for backward compatibility
            $this->dispatch('refreshFiles');
            $this->dispatch('bulkVersionsUploaded', [
                'createdVersions' => $versionCount,
                'newFiles' => $newFileCount,
            ]);

            // Mark process as completed successfully (prevents cleanup of S3 files)
            $this->processCompleted = true;

            // Close modal
            $this->close();

        } catch (\Exception $e) {
            Log::error('Error during bulk version upload', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = $e->getMessage();
            Toaster::error('Failed to upload files: '.$e->getMessage());
            $this->uploading = false;
            $this->uploadProgress = 0;
        }
    }

    public function goBackToStep1(): void
    {
        $this->currentStep = 1;
        $this->reset(['uploadedFilesData', 'autoMatches', 'newFiles', 'manualOverrides', 'errorMessage']);

        // Clear JavaScript-side bulk uploads array
        $this->dispatch('clearBulkUploads');
    }

    public function close(): void
    {
        // Mark as cancelling to prevent race conditions with event handlers
        $this->cancelling = true;

        // If uploads are in progress, cancel them
        if ($this->isUploadingToS3 && ! empty($this->pendingFiles)) {
            Log::info('Cancelling active uploads', [
                'pitch_id' => $this->pitch?->id,
                'pending_files_count' => count($this->pendingFiles),
                'is_uploading_to_s3' => $this->isUploadingToS3,
            ]);

            // Dispatch event to JavaScript to cancel Uppy uploads
            $this->dispatch('cancel-bulk-version-upload');
        }

        // If process wasn't completed successfully, clean up orphaned S3 files
        if (! $this->processCompleted && ! empty($this->pendingFiles)) {
            $this->cleanupOrphanedS3Files();
        }

        $this->isOpen = false;
        $this->reset([
            'pitch',
            'currentStep',
            'uploadedFilesData',
            'autoMatches',
            'newFiles',
            'manualOverrides',
            'uploading',
            'errorMessage',
            'uploadProgress',
            'pendingFiles',
            'fileUploadProgress',
            'isUploadingToS3',
            'processCompleted',
            'cancelling',
        ]);

        // Clear JavaScript-side bulk uploads array and mark uploads as complete
        $this->dispatch('clearBulkUploads');
        $this->dispatch('uploadsComplete');
    }

    /**
     * Clean up orphaned S3 files when user cancels without completing
     */
    protected function cleanupOrphanedS3Files(): void
    {
        // Count how many files have been uploaded to S3
        $completedFiles = array_filter($this->pendingFiles, fn ($file) => isset($file['s3_key']) && ! empty($file['s3_key']));

        Log::info('Cleaning up orphaned S3 files from cancelled bulk upload', [
            'pitch_id' => $this->pitch?->id,
            'total_pending' => count($this->pendingFiles),
            'completed_to_delete' => count($completedFiles),
        ]);

        foreach ($this->pendingFiles as $file) {
            // Only delete files that have been uploaded to S3 (have s3_key)
            if (isset($file['s3_key']) && ! empty($file['s3_key'])) {
                try {
                    if (\Storage::disk('s3')->exists($file['s3_key'])) {
                        \Storage::disk('s3')->delete($file['s3_key']);
                        Log::info('Deleted orphaned S3 file from cancelled upload', [
                            'file_name' => $file['name'],
                            's3_key' => $file['s3_key'],
                            'pitch_id' => $this->pitch?->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete orphaned S3 file', [
                        'file_name' => $file['name'] ?? 'unknown',
                        's3_key' => $file['s3_key'],
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail on cleanup errors - continue with other files
                }
            }
        }
    }

    #[Computed]
    public function availableFilesForMatching(): Collection
    {
        if (! $this->pitch) {
            return collect();
        }

        // Get latest versions of each file family
        // Shows current version in UI, backend automatically gets root when creating version
        return $this->pitch->files()
            ->latestVersions()
            ->orderBy('file_name')
            ->get();
    }

    /**
     * Get IDs of files that are already matched
     * Used to prevent duplicate matching in dropdowns
     */
    #[Computed]
    public function alreadyMatchedFileIds(): array
    {
        return array_filter(
            array_values($this->manualOverrides),
            fn ($id) => $id !== null && $id !== ''
        );
    }

    #[Computed]
    public function matchedCount(): int
    {
        // manualOverrides now contains all matches (initialized from autoMatches, then user can change)
        // So we only need to count manualOverrides to avoid double-counting
        return count($this->manualOverrides);
    }

    #[Computed]
    public function newFilesCount(): int
    {
        return count($this->currentlyUnmatchedFiles());
    }

    /**
     * Get array of file indices that are currently matched (have a value in manualOverrides)
     */
    #[Computed]
    public function currentlyMatchedFiles(): array
    {
        $matched = [];
        $filesToCheck = ! empty($this->pendingFiles) ? $this->pendingFiles : $this->uploadedFilesData;

        foreach ($filesToCheck as $index => $fileData) {
            if (isset($this->manualOverrides[$index]) && $this->manualOverrides[$index] !== null) {
                $matched[] = $index;
            }
        }

        return $matched;
    }

    /**
     * Get array of file indices that are currently unmatched (no value in manualOverrides)
     */
    #[Computed]
    public function currentlyUnmatchedFiles(): array
    {
        $unmatched = [];
        $filesToCheck = ! empty($this->pendingFiles) ? $this->pendingFiles : $this->uploadedFilesData;

        foreach ($filesToCheck as $index => $fileData) {
            if (! isset($this->manualOverrides[$index]) || $this->manualOverrides[$index] === null) {
                $unmatched[] = $index;
            }
        }

        return $unmatched;
    }

    /**
     * Check if all pending files have been uploaded to S3 (have s3_key)
     */
    #[Computed]
    public function allFilesUploaded(): bool
    {
        if (empty($this->pendingFiles)) {
            return false;
        }

        foreach ($this->pendingFiles as $file) {
            if (! isset($file['s3_key']) || empty($file['s3_key'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get summary of upload progress (for display)
     */
    #[Computed]
    public function uploadProgressSummary(): array
    {
        $total = count($this->pendingFiles);
        $completed = 0;
        $uploading = 0;
        $queued = 0;

        foreach ($this->pendingFiles as $file) {
            $status = $file['status'] ?? 'queued';
            if ($status === 'complete') {
                $completed++;
            } elseif ($status === 'uploading') {
                $uploading++;
            } else {
                $queued++;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'uploading' => $uploading,
            'queued' => $queued,
            'percentComplete' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.bulk-version-upload-modal');
    }
}
