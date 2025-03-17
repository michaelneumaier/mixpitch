<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Http\Controllers\ProjectController;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Masmerise\Toaster\Toaster;

class ManageProject extends Component
{
    use WithFileUploads;
    public Project $project;

    public $hasPreviewTrack = false;
    public $audioUrl;
    public $isUploading = false;
    public $uploadedFiles = [];
    public $tempUploadedFiles = []; // For storing file metadata
    public $newUploadedFiles = []; // For accumulating new files
    public $track; // For single file upload (keeping for backward compatibility)
    public $fileSizes = []; // Store file sizes
    public $newlyAddedFileKeys = []; // Track which files were just added
    public $newlyUploadedFileIds = []; // Track IDs of newly uploaded files
    
    // Sequential upload properties
    public $isProcessingQueue = false;
    public $uploadingFileKey = null;
    public $uploadProgress = 0;
    public $uploadProgressMessage = '';
    
    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;

    public function mount()
    {
        if ($this->project->hasPreviewTrack()) {
            $this->audioUrl = $this->project->previewTrackPath();
            $this->hasPreviewTrack = true;
        }
        
        // Initialize storage information
        $this->updateStorageInfo();
    }

    /**
     * Called when new files are selected
     * This method accumulates files instead of replacing them
     */
    public function updatedNewUploadedFiles()
    {
        $this->newlyAddedFileKeys = []; // Reset the tracking array

        $startIndex = count($this->uploadedFiles);

        foreach ($this->newUploadedFiles as $file) {
            $this->uploadedFiles[] = $file;
            // Calculate and store file size
            $this->fileSizes[] = $this->formatFileSize($file->getSize());
            // Track the index of this newly added file
            $this->newlyAddedFileKeys[] = $startIndex;
            $startIndex++;
        }

        // Reset the new files input to allow for more files to be added
        $this->newUploadedFiles = [];

        // We'll use JavaScript setTimeout in the blade file instead
        $this->dispatch('new-files-added');
    }

    /**
     * Clear the highlight for newly added files
     */
    public function clearHighlights()
    {
        $this->newlyAddedFileKeys = [];
    }

    /**
     * Format file size in human-readable format
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function publish()
    {
        $this->project->publish();
    }

    public function unpublish()
    {
        $this->project->unpublish();
    }

    public function togglePreviewTrack(ProjectFile $file)
    {
        if ($this->hasPreviewTrack == true && $this->project->preview_track == $file->id) {
            $this->clearPreviewTrack();
        } else {
            $this->project->preview_track = $file->id;
            $this->project->save();
            $this->audioUrl = $file->fullFilePath;

            $this->hasPreviewTrack = true;
            $this->dispatch('audioUrlUpdated', $this->audioUrl);
        }

        // Optionally, emit an event or flash a session message to confirm the change
        session()->flash('message', 'Preview track updated successfully.');
    }

    public function clearPreviewTrack()
    {
        $this->project->preview_track = null; // Clear the preview track
        $this->project->save();
        $this->hasPreviewTrack = false;

        // Optionally, emit an event or flash a session message to confirm the change
        session()->flash('message', 'Preview track cleared successfully.');
    }

    /**
     * Upload multiple files
     */
    public function uploadFiles()
    {
        $this->validate([
            'uploadedFiles.*' => 'required|file|mimes:mp3,wav,ogg,aac|max:102400', // 100MB max
        ]);

        $this->newlyUploadedFileIds = []; // Reset the tracking array

        foreach ($this->uploadedFiles as $file) {
            $controller = new ProjectController();
            $fileId = $controller->storeTrack($file, $this->project);
            if ($fileId) {
                $this->newlyUploadedFileIds[] = $fileId;
            }
        }

        $this->isUploading = false;
        $this->uploadedFiles = []; // Clear the files after upload
        $this->fileSizes = []; // Clear file sizes
        $this->project->refresh(); // Refresh project files relation

        session()->flash('message', 'Tracks uploaded successfully.');

        // Clear the highlight after 2 seconds
        $this->dispatch('new-uploads-completed');
    }

    /**
     * Upload a single track
     */
    public function uploadTrack()
    {
        $this->validate([
            'track' => 'required|file|mimes:mp3,wav,ogg,aac|max:102400', // 100MB max
        ]);

        $this->newlyUploadedFileIds = []; // Reset the tracking array

        $controller = new ProjectController();
        $fileId = $controller->storeTrack($this->track, $this->project);
        if ($fileId) {
            $this->newlyUploadedFileIds[] = $fileId;
        }

        $this->track = null; // Clear the file input
        $this->fileSizes = []; // Clear file sizes
        $this->project->refresh(); // Refresh project files relation

        session()->flash('message', 'Track uploaded successfully.');

        // Clear the highlight after 2 seconds
        $this->dispatch('new-uploads-completed');
    }

    /**
     * Remove a file from the upload queue
     */
    public function removeUploadedFile($key)
    {
        if (isset($this->tempUploadedFiles[$key])) {
            unset($this->tempUploadedFiles[$key]);
            unset($this->fileSizes[$key]);

            // Re-index arrays
            $this->tempUploadedFiles = array_values($this->tempUploadedFiles);
            $this->fileSizes = array_values($this->fileSizes);

            // Clear the newly added keys since indexes have changed
            $this->newlyAddedFileKeys = [];
        }
    }

    public function deleteFile($fileId)
    {
        $file = ProjectFile::findOrFail($fileId);
        $project = $this->project; // Ensure you have a way to get the current project context

        // Instantiate the controller
        $controller = new ProjectController();

        // Call the method
        $controller->deleteFile($project, $file);

        // Refresh the project model to get updated file list
        $this->project->refresh();
        
        // Update storage information display
        $this->updateStorageInfo();
    }

    /**
     * Clear the highlight for newly uploaded files
     */
    public function clearUploadHighlights()
    {
        $this->newlyUploadedFileIds = [];
    }

    public function render()
    {
        $approvedPitches = $this->getApprovedAndCompletedPitches();
        return view('livewire.project.page.manage-project', [
            'approvedPitches' => $approvedPitches,
            'hasCompletedPitch' => $this->project->pitches()->where('status', \App\Models\Pitch::STATUS_COMPLETED)->exists(),
            'hasMultipleApprovedPitches' => $this->hasMultipleApprovedPitches(),
            'approvedPitchesCount' => $this->getApprovedPitchesCount()
        ]);
    }

    /**
     * Check if the project has multiple approved pitches
     *
     * @return bool
     */
    protected function hasMultipleApprovedPitches()
    {
        return $this->getApprovedPitchesCount() > 1;
    }

    /**
     * Get the count of approved pitches
     *
     * @return int
     */
    protected function getApprovedPitchesCount()
    {
        return $this->project->pitches()
            ->where('status', \App\Models\Pitch::STATUS_APPROVED)
            ->count();
    }

    /**
     * Get approved and completed pitches with their accepted snapshots
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getApprovedAndCompletedPitches()
    {
        return $this->project->pitches()
            ->whereIn('status', ['approved', 'completed'])
            ->with(['snapshots' => function ($query) {
                $query->where('status', 'accepted');
            }, 'user'])
            ->get();
    }

    /**
     * Queue files for upload
     */
    public function queueFilesForUpload()
    {
        // This is now handled by JavaScript that directly sets tempUploadedFiles and fileSizes
        $this->newlyAddedFileKeys = array_keys($this->tempUploadedFiles);
        $this->dispatch('new-files-added');
    }

    /**
     * Process the queued files one by one
     */
    public function processQueuedFiles()
    {        
        if (empty($this->tempUploadedFiles)) {
            Toaster::warning('No files selected for upload.');
            return;
        }
        
        // Check file sizes against limits before starting uploads
        $totalSizeToUpload = 0;
        $tooLargeFiles = [];
        
        foreach ($this->tempUploadedFiles as $key => $file) {
            $fileSize = $file['size'] ?? 0;
            $totalSizeToUpload += $fileSize;
            
            // Check individual file size limit
            if (!Project::isFileSizeAllowed($fileSize)) {
                $tooLargeFiles[] = [
                    'name' => $file['name'],
                    'size' => Project::formatBytes($fileSize),
                    'limit' => Project::formatBytes(Project::MAX_FILE_SIZE_BYTES)
                ];
            }
        }
        
        // Check project storage capacity
        if (!$this->project->hasStorageCapacity($totalSizeToUpload)) {
            $this->project->refresh();
            $this->updateStorageInfo();
            
            Toaster::error(
                'Project storage limit exceeded. Available space: ' . 
                Project::formatBytes($this->project->getRemainingStorageBytes()) . 
                '. Required: ' . 
                Project::formatBytes($totalSizeToUpload)
            );
            return;
        }
        
        // Handle files that are too large
        if (!empty($tooLargeFiles)) {
            $message = count($tooLargeFiles) === 1 
                ? 'One file exceeds the maximum allowed size of ' . Project::formatBytes(Project::MAX_FILE_SIZE_BYTES)
                : count($tooLargeFiles) . ' files exceed the maximum allowed size of ' . Project::formatBytes(Project::MAX_FILE_SIZE_BYTES);
                
            foreach ($tooLargeFiles as $file) {
                $message .= "\n• {$file['name']} ({$file['size']})";
            }
            
            Toaster::error($message);
            return;
        }
        
        // Reset tracking variables
        $this->isProcessingQueue = true;
        $this->newlyUploadedFileIds = []; 
        $this->uploadProgress = 0;
        $this->uploadProgressMessage = 'Preparing to upload files...';
        
        $totalFiles = count($this->tempUploadedFiles);
        
        // Process the first file
        $this->processNextFile(0, $totalFiles);
    }
    
    /**
     * Process the next file in the queue
     */
    public function processNextFile($currentIndex, $totalFiles)
    {
        if ($currentIndex >= count($this->tempUploadedFiles)) {
            // All files processed
            $this->finishUploadProcess();
            return;
        }
        
        $this->uploadingFileKey = $currentIndex;
        $this->uploadProgress = round(($currentIndex / $totalFiles) * 100);
        $this->uploadProgressMessage = "Uploading file " . ($currentIndex + 1) . " of " . $totalFiles;
        
        \Log::info('Processing next project file', [
            'index' => $currentIndex,
            'total' => $totalFiles,
            'project_id' => $this->project->id
        ]);
        
        // Dispatch event to trigger JS file upload
        $this->dispatch('uploadNextFile', index: $currentIndex, total: $totalFiles);
    }
    
    /**
     * Handle successful file upload
     */
    public function uploadSuccess($index, $filePath, $fileId)
    {
        if (!isset($this->tempUploadedFiles[$index])) {
            \Log::error('File index not found in tempUploadedFiles', [
                'index' => $index,
                'project_id' => $this->project->id
            ]);
            return;
        }
        
        // Add to newly uploaded files
        $this->newlyUploadedFileIds[] = $fileId;
        
        // Update progress
        $totalFiles = count($this->tempUploadedFiles);
        $this->uploadProgress = round((($index + 1) / $totalFiles) * 100);
        
        // Refresh the project and update storage info
        $this->project->refresh();
        $this->updateStorageInfo();
        
        // Process the next file
        $this->processNextFile($index + 1, $totalFiles);
    }
    
    /**
     * Handle failed file upload
     */
    public function uploadFailed($index, $errorMessage)
    {
        \Log::error('File upload failed', [
            'project_id' => $this->project->id,
            'file_index' => $index,
            'error' => $errorMessage
        ]);
        
        Toaster::error("Failed to upload file: " . $errorMessage);
        
        // Process the next file, skipping this one
        $totalFiles = count($this->tempUploadedFiles);
        $this->processNextFile(($index !== null ? $index : 0) + 1, $totalFiles);
    }
    
    /**
     * Finish the upload process and clean up
     */
    protected function finishUploadProcess()
    {
        $this->isProcessingQueue = false;
        $this->uploadingFileKey = null;
        $this->uploadProgress = 100;
        $this->uploadProgressMessage = 'Upload complete!';
        
        // Clear the queue
        $this->tempUploadedFiles = [];
        $this->fileSizes = [];
        
        // Refresh the project to update files
        $this->project->refresh();
        
        Toaster::success('Files uploaded successfully.');
        $this->dispatch('new-uploads-completed');
    }

    /**
     * Update storage information for the view
     */
    public function updateStorageInfo()
    {
        $this->storageUsedPercentage = $this->project->getStorageUsedPercentage();
        $this->storageLimitMessage = $this->project->getStorageLimitMessage();
        $this->storageRemaining = Project::formatBytes($this->project->getRemainingStorageBytes());
    }
}
