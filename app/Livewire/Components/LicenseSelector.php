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
    public $previewTemplate = null;

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
            ->licenseTemplates()
            ->active()
            ->approved()
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getRecommendedTemplatesProperty(): Collection
    {
        $query = LicenseTemplate::marketplace();

        // Filter by project type if available
        if ($this->projectType) {
            $query->where(function ($q) {
                $q->where('use_case', $this->getUseCaseFromProjectType())
                  ->orWhere('category', $this->getCategoryFromProjectType());
            });
        }

        return $query->take(3)->get();
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
    }

    public function selectCustomLicense()
    {
        $this->selectedTemplateId = null;
        $this->showCustomTermsBuilder = true;
    }

    public function previewTemplate($templateId)
    {
        $this->previewTemplate = LicenseTemplate::find($templateId);
        $this->showPreviewModal = true;
    }

    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->previewTemplate = null;
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
        
        session()->flash('success', 'Template forked to your collection!');
        
        // Refresh the templates
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.components.license-selector', [
            'userTemplates' => $this->userTemplates,
            'recommendedTemplates' => $this->recommendedTemplates,
        ]);
    }
} 