<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\LicenseTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Masmerise\Toaster\Toaster;

class ManageLicenseTemplates extends Component
{
    public $selectedTemplate = null;
    public $editingTemplate = null;
    public $showCreateModal = false;
    public $showPreviewModal = false;
    public $showDeleteModal = false;
    public $previewTemplate = null;
    public $deleteTemplate = null;
    
    // Form fields for creating/editing templates
    public $name = '';
    public $description = '';
    public $content = '';
    public $category = '';
    public $use_case = '';
    public $terms = [];
    public $industry_tags = [];
    
    // Marketplace interaction
    public $marketplaceTemplates = null;
    public $showMarketplace = false;
    
    protected $rules = [
        'name' => 'required|string|max:100',
        'description' => 'required|string|max:500',
        'content' => 'required|string|min:50',
        'category' => 'required|in:music,sound-design,mixing,mastering,general',
        'use_case' => 'required|in:collaboration,sync,samples,remix,commercial',
        'terms' => 'array',
        'industry_tags' => 'array',
    ];
    
    public function mount()
    {
        $this->resetForm();
        $this->initializeDefaultTerms();
    }
    
    public function getUserTemplatesProperty(): Collection
    {
        return auth()->user()
            ->licenseTemplates()
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }
    
    public function getMarketplaceTemplatesProperty(): Collection
    {
        return LicenseTemplate::marketplace()
            ->where('user_id', '!=', auth()->id()) // Exclude own templates
            ->orderByUsage()
            ->take(20)
            ->get();
    }
    
    public function getCategoriesProperty(): array
    {
        return LicenseTemplate::getCategories();
    }
    
    public function getUseCasesProperty(): array
    {
        return LicenseTemplate::getUseCases();
    }
    
    public function getCanCreateMoreProperty(): bool
    {
        return LicenseTemplate::canUserCreate(auth()->user());
    }
    
    public function getRemainingTemplatesProperty(): ?int
    {
        $max = auth()->user()->getMaxLicenseTemplates();
        if ($max === null) return null; // Unlimited
        
        $current = auth()->user()->licenseTemplates()->count();
        return max(0, $max - $current);
    }
    
    // Template Management Actions
    public function createTemplate()
    {
        if (!$this->canCreateMore) {
            Toaster::error('You have reached your license template limit. Upgrade to Pro for unlimited templates.');
            return;
        }
        
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    public function editTemplate($templateId)
    {
        $template = auth()->user()->licenseTemplates()->findOrFail($templateId);
        
        $this->editingTemplate = $template;
        $this->name = $template->name;
        $this->description = $template->description ?? '';
        $this->content = $template->content;
        $this->category = $template->category;
        $this->use_case = $template->use_case ?? '';
        $this->terms = $template->terms ?? [];
        $this->industry_tags = $template->industry_tags ?? [];
        
        $this->showCreateModal = true;
    }
    
    public function saveTemplate()
    {
        $this->validate();
        
        try {
            if ($this->editingTemplate) {
                // Update existing template
                $this->editingTemplate->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'content' => $this->content,
                    'category' => $this->category,
                    'use_case' => $this->use_case,
                    'terms' => $this->terms,
                    'industry_tags' => $this->industry_tags,
                ]);
                
                Toaster::success('License template updated successfully!');
            } else {
                // Create new template
                $isFirst = auth()->user()->licenseTemplates()->count() === 0;
                
                auth()->user()->licenseTemplates()->create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'content' => $this->content,
                    'category' => $this->category,
                    'use_case' => $this->use_case,
                    'terms' => $this->terms,
                    'industry_tags' => $this->industry_tags,
                    'is_default' => $isFirst, // First template becomes default
                    'usage_stats' => ['created' => now()->toISOString(), 'times_used' => 0],
                    'legal_metadata' => ['jurisdiction' => 'US', 'version' => '1.0'],
                ]);
                
                Toaster::success('License template created successfully!');
            }
            
            $this->closeModal();
            
        } catch (\Exception $e) {
            Toaster::error('Error saving template: ' . $e->getMessage());
        }
    }
    
    public function setAsDefault($templateId)
    {
        try {
            $template = auth()->user()->licenseTemplates()->findOrFail($templateId);
            $template->setAsDefault();
            
            Toaster::success('Default template updated!');
            
        } catch (\Exception $e) {
            Toaster::error('Error setting default template: ' . $e->getMessage());
        }
    }
    
    public function toggleActive($templateId)
    {
        try {
            $template = auth()->user()->licenseTemplates()->findOrFail($templateId);
            $template->update(['is_active' => !$template->is_active]);
            
            $status = $template->is_active ? 'activated' : 'deactivated';
            Toaster::success("Template {$status} successfully!");
            
        } catch (\Exception $e) {
            Toaster::error('Error updating template status: ' . $e->getMessage());
        }
    }
    
    public function confirmDelete($templateId)
    {
        $this->deleteTemplate = auth()->user()->licenseTemplates()->findOrFail($templateId);
        $this->showDeleteModal = true;
    }
    
    public function deleteTemplate()
    {
        try {
            if ($this->deleteTemplate) {
                $this->deleteTemplate->delete();
                Toaster::success('License template deleted successfully!');
                $this->showDeleteModal = false;
                $this->deleteTemplate = null;
            }
        } catch (\Exception $e) {
            Toaster::error('Error deleting template: ' . $e->getMessage());
        }
    }
    
    // Marketplace Actions
    public function showMarketplace()
    {
        $this->showMarketplace = true;
    }
    
    public function hideMarketplace()
    {
        $this->showMarketplace = false;
    }
    
    public function previewTemplate($templateId, $isMarketplace = false)
    {
        if ($isMarketplace) {
            $this->previewTemplate = LicenseTemplate::marketplace()->findOrFail($templateId);
        } else {
            $this->previewTemplate = auth()->user()->licenseTemplates()->findOrFail($templateId);
        }
        
        $this->showPreviewModal = true;
    }
    
    public function forkTemplate($templateId)
    {
        if (!$this->canCreateMore) {
            Toaster::error('You have reached your license template limit. Upgrade to Pro for unlimited templates.');
            return;
        }
        
        try {
            $sourceTemplate = LicenseTemplate::marketplace()->findOrFail($templateId);
            $forkedTemplate = $sourceTemplate->createFork(auth()->user());
            
            Toaster::success('Template forked to your collection!');
            $this->hideMarketplace();
            
        } catch (\Exception $e) {
            Toaster::error('Error forking template: ' . $e->getMessage());
        }
    }
    
    // Helper Methods
    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showPreviewModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }
    
    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->previewTemplate = null;
    }
    
    private function resetForm()
    {
        $this->editingTemplate = null;
        $this->name = '';
        $this->description = '';
        $this->content = '';
        $this->category = 'general';
        $this->use_case = 'collaboration';
        $this->industry_tags = [];
        $this->initializeDefaultTerms();
    }
    
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
    
    public function render()
    {
        return view('livewire.user.manage-license-templates', [
            'userTemplates' => $this->userTemplates,
            'marketplaceTemplates' => $this->showMarketplace ? $this->marketplaceTemplates : collect(),
            'categories' => $this->categories,
            'useCases' => $this->useCases,
            'canCreateMore' => $this->canCreateMore,
            'remainingTemplates' => $this->remainingTemplates,
        ]);
    }
} 