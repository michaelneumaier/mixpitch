<div class="container mx-auto px-2 sm:px-4">
    <!-- Project Header -->
    <x-project.header 
        :project="$project" 
        :hasPreviewTrack="false" 
        context="client"
        :showEditButton="false"
        :showActions="false"
    />

    <!-- Mobile Activity Summary (visible on mobile/tablet, hidden on desktop) -->
    <div class="lg:hidden mb-6">
        <x-client-project.activity-summary-mobile :pitch="$pitch" :project="$project" :component="$this" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Workflow Status -->
            <x-pitch.workflow-status :pitch="$pitch" :project="$project" />

            <!-- Enhanced Feedback Panel -->
            <x-client-project.feedback-panel :pitch="$pitch" />

            <!-- Recall Submission Section (if applicable) -->
            @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-blue-500/10 p-4 lg:p-6 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                            <i class="fas fa-undo text-lg text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-blue-800">Submission Management</h4>
                            <p class="text-sm text-blue-600">Manage your submission status and revisions</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-2 md:p-4 lg:p-6">
                
                <div class="mb-4">
                    <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border border-blue-200/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                        <p class="text-sm text-blue-700 flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Your work has been submitted for client review. You can recall this submission if you need to make changes.
                        </p>
                    </div>
                    
                    @if($canResubmit)
                    <div class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 border border-amber-200/50 rounded-xl p-4 mb-3 backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg mr-3">
                                <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                            </div>
                            <span class="text-sm text-amber-700">
                                <strong>Files Updated:</strong> You've added or modified files since submission. You can now resubmit with your changes.
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="recallSubmission" 
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-100 to-orange-100 hover:from-amber-200 hover:to-orange-200 text-amber-700 rounded-xl font-medium transition-all duration-200 hover:scale-105"
                            wire:confirm="Are you sure you want to recall this submission? The client will no longer be able to review it until you resubmit.">
                        <i class="fas fa-undo mr-2"></i>Recall Submission
                    </button>
                    
                    @if($canResubmit)
                    <button wire:click="submitForReview" 
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Resubmit for Review
                    </button>
                    @endif
                </div>
                
                <p class="text-xs text-blue-600 mt-4 bg-blue-50/50 rounded-lg p-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    Recalling allows you to add/remove files and make changes before resubmitting.
                </p>
                </div>
            </div>
            @endif

            <!-- Producer Comment Section -->
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-purple-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-purple-500/10 via-indigo-500/10 to-purple-500/10 p-4 lg:p-6 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600">
                            <i class="fas fa-comment-dots text-lg text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-purple-800">Send Message to Client</h4>
                            <p class="text-sm text-purple-600">Communicate directly with your client</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 lg:p-6">
                
                <form wire:submit.prevent="addProducerComment">
                    <div class="mb-6">
                        <label for="newComment" class="block text-sm font-semibold text-purple-800 mb-3">
                            Your Message
                        </label>
                        <div class="bg-gradient-to-r from-purple-50/80 to-indigo-50/80 border border-purple-200/50 rounded-xl p-4 backdrop-blur-sm">
                            <textarea wire:model.defer="newComment" 
                                      id="newComment"
                                      rows="4"
                                      class="w-full px-4 py-3 text-gray-700 bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all duration-200"
                                      placeholder="Share updates, ask questions, or provide additional context..."></textarea>
                            @error('newComment') 
                                <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="bg-purple-50/50 rounded-lg p-3 flex-1">
                            <p class="text-xs text-purple-600 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                This message will be visible to your client and they'll receive an email notification
                            </p>
                        </div>
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fas fa-paper-plane mr-2"></i>Send Message
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin mr-2"></i>Sending...
                            </span>
                        </button>
                    </div>
                </form>
                </div>
            </div>

            <!-- Communication Timeline -->
            <x-client-project.communication-timeline :component="$this" :conversationItems="$this->conversationItems" />

            <!-- File Management with Separation -->
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-purple-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-purple-500/10 via-indigo-500/10 to-purple-500/10 p-4 lg:p-6 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600">
                            <i class="fas fa-file-upload text-lg text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-purple-800">File Management</h4>
                            <p class="text-sm text-purple-600">Manage client references and your deliverables</p>
                        </div>
                    </div>
                </div>

                <div class="p-2 md:p-4 lg:p-6">

                <!-- Storage Indicator -->
                <x-file-management.storage-indicator 
                    :storageUsedPercentage="$storageUsedPercentage"
                    :storageLimitMessage="$storageLimitMessage"
                    :storageRemaining="$this->formatFileSize($storageRemaining)" />

                <!-- Client Reference Files Section -->
                <div class="overflow-hidden rounded-2xl border border-blue-200/50 bg-gradient-to-br from-white/90 to-blue-50/90 shadow-lg backdrop-blur-sm mb-6">
                    <div class="border-b border-blue-200/50 bg-gradient-to-r from-blue-100/80 to-indigo-100/80 p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                    <i class="fas fa-folder-open text-white"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-blue-800">Client Reference Files
                                        <span class="ml-2 bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded-full">
                                            {{ $this->clientFiles->count() }} files
                                        </span>
                                    </h5>
                                    <p class="text-xs text-blue-600">Files uploaded by your client to provide project requirements, references, or examples</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4">
                    
                    @if($this->clientFiles->count() > 0)
                            <div class="divide-y divide-blue-100/50">
                                @foreach($this->clientFiles as $file)
                                    <div class="group py-4 transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50">
                                        <div class="flex items-center mb-3">
                                            <div class="bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600 mr-3 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl shadow-md transition-transform duration-200 group-hover:scale-105">
                                                <i class="fas fa-file"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold text-blue-900">{{ $file->file_name }}</div>
                                                <div class="text-xs text-blue-600">
                                                    {{ $this->formatFileSize($file->size) }} • 
                                                    Uploaded {{ $file->created_at->diffForHumans() }}
                                                    @if(isset($file->metadata) && json_decode($file->metadata)?->uploaded_by_client)
                                                        • <span class="font-medium">Client Upload</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="flex gap-2">
                                            <button wire:click="downloadClientFile({{ $file->id }})" 
                                                    class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-700 rounded-lg font-medium transition-all duration-200 hover:scale-105 text-sm">
                                                <i class="fas fa-download mr-2"></i>Download
                                            </button>
                                            <button wire:click="confirmDeleteClientFile({{ $file->id }})" 
                                                    class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-red-100 to-pink-100 hover:from-red-200 hover:to-pink-200 text-red-700 rounded-lg font-medium transition-all duration-200 hover:scale-105 text-sm">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full mx-auto mb-4">
                                    <i class="fas fa-inbox text-blue-500 text-xl"></i>
                                </div>
                                <p class="text-blue-600 text-sm">No client files yet. Your client can upload reference files through their portal.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Producer Deliverables Section -->
                <div data-section="producer-deliverables" class="overflow-hidden rounded-2xl border border-green-200/50 bg-gradient-to-br from-white/90 to-green-50/90 shadow-lg backdrop-blur-sm">
                    <div class="border-b border-green-200/50 bg-gradient-to-r from-green-100/80 to-emerald-100/80 p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                                    <i class="fas fa-music text-white"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-green-800">Your Deliverables
                                        <span class="ml-2 bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">
                                            {{ $this->producerFiles->count() }} files
                                        </span>
                                    </h5>
                                    <p class="text-xs text-green-600">Upload your work files here. These will be visible to your client for review</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-2 md:p-4">

                    <!-- Upload Section for Producer -->
                    <x-file-management.upload-section 
                        :model="$pitch"
                        title="Upload Deliverables"
                        description="Upload audio, PDFs, or images for your client to review" />

                    <!-- Producer Files List -->
                    @if($this->producerFiles->count() > 0)
                        <div class="divide-y divide-green-100/50 mt-4">
                            @foreach($this->producerFiles as $file)
                                <div class="overflow-hidden rounded-2xl border border-green-200/50 bg-gradient-to-br from-white/90 to-green-50/90 shadow-md backdrop-blur-sm mb-3">
                                    {{-- File Header --}}
                                    <div class="group p-4 transition-all duration-300 hover:bg-gradient-to-r hover:from-green-50/50 hover:to-emerald-50/50">
                                        <div class="flex items-center mb-3">
                                            <div class="bg-gradient-to-br from-green-100 to-emerald-100 text-green-600 mr-3 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl shadow-md transition-transform duration-200 group-hover:scale-105">
                                                <i class="fas fa-file-audio"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold text-green-900">{{ $file->file_name }}</div>
                                                <div class="text-xs text-green-600">
                                                    {{ $this->formatFileSize($file->size) }} • 
                                                    Uploaded {{ $file->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="flex gap-2">
                                            <button wire:click="downloadFile({{ $file->id }})" 
                                                    class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-green-100 to-emerald-100 hover:from-green-200 hover:to-emerald-200 text-green-700 rounded-lg font-medium transition-all duration-200 hover:scale-105 text-sm">
                                                <i class="fas fa-download mr-2"></i>Download
                                            </button>
                                            @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED, \App\Models\Pitch::STATUS_READY_FOR_REVIEW]))
                                            <button wire:click="confirmDeleteFile({{ $file->id }})" 
                                                    class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-red-100 to-pink-100 hover:from-red-200 hover:to-pink-200 text-red-700 rounded-lg font-medium transition-all duration-200 hover:scale-105 text-sm">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- Audio Player for Audio Files --}}
                                    @if(in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                        <div class="border-t border-green-200 bg-white p-3" wire:ignore>
                                            @livewire('pitch-file-player', [
                                                'file' => $file,
                                                'isInCard' => true
                                            ], key('pitch-player-'.$file->id))
                                        </div>
                                        
                                        
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 mt-4">
                            <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full mx-auto mb-4">
                                <i class="fas fa-cloud-upload-alt text-green-500 text-xl"></i>
                            </div>
                            <p class="text-green-600 text-sm">No deliverables uploaded yet. Use the upload area above to add files.</p>
                        </div>
                    @endif
                    </div>
                </div>
                </div>
            </div>

            <!-- Response to Feedback Section (if applicable) -->
            @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-amber-800 mb-3 flex items-center">
                    <i class="fas fa-reply text-amber-600 mr-2"></i>Respond to Feedback
                </h4>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-amber-700 mb-2">
                        Your Response to Client Feedback
                    </label>
                    <textarea wire:model.lazy="responseToFeedback" 
                              rows="4"
                              class="w-full px-3 py-2 border border-amber-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                              placeholder="Explain what changes you've made in response to the feedback..."></textarea>
                    @error('responseToFeedback') 
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                    @enderror
                </div>
            </div>
            @endif

            <!-- Submit for Review Section -->
            @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED]))
            <div class="bg-gradient-to-br from-purple-50/90 to-indigo-50/90 backdrop-blur-sm border border-purple-200/50 rounded-xl p-6 shadow-lg">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                        <i class="fas fa-paper-plane text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-purple-800">Ready to Submit for Review?</h4>
                        <p class="text-purple-600 text-sm">Submit your work to your client for review and approval</p>
                    </div>
                </div>

                @if($this->producerFiles->count() === 0)
                    <div class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 border border-amber-200/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg mr-3">
                                <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-amber-800">No deliverables uploaded</h5>
                                <p class="text-sm text-amber-700">You need to upload at least one file before submitting for review.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 border border-green-200/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-green-800">{{ $this->producerFiles->count() }} {{ Str::plural('file', $this->producerFiles->count()) }} ready</h5>
                                <p class="text-sm text-green-700">Your deliverables are ready to be submitted to the client.</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]) && $responseToFeedback)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h5 class="font-medium text-blue-800 mb-2">Your Response to Feedback:</h5>
                        <p class="text-sm text-blue-700 italic">{{ $responseToFeedback }}</p>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3">
                    @if($this->producerFiles->count() > 0)
                        <button wire:click="submitForReview" 
                                wire:loading.attr="disabled"
                                class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 hover:shadow-xl disabled:opacity-50 disabled:transform-none relative overflow-hidden group">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span wire:loading wire:target="submitForReview" class="inline-block w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mr-3"></span>
                            <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane mr-3 relative z-10"></i>
                            <span class="relative z-10">
                                @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
                                    Submit Revisions
                                @else
                                    Submit for Review
                                @endif
                            </span>
                        </button>
                    @else
                        <button disabled
                                class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gray-400 text-white rounded-xl font-bold text-lg opacity-50 cursor-not-allowed">
                            <i class="fas fa-paper-plane mr-3"></i>
                            Submit for Review
                        </button>
                    @endif
                    
                    <button onclick="window.scrollTo({top: document.querySelector('[data-section=producer-deliverables]').offsetTop - 100, behavior: 'smooth'})"
                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-100 to-indigo-100 hover:from-purple-200 hover:to-indigo-200 text-purple-800 border border-purple-300 rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-md">
                        <i class="fas fa-upload mr-3"></i>Upload More Files
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-sm text-purple-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Once submitted, your client will receive an email notification and can review your work through their secure portal.
                    </p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Project Activity Summary (hidden on mobile, visible on desktop) -->
            <div class="hidden lg:block">
                <x-client-project.activity-summary :pitch="$pitch" :project="$project" :component="$this" />
            </div>
            
            <!-- Client Management Details -->
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-purple-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-purple-500/10 via-indigo-500/10 to-purple-500/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600">
                            <i class="fas fa-briefcase text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-purple-800">Client Details</h3>
                            <p class="text-xs text-purple-600">Manage client information and access</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                <div class="space-y-2 text-sm">
                    <div><strong>Client Name:</strong> {{ $project->client_name ?? 'N/A' }}</div>
                    <div><strong>Client Email:</strong> {{ $project->client_email ?? 'N/A' }}</div>
                    @if($project->payment_amount > 0)
                    <div><strong>Payment Amount:</strong> ${{ number_format($project->payment_amount, 2) }}</div>
                    @endif
                </div>
                <div class="space-y-2 mt-3">
                    <button wire:click="resendClientInvite" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-purple-100 to-indigo-100 hover:from-purple-200 hover:to-indigo-200 text-purple-700 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i> Resend Client Invite
                    </button>
                    <button wire:click="previewClientPortal" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-700 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-external-link-alt mr-2"></i> Preview Client Portal
                    </button>
                </div>
                <p class="text-xs text-purple-600 mt-3 bg-purple-50/50 rounded-lg p-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Preview shows exactly what your client sees when they click the email link.
                </p>
                </div>
            </div>

            <!-- Project Actions -->
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-blue-500/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                            <i class="fas fa-cog text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-blue-800">Project Actions</h3>
                            <p class="text-xs text-blue-600">Edit and manage your project</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                <div class="space-y-3">
                    <a href="{{ route('projects.edit', $project) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-amber-100 to-orange-100 hover:from-amber-200 hover:to-orange-200 text-amber-700 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-edit mr-2"></i>Edit Project Details
                    </a>
                    <a href="{{ route('projects.show', $project) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-700 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-eye mr-2"></i>View Public Page
                    </a>
                </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-red-50/90 shadow-xl backdrop-blur-md">
                <div class="border-b border-white/20 bg-gradient-to-r from-red-500/10 via-pink-500/10 to-red-500/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-pink-600">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">Danger Zone</h3>
                            <p class="text-xs text-red-600">Irreversible project deletion</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                <p class="text-sm text-red-700 mb-4 bg-red-50/50 rounded-lg p-3">
                    <i class="fas fa-warning mr-2"></i>
                    Permanently delete this project and all associated files. This action cannot be undone.
                </p>
                <button wire:click="confirmDeleteProject" 
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-red-100 to-pink-100 hover:from-red-200 hover:to-pink-200 text-red-700 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Project
                </button>
                </div>
            </div>
        </div>
    </div>

    <!-- File Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm File Deletion</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this file? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteFile" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteFile" class="btn btn-error">Delete File</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Client File Delete Confirmation Modal -->
    @if($showDeleteClientFileModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Confirm Client File Deletion
            </h3>
            <p class="text-gray-600 mb-4">
                Are you sure you want to delete the client reference file: 
                <strong class="text-gray-900">{{ $clientFileNameToDelete }}</strong>?
            </p>
            <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded p-3 mb-6">
                <i class="fas fa-info-circle mr-2"></i>
                This file was uploaded by your client. Once deleted, they will need to re-upload it if needed.
            </p>
            <p class="text-red-600 font-medium mb-6">This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteClientFile" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteClientFile" class="btn btn-error">
                    <i class="fas fa-trash mr-2"></i>Delete File
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Project Delete Confirmation Modal -->
    @if($showProjectDeleteModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-red-800 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>Delete Project
            </h3>
            <p class="text-gray-600 mb-4">
                Are you sure you want to permanently delete this project? This will also delete:
            </p>
            <ul class="text-sm text-gray-600 mb-6 list-disc list-inside">
                <li>All project files</li>
                <li>All pitch files and data</li>
                <li>All project history and events</li>
            </ul>
            <p class="text-red-600 font-medium mb-6">This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteProject" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteProject" class="btn btn-error">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Project
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- JavaScript for File Annotations -->
    <script>
        // Global function to expand comment details
        function expandComment(fileId, commentId) {
            console.log('Expand comment', commentId, 'for file', fileId);
            
            // Show a notification with comment details
            showNotification('Comment details view - Comment ID: ' + commentId + ' (Full modal coming soon)');
        }

        // Helper function to show notifications
        function showNotification(message) {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Simple cleanup - no special handling needed
        console.log('ManageClientProject initialized');

    </script>
</div> 