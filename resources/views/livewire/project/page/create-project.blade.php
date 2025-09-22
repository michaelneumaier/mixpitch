<div>
@php
    // Define available genres
    $genres = ['Pop', 'Rock', 'Hip Hop', 'Electronic', 'R&B', 'Country', 'Jazz', 'Classical', 'Metal', 'Blues', 'Folk', 'Funk', 'Reggae', 'Soul', 'Punk'];

    // Unified Color System - Dynamic workflow-aware colors
    $workflowColors = match($workflow_type ?? 'standard') {
        'standard' => [
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800', 
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
            'accent_border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400'
        ],
        'contest' => [
            'bg' => 'bg-orange-50 dark:bg-orange-950',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text_primary' => 'text-orange-900 dark:text-orange-100', 
            'text_secondary' => 'text-orange-700 dark:text-orange-300',
            'text_muted' => 'text-orange-600 dark:text-orange-400',
            'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
            'accent_border' => 'border-orange-200 dark:border-orange-800',
            'icon' => 'text-orange-600 dark:text-orange-400'
        ],
        'direct_hire' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300', 
            'text_muted' => 'text-green-600 dark:text-green-400',
            'accent_bg' => 'bg-green-100 dark:bg-green-900',
            'accent_border' => 'border-green-200 dark:border-green-800',
            'icon' => 'text-green-600 dark:text-green-400'
        ],
        'client_management' => [
            'bg' => 'bg-purple-50 dark:bg-purple-950',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400', 
            'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
            'accent_border' => 'border-purple-200 dark:border-purple-800',
            'icon' => 'text-purple-600 dark:text-purple-400'
        ],
        default => [
            'bg' => 'bg-gray-50 dark:bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100 dark:bg-gray-900', 
            'accent_border' => 'border-gray-200 dark:border-gray-800',
            'icon' => 'text-gray-600 dark:text-gray-400'
        ]
    };

    // Semantic colors (always consistent)
    $semanticColors = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-600 dark:bg-green-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400', 
            'accent' => 'bg-amber-500'
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500'
        ]
    ];
@endphp

<div class="min-h-screen w-full bg-gradient-to-br from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900" 
     x-data="createProjectWizard()"
     x-init="init()">
    
    <div class="mx-auto p-2">
        <div class="mx-auto">

        @if($useWizard && !$isEdit)
                {{-- Modern Wizard Mode --}}
                
                <!-- Progress Indicator -->
                <div class="mb-8">
                    <div class="text-center mb-6">
                        <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent">
                            Create Your Project
                        </flux:heading>
                        <flux:text class="mt-2 text-gray-600 dark:text-gray-400">
                            Follow these steps to set up your music project
                        </flux:text>
                    </div>

                    <!-- Enhanced Progress Steps -->
                    <div class="relative max-w-4xl mx-auto">
                        <!-- Progress Line -->
                        <div class="absolute top-10 left-0 right-0 h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="absolute top-10 left-0 h-0.5 bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500" 
                             style="width: {{ (($currentStep - 1) / ($totalSteps - 1)) * 100 }}%"></div>
                        
                        <!-- Step Indicators -->
                        <div class="relative flex justify-between">
                            @php
                                $steps = [
                                    ['number' => 1, 'name' => 'Workflow', 'icon' => 'folder-open'],
                                    ['number' => 2, 'name' => 'Details', 'icon' => 'document-text'],
                                    ['number' => 3, 'name' => 'Configure', 'icon' => 'cog'],
                                    ['number' => 4, 'name' => 'Review', 'icon' => 'check-circle']
                                ];
                            @endphp
                            
                            @foreach($steps as $step)
                                <div class="flex flex-col items-center">
                                    <div class="relative">
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center transition-all duration-300
                                            {{ $currentStep > $step['number'] ? 'bg-gradient-to-br from-green-500 to-green-600 shadow-lg shadow-green-500/25' : 
                                               ($currentStep === $step['number'] ? 'bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/25' : 
                                                'bg-gray-100 dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600') }}">
                                            @if($currentStep > $step['number'])
                                                <flux:icon name="check" class="w-8 h-8 text-white" />
                                            @else
                                                <flux:icon name="{{ $step['icon'] }}" class="w-8 h-8 {{ $currentStep === $step['number'] ? 'text-white' : 'text-gray-400 dark:text-gray-500' }}" />
                                            @endif
                                        </div>
                                        @if($currentStep === $step['number'])
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-bold text-white">{{ $step['number'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="mt-3 text-sm font-medium {{ $currentStep >= $step['number'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $step['name'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Wizard Content -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        @if($currentStep === 1)
                            {{-- Step 1: Project Type & Workflow Selection --}}
                        <div class="p-2 lg:p-10">
                            <div class="text-center mb-8">
                                <flux:heading size="xl" class="font-bold">Choose Your Collaboration Type</flux:heading>
                                <flux:text class="mt-3 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                                    Each workflow is designed for different collaboration needs. Select the one that best matches your project goals.
                                </flux:text>
                            </div>
                            <div class="max-w-4xl mx-auto">
                            <x-wizard.workflow-type-selector 
                                :workflowTypes="$this->workflowTypes"
                                :selectedType="$workflow_type"
                                wireModel="workflow_type"
                            />
                            </div>

                            @error('workflow_type')
                            <div class="mt-4">
                                <flux:callout color="red" icon="exclamation-circle">
                                    <flux:callout.text>{{ $message }}</flux:callout.text>
                                </flux:callout>
                            </div>
                            @enderror
                        </div>

                        @elseif($currentStep === 2)
                            {{-- Step 2: Basic Project Details --}}
                            <div class="p-2 lg:p-10">
                            <div class="text-center mb-8">
                                <flux:heading size="xl" class="font-bold">{{ $this->step2Content['title'] }}</flux:heading>
                                <flux:text class="mt-3 text-gray-600 dark:text-gray-400">{{ $this->step2Content['subtitle'] }}</flux:text>
                            </div>

                            <div class="max-w-4xl mx-auto">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <!-- Left Column -->
                                    <div class="space-y-6">
                                        <!-- Project Name -->
                                    <flux:field>
                                        <flux:label>
                                                Project Name <span class="text-red-500">*</span>
                                        </flux:label>
                                        <flux:input 
                                            wire:model.blur="form.name" 
                                            placeholder="Enter your project name" 
                                            maxlength="80" />
                                        <flux:error name="form.name" />
                                    </flux:field>

                                        <!-- Artist Name -->
                                    <flux:field>
                                        <flux:label>
                                                Artist Name
                                                @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @endif
                                        </flux:label>
                                        <flux:input 
                                            wire:model.blur="form.artistName" 
                                            placeholder="Enter artist name" 
                                            maxlength="30" />
                                        <flux:error name="form.artistName" />
                                    </flux:field>

                                        <!-- Project Type -->
                                    <div>
                                            <x-project-types.enhanced-selector 
                                                :projectTypes="$this->projectTypes"
                                                :selected="$form->projectType"
                                                wireModel="form.projectType"
                                                :required="$workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT"
                                            />
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                <div class="space-y-6">
                                     <!-- Genre -->
                                    <flux:field>
                                        <flux:label>
                                                Genre 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                        </flux:label>
                                        <flux:select wire:model.live="form.genre">
                                                <option value="">Select a genre</option>
                                                @foreach($genres as $genre)
                                                <option value="{{ $genre }}">{{ $genre }}</option>
                                                @endforeach
                                        </flux:select>
                                        <flux:error name="form.genre" />
                                    </flux:field>
                                        <!-- Description -->
                                    <flux:field>
                                        <flux:label>
                                                Project Description 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                        </flux:label>
                                        <flux:textarea 
                                            wire:model.blur="form.description" 
                                            rows="6"
                                                      placeholder="Describe your project, what you're looking for, and any specific requirements..."
                                            maxlength="5000" />
                                        <flux:error name="form.description" />
                                    </flux:field>
                                </div>
                            </div>
                            
                            <!-- Collaboration Types - Full Width Section -->
                            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                                <div class="mb-6">
                                    <flux:heading class="mb-2">Collaboration Services</flux:heading>
                                    <flux:text class="text-gray-600 dark:text-gray-400">
                                        Select the types of collaboration you're looking for (optional)
                                    </flux:text>
                                </div>
                                
                                <flux:checkbox.group variant="pills" class="flex flex-wrap gap-3 [&_label]:cursor-pointer [&_*]:select-none">
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeMixing"
                                        value="mixing"
                                        label="Mixing" />
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeMastering"
                                        value="mastering"
                                        label="Mastering" />
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeProduction"
                                        value="production"
                                        label="Production" />
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeSongwriting"
                                        value="songwriting"
                                        label="Songwriting" />
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeVocalTuning"
                                        value="vocal-tuning"
                                        label="Vocal Tuning" />
                                    <flux:checkbox 
                                        wire:model="form.collaborationTypeAudioEditing"
                                        value="audio-editing"
                                        label="Audio Editing" />
                                </flux:checkbox.group>
                                @error('collaboration_type')
                                <div class="mt-4">
                                    <flux:error>{{ $message }}</flux:error>
                                </div>
                                @enderror
                            </div>
                        </div>
                                    </div>
                                </div>
                            </div>

                        @elseif($currentStep === 3)
                            {{-- Step 3: Workflow-Specific Configuration --}}
                        <div class="p-2 lg:p-10 space-y-6">
                            <div class="text-center">
                                <flux:heading size="xl" class="mb-2">Configure Your {{ $this->currentWorkflowConfig['name'] ?? 'Project' }}</flux:heading>
                                <flux:text class="text-gray-600 dark:text-gray-400">
                                    Set up the specific details for your {{ strtolower($this->currentWorkflowConfig['name'] ?? 'project') }}.
                                </flux:text>
                                </div>

                            <div class="max-w-4xl mx-auto space-y-6">
                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT && 
                                            $workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                    <!-- Budget Selector -->
                                            <x-wizard.budget-selector 
                                                :budgetType="$form->budgetType"
                                                :budget="$form->budget"
                                                :workflowType="$workflow_type"
                                            />
                                        @endif

                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                    <!-- Deadline Selector -->
                                        <x-wizard.deadline-selector 
                                            :deadline="$form->deadline"
                                            :workflowType="$workflow_type"
                                        />
                                        @endif

                                        @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                    {{-- Contest Settings --}}
                                    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                        <div class="flex items-center gap-3 mb-6">
                                            <flux:icon name="trophy" variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                        Contest Settings
                                            </flux:heading>
                                        </div>
                                        
                                        <div class="space-y-6">
                                            <flux:field>
                                                <flux:label>
                                                    <flux:icon name="calendar" class="mr-2" />
                                                                Submission Deadline
                                                    <flux:badge color="zinc" size="xs" class="ml-2">
                                                                    {{ $this->getTimezoneDisplayName() }}
                                                    </flux:badge>
                                                </flux:label>
                                                <flux:input 
                                                    type="datetime-local" 
                                                    wire:model="submission_deadline" />
                                                <flux:error name="submission_deadline" />
                                            </flux:field>
                                            
                                            <flux:field>
                                                <flux:label>
                                                    <flux:icon name="scale" class="mr-2" />
                                                                Judging Deadline
                                                    <flux:badge color="zinc" size="xs" class="ml-2">
                                                                    {{ $this->getTimezoneDisplayName() }}
                                                    </flux:badge>
                                                </flux:label>
                                                <flux:input 
                                                    type="datetime-local" 
                                                    wire:model="judging_deadline" />
                                                <flux:error name="judging_deadline" />
                                            </flux:field>
                                                        </div>
                                    </flux:card>

                                    {{-- Contest Prize Configuration --}}
                                    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                        <div class="flex items-center gap-3 mb-6">
                                            <flux:icon name="gift" variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                            <div>
                                                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                    Contest Prizes
                                                </flux:heading>
                                                <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">
                                                    Configure the prizes and rewards for your contest winners.
                                                </flux:text>
                                                </div>
                                            </div>
                                                        
                                                        @if($isEdit && $project)
                                                            @livewire('contest-prize-configurator', ['project' => $project], key('contest-prizes-edit-'.$project->id))
                                                        @else
                                                            @livewire('contest-prize-configurator', key('contest-prizes-edit-new'))
                                                        @endif
                                    </flux:card>

                                @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE)
                                    {{-- Direct Hire Settings --}}
                                    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                        <div class="flex items-center gap-3 mb-6">
                                            <flux:icon name="user-check" variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                        Direct Hire Settings
                                            </flux:heading>
                                                        </div>
                                                        
                                        <flux:field>
                                            <flux:label>
                                                <flux:icon name="magnifying-glass" class="mr-2" />
                                                                Target Producer <span class="text-red-500">*</span>
                                            </flux:label>
                                            <flux:input 
                                                wire:model.live.debounce.300ms="target_producer_query" 
                                                placeholder="Search for a producer..." />
                                                            
                                                            @if(count($producers) > 0)
                                                <div class="mt-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                                                @foreach($producers as $producer)
                                                                <div wire:click="$set('target_producer_id', {{ $producer->id }}); $set('target_producer_query', '{{ $producer->name }}')" 
                                                             class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors">
                                                            <div class="flex items-center gap-3">
                                                                <flux:icon name="user" class="w-5 h-5 text-gray-500" />
                                                                <span class="font-medium">{{ $producer->name }}</span>
                                                                    </div>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                            @endif
                                                            
                                            <flux:error name="target_producer_id" />
                                        </flux:field>
                                    </flux:card>

                                        @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                    {{-- Client Management Settings --}}
                                    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                        <div class="flex items-center gap-3 mb-6">
                                            <flux:icon name="briefcase" variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                        Client Management Settings
                                            </flux:heading>
                                        </div>
                                        
                                        <div class="space-y-6">
                                            <flux:field>
                                                <flux:label>
                                                    <flux:icon name="envelope" class="mr-2" />
                                                                Client Email <span class="text-red-500">*</span>
                                                </flux:label>
                                                <flux:input 
                                                    type="email"
                                                    wire:model.blur="client_email" 
                                                    placeholder="client@example.com" />
                                                <flux:error name="client_email" />
                                            </flux:field>

                                            <flux:field>
                                                <flux:label>
                                                    <flux:icon name="user" class="mr-2" />
                                                                Client Name
                                                </flux:label>
                                                <flux:input 
                                                    wire:model.blur="client_name" 
                                                    placeholder="Client's full name" />
                                                <flux:error name="client_name" />
                                            </flux:field>

                                            <flux:field>
                                                <flux:label>
                                                    <flux:icon name="currency-dollar" class="mr-2" />
                                                                Client Payment Amount <span class="text-red-500">*</span>
                                                </flux:label>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500">$</span>
                                                                </div>
                                                    <flux:input 
                                                        type="number"
                                                        wire:model.blur="payment_amount" 
                                                        placeholder="0.00" 
                                                        min="0" 
                                                        step="0.01"
                                                        class="pl-10" />
                                                            </div>
                                                <flux:description>
                                                                This is the amount your client will pay upon project approval. Set to $0 if no payment is required.
                                                </flux:description>
                                                <flux:error name="payment_amount" />
                                            </flux:field>

                                                        </div>
                                    </flux:card>
                                        @endif
                                </div>
                            </div>

                        @elseif($currentStep === 4)
                            {{-- Step 4: Review & Finalization --}}
                        <div class="space-y-6">
                            <div class="text-center">
                                <flux:heading size="xl" class="mb-2">Review Your Project</flux:heading>
                                <flux:text class="text-gray-600 dark:text-gray-400">
                                    Please review all the details before creating your project. You can go back to make changes if needed.
                                </flux:text>
                                </div>
                            <div class="max-w-4xl mx-auto space-y-6">
                            <!-- License Configuration -->
                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2">
                                <flux:heading class="flex items-center gap-3 mb-4 {{ $workflowColors['text_primary'] }}">
                                    <flux:icon name="document-text" class="w-6 h-6 {{ $workflowColors['icon'] }}" />
                                    License Terms
                                </flux:heading>
                                
                                @livewire('components.license-selector', [
                                    'projectType' => $form->projectType,
                                    'selectedTemplateId' => $selectedLicenseTemplateId,
                                    'requiresAgreement' => $requiresLicenseAgreement,
                                    'licenseNotes' => $licenseNotes
                                ], key('license-selector-' . ($project->id ?? 'new')))
                            </flux:card>

                            <!-- Project Summary -->
                                <x-wizard.project-summary 
                                    :project="$this->projectSummary"
                                    :workflowConfig="$this->currentWorkflowConfig"
                                />

                            <!-- Additional Notes -->
                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2">
                                <flux:heading class="flex items-center gap-3 mb-4 {{ $workflowColors['text_primary'] }}">
                                    <flux:icon name="chat-bubble-left-ellipsis" class="w-6 h-6 {{ $workflowColors['icon'] }}" />
                                    Additional Notes
                                </flux:heading>
                                
                                <flux:field>
                                    <flux:label>
                                        Project Notes (Optional)
                                    </flux:label>
                                    <flux:textarea 
                                        wire:model.blur="form.notes" 
                                        rows="4"
                                        placeholder="Any additional information or special requirements..." />
                                    <flux:error name="form.notes" />
                                </flux:field>
                            </flux:card>
                            </div>
                            </div>
                        @endif

                    <!-- Navigation Buttons -->
                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center max-w-4xl mx-auto">
                            <div>
                                @if($currentStep > 1)
                                <flux:button 
                                    wire:click="previousStep" 
                                    variant="outline" 
                                    icon="arrow-left"
                                    @click="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                                    Previous Step
                                </flux:button>
                                @else
                                <div></div>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                @if($currentStep < $totalSteps)
                                <flux:button 
                                    wire:click="nextStep" 
                                    variant="primary" 
                                    icon-trailing="arrow-right"
                                    @click="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
                                    Continue
                                </flux:button>
                                @else
                                <flux:button 
                                    wire:click="save" 
                                    variant="primary" 
                                    icon="rocket-launch"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove>Create Project</span>
                                    <span wire:loading>Creating Project...</span>
                                </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

        @else
            {{-- Edit Mode - Comprehensive Form --}}
                <flux:card>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <flux:heading size="xl" class="flex items-center gap-3">
                                <flux:icon name="pencil" variant="solid" class="w-8 h-8 text-blue-600" />
                                        Edit Project
                            </flux:heading>
                            <flux:text class="text-gray-600 dark:text-gray-400">
                                Update your project details and settings
                            </flux:text>
                                </div>
                                
                        <flux:badge :color="$workflow_type === 'contest' ? 'orange' : ($workflow_type === 'direct_hire' ? 'green' : ($workflow_type === 'client_management' ? 'purple' : 'blue'))" size="sm">
                            {{ ucwords(str_replace('_', ' ', $workflow_type)) }}
                        </flux:badge>
                    </div>

                    <form wire:submit.prevent="save" class="space-y-8">
                                
                                <!-- Basic Project Information -->
                        <div class="space-y-6">
                            <flux:heading size="lg" class="flex items-center gap-3">
                                <flux:icon name="information-circle" class="w-6 h-6 text-blue-600" />
                                Basic Project Information
                            </flux:heading>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="lg:col-span-2">
                                    <flux:field>
                                        <flux:label>Project Title</flux:label>
                                        <flux:input wire:model="title" placeholder="Enter your project title" />
                                        <flux:error name="title" />
                                    </flux:field>
                                        </div>

                                <flux:field>
                                    <flux:label>
                                                Artist Name
                                                @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @endif
                                    </flux:label>
                                    <flux:input wire:model="form.artistName" placeholder="Enter artist name" maxlength="30" />
                                    <flux:error name="form.artistName" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>
                                                Genre 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                    </flux:label>
                                    <flux:select wire:model="form.genre">
                                                <option value="">Select a genre</option>
                                                @foreach($genres as $genre)
                                                <option value="{{ $genre }}">{{ $genre }}</option>
                                                @endforeach
                                    </flux:select>
                                    <flux:error name="form.genre" />
                                </flux:field>

                                        <div class="lg:col-span-2">
                                    <flux:field>
                                        <flux:label>
                                                Project Description 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                        </flux:label>
                                        <flux:textarea wire:model="form.description" rows="6" placeholder="Describe your project, what you're looking for, and any specific requirements..." maxlength="5000" />
                                        <flux:error name="form.description" />
                                    </flux:field>
                                        </div>
                                    </div>
                                </div>

                                <flux:separator class="my-8" />

                                <!-- Collaboration Types -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="user-group" class="w-6 h-6 text-green-600" />
                                Collaboration Types
                            </flux:heading>
                            
                            <flux:checkbox.group variant="pills" class="flex flex-wrap gap-3 [&_label]:cursor-pointer [&_*]:select-none">
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeMixing"
                                    value="mixing"
                                    label="Mixing" />
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeMastering"
                                    value="mastering"
                                    label="Mastering" />
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeProduction"
                                    value="production"
                                    label="Production" />
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeSongwriting"
                                    value="songwriting"
                                    label="Songwriting" />
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeVocalTuning"
                                    value="vocal-tuning"
                                    label="Vocal Tuning" />
                                <flux:checkbox 
                                    wire:model="form.collaborationTypeAudioEditing"
                                    value="audio-editing"
                                    label="Audio Editing" />
                            </flux:checkbox.group>
                                </div>

                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                <flux:separator class="my-8" />
                                
                                <!-- Client Management Settings -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="briefcase" class="w-6 h-6 text-purple-600" />
                                Client Management Settings
                                @if($client_email)
                                    <flux:badge variant="success" size="sm">Client Pre-filled</flux:badge>
                                @endif
                            </flux:heading>
                            
                            @if($client_email)
                                <flux:callout variant="info" size="sm">
                                    <flux:icon name="information-circle" class="w-4 h-4" />
                                    Creating project for {{ $client_name ?: $client_email }}
                                </flux:callout>
                            @endif
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label>
                                        <flux:icon name="envelope" class="mr-2" />
                                                Client Email <span class="text-red-500">*</span>
                                    </flux:label>
                                    <flux:input type="email" wire:model.blur="client_email" placeholder="client@example.com" />
                                    <flux:error name="client_email" />
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label>
                                        <flux:icon name="user" class="mr-2" />
                                                Client Name
                                    </flux:label>
                                    <flux:input wire:model.blur="client_name" placeholder="Client's full name" />
                                    <flux:error name="client_name" />
                                </flux:field>
                                        
                                        <div class="lg:col-span-2">
                                    <flux:field>
                                        <flux:label>
                                            <flux:icon name="currency-dollar" class="mr-2" />
                                                Client Payment Amount <span class="text-red-500">*</span>
                                        </flux:label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500">$</span>
                                                </div>
                                            <flux:input type="number" wire:model.blur="payment_amount" placeholder="0.00" min="0" step="0.01" class="pl-10" />
                                            </div>
                                        <flux:description>
                                                This is the amount your client will pay upon project approval. Set to $0 if no payment is required.
                                        </flux:description>
                                        <flux:error name="payment_amount" />
                                    </flux:field>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                <flux:separator class="my-8" />
                                
                                <!-- Client Project Timeline -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="calendar" class="w-6 h-6 text-purple-600" />
                                Project Timeline
                            </flux:heading>
                            
                            <flux:field>
                                <flux:label>
                                    Project Deadline (Optional)
                                    <flux:badge color="zinc" size="xs" class="ml-2">
                                        {{ $this->getTimezoneDisplayName() }}
                                    </flux:badge>
                                </flux:label>
                                <flux:input type="datetime-local" wire:model="form.deadline" />
                                <flux:error name="form.deadline" />
                            </flux:field>
                                </div>
                                @endif

                                @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT && $workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                <flux:separator class="my-8" />
                                
                                <!-- Budget & Timeline -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="currency-dollar" class="w-6 h-6 text-amber-600" />
                                Budget & Timeline
                            </flux:heading>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div>
                                    <div>
                                        <flux:label class="mb-3">Budget Type</flux:label>
                                        <div class="space-y-3">
                                            <label class="flex items-start gap-3 cursor-pointer">
                                                <input type="radio" 
                                                       wire:model.live="form.budgetType" 
                                                       value="free"
                                                       class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:focus:ring-blue-400">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">Free Project</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">No payment required</div>
                                                </div>
                                            </label>
                                            
                                            <label class="flex items-start gap-3 cursor-pointer">
                                                <input type="radio" 
                                                       wire:model.live="form.budgetType" 
                                                       value="paid"
                                                       class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:focus:ring-blue-400">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">Paid Project</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">Set a budget amount</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <flux:error name="form.budgetType" />
                                        </div>

                                        @if($form->budgetType === 'paid')
                                <flux:field>
                                    <flux:label>Budget Amount (USD)</flux:label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500">$</span>
                                                </div>
                                        <flux:input type="number" wire:model.blur="form.budget" placeholder="0.00" min="0" step="0.01" class="pl-10" />
                                            </div>
                                    <flux:error name="form.budget" />
                                </flux:field>
                                        @endif

                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                        <div class="{{ $form->budgetType === 'free' ? 'lg:col-span-2' : '' }}">
                                    <flux:field>
                                        <flux:label>
                                                Project Deadline
                                            <flux:badge color="zinc" size="xs" class="ml-2">
                                                    {{ $this->getTimezoneDisplayName() }}
                                            </flux:badge>
                                        </flux:label>
                                        <flux:input type="datetime-local" wire:model="form.deadline" />
                                        <flux:error name="form.deadline" />
                                    </flux:field>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                <flux:separator class="my-8" />
                                
                        <!-- Contest Settings -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="trophy" class="w-6 h-6 text-purple-600" />
                                Contest Settings
                            </flux:heading>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label>
                                        <flux:icon name="calendar" class="mr-2" />
                                                Submission Deadline
                                        <flux:badge color="zinc" size="xs" class="ml-2">
                                                    {{ $this->getTimezoneDisplayName() }}
                                        </flux:badge>
                                    </flux:label>
                                    <flux:input type="datetime-local" wire:model="submission_deadline" />
                                    <flux:error name="submission_deadline" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>
                                        <flux:icon name="scale" class="mr-2" />
                                                Judging Deadline
                                        <flux:badge color="zinc" size="xs" class="ml-2">
                                                    {{ $this->getTimezoneDisplayName() }}
                                        </flux:badge>
                                    </flux:label>
                                    <flux:input type="datetime-local" wire:model="judging_deadline" />
                                    <flux:error name="judging_deadline" />
                                </flux:field>
                                    </div>
                                    
                                    <!-- Contest Prize Configuration -->
                            <div>
                                <flux:heading size="base" class="flex items-center gap-2 mb-4">
                                    <flux:icon name="gift" class="w-5 h-5 text-purple-600" />
                                                Contest Prizes
                                </flux:heading>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-4">
                                    Configure the prizes and rewards for your contest winners.
                                </flux:text>
                                        
                                        @if($isEdit && $project)
                                            @livewire('contest-prize-configurator', ['project' => $project], key('contest-prizes-edit-'.$project->id))
                                        @else
                                            @livewire('contest-prize-configurator', key('contest-prizes-edit-new'))
                                        @endif
                                    </div>
                                </div>
                                @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE)
                        <!-- Direct Hire Settings -->
                        <div class="space-y-6">
                            <flux:heading size="lg" class="flex items-center gap-3">
                                <flux:icon name="user-check" class="w-6 h-6 text-green-600" />
                                Direct Hire Settings
                            </flux:heading>
                            
                            <flux:field>
                                <flux:label>
                                    <flux:icon name="magnifying-glass" class="mr-2" />
                                            Target Producer <span class="text-red-500">*</span>
                                </flux:label>
                                <flux:input wire:model.live.debounce.300ms="target_producer_query" placeholder="Search for a producer..." />
                                        
                                        @if(count($producers) > 0)
                                    <div class="mt-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                            @foreach($producers as $producer)
                                            <div wire:click="$set('target_producer_id', {{ $producer->id }}); $set('target_producer_query', '{{ $producer->name }}')" 
                                                 class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors">
                                                <div class="flex items-center gap-3">
                                                    <flux:icon name="user" class="w-5 h-5 text-gray-500" />
                                                    <span class="font-medium">{{ $producer->name }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        
                                <flux:error name="target_producer_id" />
                            </flux:field>
                                </div>
                                @endif

                        <flux:separator class="my-8" />
                        
                        <!-- License Configuration -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="document-text" class="w-6 h-6 text-indigo-600" />
                                License Terms
                            </flux:heading>

                                        @livewire('components.license-selector', [
                                            'projectType' => $form->projectType,
                                            'selectedTemplateId' => $selectedLicenseTemplateId,
                                            'requiresAgreement' => $requiresLicenseAgreement,
                                            'licenseNotes' => $licenseNotes
                                        ], key('license-selector-edit-' . ($project->id ?? 'new')))
                                </div>

                        <flux:separator class="my-8" />
                        
                        <!-- Additional Notes -->
                        <div class="space-y-6">
                            <flux:heading class="flex items-center gap-3">
                                <flux:icon name="chat-bubble-left-ellipsis" class="w-6 h-6 text-gray-600" />
                                Additional Notes
                            </flux:heading>

                            <flux:field>
                                <flux:label>Project Notes (Optional)</flux:label>
                                <flux:description>Add any extra information or special requirements for your project.</flux:description>
                                <flux:textarea wire:model.blur="form.notes" rows="4" placeholder="Any additional information or special requirements..." />
                                <flux:error name="form.notes" />
                            </flux:field>
                                </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <flux:button type="submit" variant="primary" icon="check" class="flex-1 justify-center" wire:loading.attr="disabled">
                                <span wire:loading.remove>Update Project</span>
                                <span wire:loading>Updating...</span>
                            </flux:button>
                            <flux:button href="{{ route('projects.manage', $project) }}" variant="outline" icon="x-mark" class="flex-1 justify-center">
                                            Cancel
                            </flux:button>
                                </div>
                            </form>
                </flux:card>
        @endif
        </div>
    </div>
</div>

<script>
function createProjectWizard() {
    return {
        hasUnsavedChanges: false,
        userTimezone: '{{ auth()->user()->getTimezone() }}',
        
        init() {
            this.updateTimezoneIndicators();
            this.setupFormChangeTracking();
            this.setupNavigationWarning();
            
            // Listen for Livewire updates
            this.$nextTick(() => {
                Livewire.hook('morph.updated', () => {
                    this.updateTimezoneIndicators();
                });
            });
        },
        
        updateTimezoneIndicators() {
            try {
        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const timezoneToUse = this.userTimezone || browserTimezone;
                const timezoneDisplayName = this.getTimezoneDisplayName(timezoneToUse);
                
                // Update timezone badges
                const badges = document.querySelectorAll('[data-timezone]');
                badges.forEach(badge => {
                    badge.textContent = timezoneDisplayName;
                });
    } catch (error) {
        console.error('Error updating timezone indicators:', error);
    }
        },

        getTimezoneDisplayName(timezone) {
    try {
        const date = new Date();
        const timeString = date.toLocaleString('en-US', {
            timeZone: timezone,
            timeZoneName: 'short'
        });
        
        const match = timeString.match(/\b([A-Z]{2,5})\s*$/);
        const abbreviation = match ? match[1] : null;
        
                return abbreviation ? `${abbreviation} (${timezone})` : timezone;
    } catch (error) {
        console.error('Error getting timezone display name:', error);
        return timezone;
    }
        },
        
        setupFormChangeTracking() {
    // Listen for form changes from Livewire
            window.addEventListener('formChanged', () => {
                this.hasUnsavedChanges = true;
            });
            
            window.addEventListener('formSaved', () => {
                this.hasUnsavedChanges = false;
            });
        },
        
        setupNavigationWarning() {
    // Prevent navigation when there are unsaved changes
            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges) {
            event.preventDefault();
            event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return event.returnValue;
        }
    });

            // Handle internal navigation
            document.addEventListener('click', (event) => {
        const target = event.target.closest('a[href]');
                if (target && this.hasUnsavedChanges) {
            const href = target.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                if (!confirm('You have unsaved changes. Are you sure you want to leave this page?')) {
                    event.preventDefault();
                    return false;
                }
            }
        }
    });
        }
    }
}
</script>
</div>