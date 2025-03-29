<?php

namespace App\Livewire\Pitch\Component;

use App\Facades\Toaster;
use App\Models\Pitch;
use Livewire\Component;

class DeletePitch extends Component
{
    public Pitch $pitch;
    public $showDeleteConfirmation = false;
    public $deleteConfirmInput = '';

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    public function confirmDelete()
    {
        $this->showDeleteConfirmation = true;
    }
    
    public function cancelDelete()
    {
        $this->showDeleteConfirmation = false;
        $this->deleteConfirmInput = '';
    }
    
    public function deletePitch()
    {
        if ($this->deleteConfirmInput !== 'delete') {
            Toaster::error('Please type "delete" to confirm.');
            return;
        }
        
        // Check if there's a project-based destroy route, and if so, use it
        return redirect()->route('projects.pitches.destroyConfirmed', ['project' => $this->pitch->project->slug, 'pitch' => $this->pitch->slug]);
    }

    public function render()
    {
        return view('livewire.pitch.component.delete-pitch');
    }
}
