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

        <!-- Recommended Templates Section -->
        @if($recommendedTemplates->count() > 0)
            <div class="mb-8">
                <h4 class="text-md font-medium text-gray-900 mb-4">
                    Recommended Templates
                    @if($projectType)
                        <span class="text-sm font-normal text-gray-500">for {{ ucfirst($projectType) }}</span>
                    @endif
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($recommendedTemplates as $template)
                        <div class="border rounded-lg p-4 border-gray-200 hover:border-gray-300 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-medium text-gray-900">{{ $template->name }}</h5>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{{ $template->category_name }}</span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->description }}</p>
                            
                            <div class="flex space-x-2">
                                <button type="button" 
                                        wire:click="previewTemplate({{ $template->id }})"
                                        class="flex-1 text-xs bg-gray-100 text-gray-700 px-3 py-2 rounded hover:bg-gray-200">
                                    Preview
                                </button>
                                
                                <button type="button" 
                                        wire:click="forkTemplate({{ $template->id }})"
                                        class="flex-1 text-xs bg-indigo-100 text-indigo-700 px-3 py-2 rounded hover:bg-indigo-200">
                                    Use This
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Custom License Option -->
        <div class="mb-6">
            <label class="cursor-pointer">
                <input type="radio" 
                       wire:model.live="selectedTemplateId" 
                       value=""
                       class="sr-only">
                
                <div class="border-2 border-dashed rounded-lg p-6 text-center transition-colors {{ empty($selectedTemplateId) ? 'border-indigo-300 bg-indigo-50' : 'border-gray-300 hover:border-gray-400' }}">
                    <div class="text-gray-400 mb-2">
                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h5 class="font-medium text-gray-900 mb-1">Create Custom License</h5>
                    <p class="text-sm text-gray-600">Build your own terms from scratch</p>
                </div>
            </label>
        </div>

        <!-- License Notes -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                License Notes (Optional)
            </label>
            <textarea wire:model.lazy="licenseNotes"
                      rows="3"
                      placeholder="Add any additional notes or clarifications about the license terms..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <p class="text-xs text-gray-500 mt-1">These notes will be included with the license agreement</p>
        </div>

        <!-- Subscription Limit Warning -->
        @if(!LicenseTemplate::canUserCreate(auth()->user()))
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

    <!-- Preview Modal -->
    @if($showPreviewModal && $previewTemplate)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePreview"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $previewTemplate->name }}</h3>
                            <button wire:click="closePreview" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $previewTemplate->category_name }}</span>
                            @if($previewTemplate->use_case)
                                <span class="inline-block bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full ml-1">{{ $previewTemplate->use_case_name }}</span>
                            @endif
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto">
                            <div class="text-sm text-gray-700 whitespace-pre-line border rounded-lg p-4 bg-gray-50">
                                {{ $previewTemplate->content }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="selectTemplate({{ $previewTemplate->id }})" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Use This Template
                        </button>
                        <button wire:click="closePreview" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div> 