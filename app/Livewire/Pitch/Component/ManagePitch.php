<?php
// app/Livewire/Pitch/Component/ManagePitch.php

namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Pitch;
use App\Models\PitchFile; // Add this line
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Masmerise\Toaster\Toaster;

class ManagePitch extends Component
{
    use WithFileUploads;
    use WithPagination;

    public Pitch $pitch;
    public $files = [];
    public $comment;
    public $rating;

    //public $uploadedFiles;

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        //$this->loadFiles();
    }

    public function render()
    {
        $uploadedFiles = PitchFile::where('pitch_id', $this->pitch->id)->paginate(10);
        $events = $this->pitch->events()->latest()->paginate(5);
        $snapshots = $this->pitch->snapshots()->orderBy('created_at', 'desc')->get();

        return view('livewire.pitch.component.manage-pitch')->with([
            'uploadedFiles' => $uploadedFiles,
            'events' => $events,
            'snapshots' => $snapshots,
        ]);
    }

    // public function loadFiles()
    // {
    //     $this->uploadedFiles = PitchFile::where('pitch_id', $this->pitch->id)->paginate(10);
    // }

    public function uploadFiles()
    {
        // Check if the pitch status allows file uploads
        if (!in_array($this->pitch->status, ['in_progress', 'pending_review'])) {
            Toaster::warning('You can only upload files when the pitch is in progress or pending review.');
            return;
        }

        $currentFileCount = $this->pitch->files()->count();
        $formFileCount = count($this->files);

        if ($currentFileCount + $formFileCount > $this->pitch->max_files) {
            Toaster::warning('You have exceeded the maximum number of files allowed for this pitch.');
            return;
        }

        $this->validate([
            'files.*' => 'required|file|max:102400', // Max 100MB per file
        ]);

        foreach ($this->files as $file) {
            $filePath = $file->store('pitch_files', 'public');

            $this->pitch->files()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
            ]);
        }

        $this->files = [];

        $comment = $formFileCount . ($formFileCount > 1 ? ' files ' : ' file ') . 'have been uploaded.';
        $this->pitch->addComment($comment);

        Toaster::success('Files uploaded successfully.');
        $this->dispatch('file-upload-success');
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
        Toaster::success('File deleted successfully.');
    }

    public function submitComment()
    {
        $this->validate([
            'comment' => 'required|string|max:255',
        ]);

        $this->pitch->addComment($this->comment);

        $this->comment = '';
        Toaster::success('Comment added successfully.');
    }

    public function deleteComment($commentId)
    {
        try {
            $this->pitch->deleteComment($commentId);
            Toaster::success('Comment deleted successfully.');
        } catch (\Exception $e) {
            Toaster::warning('You are not authorized to delete this comment');
        }

        // if ($this->pitch->deleteComment($commentId)) {
        //     Toaster::success('Comment deleted successfully.');
        // } else {
        //     Toaster::warning('You are not authorized to delete this comment');
        // }
    }
    public function submitRating()
    {
        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $this->pitch->addRating($this->rating);

        $this->rating = '';
        Toaster::success(sprintf('Rating added successfully.'));
    }

    public function saveNote($fileId, $note)
    {
        $pitchFile = PitchFile::findOrFail($fileId);

        // Update the note
        $pitchFile->update([
            'note' => $note,
        ]);

        // Refresh the file list
        //$this->loadFiles();
        if ($note == "") {
            $this->pitch->addComment("Note removed from file.");
        } else {
            $this->pitch->addComment("Note added to file ({$pitchFile->file_name}): {$note}");
        }

        // Optional: Provide feedback to the user
        Toaster::success('Note saved successfully.');
    }

    public function submitForReview()
    {
        $this->pitch->createSnapshot();
        $this->pitch->changeStatus('forward', Pitch::STATUS_READY_FOR_REVIEW);
        Toaster::success('Pitch submitted for review successfully.');
        // session()->flash('message', 'Pitch submitted for review successfully.');
        // return redirect()->route('pitches.show', $this->pitch->id);
    }

    public function cancelPitchSubmission()
    {
        $this->pitch->changeStatus('backward', Pitch::STATUS_IN_PROGRESS);
        Toaster::success('Pitch cancelled successfully.');
    }
}
