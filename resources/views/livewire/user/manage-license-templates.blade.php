<div class="space-y-6">
    @if(!$embeddedMode)
        <!-- Section Header -->
        <div class="text-center">
            <h4 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent">
                License Templates
            </h4>
            <p class="text-sm text-gray-600 mt-2">
                Create and manage your custom license agreement templates
            </p>
        </div>
    @endif

    <!-- Template Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
            <div class="flex items-center">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                    <i class="fas fa-file-contract text-white text-xs"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-600">Your Templates</p>
                    <p class="text-lg font-bold text-gray-900">{{ $userTemplates->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
            <div class="flex items-center">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                    <i class="fas fa-star text-white text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-600">Default Template</p>
                    @php $defaultTemplate = $userTemplates->where('is_default', true)->first(); @endphp
                    @if($defaultTemplate)
                        <p class="text-sm font-bold text-gray-900 truncate">{{ $defaultTemplate->name }}</p>
                    @else
                        <p class="text-sm font-medium text-gray-500">None set</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm">
            <div class="flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                    <i class="fas fa-chart-line text-white text-xs"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-600">Active</p>
                    <p class="text-lg font-bold text-gray-900">{{ $userTemplates->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Limit Notice -->
    @if($remainingTemplates !== null)
        <div class="bg-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <span class="text-sm text-blue-800">
                    @if($remainingTemplates > 0)
                        You can create <strong>{{ $remainingTemplates }}</strong> more template{{ $remainingTemplates > 1 ? 's' : '' }}.
                    @else
                        You've reached your template limit.
                    @endif
                    <a href="{{ route('subscription.index') }}" class="font-medium underline">Upgrade to Pro</a> for unlimited templates.
                </span>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3">
        <button type="button" wire:click="openMarketplace()" 
                class="inline-flex items-center justify-center px-4 py-2 bg-white/80 backdrop-blur-sm border border-white/30 text-gray-700 font-medium rounded-lg shadow-sm hover:shadow-md hover:bg-white/90 transition-all duration-200">
            <i class="fas fa-store mr-2"></i>
            Browse Marketplace
        </button>
        @if($canCreateMore)
            <button type="button" wire:click="createTemplate()" 
                    class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                <i class="fas fa-plus mr-2"></i>
                Create Template
            </button>
        @endif
    </div>

    @if($userTemplates->count() > 0)
        <!-- Templates Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($userTemplates as $template)
                <div wire:key="template-{{ $template->id }}" class="bg-white/80 backdrop-blur-sm border border-white/40 rounded-xl p-4 hover:bg-white/90 hover:shadow-md transition-all duration-200">
                    <!-- Template Header -->
                    <div class="mb-3">
                        <h5 class="font-bold text-gray-900 text-base mb-1 truncate">
                            {{ $template->name }}
                        </h5>
                        @if($template->description)
                            <p class="text-gray-600 text-xs line-clamp-2">{{ $template->description }}</p>
                        @endif
                    </div>
                    
                    <!-- Template Badges -->
                    <div class="flex items-center gap-2 mb-3 flex-wrap">
                        @if($template->is_default)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-star mr-1"></i> Default
                            </span>
                        @endif
                        
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                            @if($template->is_active) bg-green-100 text-green-800 
                            @else bg-gray-100 text-gray-600 @endif">
                            @if($template->is_active)
                                <i class="fas fa-check-circle mr-1"></i> Active
                            @else
                                <i class="fas fa-pause-circle mr-1"></i> Inactive
                            @endif
                        </span>
                        
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($template->category) }}
                        </span>
                    </div>

                    <!-- Template Status Indicators -->
                    @if($template->is_public || $template->isPendingApproval() || $template->isRejected())
                        <div class="mb-3">
                            @if($template->is_public)
                                <div class="flex items-center text-xs text-green-700 bg-green-50 px-2 py-1 rounded-lg">
                                    <i class="fas fa-store mr-1"></i> Published to Marketplace
                                    @if($template->fork_count > 0)
                                        <span class="ml-2 text-green-600">• {{ $template->fork_count }} fork{{ $template->fork_count !== 1 ? 's' : '' }}</span>
                                    @endif
                                </div>
                            @elseif($template->isPendingApproval())
                                <div class="flex items-center text-xs text-yellow-700 bg-yellow-50 px-2 py-1 rounded-lg">
                                    <i class="fas fa-clock mr-1"></i> Pending Marketplace Approval
                                </div>
                            @elseif($template->isRejected())
                                <div class="flex items-center text-xs text-red-700 bg-red-50 px-2 py-1 rounded-lg">
                                    <i class="fas fa-times-circle mr-1"></i> Marketplace Submission Rejected
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Template Actions -->
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <button type="button" wire:click="previewTemplate({{ $template->id }})" 
                                    class="flex-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-eye mr-1"></i> Preview
                            </button>
                            
                            <button type="button" wire:click="editTemplate({{ $template->id }})" 
                                    class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>

                            <button type="button" wire:click="confirmDelete({{ $template->id }})" 
                                    class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        
                        <div class="flex gap-2">
                            @if(!$template->is_default)
                                <button type="button" wire:click="setAsDefault({{ $template->id }})" 
                                        class="flex-1 bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                    <i class="fas fa-star mr-1"></i> Set Default
                                </button>
                            @endif
                            
                            <button type="button" wire:click="toggleActive({{ $template->id }})" 
                                    class="flex-1 {{ $template->is_active ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-700' : 'bg-green-100 hover:bg-green-200 text-green-700' }} px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                @if($template->is_active)
                                    <i class="fas fa-pause mr-1"></i> Deactivate
                                @else
                                    <i class="fas fa-play mr-1"></i> Activate
                                @endif
                            </button>
                        </div>
                        
                        <!-- Marketplace Publishing Action -->
                        @if($template->canBePublishedToMarketplace())
                            <button type="button" wire:click="openPublishModal({{ $template->id }})" 
                                    class="w-full bg-gradient-to-r from-purple-100 to-indigo-100 hover:from-purple-200 hover:to-indigo-200 text-purple-700 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-store mr-1"></i> Publish to Marketplace
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-8">
            <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-6">
                <i class="fas fa-file-contract text-gray-400 text-2xl mb-3"></i>
                <h5 class="text-base font-bold text-gray-900 mb-2">No License Templates Yet</h5>
                <p class="text-sm text-gray-600 mb-4">
                    Create your first license template to streamline your project licensing process.
                </p>
                @if($canCreateMore)
                    <button type="button" wire:click="createTemplate()" 
                            class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Create Your First Template
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Create/Edit Template Modal -->
    @if($showCreateModal)
        @teleport('body')
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Background Effects -->
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-indigo-50/30 via-purple-50/20 to-blue-50/30 rounded-t-2xl"></div>
                <div class="absolute top-4 left-4 w-16 h-16 bg-indigo-400/10 rounded-full blur-lg"></div>
                <div class="absolute top-4 right-4 w-12 h-12 bg-purple-400/10 rounded-full blur-md"></div>
                
                <div class="relative p-6 sm:p-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-{{ $editingTemplate ? 'edit' : 'plus' }} text-white text-sm"></i>
                            </div>
                            {{ $editingTemplate ? 'Edit' : 'Create' }} License Template
                        </h3>
                        <button wire:click="closeModal()" 
                                class="group p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100/50 rounded-lg transition-all duration-200 hover:scale-110">
                            <i class="fas fa-times text-lg group-hover:scale-110 transition-transform"></i>
                        </button>
                    </div>

                    <!-- Form Content -->
                    <form wire:submit.prevent="saveTemplate">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column - Basic Info -->
                            <div class="space-y-6">
                                <!-- Template Name -->
                                <div>
                                    <label for="template_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Template Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="template_name" wire:model.blur="name" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="e.g., Standard Collaboration License" maxlength="100">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="description" wire:model.blur="description" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                              placeholder="Describe when and how this template should be used..." maxlength="500"></textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Category -->
                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select id="category" wire:model.live="category" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select a category...</option>
                                        @foreach($categories as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <p class="mt-1 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Use Case -->
                                <div>
                                    <label for="use_case" class="block text-sm font-medium text-gray-700 mb-2">
                                        Primary Use Case <span class="text-red-500">*</span>
                                    </label>
                                    <select id="use_case" wire:model.live="use_case" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Select use case...</option>
                                        @foreach($useCases as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('use_case')
                                        <p class="mt-1 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column - License Terms -->
                            <div class="space-y-6">
                                <!-- License Terms -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-4">License Terms</label>
                                    <div class="space-y-4 bg-gray-50 rounded-lg p-4">
                                        @foreach([
                                            'commercial_use' => 'Commercial Use Allowed',
                                            'attribution_required' => 'Attribution Required',
                                            'modification_allowed' => 'Modifications Allowed',
                                            'distribution_allowed' => 'Distribution Allowed',
                                            'sync_licensing_allowed' => 'Sync Licensing Allowed',
                                            'broadcast_allowed' => 'Broadcasting Allowed',
                                            'streaming_allowed' => 'Streaming Allowed'
                                        ] as $key => $label)
                                            <label class="flex items-center">
                                                <input type="checkbox" wire:model.live="terms.{{ $key }}" 
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Territory -->
                                <div>
                                    <label for="territory" class="block text-sm font-medium text-gray-700 mb-2">Territory</label>
                                    <select wire:model.live="terms.territory" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="worldwide">Worldwide</option>
                                        <option value="north_america">North America</option>
                                        <option value="europe">Europe</option>
                                        <option value="asia">Asia</option>
                                        <option value="other">Other/Custom</option>
                                    </select>
                                </div>

                                <!-- Duration -->
                                <div>
                                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                                    <select wire:model.live="terms.duration" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="perpetual">Perpetual</option>
                                        <option value="5_years">5 Years</option>
                                        <option value="3_years">3 Years</option>
                                        <option value="1_year">1 Year</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- License Content -->
                        <div class="col-span-full mt-8">
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                License Agreement Content <span class="text-red-500">*</span>
                            </label>
                            <textarea id="content" wire:model.blur="content" rows="12"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                      placeholder="Enter the full license agreement text..."></textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Use placeholders like {project_name}, {artist_name}, {date} for dynamic content</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" wire:click="closeModal()" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                {{ $editingTemplate ? 'Update' : 'Create' }} Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endteleport
    @endif

    <!-- Preview Modal -->
    @if($showPreviewModal && $currentPreviewTemplate)
        @teleport('body')
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click="closePreview()">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="p-6 sm:p-8">
                    <!-- Preview Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $currentPreviewTemplate->name }}</h3>
                        <button type="button" wire:click="closePreview()" 
                                class="group p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100/50 rounded-lg transition-all duration-200">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <!-- Template Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700">Category:</span>
                                <span class="text-gray-600 ml-1">{{ ucfirst($currentPreviewTemplate->category) }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Use Case:</span>
                                <span class="text-gray-600 ml-1">{{ ucfirst(str_replace('_', ' ', $currentPreviewTemplate->use_case ?? '')) }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Territory:</span>
                                <span class="text-gray-600 ml-1">{{ ucfirst(str_replace('_', ' ', $currentPreviewTemplate->terms['territory'] ?? 'Worldwide')) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- License Content -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="font-bold text-lg mb-4">License Agreement</h4>
                        <div class="prose prose-sm max-w-none">
                            <pre class="whitespace-pre-wrap font-sans text-sm leading-relaxed">{{ $currentPreviewTemplate->content }}</pre>
                        </div>
                    </div>

                    <!-- Preview Actions -->
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" wire:click="closePreview()" 
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            Close
                        </button>
                        
                        @if($currentPreviewTemplate->user_id === auth()->id())
                            <!-- User's own template - allow editing -->
                            <button type="button" wire:click="editTemplate({{ $currentPreviewTemplate->id }})" 
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                                Edit Template
                            </button>
                        @else
                            <!-- Marketplace template - allow forking -->
                            @if($canCreateMore)
                                <button type="button" wire:click="forkTemplate({{ $currentPreviewTemplate->id }})" 
                                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>Add to My Templates
                                </button>
                            @else
                                <div class="text-sm text-gray-500 italic">
                                    Upgrade to Pro to add more templates
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $templateToDelete)
        @teleport('body')
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click="closeModal()">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-lg w-full" wire:click.stop>
                <div class="p-6">
                    <div class="flex items-start">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-red-100 to-rose-100 rounded-xl mr-4 flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Delete License Template</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Are you sure you want to delete "<strong>{{ $templateToDelete->name }}</strong>"? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="closeModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteTemplate()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                            Delete Template
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <!-- Marketplace Modal -->
    @if($showMarketplace)
        @teleport('body')
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click="closeMarketplace()">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="p-6 sm:p-8">
                    <!-- Marketplace Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-store text-indigo-600 mr-3"></i>
                            Template Marketplace
                        </h3>
                        <button type="button" wire:click="closeMarketplace()" 
                                class="group p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100/50 rounded-lg transition-all duration-200">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <!-- Search & Filters -->
                    <div class="bg-gray-50/80 rounded-xl p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div class="md:col-span-2">
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="searchTerm" 
                                           placeholder="Search templates..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Category Filter -->
                            <div>
                                <select wire:model.live="filterCategory" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Use Case Filter -->
                            <div>
                                <select wire:model.live="filterUseCase" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Use Cases</option>
                                    @foreach($useCases as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Sort & Clear -->
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center gap-4">
                                <label class="text-sm font-medium text-gray-700">Sort by:</label>
                                <select wire:model.live="sortBy" 
                                        class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    @foreach($sortOptions as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            @if($searchTerm || $filterCategory || $filterUseCase)
                                <button type="button" wire:click="clearMarketplaceFilters" 
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Clear Filters
                                </button>
                            @endif
                        </div>
                    </div>


                    @php
                        \Log::info('VIEW MARKETPLACE CHECK', [
                            'marketplaceTemplates_count' => $marketplaceTemplates ? $marketplaceTemplates->count() : 'null',
                            'marketplaceTemplates_type' => $marketplaceTemplates ? get_class($marketplaceTemplates) : 'null',
                            'condition_result' => ($marketplaceTemplates && $marketplaceTemplates->count() > 0),
                            'showMarketplace' => $showMarketplace ?? 'undefined'
                        ]);
                    @endphp
                    @if($marketplaceTemplates && $marketplaceTemplates->count() > 0)
                        <!-- Results Count -->
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">
                                Showing {{ $marketplaceTemplates ? $marketplaceTemplates->count() : 0 }} template{{ ($marketplaceTemplates ? $marketplaceTemplates->count() : 0) !== 1 ? 's' : '' }}
                            </p>
                        </div>

                        <!-- Marketplace Templates Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($marketplaceTemplates as $template)
                                <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-6 hover:bg-white/80 hover:shadow-xl transition-all duration-300">
                                    <!-- Template Header -->
                                    <div class="mb-3">
                                        <h4 class="font-bold text-lg mb-1 text-gray-900">{{ $template->marketplace_title_display }}</h4>
                                        <p class="text-gray-600 text-sm line-clamp-2">{{ $template->marketplace_description_display }}</p>
                                    </div>
                                    
                                    <!-- Template Meta -->
                                    <div class="flex items-center gap-2 mb-3 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            {{ ucfirst($template->category) }}
                                        </span>
                                        @if($template->marketplace_featured)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                                <i class="fas fa-star mr-1"></i> Featured
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Template Stats -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                        <div class="flex items-center gap-3">
                                            @if($template->fork_count > 0)
                                                <span><i class="fas fa-code-branch mr-1"></i>{{ $template->fork_count }} fork{{ $template->fork_count !== 1 ? 's' : '' }}</span>
                                            @endif
                                            @if($template->view_count > 0)
                                                <span><i class="fas fa-eye mr-1"></i>{{ $template->view_count }} view{{ $template->view_count !== 1 ? 's' : '' }}</span>
                                            @endif
                                        </div>
                                        @if($template->user)
                                            <span class="text-indigo-600">by {{ $template->user->name }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Template Actions -->
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="previewTemplate({{ $template->id }}, true)" 
                                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                            Preview
                                        </button>
                                        @if($canCreateMore)
                                            <button type="button" wire:click="forkTemplate({{ $template->id }})" 
                                                    class="flex-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                                Fork
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">
                                @if($searchTerm || $filterCategory || $filterUseCase)
                                    No Templates Found
                                @else
                                    No Templates Available
                                @endif
                            </h4>
                            <p class="text-gray-600 mb-4">
                                @if($searchTerm || $filterCategory || $filterUseCase)
                                    Try adjusting your search criteria or clearing filters.
                                @else
                                    Check back later for community-shared templates.
                                @endif
                            </p>
                            @if($searchTerm || $filterCategory || $filterUseCase)
                                <button type="button" wire:click="clearMarketplaceFilters" 
                                        class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Clear All Filters
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endteleport
    @endif

    <!-- Publish to Marketplace Modal -->
    @if($showPublishModal && $templateToPublish)
        @teleport('body')
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click="closePublishModal()">
            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="p-6 sm:p-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-store text-white text-sm"></i>
                            </div>
                            Publish to Marketplace
                        </h3>
                        <button type="button" wire:click="closePublishModal()" 
                                class="group p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100/50 rounded-lg transition-all duration-200">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <!-- Template Info -->
                    <div class="bg-indigo-50/50 rounded-lg p-4 mb-6">
                        <h4 class="font-bold text-gray-900 mb-1">{{ $templateToPublish->name }}</h4>
                        <p class="text-sm text-gray-600">{{ $templateToPublish->description }}</p>
                    </div>

                    <!-- Publishing Form -->
                    <form wire:submit.prevent="publishToMarketplace">
                        <div class="space-y-6">
                            <!-- Marketplace Title -->
                            <div>
                                <label for="marketplaceTitle" class="block text-sm font-medium text-gray-700 mb-2">
                                    Marketplace Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="marketplaceTitle" wire:model.blur="marketplaceTitle" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Give your template a catchy marketplace title..." maxlength="150">
                                @error('marketplaceTitle')
                                    <p class="mt-1 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">This will be displayed prominently in the marketplace</p>
                            </div>

                            <!-- Marketplace Description -->
                            <div>
                                <label for="marketplaceDescription" class="block text-sm font-medium text-gray-700 mb-2">
                                    Marketplace Description <span class="text-red-500">*</span>
                                </label>
                                <textarea id="marketplaceDescription" wire:model.blur="marketplaceDescription" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Describe what makes this template special and when others should use it..." maxlength="1000"></textarea>
                                @error('marketplaceDescription')
                                    <p class="mt-1 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Help others understand the value and use cases for your template</p>
                            </div>

                            <!-- Submission Notes -->
                            <div>
                                <label for="submissionNotes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes for Reviewers <span class="text-gray-400">(Optional)</span>
                                </label>
                                <textarea id="submissionNotes" wire:model.blur="submissionNotes" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Any additional context or information for our review team..." maxlength="500"></textarea>
                                @error('submissionNotes')
                                    <p class="mt-1 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Private notes that will help our team review your submission</p>
                            </div>

                            <!-- Submission Guidelines -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h5 class="font-medium text-yellow-800 mb-2 flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Submission Guidelines
                                </h5>
                                <ul class="text-sm text-yellow-700 space-y-1">
                                    <li>• Templates must be original content created by you</li>
                                    <li>• Content should be professional and legally sound</li>
                                    <li>• Templates will be reviewed before appearing in marketplace</li>
                                    <li>• You'll receive an email notification once reviewed</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" wire:click="closePublishModal()" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg hover:from-purple-600 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                Submit for Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endteleport
    @endif
</div> 