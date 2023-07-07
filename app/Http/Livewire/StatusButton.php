<?php

namespace App\Http\Livewire;

use Livewire\Component;

class StatusButton extends Component
{
    public $status;

    public $type = 'inline';

    public function render()
    {
        return view('livewire.status-button');
    }
}