<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectManagementService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class QuickProjectModal extends Component
{
    // Modal state
    public bool $showModal = false;

    // Workflow selection
    public ?string $workflow_type = null;

    // Base fields (all workflows)
    public string $name = '';

    public string $artist_name = '';

    public ?string $project_type = null;

    public ?string $genre = null;

    public string $description = '';

    public array $collaboration_types = [];

    // Contest-specific fields
    public ?string $submission_deadline = null;

    // Client Management-specific fields
    public ?string $client_email = null;

    public ?string $client_name = null;

    public ?float $payment_amount = null;

    // Client Selection (for client management workflow)
    public array $clients = [];

    public ?string $selectedClientEmail = null;

    public bool $showClientSelection = true;

    public bool $isExistingClient = false;

    // Available options
    public array $projectTypes = [];

    public array $genres = [];

    public array $collaborationServices = [];

    protected $listeners = ['openQuickProjectModal' => 'openModal'];

    public function mount()
    {
        // Initialize available options
        $this->projectTypes = [
            'single' => 'Single',
            'ep' => 'EP',
            'album' => 'Album',
            'mixtape' => 'Mixtape',
            'remix' => 'Remix',
            'other' => 'Other',
        ];

        $this->genres = [
            'Pop',
            'Rock',
            'Hip Hop',
            'Electronic',
            'R&B',
            'Country',
            'Jazz',
            'Classical',
            'Metal',
            'Blues',
            'Folk',
            'Funk',
            'Reggae',
            'Soul',
            'Punk',
        ];

        $this->collaborationServices = [
            'Production' => 'Production',
            'Mixing' => 'Mixing',
            'Mastering' => 'Mastering',
            'Songwriting' => 'Songwriting',
            'Vocal Tuning' => 'Vocal Tuning',
            'Audio Editing' => 'Audio Editing',
        ];
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|min:5|max:80',
            'workflow_type' => 'required|in:standard,contest,client_management',
            'artist_name' => 'nullable|string|max:30',
            'project_type' => 'nullable|string',
            'genre' => 'nullable|string',
            'description' => 'nullable|string|min:5|max:1000',
            'collaboration_types' => 'nullable|array',
        ];

        // Contest-specific validation
        if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
            $rules['submission_deadline'] = 'nullable|date|after:now';
        }

        // Client Management-specific validation
        if ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
            $rules['client_email'] = 'required|email|max:255';
            $rules['client_name'] = 'nullable|string|max:255';
            $rules['payment_amount'] = 'nullable|numeric|min:0|max:999999.99';
        }

        return $rules;
    }

    public function validationAttributes()
    {
        return [
            'name' => 'project name',
            'artist_name' => 'artist name',
            'project_type' => 'project type',
            'genre' => 'genre',
            'description' => 'description',
            'submission_deadline' => 'submission deadline',
            'client_email' => 'client email',
            'client_name' => 'client name',
            'payment_amount' => 'payment amount',
        ];
    }

    #[On('openQuickProjectModal')]
    public function openModal(string $workflowType, ?string $clientEmail = null, ?string $clientName = null): void
    {
        // Reset form
        $this->reset([
            'name',
            'artist_name',
            'project_type',
            'genre',
            'description',
            'collaboration_types',
            'submission_deadline',
            'client_email',
            'client_name',
            'payment_amount',
            'selectedClientEmail',
            'isExistingClient',
        ]);

        // Set workflow type
        $this->workflow_type = $workflowType;

        // For client management workflow, load clients and show selection step
        if ($workflowType === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
            $this->loadClients();
            $this->showClientSelection = true;

            // Pre-fill client data if provided
            if ($clientEmail) {
                $this->client_email = $clientEmail;
                $this->client_name = $clientName;
                $this->showClientSelection = false;
                $this->isExistingClient = false;
            }
        } else {
            $this->showClientSelection = false;
        }

        // Set defaults
        $this->project_type = 'single';
        $this->collaboration_types = ['Production']; // Default service

        // Open modal
        $this->showModal = true;
        $this->dispatch('modal-show', name: 'quick-project-modal');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('modal-close', name: 'quick-project-modal');
    }

    /**
     * Load user's existing clients
     */
    public function loadClients(): void
    {
        $this->clients = Auth::user()
            ->projects()
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNotNull('client_email')
            ->select('client_email', 'client_name')
            ->distinct()
            ->get()
            ->map(function ($project) {
                return [
                    'email' => $project->client_email,
                    'name' => $project->client_name ?: $project->client_email,
                ];
            })
            ->unique('email')
            ->values()
            ->toArray();
    }

    /**
     * Select an existing client from dropdown
     */
    public function selectExistingClient(string $clientEmail): void
    {
        $client = collect($this->clients)->firstWhere('email', $clientEmail);

        if ($client) {
            $this->client_email = $client['email'];
            $this->client_name = $client['name'];
            $this->isExistingClient = true;
            $this->selectedClientEmail = $client['email'];
            $this->showClientSelection = false;
        }
    }

    /**
     * Choose to create a new client
     */
    public function chooseNewClient(): void
    {
        $this->client_email = null;
        $this->client_name = null;
        $this->isExistingClient = false;
        $this->selectedClientEmail = null;
        $this->showClientSelection = false;
    }

    /**
     * Go back to client selection step
     */
    public function backToClientSelection(): void
    {
        $this->showClientSelection = true;
        // Don't clear client data - user might want to go back
    }

    public function createProject(ProjectManagementService $projectManagementService, TimezoneService $timezoneService)
    {
        // Validate
        $this->validate();

        try {
            DB::transaction(function () use ($projectManagementService) {
                // Build project data with defaults
                $projectData = [
                    'name' => $this->name,
                    'workflow_type' => $this->workflow_type,
                    'artist_name' => $this->artist_name ?: '',
                    'project_type' => $this->project_type ?: 'single',
                    'genre' => $this->genre ?: 'Pop',
                    'description' => $this->description ?: $this->getDefaultDescription(),
                    'collaboration_type' => ! empty($this->collaboration_types) ? $this->collaboration_types : ['Production'],
                    'budget' => 0, // Default free
                    'status' => Project::STATUS_UNPUBLISHED,
                ];

                // Add workflow-specific data
                if ($this->workflow_type === Project::WORKFLOW_TYPE_CONTEST) {
                    // Convert submission deadline from user's timezone to UTC if provided
                    if (! empty($this->submission_deadline)) {
                        $projectData['submission_deadline'] = $this->convertDateTimeToUtc($this->submission_deadline, Auth::user());
                    }
                } elseif ($this->workflow_type === Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
                    $projectData['client_email'] = $this->client_email;
                    $projectData['client_name'] = $this->client_name;
                    $projectData['payment_amount'] = $this->payment_amount ?? 0;
                }

                // Create project
                $project = $projectManagementService->createProject(
                    Auth::user(),
                    $projectData,
                    null // No image upload in quick creation
                );

                // Refresh the project to ensure all casts are applied
                $project->refresh();

                Log::info('Quick project created successfully', [
                    'project_id' => $project->id,
                    'workflow_type' => $project->workflow_type,
                    'user_id' => Auth::id(),
                ]);

                // Close modal
                $this->closeModal();

                // Show success message
                Toaster::success('Project created successfully! Complete the setup to publish.');

                // Redirect to appropriate manage page
                if ($project->isClientManagement()) {
                    $this->redirect(route('projects.manage-client', $project), navigate: true);
                } else {
                    $this->redirect(route('projects.manage', $project), navigate: true);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to create project via QuickProjectModal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'workflow_type' => $this->workflow_type,
                'user_id' => Auth::id(),
            ]);

            Toaster::error('Failed to create project. Please try again.');
        }
    }

    /**
     * Get default description based on workflow type
     */
    protected function getDefaultDescription(): string
    {
        return match ($this->workflow_type) {
            Project::WORKFLOW_TYPE_CONTEST => 'Contest project - add details about prizes and judging criteria.',
            Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT => 'Client project - add details about client requirements and deliverables.',
            default => 'Add details about your project to help producers understand what you\'re looking for.',
        };
    }

    /**
     * Convert datetime-local input to UTC for database storage
     */
    private function convertDateTimeToUtc(string $dateTime, ?User $user = null): Carbon
    {
        try {
            $user = $user ?? Auth::user();
            $userTimezone = $user->getTimezone() ?? 'UTC';

            Log::debug('QuickProjectModal convertDateTimeToUtc called', [
                'input' => $dateTime,
                'user_timezone' => $userTimezone,
                'input_type' => gettype($dateTime),
            ]);

            // Handle datetime-local format: "2025-06-29T13:00"
            if (str_contains($dateTime, 'T')) {
                // Convert T to space and add seconds if needed
                $formattedDateTime = str_replace('T', ' ', $dateTime);
                if (substr_count($formattedDateTime, ':') === 1) {
                    $formattedDateTime .= ':00'; // Add seconds
                }

                // Validate the datetime format before conversion
                if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $formattedDateTime)) {
                    throw new \InvalidArgumentException('Invalid datetime format: '.$formattedDateTime);
                }

                // Create Carbon instance in user's timezone and convert to UTC
                $result = Carbon::createFromFormat('Y-m-d H:i:s', $formattedDateTime, $userTimezone);

                if (! $result) {
                    throw new \InvalidArgumentException('Failed to parse datetime: '.$formattedDateTime);
                }

                $result = $result->utc();

                Log::debug('QuickProjectModal: Datetime-local conversion', [
                    'input' => $dateTime,
                    'formatted' => $formattedDateTime,
                    'user_timezone' => $userTimezone,
                    'output_utc' => $result->toDateTimeString(),
                ]);

                return $result;
            }

            // Fallback: assume it's already in UTC or parse as-is
            $result = Carbon::parse($dateTime);
            if (! $result) {
                throw new \InvalidArgumentException('Failed to parse datetime: '.$dateTime);
            }

            $result = $result->utc();
            Log::debug('QuickProjectModal: Fallback conversion', [
                'input' => $dateTime,
                'output' => $result->toDateTimeString(),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('QuickProjectModal: Timezone conversion failed', [
                'input' => $dateTime,
                'user_timezone' => $userTimezone ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Fallback to current UTC time to prevent fatal errors
            return Carbon::now('UTC');
        }
    }

    public function render()
    {
        return view('livewire.quick-project-modal');
    }
}
