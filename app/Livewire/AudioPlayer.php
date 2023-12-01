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

    public function mount($audioUrl, $isPreviewTrack = false, $isInCard = false)
    {
        $this->audioUrl = $audioUrl;
        $this->identifier = uniqid('waveform_');
        $this->isPreviewTrack = $isPreviewTrack;
        $this->isInCard = $isInCard;

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
        $this->dispatch('url-updated', $this->audioUrl);
    }

    #[On('track-clear-button')]
    public function clearTrack()
    {
        $this->mainDivClass = 'flex items-center hidden';
        $this->dispatch('clear-track');
    }

    public function render()
    {
        return view('livewire.project.component.audio-player');
    }
}
