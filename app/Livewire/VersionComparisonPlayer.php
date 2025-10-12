<?php

namespace App\Livewire;

use App\Models\PitchFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class VersionComparisonPlayer extends Component
{
    public PitchFile $file;

    public ?int $versionAId = null;

    public ?int $versionBId = null;

    public bool $syncPlayback = false;

    public function mount(PitchFile $file): void
    {
        $this->file = $file->load(['pitch', 'pitch.project']);

        // Authorize view access
        $this->authorize('view', $this->file);

        // Set default versions (latest and previous)
        $versions = $this->versions;

        if ($versions->count() >= 2) {
            $this->versionAId = $versions->first()->id; // Latest
            $this->versionBId = $versions->skip(1)->first()->id; // Previous
        } elseif ($versions->count() === 1) {
            $this->versionAId = $versions->first()->id;
            $this->versionBId = $versions->first()->id;
        }
    }

    #[Computed]
    public function versions(): Collection
    {
        return $this->file->getAllVersionsWithSelf();
    }

    #[Computed]
    public function versionA(): ?PitchFile
    {
        if (! $this->versionAId) {
            return null;
        }

        return $this->versions->firstWhere('id', $this->versionAId);
    }

    #[Computed]
    public function versionB(): ?PitchFile
    {
        if (! $this->versionBId) {
            return null;
        }

        return $this->versions->firstWhere('id', $this->versionBId);
    }

    public function selectVersionA(int $versionId): void
    {
        $this->versionAId = $versionId;
    }

    public function selectVersionB(int $versionId): void
    {
        $this->versionBId = $versionId;
    }

    public function toggleSyncPlayback(): void
    {
        $this->syncPlayback = ! $this->syncPlayback;
    }

    public function swapVersions(): void
    {
        $temp = $this->versionAId;
        $this->versionAId = $this->versionBId;
        $this->versionBId = $temp;
    }

    public function render()
    {
        return view('livewire.version-comparison-player');
    }
}
