<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class AudioPlayer extends Component
{
    public $audioUrl;

    public $identifier;

    public $isPreviewTrack;

    public $isInCard;

    public $mainDivClass;

    // Optional properties for global player integration
    public $trackTitle = '';

    public $trackArtist = '';

    public $projectTitle = '';

    public $fileId = null;

    public bool $audioPlayerInitialized = false;

    public function mount($audioUrl, $isPreviewTrack = false, bool $isInCard = false, $trackTitle = '', $trackArtist = '', $projectTitle = '', $fileId = null)
    {
        $this->audioUrl = $audioUrl;
        $this->identifier = uniqid('waveform_');
        $this->isPreviewTrack = $isPreviewTrack;
        $this->isInCard = $isInCard;
        $this->trackTitle = $trackTitle;
        $this->trackArtist = $trackArtist;
        $this->projectTitle = $projectTitle;
        $this->fileId = $fileId;

        if ($audioUrl == '') {
            $this->mainDivClass = 'flex items-center hidden';
        } else {
            $this->mainDivClass = 'flex items-center';
        }
    }

    #[On('audioUrlUpdated')]
    public function audioUrlUpdated($audioUrl)
    {
        $this->audioUrl = $audioUrl;
        if ($audioUrl != '') {
            $this->mainDivClass = 'flex items-center';
        }
        $this->dispatch('clear-track');
        $this->dispatch('url-updated', $this->audioUrl);
    }

    #[On('track-clear-button')]
    public function clearTrack()
    {
        $this->mainDivClass = 'flex items-center hidden';
        $this->dispatch('clear-track');
    }

    /**
     * Play this track in the global audio player
     */
    public function playInGlobalPlayer()
    {
        if (! $this->audioUrl) {
            return;
        }

        // Create a generic track array for the global player
        $track = [
            'type' => 'audio_file',
            'id' => $this->fileId ?? $this->identifier,
            'title' => $this->trackTitle ?: 'Audio Track',
            'url' => $this->audioUrl,
            'artist' => $this->trackArtist ?: 'Unknown Artist',
            'project_title' => $this->projectTitle,
            'duration' => 0, // Will be determined by WaveSurfer
            'has_comments' => false,
        ];

        $this->dispatch('playTrack', track: $track);
    }

    public function render()
    {
        if (! $this->audioPlayerInitialized) {
            $this->dispatch('audio-player-rendered-'.$this->identifier);
        }
        $this->audioPlayerInitialized = true;

        return view('livewire.project.component.audio-player');
    }
}
