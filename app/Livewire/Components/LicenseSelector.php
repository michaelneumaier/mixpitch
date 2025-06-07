<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\LicenseTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class LicenseSelector extends Component
{
    public $selectedTemplateId = null;
    public $customTerms = [];
    public $requiresAgreement = true;
    public $licenseNotes = '';
    public $projectType = null;
    public $showCustomTermsBuilder = false;
    public $showPreviewModal = false;
    public $currentPreviewTemplate = null;

    // Expose data to parent component
    public function updatedSelectedTemplateId($value)
    {
        $this->dispatch('licenseTemplateSelected', [
            'template_id' => $value,
            'requires_agreement' => $this->requiresAgreement,
            'license_notes' => $this->licenseNotes,
        ]);
    }

    public function updatedRequiresAgreement($value)
    {
        $this->dispatch('licenseRequirementChanged', [
            'template_id' => $this->selectedTemplateId,
            'requires_agreement' => $value,
            'license_notes' => $this->licenseNotes,
        ]);
    }

    public function updatedLicenseNotes($value)
    {
        $this->dispatch('licenseNotesChanged', [
            'template_id' => $this->selectedTemplateId,
            'requires_agreement' => $this->requiresAgreement,
            'license_notes' => $value,
        ]);
    }

    public function mount($selectedTemplateId = null, $projectType = null, $requiresAgreement = true)
    {
        $this->selectedTemplateId = $selectedTemplateId;
        $this->projectType = $projectType;
        $this->requiresAgreement = $requiresAgreement;
    }

    public function getUserTemplatesProperty(): Collection
    {
        return auth()->user()
            ->activeLicenseTemplates()
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }



    public function getCanUserCreateTemplatesProperty(): bool
    {
        return LicenseTemplate::canUserCreate(auth()->user());
    }

    private function getUseCaseFromProjectType(): string
    {
        return match($this->projectType) {
            'mixing', 'mastering' => LicenseTemplate::USE_CASE_COLLABORATION,
            'sync' => LicenseTemplate::USE_CASE_SYNC,
            'sample_pack' => LicenseTemplate::USE_CASE_SAMPLES,
            'remix' => LicenseTemplate::USE_CASE_REMIX,
            default => LicenseTemplate::USE_CASE_COLLABORATION,
        };
    }

    private function getCategoryFromProjectType(): string
    {
        return match($this->projectType) {
            'mixing' => LicenseTemplate::CATEGORY_MIXING,
            'mastering' => LicenseTemplate::CATEGORY_MASTERING,
            'sound_design' => LicenseTemplate::CATEGORY_SOUND_DESIGN,
            default => LicenseTemplate::CATEGORY_MUSIC,
        };
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->showCustomTermsBuilder = false;
        
        // Close modal if open
        $this->showPreviewModal = false;
        $this->currentPreviewTemplate = null;
        
        // Update parent component
        $this->updatedSelectedTemplateId($templateId);
    }

    public function selectCustomLicense()
    {
        $this->selectedTemplateId = null;
        $this->showCustomTermsBuilder = true;
    }

    public function previewTemplate($templateId)
    {
        try {
            // First try to find in user's templates
            $template = auth()->user()->activeLicenseTemplates()->find($templateId);
            
            // If not found in user's templates, try marketplace
            if (!$template) {
                $template = LicenseTemplate::marketplace()->find($templateId);
            }
            
            // If still not found, try any accessible template
            if (!$template) {
                $template = LicenseTemplate::find($templateId);
            }
            
            if (!$template) {
                session()->flash('error', 'Template not found or access denied.');
                return;
            }
            
            $this->currentPreviewTemplate = $template;
            $this->showPreviewModal = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to load template preview.');
        }
    }

    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->currentPreviewTemplate = null;
    }

    public function forkTemplate($templateId)
    {
        $sourceTemplate = LicenseTemplate::find($templateId);
        
        if (!$sourceTemplate || !LicenseTemplate::canUserCreate(auth()->user())) {
            session()->flash('error', 'Unable to fork template. Check your subscription limits.');
            return;
        }

        $forkedTemplate = $sourceTemplate->createFork(auth()->user());
        $this->selectedTemplateId = $forkedTemplate->id;
        
        // Close any open modals
        $this->showPreviewModal = false;
        $this->currentPreviewTemplate = null;
        
        session()->flash('success', 'Template forked to your collection!');
        
        // Update parent component with new selection
        $this->updatedSelectedTemplateId($forkedTemplate->id);
        
        // Refresh the templates
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.components.license-selector', [
            'userTemplates' => $this->userTemplates,
        ]);
    }
} 