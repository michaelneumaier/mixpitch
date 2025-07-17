<?php

namespace App\Livewire\Pitch\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\FileManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ManageContestPitch extends Component
{
    use WithFileUploads;
    use WithPagination;
    use AuthorizesRequests;

    public Pitch $pitch;
    public Project $project;
    
    // Storage tracking
    public $storageUsedPercentage = 0;
    public $storageLimitMessage = '';
    public $storageRemaining = 0;
    
    // Contest-specific storage limit (100MB)
    const CONTEST_STORAGE_LIMIT = 100 * 1024 * 1024; // 100MB in bytes
    
    // Modal State
    public $showDeleteModal = false;
    public $fileIdToDelete = null;

    protected $listeners = [
        'refreshContestData' => 'refreshContestData',
        'filesUploaded' => 'refreshContestData',
        'fileDeleted' => 'refreshContestData',
        'echo:refreshContestData' => 'refreshContestData',  // Alternative event name
    ];

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->project = $pitch->project;
        
        // Ensure this is actually a contest pitch
        if (!$this->project->isContest() || $this->pitch->status !== Pitch::STATUS_CONTEST_ENTRY) {
            abort(404, 'This component is only for contest entries.');
        }
        
        // Authorization check
        $this->authorize('update', $this->pitch);
        
        $this->updateStorageInfo();
    }

    public function updateStorageInfo()
    {
        // Use contest-specific storage limit
        $storageLimit = self::CONTEST_STORAGE_LIMIT;
        $storageUsed = $this->pitch->total_storage_used;
        
        $this->storageUsedPercentage = round(($storageUsed / $storageLimit) * 100, 2);
        $this->storageRemaining = $storageLimit - $storageUsed;
        $this->storageLimitMessage = $this->formatFileSize($storageUsed) . ' of ' . $this->formatFileSize($storageLimit);
    }

    public function formatFileSize($bytes)
    {
        return Pitch::formatBytes($bytes);
    }

    public function confirmDeleteFile($fileId)
    {
        $this->fileIdToDelete = $fileId;
        $this->showDeleteModal = true;
    }

    public function deleteFile()
    {
        if (!$this->fileIdToDelete) {
            return;
        }

        try {
            $file = $this->pitch->files()->findOrFail($this->fileIdToDelete);
            
            // Authorization check
            if (!Gate::allows('deleteFile', $file)) {
                Toaster::error('You are not authorized to delete this file.');
                return;
            }
            
            $fileName = $file->file_name;
            $fileSize = $file->size;
            
            // Delete the file using FileManagementService
            app(FileManagementService::class)->deletePitchFile($file);
            
            // Update storage tracking
            $this->pitch->decrementStorageUsed($fileSize);
            $this->updateStorageInfo();
            
            Toaster::success("File '{$fileName}' deleted successfully.");
            
        } catch (\Exception $e) {
            Log::error('Error deleting contest entry file', [
                'file_id' => $this->fileIdToDelete,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to delete file. Please try again.');
        } finally {
            $this->showDeleteModal = false;
            $this->fileIdToDelete = null;
        }
    }

    public function downloadFile($fileId)
    {
        try {
            $file = $this->pitch->files()->findOrFail($fileId);
            
            // Authorization check
            if (!Gate::allows('downloadFile', $file)) {
                Toaster::error('You are not authorized to download this file.');
                return;
            }
            
            return response()->download($file->getStoragePath(), $file->file_name);
            
        } catch (\Exception $e) {
            Log::error('Error downloading contest entry file', [
                'file_id' => $fileId,
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to download file. Please try again.');
        }
    }

    public function deletePitch()
    {
        try {
            $this->authorize('delete', $this->pitch);
            
            $projectSlug = $this->project->slug;
            $this->pitch->delete();
            
            Toaster::success('Contest entry deleted successfully.');
            
            return redirect()->route('projects.show', $projectSlug);
            
        } catch (\Exception $e) {
            Log::error('Error deleting contest pitch', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage()
            ]);
            Toaster::error('Failed to delete contest entry. Please try again.');
        }
    }

    public function submitEntry()
    {
        try {
            $this->authorize('update', $this->pitch);
            
            // Check if entry already submitted
            if ($this->pitch->submitted_at) {
                Toaster::warning('Contest entry has already been submitted.');
                return;
            }
            
            // Check if entry has files
            if ($this->pitch->files()->count() === 0) {
                Toaster::error('Please upload at least one file before submitting your contest entry.');
                return;
            }
            
            // Check if contest is still open
            if ($this->project->isSubmissionPeriodClosed()) {
                Toaster::error('Contest submissions are closed.');
                return;
            }
            
            // Create snapshot for the contest entry submission
            $previousSnapshot = $this->pitch->snapshots()->latest()->first();
            $newVersion = $previousSnapshot ? ($previousSnapshot->snapshot_data['version'] ?? 0) + 1 : 1;

            $snapshotData = [
                'version' => $newVersion,
                'file_ids' => $this->pitch->files->pluck('id')->toArray(),
                'submission_type' => 'contest_entry',
                'submitted_at' => now()->toISOString(),
                'previous_snapshot_id' => $previousSnapshot?->id,
            ];

            $snapshot = $this->pitch->snapshots()->create([
                'user_id' => Auth::id(),
                'project_id' => $this->project->id,
                'snapshot_data' => $snapshotData,
                'status' => \App\Models\PitchSnapshot::STATUS_PENDING, // Submitted for contest judging
            ]);

            // Mark as submitted and link to snapshot
            $this->pitch->update([
                'submitted_at' => now(),
                'current_snapshot_id' => $snapshot->id
            ]);
            
            // Create submission event
            $this->pitch->events()->create([
                'event_type' => 'contest_entry_submitted',
                'comment' => "Contest entry submitted for judging (Version {$newVersion}).",
                'status' => $this->pitch->status,
                'snapshot_id' => $snapshot->id,
                'created_by' => Auth::id(),
                'metadata' => ['submission_type' => 'contest_entry'],
            ]);
            
            // Reload the pitch to reflect changes
            $this->pitch->refresh();
            
            Toaster::success('Contest entry submitted successfully! You can no longer upload or modify files.');
            
            Log::info('Contest entry submitted with snapshot', [
                'pitch_id' => $this->pitch->id,
                'project_id' => $this->project->id,
                'snapshot_id' => $snapshot->id,
                'submitted_at' => $this->pitch->submitted_at
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error submitting contest entry', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('Failed to submit contest entry. Please try again.');
        }
    }

    public function refreshContestData()
    {
        // Reload the pitch model completely from the database to ensure fresh storage data
        $this->pitch = Pitch::with('files')->find($this->pitch->id);
        $this->updateStorageInfo();
    }

    /**
     * Check if the current user can upload files to this contest pitch.
     */
    public function getCanUploadFilesProperty(): bool
    {
        return Gate::allows('uploadFile', $this->pitch);
    }

    public function render()
    {
        // Always recalculate storage info to ensure fresh values
        $this->updateStorageInfo();
        
        $files = $this->pitch->files()->orderBy('created_at', 'desc')->paginate(10);
        
        return view('livewire.pitch.component.manage-contest-pitch', [
            'files' => $files,
            'storageUsedPercentage' => $this->storageUsedPercentage,
            'storageLimitMessage' => $this->storageLimitMessage,
            'storageRemaining' => $this->storageRemaining,
        ]);
    }
} 