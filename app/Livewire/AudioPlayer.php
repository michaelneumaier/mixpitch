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

    public function mount($audioUrl, $isPreviewTrack = false, $isInCard = false)
    {
        $this->audioUrl = $audioUrl;
        $this->identifier = uniqid('waveform_');
        $this->isPreviewTrack = $isPreviewTrack;
        $this->isInCard = $isInCard;
    }

    #[On('audioUrlUpdated')]
    public function audioUrlUpdated($audioUrl)
    {
        $this->audioUrl = $audioUrl;
        $this->dispatch('url-updated', $this->audioUrl);
    }

    #[On('track-clear-button')]
    public function clearTrack()
    {
        $this->dispatch('clear-track');
    }

    public function render()
    {
        return view('livewire.project.component.audio-player');
    }
}
