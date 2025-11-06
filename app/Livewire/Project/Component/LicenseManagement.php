<?php

namespace App\Livewire\Project\Component;

use App\Models\LicenseTemplate;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LicenseManagement extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public array $workflowColors = [];

    public array $semanticColors = [];

    // Preview modal state
    public $showPreviewModal = false;

    public $currentPreviewTemplate = null;

    // Form state
    public $selectedTemplateId = null;

    public $requiresAgreement = false;

    public $licenseNotes = '';

    // Create template modal state
    public $showCreateModal = false;

    public $name = '';

    public $description = '';

    public $content = '';

    public $category = '';

    public $use_case = '';

    public $terms = [];

    public function mount(Project $project, array $workflowColors = [], array $semanticColors = [])
    {
        $this->project = $project;
        $this->workflowColors = $workflowColors;
        $this->semanticColors = $semanticColors;

        // Initialize form values from project
        $this->selectedTemplateId = $project->license_template_id;
        $this->requiresAgreement = $project->requires_license_agreement ?? false;
        $this->licenseNotes = $project->license_notes ?? '';
    }

    /**
     * Get user's active license templates
     */
    public function getUserTemplatesProperty(): Collection
    {
        return auth()->user()
            ->activeLicenseTemplates()
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Preview a template's full content
     */
    public function previewTemplate($templateId)
    {
        $template = LicenseTemplate::find($templateId);

        if ($template && ($template->user_id === auth()->id() || $template->is_public)) {
            $this->currentPreviewTemplate = $template;
            $this->showPreviewModal = true;
        }
    }

    /**
     * Close preview modal
     */
    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->currentPreviewTemplate = null;
    }

    /**
     * Select a template (from preview or card)
     */
    public function selectTemplate($templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->closePreview();
    }

    /**
     * Toggle template selection (select if not selected, deselect if already selected)
     */
    public function toggleTemplate($templateId)
    {
        if ($this->selectedTemplateId == $templateId) {
            $this->selectedTemplateId = null;
        } else {
            $this->selectedTemplateId = $templateId;
        }
    }

    /**
     * Get categories for template creation
     */
    public function getCategoriesProperty(): array
    {
        return LicenseTemplate::getCategories();
    }

    /**
     * Get use cases for template creation
     */
    public function getUseCasesProperty(): array
    {
        return LicenseTemplate::getUseCases();
    }

    /**
     * Check if user can create more templates
     */
    public function getCanUserCreateTemplatesProperty(): bool
    {
        return LicenseTemplate::canUserCreate(auth()->user());
    }

    /**
     * Open create template modal
     */
    public function createTemplate()
    {
        if (! $this->canUserCreateTemplates) {
            Toaster::error('You have reached your license template limit. Upgrade to Pro for unlimited templates.');

            return;
        }

        $this->resetTemplateForm();
        $this->showCreateModal = true;
    }

    /**
     * Save new template
     */
    public function saveTemplate()
    {
        $rules = [
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'content' => 'required|string|min:50',
            'category' => 'required|in:music,sound-design,mixing,mastering,general',
            'use_case' => 'required|in:collaboration,sync,samples,remix,commercial',
            'terms' => 'array',
        ];

        $this->validate($rules);

        try {
            $isFirst = auth()->user()->licenseTemplates()->count() === 0;

            $newTemplate = auth()->user()->licenseTemplates()->create([
                'name' => $this->name,
                'description' => $this->description,
                'content' => $this->content,
                'category' => $this->category,
                'use_case' => $this->use_case,
                'terms' => $this->terms,
                'is_default' => $isFirst,
                'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
            ]);

            // Automatically select the newly created template
            $this->selectedTemplateId = $newTemplate->id;
            $this->requiresAgreement = true;

            $this->closeTemplateModal();
            Toaster::success('Template created and selected successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating license template', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            Toaster::error('Error creating template: '.$e->getMessage());
        }
    }

    /**
     * Close template creation modal
     */
    public function closeTemplateModal()
    {
        $this->showCreateModal = false;
        $this->resetTemplateForm();
    }

    /**
     * Reset template form fields
     */
    private function resetTemplateForm()
    {
        $this->name = '';
        $this->description = '';
        $this->content = '';
        $this->category = 'general';
        $this->use_case = 'collaboration';
        $this->initializeDefaultTerms();
    }

    /**
     * Initialize default terms for new template
     */
    private function initializeDefaultTerms()
    {
        $this->terms = [
            'commercial_use' => false,
            'attribution_required' => false,
            'modification_allowed' => true,
            'distribution_allowed' => false,
            'sync_licensing_allowed' => false,
            'broadcast_allowed' => false,
            'streaming_allowed' => true,
            'territory' => 'worldwide',
            'duration' => 'perpetual',
        ];
    }

    /**
     * Update project license settings
     */
    public function updateLicense(): void
    {
        try {
            // Authorization check
            $this->authorize('update', $this->project);

            // Build validation rules
            $rules = [
                'selectedTemplateId' => 'nullable|exists:license_templates,id',
                'requiresAgreement' => 'boolean',
                'licenseNotes' => 'nullable|string|max:10000',
            ];

            $messages = [
                'selectedTemplateId.exists' => 'The selected license template is invalid.',
                'licenseNotes.max' => 'License notes cannot exceed 10,000 characters.',
            ];

            // Validate
            $this->validate($rules, $messages);

            // Update the project directly in database
            Project::where('id', $this->project->id)->update([
                'license_template_id' => $this->selectedTemplateId,
                'requires_license_agreement' => $this->requiresAgreement,
                'license_notes' => $this->licenseNotes,
            ]);

            // Refresh the project model
            $this->project->refresh();

            // Success notification
            Toaster::success('License settings updated successfully!');

            // Dispatch event for modal to listen to
            $this->dispatch('license-updated');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to update this project.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error updating project license', [
                'project_id' => $this->project->id,
                'selected_template_id' => $this->selectedTemplateId,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to update license settings. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.project.component.license-management');
    }
}
