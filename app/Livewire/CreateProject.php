<?php

namespace App\Livewire;

use App\Http\Controllers\ProjectController;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateProject extends Component
{
    use WithFileUploads;

    public Project $project;
    public Project $originalProject;
    public ProjectForm $form;
    public $isEdit = false;
    public $projectImage;
    public $deleteProjectImage = false;
    public $deletePreviewTrack = false;
    public $initWaveSurfer;
    public $track;
    public $audioUrl;

    public function mount($project = null)
    {

        if ($project) {
            $this->originalProject = $project;
            $this->project = $project;
            $this->isEdit = true;
            $this->form->name = $project->name;
            $this->form->artistName;
            $this->form->projectType = $project->project_type;
            if ($project->image_path) {
                $this->projectImage = asset('storage/' . $project->image_path);
            }
            $this->form->description = $project->description;
            $this->form->genre = $project->genre;
            $this->form->collaborationTypeMixing = $project->collaboration_type['mixing'];
            $this->form->collaborationTypeMastering = $project->collaboration_type['mastering'];
            $this->form->collaborationTypeProduction = $project->collaboration_type['production'];
            $this->form->collaborationTypeSongwriting = $project->collaboration_type['songwriting'];
            $this->form->collaborationTypeVocalTuning = $project->collaboration_type['vocal_tuning'];
            $this->form->budget = $project->budget;
            $this->form->deadline = $project->deadline;
            if ($project->hasPreviewTrack()) {
                $this->audioUrl = $project->previewTrackPath();
                //$this->dispatch('audioUrlUpdated', $this->audioUrl);
            }
            //$this->form->track = $project->preview_track;
            $this->form->notes = $project->notes;
        } else {

            //$this->form->budget = 0;
        }
    }

    public function rendered()
    {
        $this->dispatch('audioUrlUpdated', $this->audioUrl);
    }

    public function revertImage()
    {
        if ($this->isEdit && $this->form->projectImage) {
            $this->form->projectImage = null;
        } else if ($this->isEdit && !$this->form->projectImage && $this->projectImage) {
            $this->projectImage = null;
            $this->deleteProjectImage = true;
        } else {
            $this->form->projectImage = null;
        }
    }


    public function clearTrack()
    {

        if ($this->isEdit && $this->audioUrl == $this->originalProject->previewTrackPath()) {
            $this->audioUrl = null;
            $this->track = null;
            $this->deletePreviewTrack = true;
            $this->dispatch('track-clear-button');
        } else if ($this->isEdit) {
            $this->track = null;
            if ($this->deletePreviewTrack) {
                $this->audioUrl = null;
                $this->dispatch('track-clear-button');
            } else {
                $this->audioUrl = $this->originalProject->previewTrackPath();
                $this->dispatch('audioUrlUpdated', $this->audioUrl);
            }
        } else {
            $this->track = null;
            $this->audioUrl = null;
            $this->dispatch('track-clear-button');
        }
    }

    public function updatedFormTrack()
    {
        // Handle the file upload. For example, store it in a temporary disk or something similar.
        // $path = $validatedData['track']->store('tracks', 'temporary');
        // $this->temporaryTrackPath = $path; // You can then use this path to reference the uploaded file
    }

    public function updatedTrack()
    {
        // $this->validate([
        //     'track' => 'file|mimes:mp3,wav', // Add your validation rules here
        // ]);

        // Update the audio URL for the AudioPlayer component

        $this->audioUrl = $this->track->temporaryUrl();
        $this->dispatch('audioUrlUpdated', $this->audioUrl);
    }

    public function save()
    {
        $this->validate();
        if ($this->isEdit == false) {
            $project = new Project();
            $project->user_id = auth()->id();
        } else {
            $project = $this->project;
        }

        $project->name = $this->form->name;
        $project->artist_name = $this->form->artistName;


        if ($this->isEdit && $this->form->projectImage && $this->originalProject->image_path) {
            $this->deleteProjectImage = true;
            $project->image_path = null;
        }

        if ($this->deleteProjectImage) {
            $this->originalProject->deleteProjectImage();
            $project->image_path = null;
        }
        if ($this->form->projectImage) {
            $path = $this->form->projectImage->store('images', 'public');
            $project->image_path = $path;
        }

        $project->project_type = $this->form->projectType;
        $project->description = $this->form->description;
        $project->genre = $this->form->genre;
        $project->collaboration_type = [
            'mixing' => $this->form->collaborationTypeMixing,
            'mastering' => $this->form->collaborationTypeMastering,
            'production' => $this->form->collaborationTypeProduction,
            'songwriting' => $this->form->collaborationTypeSongwriting,
            'vocal_tuning' => $this->form->collaborationTypeVocalTuning,
        ];
        $project->budget = $this->form->budget;
        $project->deadline = $this->form->deadline;
        $project->notes = $this->form->notes;

        if ($this->isEdit && $this->deletePreviewTrack) {
            $controller = new ProjectController();
            $oldProjectPreviewFile = $project->previewTrack;
            $controller->deleteFile(
                $project,
                $oldProjectPreviewFile
            );
            $project->preview_track = null;
        }
        if ($this->track) {
            $controller = new ProjectController();
            $project->preview_track = $controller->storeTrack($this->track, $project);
        }
        $project->save();
        return redirect()->route('projects.show', $project);
    }

    public function render()
    {
        return view('livewire.project.page.create-project');
    }
}
