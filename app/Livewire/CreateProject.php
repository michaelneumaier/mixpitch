<?php

namespace App\Livewire;

// Remove direct controller usage
// use App\Http\Controllers\ProjectController;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\ContestPrize;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

// Added for refactoring
use App\Services\Project\ProjectManagementService;
use App\Exceptions\Project\ProjectCreationException;
use App\Exceptions\Project\ProjectUpdateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\Toaster;
use App\Models\ProjectType;

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

    // Wizard properties
    public int $currentStep = 1;
    public int $totalSteps = 4;
    public array $wizardSteps = [];
    public bool $useWizard = true; // Can be disabled for edit mode

    // Add project_type property (defaulting to standard)
    // Note: It seems ProjectForm already has this based on mount(), ensure it's public there
    // public string $project_type = Project::TYPE_STANDARD;

    public $title = '';
    public $description = '';
    public $artist_name = '';
    // Removed: public $project_type = ''; // Keep original if used for subtypes
    public $collaboration_type = '';
    public $budget = '';
    public $genre_id = '';
    public $subgenre_id = '';
    public $visibility = 'public';

    // New property for workflow type
    public string $workflow_type = Project::WORKFLOW_TYPE_STANDARD;

    // Properties for Contest
    public $submission_deadline = null;
    public $judging_deadline = null;
    public $prize_amount = null;
    public $prize_currency = Project::DEFAULT_CURRENCY;
    
    // New properties for contest prize integration
    public $totalPrizeBudget = 0;
    public $prizeCount = 0;

    // Properties for Direct Hire
    public $target_producer_id = null;
    public $target_producer_query = '';
    public $producers = [];

    // Properties for Client Management
    public ?string $client_email = null;
    public ?string $client_name = null;
    public $payment_amount = null; // Added for Client Management Payment

    // License properties
    public $selectedLicenseTemplateId = null;
    public $requiresLicenseAgreement = true;
    public $licenseNotes = '';
    public $customLicenseTerms = [];

    protected $listeners = [
        'prizesUpdated' => 'handlePrizesUpdated',
        'prizesSaved' => 'handlePrizesSaved',
        // License listeners
        'licenseTemplateSelected' => 'handleLicenseTemplateSelected',
        'licenseRequirementChanged' => 'handleLicenseRequirementChanged',
        'licenseNotesChanged' => 'handleLicenseNotesChanged',
    ];

    protected function rules(): array
    {
        $rules = [
            // Form object properties
            'form.name' => 'required|string|min:5|max:80',
            'form.artistName' => 'nullable|string|max:30',
            'form.projectType' => 'required|string|max:50',
            'form.description' => 'required|string|min:5|max:1000',
            'form.genre' => 'required|in:Blues,Classical,Country,Electronic,Folk,Funk,Hip Hop,Jazz,Metal,Pop,Reggae,Rock,Soul,R&B,Punk',
            'form.budgetType' => 'required|in:free,paid',
            'form.budget' => 'nullable|numeric|min:0',
            'form.deadline' => 'nullable|date',
            'form.collaborationTypeMixing' => 'boolean',
            'form.collaborationTypeMastering' => 'boolean',
            'form.collaborationTypeProduction' => 'boolean',
            'form.collaborationTypeSongwriting' => 'boolean',
            'form.collaborationTypeVocalTuning' => 'boolean',

            // Component properties
            'workflow_type' => ['required', Rule::in(Project::getWorkflowTypes())],

            // Conditional Validation - UPDATED: Removed old prize validation for contests
            'submission_deadline' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CONTEST.'|nullable|date|after:now',
            'judging_deadline' => 'nullable|date|after:submission_deadline',
            // Note: prize_amount and prize_currency are now managed by ContestPrizeConfigurator

            'target_producer_id' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_DIRECT_HIRE.'|nullable|exists:users,id',

            'client_email' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT.'|nullable|email|max:255',
            'client_name' => 'nullable|string|max:255',

            // Added: Validation for Client Management Payment Amount
            'payment_amount' => 'required_if:workflow_type,'.Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT.'|nullable|numeric|min:0',
        ];

        // Apply step-specific validation in wizard mode
        if ($this->useWizard && !$this->isEdit) {
            return $this->getStepValidationRules($rules);
        }

        return $rules;
    }

    /**
     * Get validation rules for the current step
     */
    protected function getStepValidationRules(array $allRules): array
    {
        switch ($this->currentStep) {
            case 1: // Project Type & Workflow Selection
                return [
                    'workflow_type' => $allRules['workflow_type'],
                ];
            case 2: // Basic Project Details
                $stepRules = [
                    'form.name' => $allRules['form.name'],
                ];
                
                // For Client Management, only project name is required
                if ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
                    // Optional fields for client management
                    $stepRules['form.description'] = 'nullable|string|min:5|max:1000';
                    $stepRules['form.artistName'] = $allRules['form.artistName'];
                    $stepRules['form.projectType'] = 'nullable|string|max:50';
                    $stepRules['form.genre'] = 'nullable|in:Blues,Classical,Country,Electronic,Folk,Funk,Hip Hop,Jazz,Metal,Pop,Reggae,Rock,Soul,R&B,Punk';
                } else {
                    // Standard validation for other workflows
                    $stepRules['form.description'] = $allRules['form.description'];
                    $stepRules['form.artistName'] = $allRules['form.artistName'];
                    $stepRules['form.projectType'] = $allRules['form.projectType'];
                    $stepRules['form.genre'] = $allRules['form.genre'];
                }
                
                // Collaboration types are always optional in step 2
                $stepRules['form.collaborationTypeMixing'] = $allRules['form.collaborationTypeMixing'];
                $stepRules['form.collaborationTypeMastering'] = $allRules['form.collaborationTypeMastering'];
                $stepRules['form.collaborationTypeProduction'] = $allRules['form.collaborationTypeProduction'];
                $stepRules['form.collaborationTypeSongwriting'] = $allRules['form.collaborationTypeSongwriting'];
                $stepRules['form.collaborationTypeVocalTuning'] = $allRules['form.collaborationTypeVocalTuning'];
                
                return $stepRules;
            case 3: // Workflow-Specific Configuration
                $stepRules = [];
                
                // Budget and deadline are not required for Client Management OR Contests (prizes managed separately)
                if ($this->workflow_type !== Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT && 
                    $this->workflow_type !== Project::WORKFLOW_TYPE_CONTEST) {
                    $stepRules['form.budgetType'] = $allRules['form.budgetType'];
                    $stepRules['form.budget'] = $allRules['form.budget'];
                }
                
                // Deadline is optional for Client Management
                if ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
                    $stepRules['form.deadline'] = 'nullable|date';
                } else {
                    $stepRules['form.deadline'] = $allRules['form.deadline'];
                }
                
                // Add workflow-specific rules
                if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
                    $stepRules['submission_deadline'] = $allRules['submission_deadline'];
                    $stepRules['judging_deadline'] = $allRules['judging_deadline'];
                    // Note: Prize configuration is now handled by ContestPrizeConfigurator component
                } elseif ($this->workflow_type === Project::WORKFLOW_TYPE_DIRECT_HIRE) {
                    $stepRules['target_producer_id'] = $allRules['target_producer_id'];
                } elseif ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
                    $stepRules['client_email'] = $allRules['client_email'];
                    $stepRules['client_name'] = $allRules['client_name'];
                    $stepRules['payment_amount'] = $allRules['payment_amount'];
                }
                
                return $stepRules;
            case 4: // Review & Finalization
                return []; // No additional validation needed for review step
            default:
                return $allRules;
        }
    }

    /**
     * Initialize wizard steps configuration
     */
    protected function initializeWizardSteps(): void
    {
        $this->wizardSteps = [
            [
                'label' => 'Workflow',
                'description' => 'Choose type',
                'icon' => 'fas fa-route',
            ],
            [
                'label' => 'Details',
                'description' => 'Basic info',
                'icon' => 'fas fa-info-circle',
            ],
            [
                'label' => 'Configure',
                'description' => 'Settings',
                'icon' => 'fas fa-cog',
            ],
            [
                'label' => 'Review',
                'description' => 'Finalize',
                'icon' => 'fas fa-check',
            ],
        ];
    }

    /**
     * Get workflow type configurations for the selector
     */
    public function getWorkflowTypesProperty(): array
    {
        return [
            [
                'value' => Project::WORKFLOW_TYPE_STANDARD,
                'name' => 'Standard Project',
                'description' => 'Open to all producers. Receive multiple pitches and choose the best one.',
                'icon' => 'fas fa-bullhorn',
                'color' => 'blue',
                'features' => [
                    'Open to all producers',
                    'Multiple pitch submissions',
                    'Choose your favorite',
                    'Standard workflow'
                ],
                'badge' => 'Most Popular'
            ],
            [
                'value' => Project::WORKFLOW_TYPE_CONTEST,
                'name' => 'Contest Project',
                'description' => 'Run a competition with deadlines and prizes to attract top talent.',
                'icon' => 'fas fa-trophy',
                'color' => 'amber',
                'features' => [
                    'Competition format',
                    'Set submission deadlines',
                    'Prize pool',
                    'Judging period'
                ],
                'badge' => 'High Engagement'
            ],
            // Direct Hire temporarily hidden - not fully implemented yet
            // [
            //     'value' => Project::WORKFLOW_TYPE_DIRECT_HIRE,
            //     'name' => 'Direct Hire',
            //     'description' => 'Invite a specific producer to work on your project privately.',
            //     'icon' => 'fas fa-user-check',
            //     'color' => 'green',
            //     'features' => [
            //         'Private collaboration',
            //         'Invite specific producer',
            //         'Direct communication',
            //         'Faster turnaround'
            //     ],
            //     'badge' => 'Exclusive'
            // ],
            [
                'value' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
                'name' => 'Client Management',
                'description' => 'Manage projects for external clients with a dedicated portal.',
                'icon' => 'fas fa-briefcase',
                'color' => 'purple',
                'features' => [
                    'Client portal access',
                    'Payment integration',
                    'Professional workflow',
                    'Client communication'
                ],
                'badge' => 'Professional'
            ],
        ];
    }

    /**
     * Get current workflow configuration
     */
    public function getCurrentWorkflowConfigProperty(): array
    {
        $workflowTypes = $this->workflowTypes;
        foreach ($workflowTypes as $type) {
            if ($type['value'] === $this->workflow_type) {
                // Add workflow-specific fields for the summary
                $type['fields'] = $this->getWorkflowSpecificFields($this->workflow_type);
                return $type;
            }
        }
        return [];
    }

    /**
     * Get workflow-specific fields for summary display
     */
    protected function getWorkflowSpecificFields(string $workflowType): array
    {
        switch ($workflowType) {
            case Project::WORKFLOW_TYPE_CONTEST:
                return [
                    ['key' => 'submission_deadline', 'label' => 'Submission Deadline', 'type' => 'date'],
                    ['key' => 'judging_deadline', 'label' => 'Judging Deadline', 'type' => 'date'],
                    // Note: Prize configuration is now handled by ContestPrizeConfigurator component
                ];
            case Project::WORKFLOW_TYPE_DIRECT_HIRE:
                return [
                    ['key' => 'target_producer_query', 'label' => 'Target Producer', 'type' => 'text'],
                ];
            case Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT:
                return [
                    ['key' => 'client_email', 'label' => 'Client Email', 'type' => 'text'],
                    ['key' => 'client_name', 'label' => 'Client Name', 'type' => 'text'],
                    ['key' => 'payment_amount', 'label' => 'Payment Amount', 'type' => 'currency'],
                ];
            default:
                return [];
        }
    }

    /**
     * Get project data for summary display
     */
    public function getProjectSummaryProperty(): array
    {
        $summary = [
            'name' => $this->form->name,
            'artist_name' => $this->form->artistName,
            'project_type' => $this->form->projectType,
            'description' => $this->form->description,
            'genre' => $this->form->genre,
            'collaboration_types' => $this->getSelectedCollaborationTypes(),
            'budget' => $this->form->budget,
            'deadline' => $this->form->deadline,
            'additional_notes' => $this->form->notes,
            'workflow_type' => $this->workflow_type,
            
            // Workflow-specific fields
            'submission_deadline' => $this->submission_deadline,
            'judging_deadline' => $this->judging_deadline,
            'total_prize_budget' => $this->totalPrizeBudget,
            'prize_count' => $this->prizeCount,
            'target_producer_query' => $this->target_producer_query,
            'client_email' => $this->client_email,
            'client_name' => $this->client_name,
            'payment_amount' => $this->payment_amount,
        ];

        // Add contest prize data for the wizard summary
        if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
            $summary['totalPrizeBudget'] = $this->totalPrizeBudget;
            $summary['prizeCount'] = $this->prizeCount;
            
            // If editing an existing project, get the actual prize summary
            if ($this->isEdit && $this->project && $this->project->hasPrizes()) {
                $summary['prizeSummary'] = $this->project->getPrizeSummary();
            } else {
                // For new projects, get prize data from session (set by the configurator)
                $summary['prizeSummary'] = session('wizard_prize_summary', []);
            }
        }

        return $summary;
    }

    /**
     * Get selected collaboration types as array
     */
    protected function getSelectedCollaborationTypes(): array
    {
        $types = [];
        if ($this->form->collaborationTypeMixing) $types[] = 'mixing';
        if ($this->form->collaborationTypeMastering) $types[] = 'mastering';
        if ($this->form->collaborationTypeProduction) $types[] = 'production';
        if ($this->form->collaborationTypeSongwriting) $types[] = 'songwriting';
        if ($this->form->collaborationTypeVocalTuning) $types[] = 'vocal tuning';
        return $types;
    }

    /**
     * Navigate to next step
     */
    public function nextStep(): void
    {
        if (!$this->useWizard || $this->isEdit) {
            return;
        }

        // Custom validation for collaboration types in step 2 (except for Client Management)
        if ($this->currentStep === 2 && $this->workflow_type !== Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
            $hasCollaborationType = $this->form->collaborationTypeMixing || 
                                  $this->form->collaborationTypeMastering || 
                                  $this->form->collaborationTypeProduction || 
                                  $this->form->collaborationTypeSongwriting || 
                                  $this->form->collaborationTypeVocalTuning;
            
            if (!$hasCollaborationType) {
                $this->addError('collaboration_type', 'Please select at least one collaboration type.');
                return;
            }
        }

        // Validate current step
        $allRules = $this->rules();
        $stepRules = $this->getStepValidationRules($allRules);
        
        // This will throw ValidationException if validation fails, which Livewire handles
        $this->validate($stepRules);

        // Clear any previous collaboration type errors
        $this->resetErrorBag('collaboration_type');

        // Advance to next step
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    /**
     * Navigate to previous step
     */
    public function previousStep(): void
    {
        if (!$this->useWizard || $this->isEdit) {
            return;
        }

        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Go to specific step (if accessible)
     */
    public function goToStep(int $step): void
    {
        if (!$this->useWizard || $this->isEdit) {
            return;
        }

        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    public function mount($project = null)
    {
        // Initialize wizard steps
        $this->initializeWizardSteps();
        
        $this->project = new Project(); // Keep for reference, maybe not needed

        if ($project) {
            // Add authorization check for edit mode
            try {
                $this->authorize('update', $project);
            } catch (AuthorizationException $e) {
                abort(403);
            }

            // Disable wizard for edit mode
            $this->useWizard = false;

            // Load the existing project for editing
            $this->originalProject = $project; // Store original for comparison if needed
            $this->project = $project; // Keep a reference to the model
            $this->isEdit = true;

            // Populate form object with project subtype and other details
            $this->form->setProject($project); // Use the form object's method

            // Populate component properties for workflow and conditional fields
            $this->workflow_type = $project->workflow_type ?? Project::WORKFLOW_TYPE_STANDARD;
            $this->title = $project->title; // Populate component title as well
            $this->description = $project->description;
            $this->artist_name = $project->artist_name;
            $this->collaboration_type = $project->collaboration_type;
            $this->budget = $project->budget;
            $this->genre_id = $project->genre_id;
            $this->subgenre_id = $project->subgenre_id;
            $this->visibility = $project->visibility;

            // Populate workflow-specific fields
            if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
                $this->submission_deadline = $project->submission_deadline ? $project->submission_deadline->format('Y-m-d\TH:i') : null;
                $this->judging_deadline = $project->judging_deadline ? $project->judging_deadline->format('Y-m-d\TH:i') : null;
                
                // Load prize data from new ContestPrize system
                $this->totalPrizeBudget = $project->getTotalPrizeBudget();
                $this->prizeCount = $project->contestPrizes()->count();
                
                // Keep old fields for compatibility but load from new system if available
                if ($project->hasPrizes()) {
                    // Update budget to match total cash prizes
                    $this->form->budget = $this->totalPrizeBudget;
                    $this->form->budgetType = $this->totalPrizeBudget > 0 ? 'paid' : 'free';
                } else {
                    // Fallback to old prize fields if no new prizes exist
                    $this->prize_amount = $project->prize_amount;
                    $this->prize_currency = $project->prize_currency;
                }
            }
            if ($this->workflow_type === Project::WORKFLOW_TYPE_DIRECT_HIRE) {
                $this->target_producer_id = $project->target_producer_id;
                if ($project->targetProducer) {
                    $this->target_producer_query = $project->targetProducer->name; // Pre-fill search query
                }
            }
            if ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
                $this->client_email = $project->client_email;
                $this->client_name = $project->client_name;
                // Added: Populate payment_amount for edit mode
                // Need to retrieve this from the Pitch model, not the Project model.
                // Assuming a single pitch is auto-created and linked.
                $associatedPitch = $project->pitches()->first();
                if ($associatedPitch) {
                    $this->payment_amount = $associatedPitch->payment_amount;
                }
            }

            // Populate license data for edit mode
            $this->selectedLicenseTemplateId = $project->license_template_id;
            $this->requiresLicenseAgreement = $project->requires_license_agreement ?? true;
            $this->licenseNotes = $project->license_notes ?? '';
            $this->customLicenseTerms = $project->custom_license_terms ?? [];

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
            // Default the form's projectType (subtype) to 'single' or another appropriate default
            $this->form->projectType = 'single';
            // Component's workflow_type already defaults to WORKFLOW_TYPE_STANDARD
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

    /**
     * Search for producers when the query is updated.
     */
    public function updatedTargetProducerQuery(string $query): void
    {
        if (strlen($query) < 2) {
            $this->producers = [];
            return;
        }

        // Assuming producers are regular users for now.
        // Add role filtering if applicable (e.g., where('role', 'producer'))
        $this->producers = \App\Models\User::where('name', 'like', '%'.$query.'%')
            ->where('id', '!=', auth()->id()) // Exclude self
            ->select('id', 'name')
            ->take(10)
            ->get();
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
        $this->validate(['track' => 'file|mimes:mp3,wav,flac,aac,m4a,ogg|max:204800']); // 200MB max
        
        // Remove existing audio URL when new track is uploaded
        $this->audioUrl = null;
        
        try {
            // Store the track temporarily to generate a preview URL
        $this->audioUrl = $this->track->temporaryUrl();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to process the uploaded track. Please try again.');
                 $this->track = null;
        }
        
        $this->initWaveSurfer = true;
    }

    // ========== LICENSE EVENT HANDLERS ==========

    /**
     * Handle license template selection
     */
    public function handleLicenseTemplateSelected($data)
    {
        $this->selectedLicenseTemplateId = $data['template_id'];
        $this->requiresLicenseAgreement = $data['requires_agreement'];
        $this->licenseNotes = $data['license_notes'];
    }

    /**
     * Handle license requirement changes
     */
    public function handleLicenseRequirementChanged($data)
    {
        $this->selectedLicenseTemplateId = $data['template_id'];
        $this->requiresLicenseAgreement = $data['requires_agreement'];
        $this->licenseNotes = $data['license_notes'];
            }

    /**
     * Handle license notes changes
     */
    public function handleLicenseNotesChanged($data)
    {
        $this->selectedLicenseTemplateId = $data['template_id'];
        $this->requiresLicenseAgreement = $data['requires_agreement'];
        $this->licenseNotes = $data['license_notes'];
    }

    /**
     * Save the project (Create or Update).
     */
    public function save(ProjectManagementService $projectService)
    {
        try {
            // Format deadline if it exists
            if (!empty($this->form->deadline)) {
                try {
                    $this->form->deadline = \Carbon\Carbon::parse($this->form->deadline)->startOfDay()->format('Y-m-d');
                } catch (\Exception $e) {
                    $this->form->deadline = null;
                }
            }

            // Format budget based on budgetType
            if ($this->form->budgetType === 'free') {
                $this->form->budget = 0;
            } else {
                // Remove any non-numeric characters except decimal point
                $this->form->budget = preg_replace('/[^\d.]/', '', $this->form->budget);
            }

            // Validate component rules first (includes workflow-specific validation)
            $this->validate();
            
            // Then validate form data
            $validatedFormData = $this->form->validate();
            
            // Sync component title with form name if needed
            if (empty($this->title) && !empty($this->form->name)) {
                $this->title = $this->form->name;
            } else if (empty($this->form->name) && !empty($this->title)) {
                $this->form->name = $this->title;
            }
            
            // Build collaboration type array from checkboxes
            $collaborationTypes = [];
            if (!empty($this->form->collaborationTypeMixing)) $collaborationTypes[] = 'Mixing';
            if (!empty($this->form->collaborationTypeMastering)) $collaborationTypes[] = 'Mastering';
            if (!empty($this->form->collaborationTypeProduction)) $collaborationTypes[] = 'Production';
            if (!empty($this->form->collaborationTypeSongwriting)) $collaborationTypes[] = 'Songwriting';
            if (!empty($this->form->collaborationTypeVocalTuning)) $collaborationTypes[] = 'Vocal Tuning';
            
            // If none selected, use default
            if (empty($collaborationTypes)) {
                $collaborationTypes[] = 'Production'; // Default
            }
        
            // Prepare project data
            $projectData = [
                'user_id' => auth()->id(),
                'name' => $this->form->name,
                'title' => $this->title,
                'description' => $this->form->description ?: 'Client project', // Default for client management
                'artist_name' => $this->form->artistName,
                'project_type' => $this->form->projectType ?: 'single', // Keep for backward compatibility
                'collaboration_type' => $collaborationTypes,
                
                // Format budget as numeric value - for Client Management, use 0 as default
                'budget' => $this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT ? 0 : 
                           ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST ? $this->totalPrizeBudget : 
                           (is_numeric($this->form->budget) ? (float)$this->form->budget : 0)),
                
                // Format deadline for database (use submission_deadline for contests, standard deadline for others)
                'deadline' => $this->workflow_type === Project::WORKFLOW_TYPE_CONTEST ? 
                            ($this->submission_deadline ? \Carbon\Carbon::parse($this->submission_deadline) : null) :
                            (!empty($this->form->deadline) ? \Carbon\Carbon::parse($this->form->deadline) : null),
                
                'genre' => $this->form->genre ?: 'Pop', // Default genre for client management
                'genre_id' => $this->genre_id,
                'subgenre_id' => $this->subgenre_id,
                'visibility' => $this->visibility ?? 'public',
                
                // Workflow specific fields
                'workflow_type' => $this->workflow_type,
                'submission_deadline' => $this->workflow_type === Project::WORKFLOW_TYPE_CONTEST ? $this->submission_deadline : null,
                'judging_deadline' => $this->workflow_type === Project::WORKFLOW_TYPE_CONTEST ? $this->judging_deadline : null,
                // Note: prize_amount and prize_currency are now managed by ContestPrizeConfigurator
                'target_producer_id' => $this->workflow_type === Project::WORKFLOW_TYPE_DIRECT_HIRE ? $this->target_producer_id : null,
                'client_email' => $this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT ? $this->client_email : null,
                'client_name' => $this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT ? $this->client_name : null,
                
                // Add payment_amount for Client Management (this gets passed to the ProjectObserver)
                'payment_amount' => $this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT ? $this->payment_amount : null,
                
                // License fields
                'license_template_id' => $this->selectedLicenseTemplateId,
                'license_notes' => $this->licenseNotes,
                'requires_license_agreement' => $this->requiresLicenseAgreement,
                'license_status' => $this->requiresLicenseAgreement ? 'pending' : 'active',
                'license_jurisdiction' => 'US', // Default jurisdiction
                'custom_license_terms' => !empty($this->customLicenseTerms) ? $this->customLicenseTerms : null,
            ];
            
            // Add project_type_id by looking up the ProjectType
            if (!empty($this->form->projectType)) {
                $projectType = ProjectType::where('slug', $this->form->projectType)->first();
                if ($projectType) {
                    $projectData['project_type_id'] = $projectType->id;
                }
            }
            
            if ($this->isEdit && $this->originalProject) {
                // Update existing project
                $this->authorize('update', $this->originalProject);
                $project = $projectService->updateProject(
                    $this->originalProject,
                    $projectData,
                    $this->form->projectImage,
                    true
                );
                Toaster::success('Project updated successfully!');
            } else {
                // Create new project
                $this->authorize('create', Project::class);
                $project = $projectService->createProject(
                    auth()->user(),
                    $projectData,
                    $this->form->projectImage
                );
                $project->update(['status' => Project::STATUS_UNPUBLISHED, 'is_published' => false]);
                
                // Save contest prizes if this is a contest project
                if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
                    $prizesSaved = \App\Livewire\ContestPrizeConfigurator::saveStoredPrizesToProject($project);
                    if ($prizesSaved) {
                        // Update project budget with total cash prizes
                        $totalCashPrizes = $project->getTotalPrizeBudget();
                        if ($totalCashPrizes > 0) {
                            $project->update(['budget' => $totalCashPrizes]);
                        }
                    }
                }
                
                // Clear wizard-related session data
                session()->forget(['wizard_prize_summary', 'contest_prize_data']);
                
                Toaster::success('Project created successfully!');
            }
            
            // Redirect to the project management page
            return redirect()->route('projects.manage', $project->slug);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they can be caught by Livewire
            throw $e;
        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to perform this action.');
        } catch (ProjectCreationException | ProjectUpdateException $e) {
            Toaster::error('There was an error saving the project: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error("Error saving project: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Special test helper to simplify testing image uploads.
     * This method is only usable in testing environments.
     */
    public function forceImageUpdate()
    {
        if (!app()->environment('testing')) {
            throw new \Exception('This method can only be used in the testing environment.');
        }

        if (!$this->form->projectImage) {
            throw new \Exception('No project image has been uploaded to update.');
        }

        if (!$this->isEdit || !$this->project || !$this->project->exists) {
            throw new \Exception('Cannot update image on a project that does not exist.');
        }

        $imageFile = $this->form->projectImage;
        
        // Generate a unique filename for the test
        $timestamp = time();
        $randomStr = substr(md5(rand()), 0, 10);
        $filename = "test_forced_{$timestamp}_{$randomStr}.jpg";
        
        // Force a new image path to ensure it's different
        $uniqueImagePath = $imageFile->storeAs(
            'project_images',
            $filename,
            's3'
        );

        // Update the project directly
        $oldImagePath = $this->project->image_path;
        $this->project->image_path = $uniqueImagePath;
        $this->project->save();

        // Delete old image if it exists
        if ($oldImagePath) {
            Storage::disk('s3')->delete($oldImagePath);
        }

        return $this->project;
    }

    /**
     * Get workflow-specific content for step 2
     */
    public function getStep2ContentProperty(): array
    {
        switch ($this->workflow_type) {
            case Project::WORKFLOW_TYPE_STANDARD:
                return [
                    'title' => 'Project Details',
                    'subtitle' => 'Tell us about your project. Provide clear details to attract the right collaborators.',
                    'required_fields' => ['name', 'description', 'projectType', 'genre'],
                ];
            case Project::WORKFLOW_TYPE_CONTEST:
                return [
                    'title' => 'Contest Details',
                    'subtitle' => 'Set up your contest project. Clear details will help producers understand what you\'re looking for.',
                    'required_fields' => ['name', 'description', 'projectType', 'genre'],
                ];
            case Project::WORKFLOW_TYPE_DIRECT_HIRE:
                return [
                    'title' => 'Project Details',
                    'subtitle' => 'Describe your project for the producer you\'ll be working with directly.',
                    'required_fields' => ['name', 'description', 'projectType', 'genre'],
                ];
            case Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT:
                return [
                    'title' => 'Client Project Setup',
                    'subtitle' => 'Set up the basic details for your client project. Only the project name is required - you can add more details later.',
                    'required_fields' => ['name'],
                ];
            default:
                return [
                    'title' => 'Project Details',
                    'subtitle' => 'Tell us about your project.',
                    'required_fields' => ['name', 'description', 'projectType', 'genre'],
                ];
        }
    }

    /**
     * Handle prize updates from the ContestPrizeConfigurator component
     */
    public function handlePrizesUpdated($data)
    {
        $this->totalPrizeBudget = $data['totalCashPrizes'] ?? 0;
        $this->prizeCount = $data['prizeCounts']['total'] ?? 0;
        
        // Store prize summary for wizard display
        if (isset($data['prizeSummary'])) {
            session(['wizard_prize_summary' => $data['prizeSummary']]);
        }
        
        // Auto-update form budget if it's a contest
        if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
            if ($this->totalPrizeBudget > 0) {
                $this->form->budgetType = 'paid';
                $this->form->budget = $this->totalPrizeBudget;
            } else {
                $this->form->budgetType = 'free';
                $this->form->budget = 0;
            }
        }
    }

    /**
     * Handle when prizes are saved by the configurator
     */
    public function handlePrizesSaved()
    {
        // Optionally refresh the component or show a message
        $this->dispatch('refresh');
    }

    /**
     * Get active project types for dropdowns
     */
    public function getProjectTypesProperty()
    {
        return ProjectType::getActive();
    }

    public function render()
    {
        return view('livewire.project.page.create-project');
    }
}
