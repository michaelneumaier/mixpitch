<?php

namespace App\Livewire\Components;

use App\Models\PitchFile;
use Illuminate\Support\Collection;
use Livewire\Component;

class VersionHistoryDropdown extends Component
{
    public PitchFile $file;

    public bool $showActions = true;

    public bool $compact = false;

    public string $triggerType = 'button'; // 'button' or 'badge'

    public ?int $currentVersionId = null;

    public function mount(PitchFile $file, bool $showActions = true, bool $compact = false, string $triggerType = 'button')
    {
        $this->file = $file;
        $this->showActions = $showActions;
        $this->compact = $compact;
        $this->triggerType = $triggerType;
        $this->currentVersionId = $file->id;
    }

    /**
     * Get all versions of this file (including root)
     */
    public function getVersions(): Collection
    {
        return $this->file->getAllVersionsWithSelf();
    }

    /**
     * Switch to a specific version (navigates to that version's page)
     */
    public function switchToVersion(int $versionId): void
    {
        $version = PitchFile::find($versionId);

        if (! $version) {
            return;
        }

        // Dispatch event to parent component to switch file
        $this->dispatch('versionSwitched', versionId: $versionId);

        // Also navigate to the version's player page if available
        $this->redirect(route('pitch-files.show', ['file' => $version->uuid]));
    }

    /**
     * Use a specific version (in-place swap in file list)
     */
    public function useThisVersion(int $versionId): void
    {
        $version = PitchFile::find($versionId);

        if (! $version) {
            return;
        }

        // Get the root file to identify the file family
        $rootFileId = $version->getRootFile()->id;

        // Dispatch event to parent components to swap the version
        $this->dispatch('swapToFileVersion', [
            'rootFileId' => $rootFileId,
            'newVersionId' => $versionId,
        ]);

        // Update current version for this dropdown
        $this->currentVersionId = $versionId;

        \Masmerise\Toaster\Toaster::success('Switched to '.$version->getVersionLabel());
    }

    /**
     * Download a specific version
     */
    public function downloadVersion(int $versionId): void
    {
        $version = PitchFile::findOrFail($versionId);

        $this->authorize('downloadFile', $version);

        // Navigate to download route
        $this->redirect(route('pitch-files.download', ['file' => $version->uuid]));
    }

    /**
     * Delete a specific version
     */
    public function deleteVersion(int $versionId): void
    {
        $version = PitchFile::findOrFail($versionId);

        $this->authorize('deleteVersion', $version);

        // Dispatch to parent component for confirmation modal
        $this->dispatch('confirmDeleteVersion', versionId: $versionId);
    }

    /**
     * Play a specific version in the audio player
     */
    public function playVersion(int $versionId): void
    {
        $version = PitchFile::findOrFail($versionId);

        $this->authorize('view', $version);

        // Dispatch event to play this version
        $this->dispatch('playPitchFile', pitchFileId: $version->id);
    }

    public function render()
    {
        return view('livewire.components.version-history-dropdown', [
            'versions' => $this->getVersions(),
        ]);
    }
}
