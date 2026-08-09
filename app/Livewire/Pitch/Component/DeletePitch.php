<?php

namespace App\Livewire\Pitch\Component;

use App\Models\Pitch;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class DeletePitch extends Component
{
    public Pitch $pitch;

    public $showDeleteConfirmation = false;

    public $deleteConfirmInput = '';

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    protected $listeners = ['confirmDeletePitch' => 'confirmDelete'];

    public function confirmDelete()
    {
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirmation = false;
        $this->deleteConfirmInput = '';
        $this->dispatch('closeModal', 'deletePitchModal');
    }

    public function deletePitch()
    {
        if ($this->deleteConfirmInput !== 'delete') {
            Toaster::error('Please type "delete" to confirm.');

            return;
        }

        // Submit the hidden POST form so the destructive deletion is CSRF-protected.
        // The actual deletion (authorization, file cleanup, DB transaction) lives in
        // PitchController::destroyConfirmed.
        $this->js("document.getElementById('delete-pitch-form-{$this->pitch->id}').submit();");
    }

    public function render()
    {
        return view('livewire.pitch.component.delete-pitch');
    }
}
