<?php

namespace App\Livewire\Project\Component;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ProjectDetailsCard extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public array $workflowColors = [];

    // Basic project details
    public string $artistName = '';

    public string $genre = '';

    public string $description = '';

    public string $notes = '';

    // Collaboration types
    public array $collaborationTypes = [];

    // Budget (for standard projects)
    public string $budgetType = 'free';

    public float $budget = 0;

    // Deadline
    public ?string $deadline = null;

    public ?string $deadlineDisplay = null;

    // License settings
    public bool $requiresAgreement = false;

    public ?int $selectedTemplateId = null;

    public string $licenseNotes = '';

    // Modal state
    public bool $showLicenseTemplateModal = false;

    public bool $showLicensePreviewModal = false;

    public $previewTemplate = null;

    public function mount(Project $project, array $workflowColors = [])
    {
        $this->project = $project;
        $this->workflowColors = $workflowColors;

        // Initialize basic details
        $this->artistName = $project->artist_name ?? '';
        $this->genre = $project->genre ?? '';
        $this->description = $project->description ?? '';
        $this->notes = $project->notes ?? '';

        // Initialize collaboration types
        $types = is_array($project->collaboration_type)
            ? $project->collaboration_type
            : json_decode($project->collaboration_type ?? '[]', true);

        $this->collaborationTypes = [
            'mixing' => in_array('Mixing', $types),
            'mastering' => in_array('Mastering', $types),
            'production' => in_array('Production', $types),
            'songwriting' => in_array('Songwriting', $types),
            'vocalTuning' => in_array('Vocal Tuning', $types),
        ];

        // Initialize budget
        $this->budgetType = $project->budget > 0 ? 'paid' : 'free';
        $this->budget = $project->budget ?? 0;

        // Initialize deadline
        if ($project->deadline) {
            $timezoneService = app(\App\Services\TimezoneService::class);
            $rawDeadline = $project->getRawOriginal('deadline');

            if ($rawDeadline) {
                $utcTime = null;
                if (strpos($rawDeadline, ':') !== false) {
                    $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawDeadline, 'UTC');
                } else {
                    $utcTime = \Carbon\Carbon::createFromFormat('Y-m-d', $rawDeadline, 'UTC')->startOfDay();
                }

                $userTime = $timezoneService->convertToUserTimezone($utcTime, auth()->user());
                $this->deadlineDisplay = $userTime->format('M j, Y g:i A');
                $this->deadline = $userTime->format('Y-m-d\TH:i');
            }
        }

        // Initialize license settings
        $this->requiresAgreement = $project->requires_license_agreement ?? false;
        $this->selectedTemplateId = $project->license_template_id;
        $this->licenseNotes = $project->license_notes ?? '';
    }

    /**
     * Update project details (artist name, genre, description, notes)
     */
    public function updateProjectDetails(array $updates): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // Build validation rules dynamically
            $rules = [];
            $messages = [];

            if (isset($updates['artist_name'])) {
                $rules['artist_name'] = 'nullable|string|max:255';
                $messages['artist_name.max'] = 'Artist name cannot exceed 255 characters.';
            }

            if (isset($updates['genre'])) {
                $rules['genre'] = 'nullable|string|max:100|in:'.\App\Enums\Genre::validationString();
                $messages['genre.max'] = 'Genre cannot exceed 100 characters.';
                $messages['genre.in'] = 'Please select a valid genre.';
            }

            if (isset($updates['description'])) {
                $rules['description'] = 'nullable|string|max:10000';
                $messages['description.max'] = 'Description cannot exceed 10,000 characters.';
            }

            if (isset($updates['notes'])) {
                $rules['notes'] = 'nullable|string|max:10000';
                $messages['notes.max'] = 'Notes cannot exceed 10,000 characters.';
            }

            // Validate
            $validated = validator($updates, $rules, $messages)->validate();

            // Update the project
            Project::where('id', $this->project->id)->update($validated);

            // Refresh project model
            $this->project->refresh();

            // Success notification
            Toaster::success('Project details updated successfully!');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating project details', [
                'project_id' => $this->project->id,
                'updates' => $updates,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update project details. Please try again.');
        }
    }

    /**
     * Update artist name (called from Alpine.js)
     */
    public function updateArtistName(): void
    {
        $this->updateProjectDetails(['artist_name' => $this->artistName]);
    }

    /**
     * Update genre (called from Alpine.js)
     */
    public function updateGenre(): void
    {
        $this->updateProjectDetails(['genre' => $this->genre]);
    }

    /**
     * Update description (called from Alpine.js)
     */
    public function updateDescription(): void
    {
        $this->updateProjectDetails(['description' => $this->description]);
    }

    /**
     * Update notes (called from Alpine.js)
     */
    public function updateNotes(): void
    {
        $this->updateProjectDetails(['notes' => $this->notes]);
    }

    /**
     * Update collaboration types
     */
    public function updateCollaborationTypes(): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // Build selected types array
            $selected = [];
            if ($this->collaborationTypes['mixing']) {
                $selected[] = 'Mixing';
            }
            if ($this->collaborationTypes['mastering']) {
                $selected[] = 'Mastering';
            }
            if ($this->collaborationTypes['production']) {
                $selected[] = 'Production';
            }
            if ($this->collaborationTypes['songwriting']) {
                $selected[] = 'Songwriting';
            }
            if ($this->collaborationTypes['vocalTuning']) {
                $selected[] = 'Vocal Tuning';
            }

            // Validate at least one is selected (unless client management)
            if (empty($selected) && ! $this->project->isClientManagement()) {
                Toaster::error('Please select at least one collaboration type.');

                return;
            }

            // Update the project
            Project::where('id', $this->project->id)->update([
                'collaboration_type' => json_encode($selected),
            ]);

            // Refresh project model
            $this->project->refresh();

            // Success notification
            Toaster::success('Collaboration types updated successfully!');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating collaboration types', [
                'project_id' => $this->project->id,
                'types' => $selected ?? [],
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update collaboration types. Please try again.');
        }
    }

    /**
     * Update budget (called from Alpine.js)
     */
    public function updateBudget(): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // Only allow for standard projects
            if (! $this->project->isStandard()) {
                Toaster::error('Budget can only be updated for standard projects.');

                return;
            }

            // Validate
            $validated = validator([
                'budget_type' => $this->budgetType,
                'budget' => $this->budget,
            ], [
                'budget_type' => 'required|in:free,paid',
                'budget' => 'nullable|numeric|min:0|max:999999.99',
            ])->validate();

            // Update the project
            Project::where('id', $this->project->id)->update([
                'budget' => $this->budgetType === 'free' ? 0 : $this->budget,
            ]);

            // Refresh project model
            $this->project->refresh();

            // Success notification
            Toaster::success('Budget updated successfully!');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating budget', [
                'project_id' => $this->project->id,
                'budget_type' => $this->budgetType,
                'budget' => $this->budget,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update budget. Please try again.');
        }
    }

    /**
     * Update deadline (called from Alpine.js)
     */
    public function updateDeadline(): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // If deadline is empty, clear it
            if (empty($this->deadline)) {
                Project::where('id', $this->project->id)->update(['deadline' => null]);
                $this->deadlineDisplay = null;
                Toaster::success('Deadline cleared!');

                return;
            }

            // Convert datetime-local format (2025-11-20T21:06) to format expected by convertToUtc (2025-11-20 21:06:00)
            $formattedDeadline = str_replace('T', ' ', $this->deadline).':00';

            // Convert from user timezone to UTC
            $timezoneService = app(\App\Services\TimezoneService::class);
            $utcDeadline = $timezoneService->convertToUtc($formattedDeadline, auth()->user());

            // Validate deadline is in future
            if ($utcDeadline->isPast()) {
                Toaster::error('Deadline must be in the future.');

                return;
            }

            // Update the project
            Project::where('id', $this->project->id)->update([
                'deadline' => $utcDeadline->toDateTimeString(),
            ]);

            // Update display
            $dt = new \DateTime($this->deadline);
            $this->deadlineDisplay = $dt->format('M j, Y g:i A');

            // Refresh project model
            $this->project->refresh();

            // Success notification
            Toaster::success('Deadline updated successfully!');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating deadline', [
                'project_id' => $this->project->id,
                'deadline' => $this->deadline,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update deadline. Please try again.');
        }
    }

    /**
     * Clear deadline
     */
    public function clearDeadline(): void
    {
        $this->deadline = null;
        $this->updateDeadline();
    }

    /**
     * Update license settings
     */
    public function updateLicenseSettings(): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // Validate
            $validated = validator([
                'requires_agreement' => $this->requiresAgreement,
                'template_id' => $this->selectedTemplateId,
                'license_notes' => $this->licenseNotes,
            ], [
                'requires_agreement' => 'boolean',
                'template_id' => 'nullable|exists:license_templates,id',
                'license_notes' => 'nullable|string|max:10000',
            ], [
                'template_id.exists' => 'The selected license template is invalid.',
                'license_notes.max' => 'License notes cannot exceed 10,000 characters.',
            ])->validate();

            // Update the project
            Project::where('id', $this->project->id)->update([
                'requires_license_agreement' => $this->requiresAgreement,
                'license_template_id' => $this->selectedTemplateId,
                'license_notes' => $this->licenseNotes,
            ]);

            // Refresh project model
            $this->project->refresh();

            // Success notification
            Toaster::success('License settings updated successfully!');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating license settings', [
                'project_id' => $this->project->id,
                'settings' => [
                    'requires_agreement' => $this->requiresAgreement,
                    'template_id' => $this->selectedTemplateId,
                    'license_notes' => $this->licenseNotes,
                ],
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update license settings. Please try again.');
        }
    }

    /**
     * Open license template selector modal
     */
    public function openLicenseTemplateSelector(): void
    {
        $this->showLicenseTemplateModal = true;
    }

    /**
     * Clear selected template
     */
    public function clearTemplate(): void
    {
        $this->selectedTemplateId = null;
    }

    /**
     * Get the currently selected license template
     */
    public function getSelectedTemplateProperty()
    {
        if ($this->selectedTemplateId) {
            return \App\Models\LicenseTemplate::find($this->selectedTemplateId);
        }

        return null;
    }

    /**
     * Handle template selection from LicenseSelector component
     */
    #[On('licenseTemplateSelected')]
    public function handleTemplateSelected($data): void
    {
        $this->selectedTemplateId = $data['template_id'];
        $this->showLicenseTemplateModal = false;
    }

    /**
     * Listen for project updates from parent
     */
    #[On('project-updated')]
    public function refreshProject(): void
    {
        $this->project->refresh();

        // Re-initialize values
        $this->mount($this->project, $this->workflowColors);
    }

    /**
     * Preview license template details
     */
    public function previewLicenseTemplate(): void
    {
        if ($this->project->licenseTemplate) {
            $this->previewTemplate = $this->project->licenseTemplate;
            $this->showLicensePreviewModal = true;
        }
    }

    /**
     * Close license preview modal
     */
    public function closeLicensePreview(): void
    {
        $this->showLicensePreviewModal = false;
        $this->previewTemplate = null;
    }

    /**
     * Get available genres for dropdown
     */
    public function getAvailableGenresProperty(): array
    {
        return Project::getGenres();
    }

    public function render()
    {
        return view('livewire.project.component.project-details-card');
    }
}
