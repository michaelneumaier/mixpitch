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
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                    <i class="fas fa-undo text-blue-600 mr-2"></i>Submission Management
                </h4>
                
                <div class="mb-4">
                    <p class="text-sm text-blue-700 mb-3">
                        Your work has been submitted for client review. You can recall this submission if you need to make changes.
                    </p>
                    
                    @if($canResubmit)
                    <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
                            <span class="text-sm text-amber-700">
                                <strong>Files Updated:</strong> You've added or modified files since submission. You can now resubmit with your changes.
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="recallSubmission" 
                            class="btn btn-outline btn-warning flex-1"
                            wire:confirm="Are you sure you want to recall this submission? The client will no longer be able to review it until you resubmit.">
                        <i class="fas fa-undo mr-2"></i>Recall Submission
                    </button>
                    
                    @if($canResubmit)
                    <button wire:click="submitForReview" 
                            class="btn btn-primary flex-1">
                        <i class="fas fa-paper-plane mr-2"></i>Resubmit for Review
                    </button>
                    @endif
                </div>
                
                <p class="text-xs text-blue-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Recalling allows you to add/remove files and make changes before resubmitting.
                </p>
            </div>
            @endif

            <!-- Producer Comment Section -->
            <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
                <h4 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-comment-dots text-purple-500 mr-2"></i>
                    Send Message to Client
                </h4>
                
                <form wire:submit.prevent="addProducerComment">
                    <div class="mb-4">
                        <label for="newComment" class="block text-sm font-medium text-gray-700 mb-2">
                            Your Message
                        </label>
                        <textarea wire:model.defer="newComment" 
                                  id="newComment"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Share updates, ask questions, or provide additional context..."></textarea>
                        @error('newComment') 
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                        @enderror
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            This message will be visible to your client and they'll receive an email notification
                        </p>
                        <button type="submit" 
                                class="btn btn-primary"
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

            <!-- Communication Timeline -->
            <x-client-project.communication-timeline :component="$this" :conversationItems="$this->conversationItems" />

            <!-- File Management with Separation -->
            <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
                <h4 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-file-upload text-purple-500 mr-2"></i>File Management
                </h4>

                <!-- Storage Indicator -->
                <x-file-management.storage-indicator 
                    :storageUsedPercentage="$storageUsedPercentage"
                    :storageLimitMessage="$storageLimitMessage"
                    :storageRemaining="$this->formatFileSize($storageRemaining)" />

                <!-- Client Reference Files Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h5 class="font-semibold text-blue-800 mb-3 flex items-center">
                        <i class="fas fa-folder-open text-blue-600 mr-2"></i>
                        Client Reference Files
                        <span class="ml-2 bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded-full">
                            {{ $this->clientFiles->count() }} files
                        </span>
                    </h5>
                    <p class="text-sm text-blue-700 mb-4">Files uploaded by your client to provide project requirements, references, or examples.</p>
                    
                    @if($this->clientFiles->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->clientFiles as $file)
                                <div class="flex items-center justify-between py-2 px-3 bg-blue-100 rounded">
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-blue-600 mr-2"></i>
                                        <div>
                                            <span class="text-sm font-medium text-blue-800">{{ $file->file_name }}</span>
                                            <div class="text-xs text-blue-600">
                                                {{ $this->formatFileSize($file->file_size) }} • 
                                                Uploaded {{ $file->created_at->diffForHumans() }}
                                                @if(isset($file->metadata) && json_decode($file->metadata)?->uploaded_by_client)
                                                    • <span class="font-medium">Client Upload</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="downloadFile({{ $file->id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </button>
                                        <button wire:click="confirmDeleteClientFile({{ $file->id }})" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-inbox text-blue-400 text-3xl mb-3"></i>
                            <p class="text-blue-600 text-sm">No client files yet. Your client can upload reference files through their portal.</p>
                        </div>
                    @endif
                </div>

                <!-- Producer Deliverables Section -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h5 class="font-semibold text-green-800 mb-3 flex items-center">
                        <i class="fas fa-music text-green-600 mr-2"></i>
                        Your Deliverables
                        <span class="ml-2 bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">
                            {{ $this->producerFiles->count() }} files
                        </span>
                    </h5>
                    <p class="text-sm text-green-700 mb-4">Upload your work files here. These will be visible to your client for review.</p>

                    <!-- Upload Section for Producer -->
                    <x-file-management.upload-section 
                        :model="$pitch"
                        title="Upload Deliverables"
                        description="Upload audio, PDFs, or images for your client to review" />

                    <!-- Producer Files List -->
                    @if($this->producerFiles->count() > 0)
                        <div class="space-y-2 mt-4">
                            @foreach($this->producerFiles as $file)
                                <div class="flex items-center justify-between py-2 px-3 bg-green-100 rounded">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-audio text-green-600 mr-2"></i>
                                        <div>
                                            <span class="text-sm font-medium text-green-800">{{ $file->file_name }}</span>
                                            <div class="text-xs text-green-600">
                                                {{ $this->formatFileSize($file->file_size) }} • 
                                                Uploaded {{ $file->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="downloadFile({{ $file->id }})" 
                                                class="text-green-600 hover:text-green-800 text-sm">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </button>
                                        @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED]))
                                        <button wire:click="confirmDeleteFile({{ $file->id }})" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 mt-4">
                            <i class="fas fa-cloud-upload-alt text-green-400 text-3xl mb-3"></i>
                            <p class="text-green-600 text-sm">No deliverables uploaded yet. Use the upload area above to add files.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Response to Feedback Section (if applicable) -->
            @if($pitch->status === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED)
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
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Project Activity Summary (hidden on mobile, visible on desktop) -->
            <div class="hidden lg:block">
                <x-client-project.activity-summary :pitch="$pitch" :project="$project" :component="$this" />
            </div>
            
            <!-- Client Management Details -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-purple-800 mb-3">
                    <i class="fas fa-briefcase mr-2"></i>Client Details
                </h3>
                <div class="space-y-2 text-sm">
                    <div><strong>Client Name:</strong> {{ $project->client_name ?? 'N/A' }}</div>
                    <div><strong>Client Email:</strong> {{ $project->client_email ?? 'N/A' }}</div>
                    @if($project->payment_amount > 0)
                    <div><strong>Payment Amount:</strong> ${{ number_format($project->payment_amount, 2) }}</div>
                    @endif
                </div>
                <div class="space-y-2 mt-3">
                    <button wire:click="resendClientInvite" class="btn btn-sm btn-outline btn-primary w-full">
                        <i class="fas fa-paper-plane mr-1"></i> Resend Client Invite
                    </button>
                    <button wire:click="previewClientPortal" class="btn btn-sm btn-outline btn-info w-full">
                        <i class="fas fa-external-link-alt mr-1"></i> Preview Client Portal
                    </button>
                </div>
                <p class="text-xs text-purple-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Preview shows exactly what your client sees when they click the email link.
                </p>
            </div>

            <!-- Project Actions -->
            <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
                <h3 class="text-lg font-semibold mb-3">
                    <i class="fas fa-cog mr-2"></i>Project Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('projects.edit', $project) }}" 
                       class="btn btn-outline btn-warning w-full">
                        <i class="fas fa-edit mr-2"></i>Edit Project Details
                    </a>
                    <a href="{{ route('projects.show', $project) }}" 
                       class="btn btn-outline btn-info w-full">
                        <i class="fas fa-eye mr-2"></i>View Public Page
                    </a>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-red-800 mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                </h3>
                <p class="text-sm text-red-700 mb-3">
                    Permanently delete this project and all associated files. This action cannot be undone.
                </p>
                <button wire:click="confirmDeleteProject" 
                        class="btn btn-error btn-sm w-full">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Project
                </button>
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
</div> 