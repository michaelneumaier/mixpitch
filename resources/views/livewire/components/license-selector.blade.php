<div class="license-selector-component">
    @if(!$templatePickerOnly)
        <!-- Header Section -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">License Terms</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Choose how others can use your project work</p>
        </div>

        <!-- License Agreement Toggle -->
        <div class="mb-6">
            <label class="flex items-center space-x-3">
                <input type="checkbox"
                       wire:model.live="requiresAgreement"
                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Require license agreement</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Contributors must agree to terms before participating</p>
                </div>
            </label>
        </div>
    @endif

    @if($requiresAgreement || $templatePickerOnly)
        <!-- Your Templates Section -->
        @if($userTemplates->count() > 0)
            <div class="mb-8">
                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Your License Templates</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($userTemplates as $template)
                        <div class="license-template-card relative">
                            <label class="cursor-pointer">
                                <input type="radio" 
                                       wire:model.live="selectedTemplateId" 
                                       value="{{ $template->id }}"
                                       class="sr-only">
                                
                                <div class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md {{ $selectedTemplateId == $template->id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950 ring-2 ring-indigo-200 dark:ring-indigo-700' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800' }}">
                                    <!-- Template Header -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                                {{ $template->name }}
                                                @if($template->is_default)
                                                    <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-200 px-2 py-1 rounded-full">Default</span>
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
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                        <span>Used {{ $template->getUsageCount() }} times</span>
                                        <button type="button" 
                                                wire:click="previewTemplate({{ $template->id }})"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200">
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
            <div class="border rounded-lg p-6 text-center transition-colors border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 bg-white dark:bg-gray-800">
                <div class="flex justify-center mb-3">
                    <flux:icon.document-plus class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                </div>
                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-1">Create Custom License</h5>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Build your own terms from scratch</p>

                @if($this->canUserCreateTemplates)
                    <flux:button wire:click="createTemplate" variant="primary" icon="plus">
                        Create New Template
                    </flux:button>
                @else
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Template limit reached</p>
                        <flux:button href="{{ route('subscription.index') }}" variant="primary" icon="star">
                            Upgrade to Pro
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Success Message for Template Creation -->
        @if(session()->has('template-created'))
            <div class="mb-6 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                    <span class="text-green-800 dark:text-green-200 font-medium">{{ session('template-created') }}</span>
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if(session()->has('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 mr-2"></i>
                    <span class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(!$templatePickerOnly)
            <!-- License Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    License Notes (Optional)
                </label>
                <textarea wire:model.live="licenseNotes"
                          rows="3"
                          placeholder="Add any additional notes or clarifications about the license terms..."
                          class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">These notes will be included with the license agreement</p>
            </div>
        @endif

        <!-- Subscription Limit Warning -->
        @if(!$this->canUserCreateTemplates)
            <div class="bg-yellow-50 dark:bg-yellow-950 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Template Limit Reached</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
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
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $currentPreviewTemplate->name }}</h3>
                            <button type="button" wire:click="closePreview" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-1 rounded-full">{{ $currentPreviewTemplate->category_name }}</span>
                            @if($currentPreviewTemplate->use_case)
                                <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-200 text-xs px-2 py-1 rounded-full ml-1">{{ $currentPreviewTemplate->use_case_name }}</span>
                            @endif
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto">
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                                @if($currentPreviewTemplate->content)
                                    {{ $currentPreviewTemplate->content }}
                                @else
                                    <div class="text-gray-500 dark:text-gray-400 italic">No license content available for this template.</div>
                                @endif
                            </div>
                            

                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="selectTemplate({{ $currentPreviewTemplate->id }})" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Use This Template
                        </button>
                        <button type="button" wire:click="closePreview" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endteleport
@endif

<!-- Create Template Modal -->
<flux:modal name="create-license-template" wire:model.self="showCreateModal" class="max-w-4xl">
    <div class="space-y-6">
        <!-- Modal Header -->
        <div>
            <flux:heading size="lg">Create License Template</flux:heading>
            <flux:subheading>Build your own terms from scratch</flux:subheading>
        </div>

        <!-- Form Content -->
        <form wire:submit.prevent="saveTemplate" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column - Basic Info -->
                <div class="space-y-6">
                    <!-- Template Name -->
                    <div>
                        <label for="template_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Template Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="template_name" wire:model.blur="name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100"
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
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" wire:model.blur="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100"
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
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" wire:model.live="category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100">
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
                        <label for="use_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Use Case <span class="text-red-500">*</span>
                        </label>
                        <select id="use_case" wire:model.live="use_case"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">License Terms</label>
                        <div class="space-y-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
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
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Territory -->
                    <div>
                        <label for="territory" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Territory</label>
                        <select wire:model.live="terms.territory"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100">
                            <option value="worldwide">Worldwide</option>
                            <option value="north_america">North America</option>
                            <option value="europe">Europe</option>
                            <option value="asia">Asia</option>
                            <option value="other">Other/Custom</option>
                        </select>
                    </div>

                    <!-- Duration -->
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration</label>
                        <select wire:model.live="terms.duration"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-gray-100">
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
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    License Agreement Content <span class="text-red-500">*</span>
                </label>
                <textarea id="content" wire:model.blur="content" rows="12"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm dark:bg-gray-800 dark:text-gray-100"
                          placeholder="Enter the full license agreement text..."></textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use placeholders like {project_name}, {artist_name}, {date} for dynamic content</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create Template</flux:button>
            </div>
        </form>
    </div>
</flux:modal>
</div> 