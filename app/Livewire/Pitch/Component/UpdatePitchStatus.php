<?php

// app/Http/Livewire/Pitch/Component/UpdatePitchStatus.php
namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;

class UpdatePitchStatus extends Component
{
    public $pitch;
    public $status;

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->status = $pitch->status;
    }

    public function changeStatus($direction)
    {
        $project = $this->pitch->project;

        // Ensure the authenticated user is the owner of the project
        if (!Auth::check() || $project->user_id !== Auth::id()) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        try {
            $this->pitch->changeStatus($direction);
            $this->status = $this->pitch->status;
            session()->flash('message', 'Pitch status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.pitch.component.update-pitch-status');
    }
}
