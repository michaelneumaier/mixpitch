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
    public $currentPreviewTemplate = null;
    public $templateToDelete = null;
    
    // Form fields for creating/editing templates
    public $name = '';
    public $description = '';
    public $content = '';
    public $category = '';
    public $use_case = '';
    public $terms = [];
    public $industry_tags = [];
    
    // Marketplace interaction
    public $marketplaceTemplates;
    public $showMarketplace = false;
    
    // Publishing to marketplace
    public $showPublishModal = false;
    public $templateToPublish = null;
    public $marketplaceTitle = '';
    public $marketplaceDescription = '';
    public $submissionNotes = '';
    
    // Enhanced marketplace filtering
    public $searchTerm = '';
    public $filterCategory = '';
    public $filterUseCase = '';
    public $sortBy = 'popular';
    
    protected $rules = [
        'name' => 'required|string|max:100',
        'description' => 'required|string|max:500',
        'content' => 'required|string|min:50',
        'category' => 'required|in:music,sound-design,mixing,mastering,general',
        'use_case' => 'required|in:collaboration,sync,samples,remix,commercial',
        'terms' => 'array',
        'industry_tags' => 'array',
        // Marketplace publishing validation
        'marketplaceTitle' => 'required|string|max:150',
        'marketplaceDescription' => 'required|string|max:1000',
        'submissionNotes' => 'nullable|string|max:500',
    ];
    
    public function mount()
    {
        $this->resetForm();
        $this->initializeDefaultTerms();
        // Initialize as empty collection
        $this->marketplaceTemplates = collect();
    }
    
    public function getUserTemplatesProperty(): Collection
    {
        return auth()->user()
            ->licenseTemplates()
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
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
        $this->templateToDelete = auth()->user()->licenseTemplates()->findOrFail($templateId);
        $this->showDeleteModal = true;
    }
    
    public function deleteTemplate()
    {
        try {
            if ($this->templateToDelete) {
                $this->templateToDelete->delete();
                Toaster::success('License template deleted successfully!');
                $this->showDeleteModal = false;
                $this->templateToDelete = null;
            }
        } catch (\Exception $e) {
            Toaster::error('Error deleting template: ' . $e->getMessage());
        }
    }
    
        // Marketplace Actions
    public function openMarketplace()
    {
        try {
            \Log::info('Opening marketplace - loading templates now');
            
            // Set filters to default
            $this->searchTerm = '';
            $this->filterCategory = '';
            $this->filterUseCase = '';
            $this->sortBy = 'popular';
            
            // Load marketplace templates directly
            $this->marketplaceTemplates = LicenseTemplate::marketplace()
                ->where('user_id', '!=', auth()->id())
                ->with('user')
                ->orderByPopularity()
                ->take(24)
                ->get();
            
            \Log::info('Templates loaded directly', [
                'count' => $this->marketplaceTemplates->count(),
                'template_ids' => $this->marketplaceTemplates->pluck('id')->toArray()
            ]);
            
            // Now show the marketplace
            $this->showMarketplace = true;
            
        } catch (\Exception $e) {
            \Log::error('Error opening marketplace: ' . $e->getMessage());
            Toaster::error('Error opening marketplace. Please try again.');
        }
    }
    


    public function closeMarketplace()
    {
        $this->showMarketplace = false;
    }
    
    public function openPublishModal($templateId)
    {
        $template = auth()->user()->licenseTemplates()->findOrFail($templateId);
        
        if (!$template->canBePublishedToMarketplace()) {
            Toaster::error('This template cannot be published to the marketplace.');
            return;
        }
        
        $this->templateToPublish = $template;
        $this->marketplaceTitle = $template->name;
        $this->marketplaceDescription = $template->description ?? '';
        $this->submissionNotes = '';
        $this->showPublishModal = true;
    }
    
    public function publishToMarketplace()
    {
        $this->validate([
            'marketplaceTitle' => 'required|string|max:150',
            'marketplaceDescription' => 'required|string|max:1000',
            'submissionNotes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->templateToPublish->submitToMarketplace([
                'marketplace_title' => $this->marketplaceTitle,
                'marketplace_description' => $this->marketplaceDescription,
                'submission_notes' => $this->submissionNotes,
            ]);
            
            Toaster::success('Template submitted to marketplace for approval!');
            $this->closePublishModal();
            
        } catch (\Exception $e) {
            Toaster::error('Error submitting template: ' . $e->getMessage());
        }
    }
    
    public function closePublishModal()
    {
        $this->showPublishModal = false;
        $this->templateToPublish = null;
        $this->marketplaceTitle = '';
        $this->marketplaceDescription = '';
        $this->submissionNotes = '';
        $this->resetValidation(['marketplaceTitle', 'marketplaceDescription', 'submissionNotes']);
    }
    
    public function clearMarketplaceFilters()
    {
        $this->searchTerm = '';
        $this->filterCategory = '';
        $this->filterUseCase = '';
        $this->sortBy = 'popular';
        // For now, just reload all templates - we can add filtering later
        if ($this->showMarketplace) {
            $this->openMarketplace();
        }
    }
    
    public function previewTemplate($templateId, $isMarketplace = false)
    {
        if ($isMarketplace) {
            $this->currentPreviewTemplate = LicenseTemplate::marketplace()->findOrFail($templateId);
            // Track view for marketplace templates
            $this->currentPreviewTemplate->incrementViewCount();
        } else {
            $this->currentPreviewTemplate = auth()->user()->licenseTemplates()->findOrFail($templateId);
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
            $this->closeMarketplace();
            $this->closePreview(); // Also close the preview modal
            
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
        $this->showPublishModal = false;
        $this->showMarketplace = false;
        $this->resetForm();
    }
    
    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->currentPreviewTemplate = null;
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
        
        // Initialize marketplace filter properties
        $this->searchTerm = '';
        $this->filterCategory = '';
        $this->filterUseCase = '';
        $this->sortBy = 'popular';
        
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
        \Log::info('Simple render', [
            'showMarketplace' => $this->showMarketplace,
            'marketplaceTemplates_count' => $this->marketplaceTemplates ? $this->marketplaceTemplates->count() : 'null'
        ]);
        
        return view('livewire.user.manage-license-templates', [
            'userTemplates' => $this->userTemplates,
            'marketplaceTemplates' => $this->marketplaceTemplates ?? collect(),
            'categories' => $this->categories,
            'useCases' => $this->useCases,
            'canCreateMore' => $this->canCreateMore,
            'remainingTemplates' => $this->remainingTemplates,
            'sortOptions' => [
                'popular' => 'Most Popular',
                'newest' => 'Newest',
                'views' => 'Most Viewed',
                'alphabetical' => 'Alphabetical'
            ],
        ]);
    }
} 