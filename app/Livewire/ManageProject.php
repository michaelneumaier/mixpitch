<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Http\Controllers\ProjectController;
use Livewire\WithFileUploads;

class ManageProject extends Component
{
    use WithFileUploads;
    public Project $project;

    public $hasPreviewTrack = false;
    public $audioUrl;
    public $isUploading = false;
    public $uploadedFiles = [];



    public function mount()
    {
        if ($this->project->hasPreviewTrack()) {
            $this->audioUrl = $this->project->previewTrackPath();
            $this->hasPreviewTrack = true;
        }
    }


    public function publish()
    {
        $this->project->setStatus('open');
    }

    public function unpublish()
    {
        $this->project->setStatus('unpublished');
    }

    public function togglePreviewTrack(ProjectFile $file)
    {
        if ($this->hasPreviewTrack == true && $this->project->preview_track == $file->id) {
            $this->clearPreviewTrack();
        } else {
            $this->project->preview_track = $file->id;
            $this->project->save();
            $this->audioUrl = $file->fullFilePath;

            $this->hasPreviewTrack = true;
            $this->dispatch('audioUrlUpdated', $this->audioUrl);
        }

        // Optionally, emit an event or flash a session message to confirm the change
        session()->flash('message', 'Preview track updated successfully.');
    }
    public function clearPreviewTrack()
    {
        $this->project->preview_track = null; // Clear the preview track
        $this->project->save();
        $this->hasPreviewTrack = false;

        // Optionally, emit an event or flash a session message to confirm the change
        session()->flash('message', 'Preview track cleared successfully.');
    }

    // public function updatedUploadedFiles()
    // {
    //     // Accumulate newly uploaded files
    //     foreach ($this->uploadedFiles as $file) {
    //         $this->allFiles[] = $file; // Example of accumulating file names; adjust as needed
    //     }

    //     // Clear the current selection to allow for new files to be added
    //     $this->uploadedFiles = [];
    // }

    public function uploadFiles()
    {

        foreach ($this->uploadedFiles as $file) {
            $controller = new ProjectController();
            $controller->storeTrack($file, $this->project);
        }

        $this->isUploading = false;
        $this->uploadedFiles = []; // Return to file listing view
        //$this->project->refresh(); // Refresh project files relation
    }

    public function deleteFile($fileId)
    {
        $file = ProjectFile::findOrFail($fileId);
        $project = $this->project; // Ensure you have a way to get the current project context

        // Instantiate the controller
        $controller = new ProjectController();

        // Call the method
        $controller->deleteFile($project, $file);

        // Optionally, emit an event or refresh part of the component
        // Or if you keep a local collection of files in the component:
        $this->project->refresh();
    }

    public function render()
    {
        return view('livewire.project.page.manage-project');
    }
}
