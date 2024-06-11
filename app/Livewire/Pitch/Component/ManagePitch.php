<?php
// app/Livewire/Pitch/Component/ManagePitch.php

namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Pitch;
use App\Models\PitchFile; // Add this line
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ManagePitch extends Component
{
    use WithFileUploads;


    public Pitch $pitch;
    public $files = [];

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    public function uploadFiles()
    {
        $this->validate([
            'files.*' => 'required|file|max:10240', // Max 10MB per file
        ]);

        foreach ($this->files as $file) {
            $filePath = $file->store('pitch_files', 'public');

            // Save file information in the database, assuming you have a PitchFile model
            $this->pitch->files()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
            ]);
        }

        // Clear the file input
        $this->files = [];

        session()->flash('message', 'Files uploaded successfully.');
    }

    public function deleteFile(PitchFile $file) // Add this line
    {
        // Ensure the file belongs to the pitch
        if ($file->pitch_id !== $this->pitch->id) {
            return;
        }

        // Delete the file from storage
        Storage::disk('public')->delete($file->file_path);

        // Delete the file record from the database
        $file->delete();

        session()->flash('message', 'File deleted successfully.');
    }

    public function render()
    {
        return view('livewire.pitch.component.manage-pitch');
    }
}
