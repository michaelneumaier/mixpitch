@props(['workflowColors' => [], 'semanticColors' => []])

@php
    $licenseTemplate = $project->license_template_id ? $project->licenseTemplate : null;
    $requiresAgreement = $project->requires_license_agreement ?? false;
    $hasLicenseNotes = !empty($project->license_notes);
    
    // Get fresh license signatures for this project
    $licenseSignatures = $project->licenseSignatures()->get();
    $pendingSignatures = $licenseSignatures->where('status', 'pending');
    $signedSignatures = $licenseSignatures->where('status', 'active');

    // Create workflow-aware gradient classes matching other components
    $gradientClasses = match($project->workflow_type) {
        'standard' => [
            'outer' => 'bg-gradient-to-br from-blue-50/95 to-indigo-50/90 dark:from-blue-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-blue-200/50 dark:border-blue-700/50',
            'header' => 'bg-gradient-to-r from-blue-100/80 to-indigo-100/80 dark:from-blue-900/80 dark:to-indigo-900/80 border-b border-blue-200/30 dark:border-blue-700/30',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'text-blue-600 dark:text-blue-400'
        ],
        'contest' => [
            'outer' => 'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90 backdrop-blur-sm border border-amber-200/50 dark:border-amber-700/50',
            'header' => 'bg-gradient-to-r from-amber-100/80 to-yellow-100/80 dark:from-amber-900/80 dark:to-yellow-900/80 border-b border-amber-200/30 dark:border-amber-700/30',
            'text_primary' => 'text-amber-900 dark:text-amber-100',
            'text_secondary' => 'text-amber-700 dark:text-amber-300',
            'text_muted' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'text-amber-600 dark:text-amber-400'
        ],
        'direct_hire' => [
            'outer' => 'bg-gradient-to-br from-green-50/95 to-emerald-50/90 dark:from-green-950/95 dark:to-emerald-950/90 backdrop-blur-sm border border-green-200/50 dark:border-green-700/50',
            'header' => 'bg-gradient-to-r from-green-100/80 to-emerald-100/80 dark:from-green-900/80 dark:to-emerald-900/80 border-b border-green-200/30 dark:border-green-700/30',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300',
            'text_muted' => 'text-green-600 dark:text-green-400',
            'icon' => 'text-green-600 dark:text-green-400'
        ],
        'client_management' => [
            'outer' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-purple-200/50 dark:border-purple-700/50',
            'header' => 'bg-gradient-to-r from-purple-100/80 to-indigo-100/80 dark:from-purple-900/80 dark:to-indigo-900/80 border-b border-purple-200/30 dark:border-purple-700/30',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-600 dark:text-purple-400'
        ],
        default => [
            'outer' => 'bg-gradient-to-br from-gray-50/95 to-slate-50/90 dark:from-gray-950/95 dark:to-slate-950/90 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50',
            'header' => 'bg-gradient-to-r from-gray-100/80 to-slate-100/80 dark:from-gray-900/80 dark:to-slate-900/80 border-b border-gray-200/30 dark:border-gray-700/30',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-600 dark:text-gray-400'
        ]
    };

    // Provide fallback colors if not passed from parent
    $workflowColors = $workflowColors ?? [
        'text_primary' => $gradientClasses['text_primary'],
        'text_secondary' => $gradientClasses['text_secondary'],
        'text_muted' => $gradientClasses['text_muted'],
        'icon' => $gradientClasses['icon']
    ];

    $semanticColors = $semanticColors ?? [
        'success' => ['bg' => 'bg-green-50 dark:bg-green-950', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'text-green-600 dark:text-green-400'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950', 'text' => 'text-amber-800 dark:text-amber-200', 'icon' => 'text-amber-600 dark:text-amber-400'],
        'danger' => ['bg' => 'bg-red-50 dark:bg-red-950', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'text-red-600 dark:text-red-400']
    ];
@endphp
<div>
<!-- License Management Section -->
<div class="{{ $gradientClasses['outer'] }} rounded-2xl shadow-lg overflow-hidden">
    <!-- Professional Header matching workflow-status style -->
    <div class="{{ $gradientClasses['header'] }} p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold {{ $gradientClasses['text_primary'] }} flex items-center">
                    <flux:icon.document-text class="w-5 h-5 {{ $gradientClasses['icon'] }} mr-3" />
                    License Management
                </h3>
                <p class="text-sm {{ $gradientClasses['text_secondary'] }} mt-1">
                    @if($requiresAgreement)
                        {{ $signedSignatures->count() }} {{ Str::plural('agreement', $signedSignatures->count()) }} active
                    @else
                        Using platform default terms
                    @endif
                </p>
            </div>
            <div class="text-right">
                @if($requiresAgreement)
                    <div class="text-2xl font-bold {{ $gradientClasses['icon'] }}">{{ $signedSignatures->count() }}</div>
                    <div class="text-xs {{ $gradientClasses['text_muted'] }}">Agreements</div>
                @else
                    <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl px-3 py-2">
                        <div class="text-xs {{ $gradientClasses['text_secondary'] }} font-medium">Default Terms</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6">
        @if($licenseTemplate || $requiresAgreement || $hasLicenseNotes)
            <!-- Compact License Overview -->
            <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl p-4 mb-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        @if($licenseTemplate)
                            <div class="flex items-center gap-2 mb-2">
                                <flux:icon.document class="w-4 h-4 {{ $gradientClasses['icon'] }}" />
                                <span class="text-sm font-medium {{ $gradientClasses['text_primary'] }}">{{ $licenseTemplate->name }}</span>
                                @if($licenseTemplate->category)
                                    <flux:badge color="gray" size="xs">
                                        {{ ucwords(str_replace('_', ' ', $licenseTemplate->category)) }}
                                    </flux:badge>
                                @endif
                            </div>
                        @endif
                        
                        @if($hasLicenseNotes)
                            <div class="text-xs {{ $gradientClasses['text_muted'] }} mb-2">
                                <span class="font-medium">Custom Notes:</span> {{ Str::limit($project->license_notes, 80) }}
                            </div>
                        @endif
                        
                        <div class="flex items-center gap-3 text-xs {{ $gradientClasses['text_muted'] }}">
                            <div class="flex items-center gap-1">
                                @if($requiresAgreement)
                                    <flux:icon.check-circle class="w-3 h-3 text-green-500" />
                                    <span>Agreement required</span>
                                @else
                                    <flux:icon.information-circle class="w-3 h-3" />
                                    <span>Platform defaults</span>
                                @endif
                            </div>
                            @if($requiresAgreement && $signedSignatures->count() > 0)
                                <div class="flex items-center gap-1">
                                    <flux:icon.users class="w-3 h-3" />
                                    <span>{{ $signedSignatures->count() }} signed</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2 ml-4">
                        @if($licenseTemplate)
                            <flux:modal.trigger name="license-preview">
                                <flux:button
                                    variant="ghost"
                                    size="xs">
                                    <flux:icon.eye class="w-3 h-3" />
                                </flux:button>
                            </flux:modal.trigger>
                        @endif
                        <flux:modal.trigger name="edit-license">
                            <flux:button
                                variant="ghost"
                                size="xs">
                                <flux:icon.pencil class="w-3 h-3 text-slate-500" />
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            </div>
            
            @if($requiresAgreement && $signedSignatures->count() > 0)
                <!-- Simple Compliance Status -->
                <div class="bg-green-50/50 dark:bg-green-950/50 border border-green-200/30 dark:border-green-800/30 rounded-lg p-3 text-center">
                    <div class="flex items-center justify-center gap-2 text-sm {{ $semanticColors['success']['text'] }}">
                        <flux:icon.shield-check class="w-4 h-4" />
                        <span>License compliance active - Status shown with each pitch</span>
                    </div>
                </div>
            @endif
        @else
            <!-- Clean Empty State -->
            <div class="bg-white/60 dark:bg-gray-800/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl p-6 text-center">
                <div class="flex items-center justify-center gap-2 {{ $gradientClasses['text_muted'] }} mb-3">
                    <flux:icon.document-text class="w-5 h-5" />
                    <span class="text-sm font-medium">Using platform default terms</span>
                </div>
                <p class="text-xs {{ $gradientClasses['text_muted'] }} mb-4">
                    Add a specific license template to provide clearer terms for collaborators.
                </p>
                <flux:modal.trigger name="edit-license">
                    <flux:button
                        variant="primary"
                        size="sm">
                        <flux:icon.plus class="w-3 h-3 mr-1" />
                        Add License
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>
</div>


<!-- License Preview Modal -->
<flux:modal name="license-preview" class="max-w-2xl">
    <div class="p-6">
        <flux:heading class="mb-4">License Agreement</flux:heading>
        
        @if($licenseTemplate)
            <div class="mb-4 flex gap-2">
                <flux:badge color="gray" size="sm">{{ $licenseTemplate->category_name ?? 'License' }}</flux:badge>
                @if($licenseTemplate->use_case)
                    <flux:badge color="blue" size="sm">{{ $licenseTemplate->use_case_name }}</flux:badge>
                @endif
            </div>
        @endif
        
        <div class="max-h-96 overflow-y-auto mb-6">
            <div id="license-content" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                <!-- License content will be loaded here -->
            </div>
        </div>
        
        <div class="flex justify-end">
            <flux:button variant="ghost" flux:modal.close>Close</flux:button>
        </div>
    </div>
</flux:modal>

<!-- Edit License Modal -->
<flux:modal name="edit-license" variant="flyout" class="space-y-6">
    <div>
        <flux:heading size="lg">Edit License Settings</flux:heading>
        <flux:subheading>Configure license template and agreement requirements</flux:subheading>
    </div>

    <div @license-updated.window="$flux.modal('edit-license').close();">
        <form wire:submit="updateLicense" class="space-y-6">

            <!-- License Agreement Toggle -->
            <div class="space-y-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="requiresAgreement" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Require license agreement</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Contributors must agree to terms before participating</p>
                    </div>
                </label>
            </div>

            @if($requiresAgreement && $this->userTemplates->count() > 0)
                <!-- Your Templates Section -->
                <div>
                    <flux:label class="mb-3">Your License Templates</flux:label>

                    <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto">
                        @foreach($this->userTemplates as $template)
                            <div class="license-template-card">
                                <div wire:click="toggleTemplate({{ $template->id }})" class="cursor-pointer">
                                    <div class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md {{ $selectedTemplateId == $template->id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950 ring-2 ring-indigo-200 dark:ring-indigo-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800' }}">
                                        <!-- Template Header -->
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex-1">
                                                <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                                    {{ $template->name }}
                                                    @if($template->is_default)
                                                        <flux:badge color="blue" size="xs" class="ml-2">Default</flux:badge>
                                                    @endif
                                                </h5>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $template->category_name }}</p>
                                            </div>

                                            @if($selectedTemplateId == $template->id)
                                                <div class="text-indigo-600">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Template Description -->
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-2">{{ $template->description }}</p>

                                        <!-- Key Terms Preview -->
                                        <div class="flex flex-wrap gap-1 mb-3">
                                            @if($template->terms['commercial_use'] ?? false)
                                                <flux:badge color="green" size="xs">Commercial Use</flux:badge>
                                            @endif

                                            @if($template->terms['sync_licensing_allowed'] ?? false)
                                                <flux:badge color="purple" size="xs">Sync Ready</flux:badge>
                                            @endif

                                            @if($template->terms['attribution_required'] ?? false)
                                                <flux:badge color="orange" size="xs">Credit Required</flux:badge>
                                            @endif

                                            @if($template->terms['modification_allowed'] ?? false)
                                                <flux:badge color="blue" size="xs">Edits Allowed</flux:badge>
                                            @endif
                                        </div>

                                        <!-- Template Actions -->
                                        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                            <span>Used {{ $template->getUsageCount() }} times</span>
                                            <button type="button"
                                                    wire:click="previewTemplate({{ $template->id }})"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200">
                                                Preview Full Terms
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Create New Template Button -->
                @if($requiresAgreement)
                    <div class="mb-4">
                        <div class="border-2 border-dashed rounded-lg p-6 text-center transition-colors border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 bg-white dark:bg-gray-900">
                            <div class="text-gray-400 dark:text-gray-500 mb-2">
                                <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-1">Create Custom License</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Build your own terms from scratch</p>

                            @if($this->canUserCreateTemplates)
                                <flux:button
                                    type="button"
                                    wire:click="createTemplate"
                                    variant="primary">
                                    <flux:icon.plus class="w-4 h-4 mr-2" />
                                    Create New Template
                                </flux:button>
                            @else
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 mb-2">Template limit reached</p>
                                    <flux:button
                                        href="{{ route('subscription.index') }}"
                                        variant="primary">
                                        <flux:icon.star class="w-4 h-4 mr-2" />
                                        Upgrade to Pro
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            <!-- License Notes -->
            <div class="space-y-2">
                <flux:label>License Notes (Optional)</flux:label>
                <flux:textarea
                    wire:model="licenseNotes"
                    rows="4"
                    placeholder="Add any custom notes or clarifications about the license terms..."></flux:textarea>
                <flux:description>Internal notes about license usage or special terms</flux:description>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <flux:button type="submit" variant="primary">
                    Save License Settings
                </flux:button>
                <flux:button
                    type="button"
                    variant="ghost"
                    flux:modal.close>
                    Cancel
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>

<!-- Template Preview Modal -->
@if($showPreviewModal && $currentPreviewTemplate)
    <flux:modal name="template-preview" class="max-w-2xl" :open="$showPreviewModal">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <flux:heading size="lg">{{ $currentPreviewTemplate->name }}</flux:heading>
                    <div class="flex gap-2 mt-2">
                        <flux:badge color="gray" size="sm">{{ $currentPreviewTemplate->category_name ?? 'License' }}</flux:badge>
                        @if($currentPreviewTemplate->use_case)
                            <flux:badge color="blue" size="sm">{{ $currentPreviewTemplate->use_case_name }}</flux:badge>
                        @endif
                    </div>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto mb-6">
                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                    @if($currentPreviewTemplate->content)
                        {{ $currentPreviewTemplate->content }}
                    @else
                        <div class="text-gray-500 dark:text-gray-400 italic">No license content available for this template.</div>
                    @endif
                </div>
            </div>

            <div class="flex justify-between items-center gap-3">
                <flux:button
                    type="button"
                    wire:click="selectTemplate({{ $currentPreviewTemplate->id }})"
                    variant="primary">
                    Use This Template
                </flux:button>
                <flux:button
                    type="button"
                    wire:click="closePreview"
                    variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
@endif

<!-- Create Template Modal -->
@if($showCreateModal)
    <flux:modal name="create-template" variant="flyout" :open="$showCreateModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Create License Template</flux:heading>
                <flux:subheading>Build your own custom license terms</flux:subheading>
            </div>

            <form wire:submit="saveTemplate" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column - Basic Info -->
                    <div class="space-y-4">
                        <!-- Template Name -->
                        <div>
                            <flux:label>Template Name <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="name" placeholder="e.g., Standard Collaboration License" maxlength="100" />
                            @error('name')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <flux:label>Description <span class="text-red-500">*</span></flux:label>
                            <flux:textarea wire:model="description" rows="3" placeholder="Describe when and how this template should be used..." maxlength="500" />
                            @error('description')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <flux:label>Category <span class="text-red-500">*</span></flux:label>
                            <flux:select wire:model="category">
                                <option value="">Select a category...</option>
                                @foreach($this->categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            @error('category')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </div>

                        <!-- Use Case -->
                        <div>
                            <flux:label>Primary Use Case <span class="text-red-500">*</span></flux:label>
                            <flux:select wire:model="use_case">
                                <option value="">Select use case...</option>
                                @foreach($this->useCases as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            @error('use_case')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column - License Terms -->
                    <div class="space-y-4">
                        <flux:label>License Terms</flux:label>
                        <div class="space-y-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            @foreach([
                                'commercial_use' => 'Commercial Use Allowed',
                                'attribution_required' => 'Attribution Required',
                                'modification_allowed' => 'Modifications Allowed',
                                'distribution_allowed' => 'Distribution Allowed',
                                'sync_licensing_allowed' => 'Sync Licensing Allowed',
                                'broadcast_allowed' => 'Broadcasting Allowed',
                                'streaming_allowed' => 'Streaming Allowed'
                            ] as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="terms.{{ $key }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <!-- Territory -->
                        <div>
                            <flux:label>Territory</flux:label>
                            <flux:select wire:model="terms.territory">
                                <option value="worldwide">Worldwide</option>
                                <option value="north_america">North America</option>
                                <option value="europe">Europe</option>
                                <option value="asia">Asia</option>
                                <option value="other">Other/Custom</option>
                            </flux:select>
                        </div>

                        <!-- Duration -->
                        <div>
                            <flux:label>Duration</flux:label>
                            <flux:select wire:model="terms.duration">
                                <option value="perpetual">Perpetual</option>
                                <option value="5_years">5 Years</option>
                                <option value="3_years">3 Years</option>
                                <option value="1_year">1 Year</option>
                                <option value="custom">Custom</option>
                            </flux:select>
                        </div>
                    </div>
                </div>

                <!-- License Content -->
                <div>
                    <flux:label>License Agreement Content <span class="text-red-500">*</span></flux:label>
                    <flux:textarea wire:model="content" rows="12" placeholder="Enter the full license agreement text..." class="font-mono text-sm" />
                    @error('content')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <flux:description>Use placeholders like {project_name}, {artist_name}, {date} for dynamic content</flux:description>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <flux:button type="submit" variant="primary">
                        Create Template
                    </flux:button>
                    <flux:button
                        type="button"
                        wire:click="closeTemplateModal"
                        variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
@endif

@php
$licenseData = [
    'name' => $licenseTemplate ? $licenseTemplate->name : 'License Agreement',
    'content' => $licenseTemplate ? nl2br(e($licenseTemplate->generateLicenseContent())) : 'No license content available.'
];
@endphp

<script>
// Pre-render license content server-side for security
const licenseData = @json($licenseData);

// Listen for modal opening to populate content
document.addEventListener('flux:modal.opened', function(event) {
    if (event.detail.name === 'license-preview') {
        const content = document.getElementById('license-content');
        if (content) {
            content.innerHTML = licenseData.content;
        }
    }
});

function sendReminders() {
    // This would trigger a Livewire method to send reminder emails
    if (confirm('Send reminder emails to all collaborators with pending license agreements?')) {
        // Add Livewire event dispatch here
        window.dispatchEvent(new CustomEvent('send-license-reminders'));
    }
}
</script> 
</div>