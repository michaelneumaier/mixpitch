<?php

namespace App\Livewire;

// Remove direct controller usage
// use App\Http\Controllers\ProjectController;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

// Added for refactoring
use App\Services\Project\ProjectManagementService;
use App\Exceptions\Project\ProjectCreationException;
use App\Exceptions\Project\ProjectUpdateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\Toaster;

class CreateProject extends Component
{
    use WithFileUploads;

    public Project $project;
    public ?Project $originalProject = null;
    public ProjectForm $form;
    public $isEdit = false;
    public $projectImage; // Existing image URL for display
    // public $deleteProjectImage = false; // Let service handle based on new image upload
    public $deletePreviewTrack = false; // Keep for now, logic needs full refactor later
    public $initWaveSurfer;
    public $track; // Uploaded track file
    public $audioUrl; // Existing audio URL for display

    public function mount($project = null)
    {
        $this->project = new Project(); // Keep for reference, maybe not needed

        if ($project) {
            // Load the existing project for editing
            $this->originalProject = $project; // Store original for comparison if needed
            $this->project = $project; // Keep a reference to the model
            $this->isEdit = true;

            // Correctly populate the form object
            $this->form->name = $project->name;
            $this->form->artistName = $project->artist_name;
            $this->form->projectType = $project->project_type;
            $this->form->description = $project->description;
            $this->form->genre = $project->genre;
            // projectImage is handled separately for display/upload
            $this->form->budgetType = $project->budget > 0 ? 'paid' : 'free'; // Determine budget type
            $this->form->budget = $project->budget;
            $this->form->deadline = $project->deadline ? $project->deadline->format('Y-m-d') : null;
            // Collaboration types need mapping
            $this->mapCollaborationTypesToForm($project->collaboration_type);
            // Notes might be part of the project or a related model - assuming project for now
            // $this->form->notes = $project->notes; // Adjust if notes are stored differently
            
            // Set display properties for existing image/track
            if ($project->image_path) {
                try {
                    $this->projectImage = $project->imageUrl; // For display
                } catch (\Exception $e) {
                    Log::error('Error getting project image URL', ['project_id' => $project->id, 'error' => $e->getMessage()]);
                    $this->projectImage = null; // Default if error
                }
            }
            if ($project->hasPreviewTrack()) {
                $this->audioUrl = $project->previewTrackPath(); // For display
            }
        } else {
            // Initialize form for create (set defaults if needed)
            $this->form->budgetType = 'free';
            $this->form->budget = 0;
        }
    }

    /**
     * Helper to map project collaboration types to form boolean properties.
     */
    private function mapCollaborationTypesToForm(?array $types): void
    {
        if (empty($types)) return;
        $this->form->collaborationTypeMixing = in_array('Mixing', $types);
        $this->form->collaborationTypeMastering = in_array('Mastering', $types);
        $this->form->collaborationTypeProduction = in_array('Production', $types);
        $this->form->collaborationTypeSongwriting = in_array('Songwriting', $types);
        $this->form->collaborationTypeVocalTuning = in_array('Vocal Tuning', $types);
    }

    #[On('refreshComponent')]
    public function refreshMe()
    {
        // placeholder, may not be needed
    }

    public function rendered()
    {
        $this->dispatch('audioUrlUpdated', $this->audioUrl);
    }

    // TODO: Review image handling logic. revertImage might need adjustment.
    // Currently, deleting image without replacing needs separate handling.
    public function revertImage()
    {
        $this->form->projectImage = null; // Clear the new upload
        $this->dispatch('image-reverted'); // Notify frontend to update display
        // We are no longer using $this->deleteProjectImage flag here.
        // If user wants to *remove* the existing image without replacing, that needs new UI/logic.
    }

    // TODO: Refactor track handling entirely in Step 5 (File Management)
    public function clearTrack()
    {
        $this->track = null; // Clear new upload
        $this->deletePreviewTrack = $this->isEdit && $this->audioUrl; // Flag existing track for deletion IF editing
        $this->audioUrl = null; // Clear display URL
            $this->dispatch('track-clear-button');
        $this->dispatch('audioUrlUpdated', null);
    }

    // TODO: Refactor track handling
    public function updatedTrack()
    {
        if ($this->track) {
            // Validate here if needed before showing temporary URL
            try {
                 $this->validateOnly('track'); // Assuming ProjectForm has rules for 'track'
        $this->audioUrl = $this->track->temporaryUrl();
        $this->dispatch('audioUrlUpdated', $this->audioUrl);
                 $this->deletePreviewTrack = false; // Don't delete existing if a new one is uploaded
            } catch (\Illuminate\Validation\ValidationException $e) {
                 $this->track = null;
                 $this->audioUrl = $this->isEdit && $this->originalProject->hasPreviewTrack() ? $this->originalProject->previewTrackPath() : null;
                 $this->dispatch('audioUrlUpdated', $this->audioUrl);
                 // Display validation error
                 Toaster::error($e->validator->errors()->first('track'));
            }
        } else {
            // Handle case where track is deselected
            $this->clearTrack();
        }
    }

    /**
     * Save the project (Create or Update).
     */
    public function save(ProjectManagementService $projectService)
    {
        // 1. Validation
        $validatedData = $this->form->validate(); // Use ProjectForm validation

        // --- Transform collaboration types --- START ---
        $collaborationTypes = [];
        if ($this->form->collaborationTypeMixing) $collaborationTypes[] = 'Mixing';
        if ($this->form->collaborationTypeMastering) $collaborationTypes[] = 'Mastering';
        if ($this->form->collaborationTypeProduction) $collaborationTypes[] = 'Production';
        if ($this->form->collaborationTypeSongwriting) $collaborationTypes[] = 'Songwriting';
        if ($this->form->collaborationTypeVocalTuning) $collaborationTypes[] = 'Vocal Tuning'; // Ensure correct name

        // Add the transformed array to validated data and remove the booleans
        $validatedData['collaboration_type'] = $collaborationTypes;
        unset(
            $validatedData['collaborationTypeMixing'],
            $validatedData['collaborationTypeMastering'],
            $validatedData['collaborationTypeProduction'],
            $validatedData['collaborationTypeSongwriting'],
            $validatedData['collaborationTypeVocalTuning'],
            // Also unset budgetType as service derives budget from the value
            $validatedData['budgetType']
        );
        // --- Transform collaboration types --- END ---

        // Separate image data if present in the form object
        $imageFile = $this->form->projectImage ?? null;
        if ($imageFile) {
            // Assume validation key is 'projectImage' if handled by ProjectForm
            unset($validatedData['projectImage']);
        }

        // TODO: Handle preview track upload/deletion using FileManagementService in Step 5
        $trackFile = $this->track ?? null; // Temporarily store the uploaded track file
        $shouldDeleteExistingTrack = $this->deletePreviewTrack;
        // Remove track from validated data for now
        unset($validatedData['track']);

        try {
            if ($this->isEdit) {
                // --- UPDATE --- (Refactored)
                $this->authorize('update', $this->project);

                $project = $projectService->updateProject(
                    $this->project,
                    $validatedData,
                    $imageFile // Service handles deleting old if this is provided
                    // Explicit deletion without replacement is not handled here yet
                );

                // TODO: Handle preview track update/deletion using FileManagementService (Step 5)
                if ($trackFile) {
                    // Call file service to delete old track if exists ($this->project->preview_track)
                    // Call file service to upload $trackFile and associate with $project->id
                    // Update $project->preview_track = new_file_id;
                    Log::info('TODO: Upload new preview track', ['project_id' => $project->id]);
                } elseif ($shouldDeleteExistingTrack && $this->project->preview_track) {
                    // Call file service to delete old track ($this->project->preview_track)
                    // Set $project->preview_track = null;
                     Log::info('TODO: Delete existing preview track', ['project_id' => $project->id, 'track_id' => $this->project->preview_track]);
                }
                // TODO: Save project again if preview_track ID was updated by file service
                // $project->save();

                Toaster::success('Project updated successfully!');
                return redirect()->route('projects.manage', $project);

            } else {
                // --- CREATE --- (Refactored)
                $this->authorize('create', Project::class);

                $project = $projectService->createProject(
                    auth()->user(),
                    $validatedData,
                    $imageFile
                );

                 // TODO: Handle preview track upload using FileManagementService (Step 5)
                 if ($trackFile) {
                    // Call file service to upload $trackFile and associate with $project->id
                    // Update $project->preview_track = new_file_id;
                    // $project->save();
                     Log::info('TODO: Upload preview track for new project', ['project_id' => $project->id]);
                 }

                Toaster::success('Project created successfully!');
            return redirect()->route('projects.show', $project);
            }

        } catch (ProjectCreationException | ProjectUpdateException $e) {
            Log::error('Project Save/Update Error: ' . $e->getMessage(), ['exception' => get_class($e), 'isEdit' => $this->isEdit, 'project_id' => $this->project->id ?? null]); // Removed trace for brevity
            Toaster::error($e->getMessage());
            // Re-throw in testing environment for clearer test failures
            if (app()->environment('testing')) {
                throw $e;
            }
            return; // Stop execution to prevent redirect on failure
        } catch (AuthorizationException $e) {
            Log::warning('Authorization failed for saving project', ['user_id' => auth()->id(), 'isEdit' => $this->isEdit, 'project_id' => $this->project->id ?? null]);
            Toaster::error('You are not authorized to perform this action.');
             // Re-throw in testing environment
            if (app()->environment('testing')) {
                throw $e;
            }
            return; // Stop execution
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors specifically if desired, but Livewire usually handles display
             Log::info('Validation failed saving project', ['errors' => $e->errors()]);
             // Let Livewire handle validation errors, don't re-throw or return here
        } catch (\Exception $e) {
            Log::error('Unexpected Error Saving Project: ' . $e->getMessage(), ['exception' => get_class($e), 'isEdit' => $this->isEdit, 'project_id' => $this->project->id ?? null]); // Removed trace
            Toaster::error('An unexpected error occurred. Please try again.');
             // Re-throw in testing environment
             if (app()->environment('testing')) {
                throw $e;
            }
            return; // Stop execution
        }
    }

    public function render()
    {
        return view('livewire.project.page.create-project');
    }
}
