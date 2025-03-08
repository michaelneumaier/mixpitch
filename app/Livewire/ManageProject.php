<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Http\Controllers\ProjectController;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ManageProject extends Component
{
    use WithFileUploads;
    public Project $project;

    public $hasPreviewTrack = false;
    public $audioUrl;
    public $isUploading = false;
    public $uploadedFiles = [];
    public $newUploadedFiles = []; // For accumulating new files
    public $track; // For single file upload (keeping for backward compatibility)
    public $fileSizes = []; // Store file sizes
    public $newlyAddedFileKeys = []; // Track which files were just added
    public $newlyUploadedFileIds = []; // Track IDs of newly uploaded files

    public function mount()
    {
        if ($this->project->hasPreviewTrack()) {
            $this->audioUrl = $this->project->previewTrackPath();
            $this->hasPreviewTrack = true;
        }
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
        if (isset($this->uploadedFiles[$key])) {
            unset($this->uploadedFiles[$key]);
            unset($this->fileSizes[$key]);

            // Re-index arrays
            $this->uploadedFiles = array_values($this->uploadedFiles);
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

        // Optionally, emit an event or refresh part of the component
        // Or if you keep a local collection of files in the component:
        $this->project->refresh();
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
            'hasCompletedPitch' => $this->project->pitches()->where('status', \App\Models\Pitch::STATUS_COMPLETED)->exists()
        ]);
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
}
