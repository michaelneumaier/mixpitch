<?php

namespace App\Livewire\ClientPortal;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class PostApprovalSuccessCard extends Component
{
    public Project $project;

    public Pitch $pitch;

    public Collection $milestones;

    public bool $showDownloadModal = false;

    public function mount(Project $project, Pitch $pitch, Collection $milestones)
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->milestones = $milestones;
    }

    public function openDownloadModal()
    {
        $this->showDownloadModal = true;
    }

    public function closeDownloadModal()
    {
        $this->showDownloadModal = false;
    }

    public function getDeliverableFilesProperty()
    {
        // Get files from the latest snapshot if available
        $latestSnapshot = $this->pitch->snapshots()->latest()->first();

        if ($latestSnapshot && isset($latestSnapshot->snapshot_data['file_ids'])) {
            return PitchFile::whereIn('id', $latestSnapshot->snapshot_data['file_ids'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Fallback to all pitch files if no snapshot exists
        return $this->pitch->files()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function downloadFile($fileId)
    {
        // Generate a temporary signed URL for the file download
        $downloadUrl = URL::temporarySignedRoute(
            'client.portal.download_file',
            now()->addMinutes(5),
            [
                'project' => $this->project->id,
                'pitchFile' => $fileId,
            ]
        );

        // Dispatch browser event to trigger download
        $this->dispatch('download-file', url: $downloadUrl);
    }

    public function formatFileSize(int $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '0 bytes';
        }

        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function getFileIcon($mimeType): string
    {
        if (str_starts_with($mimeType, 'audio/')) {
            return 'musical-note';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video-camera';
        } elseif ($mimeType === 'application/pdf') {
            return 'document-text';
        } elseif (str_starts_with($mimeType, 'image/')) {
            return 'photo';
        } elseif ($mimeType === 'application/zip') {
            return 'archive-box';
        } else {
            return 'document';
        }
    }

    public function render()
    {
        return view('livewire.client-portal.post-approval-success-card');
    }
}
