<div class="license-selector-component">
    <!-- Header Section -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">License Terms</h3>
        <p class="text-sm text-gray-600">Choose how others can use your project work</p>
    </div>

    <!-- License Agreement Toggle -->
    <div class="mb-6">
        <label class="flex items-center space-x-3">
            <input type="checkbox" 
                   wire:model.live="requiresAgreement"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <div>
                <span class="text-sm font-medium text-gray-900">Require license agreement</span>
                <p class="text-xs text-gray-500">Contributors must agree to terms before participating</p>
            </div>
        </label>
    </div>

    @if($requiresAgreement)
        <!-- Your Templates Section -->
        @if($userTemplates->count() > 0)
            <div class="mb-8">
                <h4 class="text-md font-medium text-gray-900 mb-4">Your License Templates</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($userTemplates as $template)
                        <div class="license-template-card relative">
                            <label class="cursor-pointer">
                                <input type="radio" 
                                       wire:model.live="selectedTemplateId" 
                                       value="{{ $template->id }}"
                                       class="sr-only">
                                
                                <div class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md {{ $selectedTemplateId == $template->id ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200 hover:border-gray-300' }}">
                                    <!-- Template Header -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-900 flex items-center">
                                                {{ $template->name }}
                                                @if($template->is_default)
                                                    <span class="ml-2 text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">Default</span>
                                                @endif
                                            </h5>
                                            <p class="text-xs text-gray-500 mt-1">{{ $template->category_name }}</p>
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
                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->description }}</p>
                                    
                                    <!-- Key Terms Preview -->
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @if($template->terms['commercial_use'] ?? false)
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Commercial Use</span>
                                        @endif
                                        
                                        @if($template->terms['sync_licensing_allowed'] ?? false)
                                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Sync Ready</span>
                                        @endif
                                        
                                        @if($template->terms['attribution_required'] ?? false)
                                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">Credit Required</span>
                                        @endif
                                        
                                        @if($template->terms['modification_allowed'] ?? false)
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Edits Allowed</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Template Actions -->
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>Used {{ $template->getUsageCount() }} times</span>
                                        <button type="button" 
                                                wire:click="previewTemplate({{ $template->id }})"
                                                class="text-indigo-600 hover:text-indigo-800">
                                            Preview Full Terms
                                        </button>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Custom License Option -->
        <div class="mb-6">
            <div class="border-2 border-dashed rounded-lg p-6 text-center transition-colors border-gray-300 hover:border-gray-400 hover:bg-gray-50">
                <div class="text-gray-400 mb-2">
                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h5 class="font-medium text-gray-900 mb-1">Create Custom License</h5>
                <p class="text-sm text-gray-600 mb-4">Build your own terms from scratch</p>
                
                @if($this->canUserCreateTemplates)
                    <button type="button" 
                            wire:click="createTemplate"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-lg transition-all duration-200 hover:scale-105 shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Create New Template
                    </button>
                @else
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-2">Template limit reached</p>
                        <a href="{{ route('subscription.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-crown mr-2"></i>
                            Upgrade to Pro
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Success Message for Template Creation -->
        @if(session()->has('template-created'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    <span class="text-green-800 font-medium">{{ session('template-created') }}</span>
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if(session()->has('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                    <span class="text-red-800 font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- License Notes -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                License Notes (Optional)
            </label>
            <textarea wire:model.live="licenseNotes"
                      rows="3"
                      placeholder="Add any additional notes or clarifications about the license terms..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <p class="text-xs text-gray-500 mt-1">These notes will be included with the license agreement</p>
        </div>

        <!-- Subscription Limit Warning -->
        @if(!$this->canUserCreateTemplates)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Template Limit Reached</h3>
                        <p class="text-sm text-yellow-700 mt-1">
                            You've reached your license template limit. 
                            <a href="{{ route('subscription.index') }}" class="font-medium underline">Upgrade to Pro</a> 
                            for unlimited templates.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

<!-- Preview Modal - Moved outside component to avoid positioning issues -->
@if($showPreviewModal && $currentPreviewTemplate)
    @teleport('body')
        <div class="fixed inset-0 z-[9999] overflow-y-auto" 
             aria-labelledby="modal-title" role="dialog" aria-modal="true" 
             style="z-index: 9999;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePreview"></div>
                
                <!-- Spacer element to center modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $currentPreviewTemplate->name }}</h3>
                            <button type="button" wire:click="closePreview" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $currentPreviewTemplate->category_name }}</span>
                            @if($currentPreviewTemplate->use_case)
                                <span class="inline-block bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full ml-1">{{ $currentPreviewTemplate->use_case_name }}</span>
                            @endif
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto">
                            <div class="text-sm text-gray-700 whitespace-pre-line border rounded-lg p-4 bg-gray-50">
                                @if($currentPreviewTemplate->content)
                                    {{ $currentPreviewTemplate->content }}
                                @else
                                    <div class="text-gray-500 italic">No license content available for this template.</div>
                                @endif
                            </div>
                            

                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="selectTemplate({{ $currentPreviewTemplate->id }})" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Use This Template
                        </button>
                        <button type="button" wire:click="closePreview" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endteleport
@endif

<!-- Create Template Modal -->
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
                                <i class="fas fa-plus text-white text-sm"></i>
                            </div>
                            Create License Template
                        </h3>
                        <button wire:click="closeTemplateModal()" 
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
                                        @foreach($this->categories as $key => $label)
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
                                        @foreach($this->useCases as $key => $label)
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
                            <button type="button" wire:click="closeTemplateModal()" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                Create Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endteleport
@endif
</div> 