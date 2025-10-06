<?php

namespace App\Livewire\ClientPortal;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProducerDeliverables extends Component
{
    public Project $project;

    public Pitch $pitch;

    public array $branding = [];

    public bool $isPreview = false;

    public ?int $selectedSnapshotId = null;

    /**
     * Prepare snapshot history data for client view.
     */
    #[Computed]
    public function snapshotHistory(): Collection
    {
        // Sort snapshots by creation date descending (newest first)
        $snapshots = $this->pitch->snapshots->sortByDesc('created_at')->values();

        // If we have real snapshots, use them
        if ($snapshots->count() > 0) {
            return $snapshots->map(function ($snapshot, $index) {
                // Get files for this snapshot
                $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];
                $files = $this->pitch->files()->whereIn('id', $fileIds)->get();

                return [
                    'id' => $snapshot->id,
                    'version' => $snapshot->snapshot_data['version'] ?? ($snapshots->count() - $index),
                    'submitted_at' => $snapshot->created_at,
                    'status' => $snapshot->status,
                    'file_count' => count($fileIds),
                    'response_to_feedback' => $snapshot->snapshot_data['response_to_feedback'] ?? null,
                    'files' => $files,
                ];
            });
        }

        // Fallback: If no snapshots but files exist, create virtual snapshot history
        if ($this->pitch->files->count() > 0) {
            return collect([[
                'id' => 'current',
                'version' => 1,
                'submitted_at' => $this->pitch->updated_at,
                'status' => 'pending',
                'file_count' => $this->pitch->files->count(),
                'response_to_feedback' => null,
                'files' => $this->pitch->files,
            ]]);
        }

        // No snapshots and no files
        return collect();
    }

    /**
     * Get the current snapshot to display.
     */
    #[Computed]
    public function currentSnapshot()
    {
        if ($this->selectedSnapshotId) {
            $snapshot = $this->pitch->snapshots->find($this->selectedSnapshotId);
            if ($snapshot) {
                // Hydrate files from snapshot data
                $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];
                $snapshot->files = $this->pitch->files()->whereIn('id', $fileIds)->get();

                return $snapshot;
            }
        }

        // Try to get latest snapshot first
        $latestSnapshot = $this->pitch->snapshots->sortByDesc('created_at')->first();

        if ($latestSnapshot) {
            // Hydrate files from snapshot data
            $fileIds = $latestSnapshot->snapshot_data['file_ids'] ?? [];
            $latestSnapshot->files = $this->pitch->files()->whereIn('id', $fileIds)->get();

            return $latestSnapshot;
        }

        // Fallback: Create a virtual snapshot from current pitch files for backward compatibility
        if ($this->pitch->files->count() > 0) {
            $virtualSnapshot = new class
            {
                public $id;

                public $pitch_id;

                public $created_at;

                public $snapshot_data;

                public $status;

                public $files;

                public $version;

                public $response_to_feedback;

                public function hasFiles()
                {
                    return $this->files && $this->files->count() > 0;
                }
            };

            $virtualSnapshot->id = 'current';
            $virtualSnapshot->pitch_id = $this->pitch->id;
            $virtualSnapshot->created_at = $this->pitch->updated_at;
            $virtualSnapshot->snapshot_data = [
                'version' => 1,
                'file_ids' => $this->pitch->files->pluck('id')->toArray(),
                'response_to_feedback' => null,
            ];
            $virtualSnapshot->status = 'pending';
            $virtualSnapshot->files = $this->pitch->files;
            $virtualSnapshot->version = 1;
            $virtualSnapshot->response_to_feedback = null;

            return $virtualSnapshot;
        }

        return null;
    }

    /**
     * Get files from the current snapshot.
     */
    #[Computed]
    public function currentFiles(): Collection
    {
        $snapshot = $this->currentSnapshot;

        return $snapshot ? ($snapshot->files ?? collect()) : collect();
    }

    /**
     * Get unapproved files from current snapshot.
     */
    #[Computed]
    public function unapprovedFiles(): Collection
    {
        return $this->currentFiles->filter(function ($file) {
            return $file->client_approval_status !== 'approved';
        });
    }

    /**
     * Get approved files from current snapshot.
     */
    #[Computed]
    public function approvedFiles(): Collection
    {
        return $this->currentFiles->filter(function ($file) {
            return $file->client_approval_status === 'approved';
        });
    }

    /**
     * Determine if deliverables should be shown based on workflow state.
     */
    #[Computed]
    public function shouldShowDeliverables(): bool
    {
        $snapshot = $this->currentSnapshot;

        // For client management workflow, show deliverables when producer has submitted files for review
        if ($this->project->isClientManagement()) {
            $statusAllowed = in_array($this->pitch->status, [
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                Pitch::STATUS_COMPLETED,
            ]);
            $hasCurrentSnapshot = $snapshot ? true : false;
            $hasFiles = false;
            if ($snapshot) {
                $hasFiles = method_exists($snapshot, 'hasFiles') ? $snapshot->hasFiles() : ($snapshot->files ?? collect())->count() > 0;
            }

            return $statusAllowed && $hasCurrentSnapshot && $hasFiles;
        } else {
            // For other workflows, use the original logic
            return $snapshot && (method_exists($snapshot, 'hasFiles') ? $snapshot->hasFiles() : ($snapshot->files ?? collect())->count() > 0);
        }
    }

    /**
     * Switch to a different snapshot.
     */
    public function switchSnapshot(?int $snapshotId): void
    {
        $this->selectedSnapshotId = $snapshotId;

        // Clear computed property caches
        unset($this->currentSnapshot, $this->currentFiles, $this->unapprovedFiles, $this->approvedFiles);
    }

    /**
     * Approve a specific file.
     */
    public function approveFile(int $fileId): void
    {
        $file = PitchFile::find($fileId);

        if (! $file || $file->pitch_id !== $this->pitch->id) {
            $this->dispatch('notify', type: 'error', message: 'File not found.');

            return;
        }

        $file->update([
            'client_approval_status' => 'approved',
            'client_approved_at' => now(),
        ]);

        // Clear computed caches and refresh
        unset($this->currentFiles, $this->unapprovedFiles, $this->approvedFiles);

        $this->dispatch('notify', type: 'success', message: 'File approved successfully.');
        $this->dispatch('file-approval-changed', fileId: $fileId, status: 'approved');
    }

    /**
     * Unapprove a specific file.
     */
    public function unapproveFile(int $fileId): void
    {
        $file = PitchFile::find($fileId);

        if (! $file || $file->pitch_id !== $this->pitch->id) {
            $this->dispatch('notify', type: 'error', message: 'File not found.');

            return;
        }

        $file->update([
            'client_approval_status' => null,
            'client_approved_at' => null,
        ]);

        // Clear computed caches and refresh
        unset($this->currentFiles, $this->unapprovedFiles, $this->approvedFiles);

        $this->dispatch('notify', type: 'success', message: 'File unapproved successfully.');
        $this->dispatch('file-approval-changed', fileId: $fileId, status: 'unapproved');
    }

    /**
     * Approve all files in the current snapshot.
     */
    public function approveAllFiles(): void
    {
        $updated = $this->pitch->files()
            ->where(function ($query) {
                $query
                    ->whereNull('client_approval_status')
                    ->orWhere('client_approval_status', '!=', 'approved');
            })
            ->update([
                'client_approval_status' => 'approved',
                'client_approved_at' => now(),
            ]);

        // Clear computed caches and refresh
        unset($this->currentFiles, $this->unapprovedFiles, $this->approvedFiles);

        $this->dispatch('notify', type: 'success', message: "All files approved ({$updated} files).");
        $this->dispatch('file-approval-changed', status: 'approved_all');
    }

    /**
     * Unapprove all files in the current snapshot.
     */
    public function unapproveAllFiles(): void
    {
        $updated = $this->pitch->files()
            ->where('client_approval_status', 'approved')
            ->update([
                'client_approval_status' => null,
                'client_approved_at' => null,
            ]);

        // Clear computed caches and refresh
        unset($this->currentFiles, $this->unapprovedFiles, $this->approvedFiles);

        $this->dispatch('notify', type: 'success', message: "All files unapproved ({$updated} files).");
        $this->dispatch('file-approval-changed', status: 'unapproved_all');
    }

    /**
     * Listen for file updates from child components.
     */
    #[On('file-updated')]
    public function refreshFiles(): void
    {
        // Refresh the pitch relationship to get updated files
        $this->pitch->refresh();

        // Clear computed caches
        unset($this->currentSnapshot, $this->currentFiles, $this->unapprovedFiles, $this->approvedFiles, $this->snapshotHistory);
    }

    public function render()
    {
        return view('livewire.client-portal.producer-deliverables');
    }
}
