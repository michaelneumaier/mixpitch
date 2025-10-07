<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg overflow-hidden">
    <!-- Image Preview Container -->
    <div class="image-preview-container" x-data="{ isModalOpen: @entangle('isModalOpen') }">
        
        <!-- Thumbnail/Preview Image -->
        <div class="relative group cursor-pointer" wire:click="toggleModal">
            <img src="{{ $this->getFileUrl() }}" 
                 alt="{{ $file->file_name }}"
                 class="w-full h-auto max-h-96 object-contain rounded-lg">
            
            <!-- Overlay with view full size option -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <div class="bg-white bg-opacity-90 rounded-full p-3">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Details -->
        <div class="p-4 border-t">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h3 class="text-lg font-semibold truncate">{{ $file->file_name }}</h3>
                    <p class="text-sm text-gray-500">{{ $file->formatted_size }}</p>
                </div>
                <div class="flex space-x-2">
                    <!-- Comment Button -->
                    @if($this->canAddComments())
                        <button wire:click="toggleCommentForm" 
                                class="text-gray-600 hover:text-gray-800 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif

                    <!-- Download Button -->
                    <a href="{{ $this->getDownloadUrl() }}" 
                       target="_blank"
                       class="text-gray-600 hover:text-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Full Size Button -->
                    <button wire:click="toggleModal" 
                            class="text-gray-600 hover:text-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Full Size Modal -->
        <div x-show="isModalOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
             @click="$wire.toggleModal()"
             @keydown.escape.window="$wire.toggleModal()">
            
            <div class="relative max-w-full max-h-full" @click.stop>
                <img src="{{ $this->getFileUrl() }}" 
                     alt="{{ $file->file_name }}"
                     class="max-w-full max-h-full object-contain">
                
                <!-- Close Button -->
                <button wire:click="toggleModal" 
                        class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    @if($showComments && ($this->canShowComments() || $this->canAddComments()))
        <div class="p-6 border-t">
            <h3 class="text-lg font-semibold mb-4">Comments</h3>
            
            <!-- Add Comment Form -->
            @if($showAddCommentForm)
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Add a comment about this image
                        </label>
                    </div>
                    <textarea wire:model="newComment" 
                              placeholder="Add your comment..."
                              class="w-full p-2 border rounded-lg resize-none" 
                              rows="3"></textarea>
                    <div class="mt-2 flex space-x-2">
                        <button wire:click="addComment" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Add Comment
                        </button>
                        <button wire:click="toggleCommentForm" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Cancel
                        </button>
                    </div>
                </div>
            @endif

            <!-- Comments List -->
            @if($this->canShowComments())
                <div class="space-y-4">
                    @foreach($comments as $comment)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <strong>{{ $comment->user->name ?? 'Client' }}</strong>
                                    <span class="text-sm text-gray-500 ml-2">
                                        {{ $comment->created_at_for_user->diffForHumans() }}
                                    </span>
                                </div>
                                @if($this->canAddComments())
                                    <div class="flex space-x-2">
                                        @if($fileType === 'pitch_file')
                                            <button wire:click="toggleResolveComment({{ $comment->id }})"
                                                    class="text-sm {{ $comment->resolved ? 'text-green-600' : 'text-gray-500' }}">
                                                {{ $comment->resolved ? 'Resolved' : 'Mark Resolved' }}
                                            </button>
                                        @endif
                                        <button wire:click="confirmDelete({{ $comment->id }})"
                                                class="text-sm text-red-600">
                                            Delete
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <p class="text-gray-700">{{ $comment->comment }}</p>
                            
                            <!-- Replies -->
                            @if($comment->replies->isNotEmpty())
                                <div class="mt-3 ml-4 space-y-2">
                                    @foreach($comment->replies as $reply)
                                        <div class="border-l-2 border-gray-200 pl-3">
                                            <strong>{{ $reply->user->name ?? 'Client' }}</strong>
                                            <span class="text-sm text-gray-500 ml-2">
                                                {{ $reply->created_at->diffForHumans() }}
                                            </span>
                                            <p class="text-gray-600">{{ $reply->comment }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Reply Form -->
                            @if($showReplyForm && $replyToCommentId === $comment->id)
                                <div class="mt-3 ml-4">
                                    <textarea wire:model="replyText" 
                                              placeholder="Add your reply..."
                                              class="w-full p-2 border rounded-lg resize-none" 
                                              rows="2"></textarea>
                                    <div class="mt-2 flex space-x-2">
                                        <button wire:click="submitReply" 
                                                class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                                            Reply
                                        </button>
                                        <button wire:click="toggleReplyForm" 
                                                class="px-3 py-1 bg-gray-300 text-gray-700 rounded text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            @else
                                @if($this->canAddComments())
                                    <button wire:click="toggleReplyForm({{ $comment->id }})"
                                            class="mt-2 text-sm text-blue-600">
                                        Reply
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                @if($this->canAddComments())
                    <p class="text-gray-500 text-center py-4">No comments yet. Be the first to comment!</p>
                @endif
            @endif
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
                <h3 class="text-lg font-semibold mb-4">Delete Comment?</h3>
                <p class="text-gray-600 mb-4">This action cannot be undone.</p>
                <div class="flex space-x-2">
                    <button wire:click="deleteComment" 
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Delete
                    </button>
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>