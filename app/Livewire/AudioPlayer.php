<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class AudioPlayer extends Component

{
    public $audioUrl;
    public $identifier;

    public function mount($audioUrl)
    {
        $this->audioUrl = $audioUrl;
        $this->identifier = uniqid('waveform_');
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
