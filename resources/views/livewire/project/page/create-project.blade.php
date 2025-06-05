@php
    // Define available genres
    $genres = ['Pop', 'Rock', 'Hip Hop', 'Electronic', 'R&B', 'Country', 'Jazz', 'Classical', 'Metal', 'Blues', 'Folk', 'Funk', 'Reggae', 'Soul', 'Punk'];
@endphp

<div class="min-h-screen relative" x-data>
    <!-- Enhanced Background Effects -->
    <div class="fixed inset-0 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50"></div>
    <div class="fixed inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
    
    <!-- Decorative Elements -->
    <div class="fixed top-20 left-10 w-20 h-20 bg-blue-200/30 rounded-full blur-xl"></div>
    <div class="fixed bottom-20 right-10 w-32 h-32 bg-purple-200/30 rounded-full blur-xl"></div>
    <div class="fixed top-1/2 left-1/4 w-16 h-16 bg-pink-200/30 rounded-full blur-xl"></div>
    <div class="fixed top-1/3 right-1/3 w-24 h-24 bg-indigo-200/20 rounded-full blur-2xl"></div>

    <div class="container mx-auto p-3 sm:p-4 md:p-8 relative z-10">
        @if($useWizard && !$isEdit)
            {{-- Wizard Mode for Create --}}
            <div class="max-w-4xl mx-auto">
                <!-- Enhanced Progress Indicator Background -->
                <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl mb-6 p-6">
                    <x-wizard.progress-indicator 
                        :currentStep="$currentStep" 
                        :totalSteps="$totalSteps" 
                        :steps="$wizardSteps" 
                    />
                </div>

                <!-- Enhanced Wizard Content -->
                <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                    <!-- Background Effects for Content -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-purple-50/20 to-pink-50/30 rounded-2xl"></div>
                    
                    <div class="relative p-6">
                        @if($currentStep === 1)
                            {{-- Step 1: Project Type & Workflow Selection --}}
                            <div class="space-y-6">
                                <div class="text-center mb-8">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Choose Your Project Workflow</h2>
                                    <p class="text-gray-600">Select the workflow type that best fits your project needs. This will determine the available features and collaboration options.</p>
                                </div>

                                <x-wizard.workflow-type-selector 
                                    :workflowTypes="$this->workflowTypes"
                                    :selectedType="$workflow_type"
                                    wireModel="workflow_type"
                                />

                                @error('workflow_type')
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-center text-red-800">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span class="text-sm font-medium">{{ $message }}</span>
                                    </div>
                                </div>
                                @enderror
                            </div>

                        @elseif($currentStep === 2)
                            {{-- Step 2: Basic Project Details --}}
                            <div class="space-y-6">
                                <div class="text-center mb-8">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $this->step2Content['title'] }}</h2>
                                    <p class="text-gray-600">{{ $this->step2Content['subtitle'] }}</p>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Left Column -->
                                    <div class="space-y-6">
                                        <!-- Project Name -->
                                        <div>
                                            <label for="project_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                Project Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="project_name" wire:model.blur="form.name" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Enter your project name" maxlength="80">
                                            @error('form.name')
                                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Artist Name -->
                                        <div>
                                            <label for="artist_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                Artist Name
                                                @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @endif
                                            </label>
                                            <input type="text" id="artist_name" wire:model.blur="form.artistName" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Enter artist name" maxlength="30">
                                            @error('form.artistName')
                                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Project Type -->
                                        <div class="lg:col-span-2">
                                            <x-project-types.enhanced-selector 
                                                :projectTypes="$this->projectTypes"
                                                :selected="$form->projectType"
                                                wireModel="form.projectType"
                                                :required="$workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT"
                                            />
                                        </div>

                                        <!-- Genre -->
                                        <div>
                                            <label for="genre" class="block text-sm font-medium text-gray-700 mb-2">
                                                Genre 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            <select id="genre" wire:model.live="form.genre" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">Select a genre</option>
                                                @foreach($genres as $genre)
                                                <option value="{{ $genre }}">{{ $genre }}</option>
                                                @endforeach
                                            </select>
                                            @error('form.genre')
                                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="space-y-6">
                                        <!-- Description -->
                                        <div>
                                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                                Project Description 
                                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                                    <span class="text-gray-500">(Optional)</span>
                                                @else
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            <textarea id="description" wire:model.blur="form.description" rows="6"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                      placeholder="Describe your project, what you're looking for, and any specific requirements..."
                                                      maxlength="5000"></textarea>
                                            @error('form.description')
                                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Collaboration Types -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                                What type of collaboration are you looking for? 
                                                <span class="text-gray-500">(Optional)</span>
                                            </label>
                                            <div class="space-y-3">
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="form.collaborationTypeMixing" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Mixing</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="form.collaborationTypeMastering" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Mastering</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="form.collaborationTypeProduction" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Production</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="form.collaborationTypeSongwriting" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Songwriting</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" wire:model="form.collaborationTypeVocalTuning" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Vocal Tuning</span>
                                                </label>
                                            </div>
                                            @error('collaboration_type')
                                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @elseif($currentStep === 3)
                            {{-- Step 3: Workflow-Specific Configuration --}}
                            <div class="space-y-6">
                                <div class="text-center mb-8">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Configure Your {{ $this->currentWorkflowConfig['name'] ?? 'Project' }}</h2>
                                    <p class="text-gray-600">Set up the specific details for your {{ strtolower($this->currentWorkflowConfig['name'] ?? 'project') }}.</p>
                                </div>

                                <!-- Single Column Layout -->
                                <div class="space-y-6 max-w-2xl mx-auto">
                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT && 
                                            $workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                            <!-- Enhanced Budget Selector -->
                                            <x-wizard.budget-selector 
                                                :budgetType="$form->budgetType"
                                                :budget="$form->budget"
                                                :workflowType="$workflow_type"
                                            />
                                        @endif

                                        <!-- Enhanced Deadline Selector (Hidden for Contests - they have their own deadlines) -->
                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                        <x-wizard.deadline-selector 
                                            :deadline="$form->deadline"
                                            :workflowType="$workflow_type"
                                        />
                                        @endif

                                        @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                            {{-- Enhanced Contest-specific fields --}}
                                            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
                                                <!-- Background Effects -->
                                                <div class="absolute inset-0 bg-gradient-to-br from-amber-50/50 to-orange-50/50"></div>
                                                <div class="absolute top-4 right-4 w-16 h-16 bg-amber-400/10 rounded-full blur-lg"></div>
                                                
                                                <div class="relative">
                                                    <h4 class="text-xl font-bold bg-gradient-to-r from-amber-700 to-orange-700 bg-clip-text text-transparent mb-6 flex items-center">
                                                        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                                            <i class="fas fa-trophy text-white"></i>
                                                        </div>
                                                        Contest Settings
                                                    </h4>
                                                    
                                                    <div class="space-y-6">
                                                        <div>
                                                            <label for="submission_deadline" class="block text-sm font-bold text-amber-700 mb-3">
                                                                <i class="fas fa-clock mr-2"></i>
                                                                Submission Deadline <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="datetime-local" id="submission_deadline" wire:model.blur="submission_deadline" 
                                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition-all duration-200">
                                                            @error('submission_deadline')
                                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $message }}
                                                            </p>
                                                            @enderror
                                                        </div>

                                                        <div>
                                                            <label for="judging_deadline" class="block text-sm font-bold text-amber-700 mb-3">
                                                                <i class="fas fa-gavel mr-2"></i>
                                                                Judging Deadline
                                                            </label>
                                                            <input type="datetime-local" id="judging_deadline" wire:model.blur="judging_deadline" 
                                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition-all duration-200">
                                                            @error('judging_deadline')
                                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $message }}
                                                            </p>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Enhanced Contest Prize Configuration --}}
                                            <div class="mt-6">
                                                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
                                                    <!-- Background Effects -->
                                                    <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 to-indigo-50/50"></div>
                                                    <div class="absolute top-4 right-4 w-12 h-12 bg-purple-400/10 rounded-full blur-lg"></div>
                                                    
                                                    <div class="relative">
                                                        <h4 class="text-xl font-bold bg-gradient-to-r from-purple-700 to-indigo-700 bg-clip-text text-transparent mb-3 flex items-center">
                                                            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                                                <i class="fas fa-trophy text-white"></i>
                                                            </div>
                                                            Contest Prizes
                                                        </h4>
                                                        <p class="text-gray-700 font-medium mb-6">Configure the prizes and rewards for your contest winners.</p>
                                                        
                                                        @if($isEdit && $project)
                                                            @livewire('contest-prize-configurator', ['project' => $project], key('contest-prizes-edit-'.$project->id))
                                                        @else
                                                            @livewire('contest-prize-configurator', key('contest-prizes-edit-new'))
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE)
                                            {{-- Enhanced Direct Hire-specific fields --}}
                                            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
                                                <!-- Background Effects -->
                                                <div class="absolute inset-0 bg-gradient-to-br from-green-50/50 to-emerald-50/50"></div>
                                                <div class="absolute top-4 right-4 w-16 h-16 bg-green-400/10 rounded-full blur-lg"></div>
                                                
                                                <div class="relative">
                                                    <h4 class="text-xl font-bold bg-gradient-to-r from-green-700 to-emerald-700 bg-clip-text text-transparent mb-6 flex items-center">
                                                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                                            <i class="fas fa-user-check text-white"></i>
                                                        </div>
                                                        Direct Hire Settings
                                                    </h4>
                                                    
                                                    <div>
                                                        <label for="target_producer" class="block text-sm font-bold text-green-700 mb-3">
                                                            <i class="fas fa-search mr-2"></i>
                                                            Target Producer <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text" id="target_producer" wire:model.live.debounce.300ms="target_producer_query" 
                                                               class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition-all duration-200"
                                                               placeholder="Search for a producer...">
                                                        
                                                        @if(count($producers) > 0)
                                                        <div class="mt-3 bg-white/90 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                                            @foreach($producers as $producer)
                                                            <div wire:click="$set('target_producer_id', {{ $producer->id }}); $set('target_producer_query', '{{ $producer->name }}')" 
                                                                 class="px-4 py-3 hover:bg-green-50/50 cursor-pointer border-b border-green-100/50 last:border-b-0 transition-all duration-200">
                                                                <div class="flex items-center">
                                                                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mr-3 shadow-md">
                                                                        <i class="fas fa-user text-white"></i>
                                                                    </div>
                                                                    <span class="font-medium text-green-800">{{ $producer->name }}</span>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                        @endif
                                                        
                                                        @error('target_producer_id')
                                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                                            {{ $message }}
                                                        </p>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                        @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                            {{-- Enhanced Client Management-specific fields --}}
                                            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
                                                <!-- Background Effects -->
                                                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 to-indigo-50/50"></div>
                                                <div class="absolute top-4 right-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
                                                
                                                <div class="relative">
                                                    <h4 class="text-xl font-bold bg-gradient-to-r from-purple-700 to-indigo-700 bg-clip-text text-transparent mb-6 flex items-center">
                                                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                                            <i class="fas fa-briefcase text-white"></i>
                                                        </div>
                                                        Client Management Settings
                                                    </h4>
                                                    
                                                    <div class="space-y-6">
                                                        <div>
                                                            <label for="client_email" class="block text-sm font-bold text-purple-700 mb-3">
                                                                <i class="fas fa-envelope mr-2"></i>
                                                                Client Email <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="email" id="client_email" wire:model.blur="client_email" 
                                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-200"
                                                                   placeholder="client@example.com">
                                                            @error('client_email')
                                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $message }}
                                                            </p>
                                                            @enderror
                                                        </div>

                                                        <div>
                                                            <label for="client_name" class="block text-sm font-bold text-purple-700 mb-3">
                                                                <i class="fas fa-user mr-2"></i>
                                                                Client Name
                                                            </label>
                                                            <input type="text" id="client_name" wire:model.blur="client_name" 
                                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-200"
                                                                   placeholder="Client's full name">
                                                            @error('client_name')
                                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $message }}
                                                            </p>
                                                            @enderror
                                                        </div>

                                                        <div>
                                                            <label for="payment_amount" class="block text-sm font-bold text-purple-700 mb-3">
                                                                <i class="fas fa-dollar-sign mr-2"></i>
                                                                Client Payment Amount <span class="text-red-500">*</span>
                                                            </label>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                                    <span class="text-purple-600 font-bold">$</span>
                                                                </div>
                                                                <input type="number" id="payment_amount" wire:model.blur="payment_amount" 
                                                                       class="w-full pl-10 pr-4 py-3 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-200"
                                                                       placeholder="0.00" min="0" step="0.01">
                                                            </div>
                                                            <p class="mt-2 text-sm text-purple-600 flex items-center">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                This is the amount your client will pay upon project approval. Set to $0 if no payment is required.
                                                            </p>
                                                            @error('payment_amount')
                                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                                {{ $message }}
                                                            </p>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                </div>
                            </div>

                        @elseif($currentStep === 4)
                            {{-- Step 4: Review & Finalization --}}
                            <div class="space-y-6">
                                <div class="text-center mb-8">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Review Your Project</h2>
                                    <p class="text-gray-600">Please review all the details before creating your project. You can go back to make changes if needed.</p>
                                </div>

                                <!-- License Configuration Section -->
                                <div class="bg-gradient-to-br from-white/90 to-indigo-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg mb-6">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                                            <i class="fas fa-file-contract text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">License Terms</h3>
                                    </div>

                                    <!-- License Selector Component -->
                                    @livewire('components.license-selector', [
                                        'projectType' => $form->projectType,
                                        'selectedTemplateId' => $selectedLicenseTemplateId,
                                        'requiresAgreement' => $requiresLicenseAgreement,
                                        'licenseNotes' => $licenseNotes
                                    ], key('license-selector-' . ($project->id ?? 'new')))
                                </div>

                                <x-wizard.project-summary 
                                    :project="$this->projectSummary"
                                    :workflowConfig="$this->currentWorkflowConfig"
                                />

                                <!-- Enhanced Additional Notes -->
                                <div class="bg-gradient-to-br from-white/90 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl mr-4">
                                            <i class="fas fa-sticky-note text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-600 to-gray-700 bg-clip-text text-transparent">Additional Notes</h3>
                                    </div>

                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-3">
                                            <i class="fas fa-comment mr-2"></i>
                                            Project Notes (Optional)
                                        </label>
                                        <p class="text-gray-600 font-medium mb-4">Add any extra information or special requirements for your project.</p>
                                        <textarea id="notes" wire:model.blur="form.notes" rows="4"
                                                  class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-gray-500/20 focus:border-gray-400 transition-all duration-200"
                                                  placeholder="Any additional information or special requirements..."></textarea>
                                        @error('form.notes')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Enhanced Navigation Buttons -->
                    <div class="relative bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm px-6 py-4 border-t border-white/30">
                        <!-- Background Effects -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/30 to-purple-50/30"></div>
                        
                        <div class="relative flex justify-between items-center">
                            <div>
                                @if($currentStep > 1)
                                <button type="button" wire:click="previousStep" 
                                        x-on:click="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
                                        class="group inline-flex items-center px-6 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-lg hover:shadow-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                    <i class="fas fa-arrow-left mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                    Previous
                                </button>
                                @endif
                            </div>

                            <div class="flex items-center space-x-3">
                                @if($currentStep < $totalSteps)
                                <button type="button" wire:click="nextStep" 
                                        x-on:click="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
                                        class="group relative inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                    <!-- Button Background Effect -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <!-- Button Content -->
                                    <div class="relative flex items-center">
                                        Next
                                        <i class="fas fa-arrow-right ml-2 group-hover:scale-110 transition-transform duration-200"></i>
                                    </div>
                                </button>
                                @else
                                <button type="button" wire:click="save" 
                                        class="group relative inline-flex items-center px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-500/25">
                                    <!-- Button Background Effect -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-green-400/20 to-emerald-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <!-- Button Content -->
                                    <div class="relative flex items-center">
                                        <i class="fas fa-check mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                        Create Project
                                    </div>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- Edit Mode - Comprehensive Form --}}
            <!-- Background Effects -->
            <div class="fixed inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-purple-400/20 to-blue-600/20 rounded-full blur-3xl"></div>
                <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-300/10 to-purple-300/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-1/3 right-1/4 w-48 h-48 bg-gradient-to-l from-purple-300/15 to-blue-300/15 rounded-full blur-xl"></div>
            </div>

            <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30">
                <div class="py-12">
                    <!-- Enhanced Header Section -->
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
                        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden p-8">
                            <!-- Background Effects -->
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-purple-50/20 to-pink-50/30"></div>
                            <div class="absolute top-4 left-4 w-24 h-24 bg-blue-400/10 rounded-full blur-xl"></div>
                            <div class="absolute top-4 right-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
                            
                            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-6 lg:mb-0">
                                    <h1 class="text-4xl lg:text-5xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-blue-600 bg-clip-text text-transparent mb-2 flex items-center">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mr-4 shadow-lg">
                                            <i class="fas fa-edit text-white text-xl"></i>
                                        </div>
                                        Edit Project
                                    </h1>
                                    <p class="text-gray-600 text-lg font-medium">Update your project details and settings</p>
                                </div>
                                
                                <!-- Enhanced Project Status -->
                                <div class="bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl p-6 text-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mb-3 mx-auto shadow-lg">
                                        <i class="fas fa-project-diagram text-white"></i>
                                    </div>
                                    <div class="text-sm font-medium bg-gradient-to-r from-blue-700 to-purple-700 bg-clip-text text-transparent">Workflow Type</div>
                                    <div class="text-lg font-bold bg-gradient-to-r from-blue-800 to-purple-800 bg-clip-text text-transparent">{{ ucwords(str_replace('_', ' ', $workflow_type)) }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center justify-center">
                                        <i class="fas fa-lock mr-1"></i>
                                        Cannot be changed
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-xl overflow-hidden">
                            <form wire:submit="save" class="p-8 space-y-8">
                                
                                <!-- Basic Project Information -->
                                <div class="bg-gradient-to-br from-white/90 to-blue-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4">
                                            <i class="fas fa-info-circle text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Basic Information</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Project Name -->
                                        <div class="lg:col-span-2">
                                            <label for="project_name" class="block text-sm font-medium text-blue-700 mb-3">
                                                <i class="fas fa-tag mr-2"></i>
                                                Project Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="project_name" wire:model.blur="form.name" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all duration-200"
                                                   placeholder="Enter your project name" maxlength="80">
                                            @error('form.name')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Artist Name -->
                                        <div>
                                            <label for="artist_name" class="block text-sm font-medium text-blue-700 mb-3">
                                                <i class="fas fa-user mr-2"></i>
                                                Artist Name
                                            </label>
                                            <input type="text" id="artist_name" wire:model.blur="form.artistName" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all duration-200"
                                                   placeholder="Enter artist name" maxlength="30">
                                            @error('form.artistName')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Project Type -->
                                        <div class="lg:col-span-2">
                                            <x-project-types.enhanced-selector 
                                                :projectTypes="$this->projectTypes"
                                                :selected="$form->projectType"
                                                wireModel="form.projectType"
                                                :required="$workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT"
                                            />
                                        </div>

                                        <!-- Genre -->
                                        <div>
                                            <label for="genre" class="block text-sm font-medium text-blue-700 mb-3">
                                                <i class="fas fa-music mr-2"></i>
                                                Genre
                                            </label>
                                            <select id="genre" wire:model.live="form.genre" 
                                                    class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all duration-200">
                                                <option value="">Select a genre</option>
                                                @foreach($genres as $genre)
                                                <option value="{{ $genre }}">{{ $genre }}</option>
                                                @endforeach
                                            </select>
                                            @error('form.genre')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Description -->
                                        <div class="lg:col-span-2">
                                            <label for="description" class="block text-sm font-medium text-blue-700 mb-3">
                                                <i class="fas fa-align-left mr-2"></i>
                                                Project Description
                                            </label>
                                            <textarea id="description" wire:model.blur="form.description" rows="6"
                                                      class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all duration-200"
                                                      placeholder="Describe your project, what you're looking for, and any specific requirements..."
                                                      maxlength="5000"></textarea>
                                            @error('form.description')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Collaboration Types -->
                                <div class="bg-gradient-to-br from-white/90 to-green-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-4">
                                            <i class="fas fa-handshake text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Collaboration Types</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-50/50 transition-all duration-200 cursor-pointer">
                                            <input type="checkbox" wire:model="form.collaborationTypeMixing" class="rounded border-green-300 text-green-600 focus:ring-green-500 mr-3">
                                            <div>
                                                <div class="font-medium text-green-800">Mixing</div>
                                                <div class="text-xs text-green-600">Audio mixing services</div>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-50/50 transition-all duration-200 cursor-pointer">
                                            <input type="checkbox" wire:model="form.collaborationTypeMastering" class="rounded border-green-300 text-green-600 focus:ring-green-500 mr-3">
                                            <div>
                                                <div class="font-medium text-green-800">Mastering</div>
                                                <div class="text-xs text-green-600">Audio mastering services</div>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-50/50 transition-all duration-200 cursor-pointer">
                                            <input type="checkbox" wire:model="form.collaborationTypeProduction" class="rounded border-green-300 text-green-600 focus:ring-green-500 mr-3">
                                            <div>
                                                <div class="font-medium text-green-800">Production</div>
                                                <div class="text-xs text-green-600">Music production</div>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-50/50 transition-all duration-200 cursor-pointer">
                                            <input type="checkbox" wire:model="form.collaborationTypeSongwriting" class="rounded border-green-300 text-green-600 focus:ring-green-500 mr-3">
                                            <div>
                                                <div class="font-medium text-green-800">Songwriting</div>
                                                <div class="text-xs text-green-600">Songwriting collaboration</div>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-green-200/50 rounded-xl hover:bg-green-50/50 transition-all duration-200 cursor-pointer md:col-span-2 lg:col-span-1">
                                            <input type="checkbox" wire:model="form.collaborationTypeVocalTuning" class="rounded border-green-300 text-green-600 focus:ring-green-500 mr-3">
                                            <div>
                                                <div class="font-medium text-green-800">Vocal Tuning</div>
                                                <div class="text-xs text-green-600">Vocal tuning services</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT && $workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                <!-- Budget & Timeline -->
                                <div class="bg-gradient-to-br from-white/90 to-amber-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl mr-4">
                                            <i class="fas fa-dollar-sign text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">Budget & Timeline</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Budget Type -->
                                        <div>
                                            <label class="block text-sm font-medium text-amber-700 mb-3">
                                                <i class="fas fa-money-bill-wave mr-2"></i>
                                                Budget Type
                                            </label>
                                            <div class="space-y-3">
                                                <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-amber-200/50 rounded-xl hover:bg-amber-50/50 transition-all duration-200 cursor-pointer">
                                                    <input type="radio" wire:model="form.budgetType" value="free" class="text-amber-600 focus:ring-amber-500 mr-3">
                                                    <div>
                                                        <div class="font-medium text-amber-800">Free Project</div>
                                                        <div class="text-xs text-amber-600">No payment required</div>
                                                    </div>
                                                </label>
                                                <label class="flex items-center p-4 bg-white/60 backdrop-blur-sm border border-amber-200/50 rounded-xl hover:bg-amber-50/50 transition-all duration-200 cursor-pointer">
                                                    <input type="radio" wire:model="form.budgetType" value="paid" class="text-amber-600 focus:ring-amber-500 mr-3">
                                                    <div>
                                                        <div class="font-medium text-amber-800">Paid Project</div>
                                                        <div class="text-xs text-amber-600">Set a budget amount</div>
                                                    </div>
                                                </label>
                                            </div>
                                            @error('form.budgetType')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Budget Amount -->
                                        @if($form->budgetType === 'paid')
                                        <div>
                                            <label for="budget" class="block text-sm font-medium text-amber-700 mb-3">
                                                <i class="fas fa-calculator mr-2"></i>
                                                Budget Amount (USD)
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-amber-600 font-bold">$</span>
                                                </div>
                                                <input type="number" id="budget" wire:model.blur="form.budget" 
                                                       class="w-full pl-8 pr-4 py-3 bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition-all duration-200"
                                                       placeholder="0.00" min="0" step="0.01">
                                            </div>
                                            @error('form.budget')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                        @endif

                                        <!-- Deadline (Hidden for Contests - they have their own deadlines) -->
                                        @if($workflow_type !== \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                        <div class="{{ $form->budgetType === 'free' ? 'lg:col-span-2' : '' }}">
                                            <label for="deadline" class="block text-sm font-medium text-amber-700 mb-3">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                Project Deadline
                                            </label>
                                            <input type="date" id="deadline" wire:model.blur="form.deadline" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition-all duration-200">
                                            @error('form.deadline')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                        @endif
                                    </div>
                                </div>
                    @endif

                                <!-- Workflow-Specific Settings -->
                                @if($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CONTEST)
                                <div class="bg-gradient-to-br from-white/90 to-purple-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                                            <i class="fas fa-trophy text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Contest Settings</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div>
                                            <label for="submission_deadline" class="block text-sm font-medium text-purple-700 mb-3">
                                                <i class="fas fa-clock mr-2"></i>
                                                Submission Deadline <span class="text-red-500">*</span>
                                            </label>
                                            <input type="datetime-local" id="submission_deadline" wire:model.blur="submission_deadline" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-200">
                                            @error('submission_deadline')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="judging_deadline" class="block text-sm font-medium text-purple-700 mb-3">
                                                <i class="fas fa-gavel mr-2"></i>
                                                Judging Deadline
                                            </label>
                                            <input type="datetime-local" id="judging_deadline" wire:model.blur="judging_deadline" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-200">
                                            @error('judging_deadline')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <!-- Contest Prize Configuration -->
                                    <div class="mt-6">
                                        <div class="bg-white/60 backdrop-blur-sm border border-purple-200/30 rounded-xl p-4 mb-4">
                                            <h4 class="text-lg font-semibold text-purple-800 mb-2 flex items-center">
                                                <i class="fas fa-trophy text-purple-600 mr-2"></i>
                                                Contest Prizes
                                            </h4>
                                            <p class="text-sm text-purple-700">Configure the prizes and rewards for your contest winners.</p>
                                        </div>
                                        
                                        @if($isEdit && $project)
                                            @livewire('contest-prize-configurator', ['project' => $project], key('contest-prizes-edit-'.$project->id))
                                        @else
                                            @livewire('contest-prize-configurator', key('contest-prizes-edit-new'))
                                        @endif
                                    </div>
                                </div>
                                @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_DIRECT_HIRE)
                                <div class="bg-gradient-to-br from-white/90 to-green-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-4">
                                            <i class="fas fa-user-check text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Direct Hire Settings</h3>
                                    </div>
                                    
                                    <div>
                                        <label for="target_producer" class="block text-sm font-bold text-green-700 mb-3">
                                            <i class="fas fa-search mr-2"></i>
                                            Target Producer <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="target_producer" wire:model.live.debounce.300ms="target_producer_query" 
                                               class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition-all duration-200"
                                               placeholder="Search for a producer...">
                                        
                                        @if(count($producers) > 0)
                                        <div class="mt-2 bg-white/90 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                            @foreach($producers as $producer)
                                            <div wire:click="$set('target_producer_id', {{ $producer->id }}); $set('target_producer_query', '{{ $producer->name }}')" 
                                                 class="px-4 py-3 hover:bg-green-50/50 cursor-pointer border-b border-green-100/50 last:border-b-0 transition-all duration-200">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mr-3">
                                                        <i class="fas fa-user text-white text-xs"></i>
                                                    </div>
                                                    <span class="font-medium text-green-800">{{ $producer->name }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        
                                        @error('target_producer_id')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>
                                </div>
                                @elseif($workflow_type === \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                                <div class="bg-gradient-to-br from-white/90 to-indigo-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                                            <i class="fas fa-briefcase text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Client Management Settings</h3>
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div>
                                            <label for="client_email" class="block text-sm font-bold text-indigo-700 mb-3">
                                                <i class="fas fa-envelope mr-2"></i>
                                                Client Email <span class="text-red-500">*</span>
                                            </label>
                                            <input type="email" id="client_email" wire:model.blur="client_email" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-indigo-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-all duration-200"
                                                   placeholder="client@example.com">
                                            @error('client_email')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="client_name" class="block text-sm font-bold text-indigo-700 mb-3">
                                                <i class="fas fa-user mr-2"></i>
                                                Client Name
                                            </label>
                                            <input type="text" id="client_name" wire:model.blur="client_name" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-indigo-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-all duration-200"
                                                   placeholder="Client's full name">
                                            @error('client_name')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <div class="lg:col-span-2">
                                            <label for="payment_amount" class="block text-sm font-bold text-indigo-700 mb-3">
                                                <i class="fas fa-dollar-sign mr-2"></i>
                                                Client Payment Amount <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-indigo-600 font-bold">$</span>
                                                </div>
                                                <input type="number" id="payment_amount" wire:model.blur="payment_amount" 
                                                       class="w-full pl-10 pr-4 py-3 bg-white/80 backdrop-blur-sm border border-indigo-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-all duration-200"
                                                       placeholder="0.00" min="0" step="0.01">
                                            </div>
                                            <p class="mt-2 text-xs text-indigo-600 flex items-center">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                This is the amount your client will pay upon project approval. Set to $0 if no payment is required.
                                            </p>
                                            @error('payment_amount')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Optional Deadline for Client Management -->
                                        <div class="lg:col-span-2">
                                            <label for="deadline_client" class="block text-sm font-bold text-indigo-700 mb-3">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                Project Deadline (Optional)
                                            </label>
                                            <input type="date" id="deadline_client" wire:model.blur="form.deadline" 
                                                   class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-indigo-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-all duration-200">
                                            @error('form.deadline')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Enhanced Additional Notes -->
                                <div class="bg-gradient-to-br from-white/90 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl mr-4">
                                            <i class="fas fa-sticky-note text-white"></i>
                                        </div>
                                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-600 to-gray-700 bg-clip-text text-transparent">Additional Notes</h3>
                                    </div>

                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-3">
                                            <i class="fas fa-comment mr-2"></i>
                                            Project Notes (Optional)
                                        </label>
                                        <p class="text-gray-600 font-medium mb-4">Add any extra information or special requirements for your project.</p>
                                        <textarea id="notes" wire:model.blur="form.notes" rows="4"
                                                  class="w-full px-4 py-3 bg-white/80 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:ring-2 focus:ring-gray-500/20 focus:border-gray-400 transition-all duration-200"
                                                  placeholder="Any additional information or special requirements..."></textarea>
                                        @error('form.notes')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Enhanced Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                                    <button type="submit" 
                                            class="group relative flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-4 px-8 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                        <!-- Button Background Effect -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Button Content -->
                                        <div class="relative flex items-center justify-center">
                                            <i class="fas fa-save mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                                            Update Project
                                        </div>
                                    </button>
                                    <a href="{{ route('projects.manage', $project) }}" 
                                       class="group relative flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white py-4 px-8 rounded-xl font-bold text-lg text-center shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-gray-500/25 flex items-center justify-center">
                                        <!-- Button Background Effect -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-gray-400/20 to-gray-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Button Content -->
                                        <div class="relative flex items-center">
                                            <i class="fas fa-times mr-3 group-hover:scale-110 transition-transform duration-200"></i>
                                            Cancel
                                        </div>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>