<?php

namespace App\Livewire;

use App\Models\PitchFile;
use Livewire\Component;

class SnapshotFilePlayer extends Component
{
    public PitchFile $file;

    public $comments = [];

    public $currentTimestamp = 0;

    public $duration = 0;

    public $showDownloadButton = false;

    protected $listeners = [
        'waveformReady' => 'onWaveformReady',
        'refresh' => '$refresh',
    ];

    public function mount(PitchFile $file, $showDownloadButton = false)
    {
        $this->file = $file;
        $this->showDownloadButton = $showDownloadButton;
        $this->loadComments();
    }

    public function loadComments()
    {
        $this->comments = $this->file->comments()
            ->with('user')
            ->orderBy('timestamp')
            ->get();
    }

    public function onWaveformReady()
    {
        // Recalculate any necessary data after waveform loads
        $this->loadComments();
    }

    public function seekTo($timestamp)
    {
        $this->dispatch('seekToPosition', [
            'fileId' => $this->file->id,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Play this snapshot file in the global audio player
     */
    public function playInGlobalPlayer()
    {
        $this->dispatch('playPitchFile',
            pitchFileId: $this->file->id,
            clientMode: false,
            clientEmail: ''
        );
    }

    public function render()
    {
        return view('livewire.snapshot-file-player');
    }
}
