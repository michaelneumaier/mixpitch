{{-- blade-formatter-disable --}}
<!-- Enhanced Glass Morphism Audio Player Container -->
<div class="waveform-player-container relative">
    <!-- Background Effects for Audio Theme -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none rounded-2xl">
        <div class="absolute top-4 left-4 w-16 h-16 bg-purple-400/20 rounded-full blur-xl"></div>
        <div class="absolute bottom-4 right-4 w-12 h-12 bg-indigo-400/20 rounded-full blur-lg"></div>
        <div class="absolute top-1/2 left-1/3 w-8 h-8 bg-pink-400/15 rounded-full blur-lg"></div>
    </div>

    <!-- Main Glass Morphism Container -->
    <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 via-indigo-600/5 to-purple-600/5"></div>
        
        <!-- Enhanced File Header with Glass Morphism -->
        <div class="relative z-10 bg-gradient-to-r from-purple-50/90 to-indigo-50/90 backdrop-blur-sm border-b border-purple-200/50">
            <div class="px-6 py-5">
                <div class="flex flex-row justify-between items-start md:items-center gap-4">
                    <!-- Enhanced File Info Section -->
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg">
                            <i class="fas fa-file-audio text-white text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                                {{ $file->file_name }}
                            </h2>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="fas fa-clock text-purple-500 mr-1"></i>
                                Added {{ $file->created_at->diffForHumans() }} 
                                <span class="mx-2">•</span>
                                <i class="fas fa-file text-indigo-500 mr-1"></i>
                                {{ $file->formattedSize }}
                            </p>
                        </div>
                    </div>
                    
                    @if($isInCard ?? false)
                        {{-- Minimal buttons for card view --}}
                    @else
                        {{-- Enhanced controls for dedicated view --}}
                        <div class="flex space-x-3 items-center">
                            <!-- Download Button -->
                            <!-- <a href="{{ route('pitch-files.download', ['file' => $file->uuid]) }}" 
                               class="inline-flex items-center px-3 sm:px-2 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                <i class="fas fa-download md:px-2"></i>
                                <span class="hidden md:inline px-2">Download</span>
                            </a> -->
                            
                            @if(auth()->check() && auth()->user()->can('delete', $file))
                                <!-- Delete Button -->
                                <button wire:click="$dispatch('open-delete-modal', { fileId: {{ $file->id }} })" 
                                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-trash-alt mr-2"></i> Delete
                                </button>
                            @endif
                            

                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Enhanced Audio Content Container -->
        <div class="relative z-10 p-4 lg:p-6 flex flex-row items-start gap-6">
                         <!-- Play Controls Container -->
             <div class="flex flex-col items-center min-w-[80px] h-32 relative" wire:ignore>
                                <!-- Enhanced Play/Pause Button - Centered to waveform -->
                                <button id="playPauseBtn" 
                                        x-data="{ isPlaying: false }"
                                        x-on:click="isPlaying = !isPlaying; $dispatch('toggle-playback', { playing: isPlaying })"
                                        x-on:playback-state-changed.window="isPlaying = $event.detail.playing"
                                        class="group absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-14 h-14 bg-gradient-to-br from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 flex items-center justify-center">
                                    
                                    <!-- Animated Background Effect -->
                                    <div class="absolute inset-0 bg-white/20 rounded-2xl transform scale-0 group-hover:scale-100 transition-transform duration-300"></div>
                                    
                                    <!-- Play icon (shown when paused) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="relative z-10 h-14 w-14" x-show="!isPlaying" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>

                                    <!-- Pause icon (shown when playing) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="relative z-10 h-14 w-14" x-show="isPlaying" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>

                                <!-- Enhanced Time Display - Bottom aligned -->
                                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-center">
                                    <div class="flex items-center justify-center space-x-1 text-sm font-medium font-mono">
                                        <div id="currentTime" class="text-purple-700 w-12 text-center">00:00</div>
                                        <div class="text-gray-400">/</div>
                                        <div id="totalDuration" class="text-gray-600 w-12 text-center">00:00</div>
                                    </div>
                                </div>
                            </div>
            <!-- Waveform Container -->
            <div class="waveform-container flex-grow">
                    <!-- Waveform Header -->
                    <!-- <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                            <i class="fas fa-wave-square text-white text-sm"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Audio Waveform</h3>
                        <div class="ml-auto flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-music mr-1"></i>High Quality
                            </span>
                        </div>
                    </div> -->                            
                    <!-- Enhanced Waveform Visualization -->
                    <div class="relative">
                        <!-- Floating Add Comment Button (follows playhead) -->
                        <div id="floating-comment-btn" 
                             class="absolute -top-6 left-0 transform -translate-x-1/2 opacity-0 transition-all duration-200 z-20 pointer-events-auto">
                            <button type="button" 
                                    @click="$wire.toggleCommentForm(wavesurfer ? wavesurfer.getCurrentTime() : 0)"
                                    class="group w-7 h-7 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110 flex items-center justify-center"
                                    title="Add comment at current position">
                                <i class="fas fa-plus text-xs group-hover:scale-110 transition-transform"></i>
                            </button>
                            <!-- Tooltip for desktop -->
                            <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                                <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                                    Add comment here
                                </div>
                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>
                        
                        <div id="waveform" class="h-32 rounded-xl overflow-hidden bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200/50 shadow-inner" wire:ignore>
                            <!-- Waveform will be rendered here -->
                        </div>

                        <!-- Enhanced Comment Markers -->
                        <div class="comment-markers absolute inset-0 pointer-events-none">
                            @if($duration > 0)
                            @foreach($comments as $comment)
                            @php
                            $position = ($comment->timestamp / max(0.1, $duration)) * 100;
                            $position = min(max($position, 0), 100);
                            $tooltipClass = $position < 15 ? 'left-0 transform-none' : ($position > 85 ? 'left-auto right-0 transform-none' : 'left-0 transform -translate-x-1/2');
                            @endphp
                            <div class="absolute h-full w-1 z-10 cursor-pointer pointer-events-auto group"
                                style="left: {{ $position }}%; background: linear-gradient(to bottom, #7c3aed, #4f46e5);"
                                x-data="{ showTooltip: false }" 
                                @mouseenter="showTooltip = true"
                                @mouseleave="showTooltip = false"
                                @click="$dispatch('comment-marker-clicked', { timestamp: {{ $comment->timestamp }} })">
                                
                                <!-- Enhanced Comment Marker -->
                                <div class="h-4 w-4 rounded-full -ml-1.5 bg-gradient-to-br from-purple-500 to-indigo-600 border-2 border-white shadow-lg absolute -top-1 group-hover:scale-125 transition-all duration-200">
                                    <div class="absolute inset-0 rounded-full bg-white/30 animate-pulse"></div>
                                </div>

                                <!-- Enhanced Comment Tooltip -->
                                <div x-show="showTooltip" x-cloak
                                    class="absolute bottom-full mb-3 p-3 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20 w-72 z-50 {{ $tooltipClass }}"
                                    @click.stop>
                                    <!-- Tooltip Header -->
                                    <div class="flex items-center mb-2">
                                        <img src="{{ $comment->user->profile_photo_url }}"
                                            alt="{{ $comment->user->name }}" 
                                            class="h-6 w-6 rounded-full border-2 border-purple-200 mr-2">
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold text-gray-900">{{ $comment->user->name }}</div>
                                            <div class="text-xs text-purple-600 font-medium">{{ $comment->formattedTimestamp }}</div>
                                        </div>
                                        @if($comment->resolved)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Resolved
                                        </span>
                                        @endif
                                    </div>
                                    <!-- Comment Content -->
                                    <div class="text-sm text-gray-800 bg-gradient-to-r from-purple-50/50 to-indigo-50/50 rounded-lg p-2">
                                        {{ \Illuminate\Support\Str::limit($comment->comment, 120) }}
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Enhanced Timeline -->
                    <!-- <div id="waveform-timeline" class="h-8 mt-4 relative bg-gradient-to-r from-purple-50/80 to-indigo-50/80 backdrop-blur-sm border border-purple-200/50 rounded-lg" wire:ignore>
                    </div> -->



                    <!-- Enhanced Add Comment Form -->
                    <div x-data="{ show: @entangle('showAddCommentForm') }" x-show="show" x-cloak
                         class="mt-6 relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-blue-600/5"></div>
                        
                        <div class="relative z-10 p-6">
                            <!-- Form Header -->
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mr-3 shadow-lg">
                                    <i class="fas fa-comment-plus text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Add New Comment</h3>
                                    <p class="text-sm text-blue-600 font-medium flex items-center">
                                        <i class="fas fa-clock mr-1"></i>
                                        At {{ sprintf("%02d:%02d", floor($commentTimestamp / 60), $commentTimestamp % 60) }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Enhanced Textarea -->
                            <div class="bg-gradient-to-r from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4">
                                <textarea wire:model="newComment" 
                                          placeholder="Share your thoughts about this moment in the audio..."
                                          class="w-full px-4 py-3 text-gray-700 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200"
                                          rows="3"></textarea>
                                
                                <!-- Action Buttons -->
                                <div class="flex justify-end mt-4 space-x-3">
                                    <button type="button" 
                                            @click="show = false; $wire.showAddCommentForm = false"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                    <button type="button" 
                                            wire:click="addComment" 
                                            class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-plus mr-2"></i>Add Comment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- Simplified Comments Section - No separate header card -->
    <div class="comments-section mt-8">
        <!-- Simple Discussion Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                        <i class="fas fa-comments text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                            Discussion
                        </h3>
                        <p class="text-gray-600">
                            <span class="font-semibold">{{ count($comments) }}</span> 
                            {{ count($comments) === 1 ? 'comment' : 'comments' }} on this audio
                        </p>
                    </div>
                </div>
                
                <!-- Comment Stats & Actions -->
                <div class="flex items-center space-x-4">
                    @php
                        $resolvedCount = $comments->where('resolved', true)->count();
                        $pendingCount = $comments->where('resolved', false)->count();
                    @endphp
                    
                    @if($resolvedCount > 0)
                    <div class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>{{ $resolvedCount }} Resolved
                    </div>
                    @endif
                    
                    @if($pendingCount > 0)
                    <div class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                        <i class="fas fa-clock mr-1"></i>{{ $pendingCount }} Pending
                    </div>
                    @endif
                    
                    <!-- Add Comment Button -->
                    <button type="button" 
                            @click="$wire.toggleCommentForm(wavesurfer ? wavesurfer.getCurrentTime() : 0)"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                        <i class="fas fa-comment-plus mr-2"></i>Add Comment
                    </button>
                </div>
            </div>
        </div>

        @if(count($comments) > 0)
        <div class="space-y-6">
            @foreach($comments as $comment)
            <!-- Enhanced Individual Comment Card -->
            <div id="comment-{{ $comment->id }}"
                 class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-lg overflow-hidden {{ $comment->resolved ? 'ring-2 ring-green-200/50' : '' }}">
                <!-- Comment Status Indicator -->
                @if($comment->resolved)
                <div class="absolute top-4 right-4 z-20">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Resolved
                    </span>
                </div>
                @endif

                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-r {{ $comment->resolved ? 'from-green-600/5 to-emerald-600/5' : 'from-purple-600/5 to-indigo-600/5' }}"></div>
                
                <div class="relative z-10 p-6">
                    <div class="flex items-start space-x-4">
                        <!-- Enhanced User Avatar -->
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <img src="{{ $comment->user->profile_photo_url }}" 
                                     alt="{{ $comment->user->name }}"
                                     class="h-12 w-12 rounded-xl border-2 {{ $comment->resolved ? 'border-green-200' : 'border-purple-200' }} shadow-lg">
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 {{ $comment->resolved ? 'bg-green-500' : 'bg-purple-500' }} rounded-full border-2 border-white"></div>
                            </div>
                        </div>

                        <div class="flex-grow">
                            <!-- Comment Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">{{ $comment->user->name }}</h4>
                                    <div class="flex items-center text-sm text-gray-600 mt-1">
                                        <i class="fas fa-clock text-purple-500 mr-1"></i>
                                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                                        <span class="mx-2">•</span>
                                        <button type="button" 
                                                @click="$wire.seekTo({{ $comment->timestamp }})"
                                                class="inline-flex items-center font-medium text-purple-600 hover:text-purple-800 transition-colors">
                                            <i class="fas fa-play-circle mr-1"></i>
                                            {{ $comment->formattedTimestamp }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2">
                                    <!-- Reply Button -->
                                    <button type="button" 
                                            @click="$wire.toggleReplyForm({{ $comment->id }})"
                                            class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                        <i class="fas fa-reply mr-1"></i>Reply
                                    </button>

                                    @if(Auth::id() === $comment->user_id || Auth::id() === $file->pitch->user_id)
                                    <!-- Resolve Toggle Button -->
                                    <button type="button" 
                                            @click="$wire.toggleResolveComment({{ $comment->id }})"
                                            class="inline-flex items-center px-3 py-2 bg-gradient-to-r {{ $comment->resolved ? 'from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700' : 'from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800' }} text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                        <i class="fas {{ $comment->resolved ? 'fa-undo' : 'fa-check' }} mr-1"></i>
                                        {{ $comment->resolved ? 'Unresolve' : 'Resolve' }}
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            @click="$wire.confirmDelete({{ $comment->id }})"
                                            class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                        <i class="fas fa-trash-alt mr-1"></i>Delete
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Comment Content -->
                            <div class="bg-gradient-to-r {{ $comment->resolved ? 'from-green-50/80 to-emerald-50/80 border-green-200/50' : 'from-purple-50/80 to-indigo-50/80 border-purple-200/50' }} backdrop-blur-sm border rounded-xl p-4 mb-4">
                                <p class="text-gray-800 whitespace-pre-line leading-relaxed">{{ $comment->comment }}</p>
                            </div>

                            <!-- Reply Form -->
                            @if($showReplyForm && $replyToCommentId === $comment->id)
                            <div class="mt-4 bg-gradient-to-r from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4">
                                <h5 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-reply text-blue-600 mr-2"></i>
                                    Reply to {{ $comment->user->name }}
                                </h5>
                                <textarea wire:model="replyText" 
                                          placeholder="Write your reply here..."
                                          class="w-full px-4 py-3 text-gray-700 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200"
                                          rows="3"></textarea>
                                <div class="flex justify-end mt-3 space-x-3">
                                    <button type="button" 
                                            wire:click="toggleReplyForm"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                    <button type="button" 
                                            wire:click="submitReply" 
                                            class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Reply
                                    </button>
                                </div>
                            </div>
                            @endif

                            <!-- Enhanced Replies Section -->
                            @if($comment->has_replies)
                            <div class="mt-6 space-y-4 pl-6 border-l-2 border-purple-200/50">
                                @foreach($comment->replies as $reply)
                                <div id="comment-{{ $reply->id }}" class="relative bg-gradient-to-r from-white/90 to-purple-50/90 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4 shadow-sm">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $reply->user->profile_photo_url }}" 
                                                 alt="{{ $reply->user->name }}"
                                                 class="h-8 w-8 rounded-lg border-2 border-purple-200 shadow-sm">
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h6 class="font-medium text-gray-900 text-sm">{{ $reply->user->name }}</h6>
                                                    <p class="text-xs text-gray-600">{{ $reply->created_at->diffForHumans() }}</p>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" 
                                                            @click="$wire.toggleReplyForm({{ $reply->id }})"
                                                            class="inline-flex items-center px-2 py-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                                        <i class="fas fa-reply mr-1"></i>Reply
                                                    </button>
                                                    @if(Auth::id() === $reply->user_id || Auth::id() === $file->pitch->user_id)
                                                    <button type="button" 
                                                            @click="$wire.confirmDelete({{ $reply->id }})"
                                                            class="inline-flex items-center px-2 py-1 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-800 whitespace-pre-line">{{ $reply->comment }}</p>

                                            <!-- Nested Reply Form -->
                                            @if($showReplyForm && $replyToCommentId === $reply->id)
                                            <div class="mt-3 bg-gradient-to-r from-blue-50/60 to-purple-50/60 backdrop-blur-sm border border-blue-200/50 rounded-lg p-3">
                                                <textarea wire:model="replyText" 
                                                          placeholder="Write your reply here..."
                                                          class="w-full px-3 py-2 text-sm text-gray-700 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition-all duration-200"
                                                          rows="2"></textarea>
                                                <div class="flex justify-end mt-2 space-x-2">
                                                    <button type="button" 
                                                            wire:click="toggleReplyForm"
                                                            class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                                        Cancel
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="submitReply"
                                                            class="inline-flex items-center px-4 py-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 text-xs">
                                                        <i class="fas fa-paper-plane mr-1"></i>Reply
                                                    </button>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <!-- Enhanced Empty State -->
        <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 via-indigo-600/5 to-purple-600/5"></div>
            <div class="relative z-10 text-center py-12 px-6">
                <!-- Empty State Icon -->
                <div class="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full mx-auto mb-6 shadow-lg">
                    <i class="fas fa-comments text-white text-2xl"></i>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Start the Conversation</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    No comments yet. Be the first to share your thoughts about this audio file and help improve the creative process.
                </p>
                
                <!-- Call to Action Button -->
                <button type="button" 
                        @click="$wire.toggleCommentForm(wavesurfer ? wavesurfer.getCurrentTime() : 0)"
                        class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-comment-plus mr-3 group-hover:scale-110 transition-transform"></i>
                    Add Your First Comment
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Enhanced Delete Comment Confirmation Modal -->
    <div x-data="{ show: @entangle('showDeleteConfirmation') }" x-show="show" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen text-center sm:block sm:p-0">
            <!-- Enhanced Background overlay -->
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-black/50 backdrop-blur-sm" aria-hidden="true"></div>

            <!-- Enhanced Modal panel -->
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl px-6 pt-6 pb-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-red-600/5 via-pink-600/5 to-red-600/5"></div>
                
                <div class="relative z-10">
                    <div class="sm:flex sm:items-start">
                        <!-- Enhanced Warning Icon -->
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 shadow-lg sm:mx-0 sm:h-12 sm:w-12">
                            <i class="fas fa-exclamation-triangle text-white text-xl sm:text-lg"></i>
                        </div>
                        
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-xl leading-6 font-bold text-gray-900 mb-2" id="modal-title">
                                Delete Comment
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    Are you sure you want to delete this comment? This action cannot be undone and will also remove all replies to this comment permanently.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Action Buttons -->
                    <div class="mt-6 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                        <button type="button" 
                                wire:click="deleteComment"
                                class="w-full inline-flex justify-center items-center px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg sm:w-auto">
                            <i class="fas fa-trash-alt mr-2"></i>Delete Forever
                        </button>
                        <button type="button" 
                                wire:click="cancelDelete"
                                class="mt-3 w-full inline-flex justify-center items-center px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 sm:mt-0 sm:w-auto">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let wavesurfer;
    let readyFired = false;
    let persistedDuration = 0;
    let audioLoaded = false;
    let audioLoadPromise = null; // Add this to track the loading promise
    let lastPlayedPosition = 0; // Track the last played position

    // Helper function to format time
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // We need to ensure we initialize audio properly, avoiding duplicate loads
    const initializeAudio = (audioUrl) => {
        // If we've already started loading the audio, return the existing promise
        if (audioLoadPromise) {
            console.log('Audio loading already in progress, reusing promise');
            return audioLoadPromise;
        }

        // If already loaded, just resolve immediately
        if (audioLoaded) {
            console.log('Audio already loaded, resolving immediately');
            return Promise.resolve();
        }

        console.log('Starting new audio load');

        // Create the loading promise
        audioLoadPromise = new Promise((resolve) => {
            // Set flag before loading to prevent race conditions
            audioLoaded = true;

            // Create an audio element we can control (this alone might trigger a preload)
            const audio = new Audio();
            audio.preload = 'none'; // Try to prevent auto-loading
            wavesurfer.options.media = audio;

            // Set up the ready handler first
            wavesurfer.once('ready', () => {
                console.log('WaveSurfer ready event fired from audio load');
                persistedDuration = wavesurfer.getDuration();
                document.getElementById('totalDuration').textContent = formatTime(persistedDuration);

                // Update comment markers with the actual duration
                updateCommentMarkers(persistedDuration);

                // Mark our promise as resolved - this is a custom property
                audioLoadPromise.isResolved = true;

                resolve();
            });

            // Then load the URL
            console.log('Calling WaveSurfer load with URL');
            wavesurfer.load(audioUrl);
        });

        return audioLoadPromise;
    };

    // Helper function to update comment markers when duration changes
    const updateCommentMarkers = (duration) => {
        console.log('updateCommentMarkers called with duration:', duration);

        // Check for valid duration
        if (!duration || isNaN(duration) || duration <= 0) {
            console.warn('Invalid duration value:', duration);
            duration = 1; // Fallback to prevent division by zero
        }

        // Update the Livewire component's duration property using the instance ID finder method
        const livewireComponent = Livewire.find('{{ $_instance->getId() }}');
        if (livewireComponent) {
            livewireComponent.set('duration', duration);
        } else {
            console.error("Could not find Livewire component instance to update duration.");
        }

        // Force a refresh to update the comment markers (may need adjustment based on how comments load)
        // If comments are part of the main component render cycle, setting duration might be enough.
        // If they need explicit refresh, dispatching an event might be better.
        Livewire.dispatch('refresh'); // Consider if this is the best approach or if a targeted event is better
        console.log('Comment markers update triggered with duration:', duration);
    };

    document.addEventListener('livewire:initialized', () => {
        console.log('Initializing WaveSurfer component');
        const livewireComponentId = '{{ $_instance->getId() }}'; // Get the Livewire component ID

        // Debounce function to limit UI updates or other operations
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Create a function to dispatch critical Livewire events with debouncing
        const dispatchLivewireEvent = debounce((eventName, data = {}) => {
             const component = Livewire.find(livewireComponentId);
             if (component) {
                 component.dispatch(eventName, data);
             } else {
                 // Fallback or global dispatch if needed, though usually targetted is better
                 Livewire.dispatch(eventName, data);
             }
        }, 500); // Dispatch at most every 500ms

        // Track current timestamp in a variable without sending to server
        let currentPlayerTime = 0;

        // Initialize WaveSurfer
        wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: 'rgba(168, 85, 247, 0.6)',
            progressColor: 'rgba(139, 92, 246, 0.8)',
            cursorColor: 'rgba(99, 102, 241, 0.9)',
            barWidth: 2,
            barRadius: 1,
            responsive: true,
            height: 128,
            normalize: true,
            backend: 'WebAudio',
            mediaControls: false
        });

        // Initialize button position early
        setTimeout(() => {
            updateFloatingButtonPosition();
        }, 100);

        // Load audio file
        const audioUrl = @js($file->fullFilePath);
        console.log('Loading audio URL:', audioUrl);

        // Check if we have pre-generated waveform data
        const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);

        if (hasPreGeneratedPeaks) {
            console.log('Using pre-generated waveform data');
            // Load audio with pre-generated peaks
            const peaks = @js($file->waveform_peaks_array);

            // Debug the peaks data
            console.log('Peaks data type:', typeof peaks);
            console.log('Is peaks an array?', Array.isArray(peaks));
            console.log('Peaks sample:', peaks && peaks.length > 0 ? peaks.slice(0, 10) : peaks);

            // Set peaks directly - this will visualize without loading audio
            if (peaks && Array.isArray(peaks[0])) {
                // We have min/max peaks format
                const minPeaks = peaks.map(point => point[0]);
                const maxPeaks = peaks.map(point => point[1]);

                // Initialize the waveform with the pre-generated peaks
                // Use options instead of setPeaks to avoid auto-loading
                wavesurfer.options.peaks = [minPeaks, maxPeaks];
                console.log('Set min/max peaks');

                // Use the stored duration if available, otherwise estimate
                const storedDuration = @js($file->duration ?? null);

                // Set a fake duration based on the peaks array length if stored one is not available
                const fakeLength = maxPeaks && maxPeaks.length ? maxPeaks.length : 0;
                // Avoid division by zero and ensure we have a positive value
                const estimatedDuration = fakeLength > 0 ? (fakeLength / 67) * 60 : 60; // Default to 60 seconds if we can't calculate

                // Use actual duration if available, otherwise use estimate
                const displayDuration = storedDuration || estimatedDuration;
                console.log('Duration info:', { stored: storedDuration, estimated: estimatedDuration, using: displayDuration });

                wavesurfer.options.duration = displayDuration;
                persistedDuration = displayDuration;

                // Force a redraw to show the peaks
                if (typeof wavesurfer.drawBuffer === 'function') {
                    wavesurfer.drawBuffer();
                }

                 // Mark waveform as "ready-like" state for the visualization
                 setTimeout(() => {
                     document.getElementById('waveform').classList.add('loaded');
                     document.getElementById('totalDuration').textContent = formatTime(displayDuration);

                     // Update comment markers with the duration
                     updateCommentMarkers(displayDuration);

                     // Set initial state (paused) - important for peak-only load
                     window.dispatchEvent(new CustomEvent('playback-state-changed', {
                         detail: { playing: false }
                     }));
                 }, 100);
             } else {
                console.log('Peaks format is not as expected, falling back to normal loading');
                initializeAudio(audioUrl); // Call initializeAudio here
             }

            // Audio will be loaded on first play - audioLoaded is false initially
        } else {
             console.log('No pre-generated waveform data available, generating on the fly');
             initializeAudio(audioUrl); // Call initializeAudio here
        }

        // Listen for the Alpine.js play/pause toggle event
        window.addEventListener('toggle-playback', (event) => {
            console.log('Toggle playback event received:', event.detail.playing);

            try {
                if (event.detail.playing) {
                    // Check if audio is already loaded (only relevant if peaks were pre-gen)
                    if (hasPreGeneratedPeaks && !audioLoaded) {
                        console.log('First play - loading audio');

                        // Initialize audio and then play
                        initializeAudio(audioUrl).then(() => { // Pass audioUrl
                            console.log('Audio loaded, starting playback');
                            setTimeout(() => {
                                wavesurfer.play();
                                // Ensure the time display is updated after playback starts
                                updateTimeDisplay();
                            }, 100);
                        });

                        return; // Wait for audio to load before playing
                    }

                    // Audio might be in process of loading even if audioLoaded flag is true
                    // Or if peaks weren't pre-generated, initializeAudio might have been called earlier
                    if (audioLoadPromise && !audioLoadPromise.isResolved) {
                        console.log('Audio still loading, waiting to play');
                        audioLoadPromise.then(() => {
                            console.log('Now playing after audio load completed');
                            wavesurfer.play();
                        });
                        return;
                    }

                    // Normal play when audio is already loaded or load wasn't needed initially
                    console.log('Playing with already loaded audio or pre-generated peaks');
                    const playPromise = wavesurfer.play();

                    // Modern browsers return a promise from audio.play()
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                console.log('Playback started successfully');
                            })
                            .catch(error => {
                                console.error('Playback failed:', error);
                                // If playback fails, update UI to reflect paused state
                                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                                    detail: { playing: false }
                                }));
                            });
                    }
                } else {
                    wavesurfer.pause();
                    console.log('Playback paused');
                }
            } catch (e) {
                console.error('Error toggling playback:', e);
                // If there's an error, ensure UI reflects paused state
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));
            }
        });

        // Before unloading the page, stop any audio to prevent memory leaks
        window.addEventListener('beforeunload', () => {
            if (wavesurfer) {
                wavesurfer.pause();
                // Don't destroy as it might cause errors if already in process of unloading
            }
        });

        wavesurfer.on('ready', () => {
            console.log('WaveSurfer ready event fired');

            // Get actual duration or fallback to estimated duration if needed
            persistedDuration = wavesurfer.getDuration() || persistedDuration; // Use WS duration if available
            document.getElementById('totalDuration').textContent = formatTime(persistedDuration);
            document.getElementById('waveform').classList.add('loaded');

            // Initialize floating button position
            updateFloatingButtonPosition();

            // Prevent duplicate handling if loading audio after setting peaks
            if (!readyFired) {
                readyFired = true;

                // Set initial state (paused) if not already set by peak load
                 if (!hasPreGeneratedPeaks) {
                     window.dispatchEvent(new CustomEvent('playback-state-changed', {
                         detail: { playing: false }
                     }));
                 }

                // Update comment markers with the actual duration
                updateCommentMarkers(persistedDuration);

                // Use a deferred dispatch to avoid batching with other updates
                setTimeout(() => {
                    // Dispatch our custom event to signal waveform is ready
                    dispatchLivewireEvent('waveformReady');
                }, 100);
            }
        });

        // Smooth button position updates
        let buttonUpdateAnimationId = null;
        
        const updateFloatingButtonPosition = () => {
            const floatingBtn = document.getElementById('floating-comment-btn');
            const waveformContainer = document.getElementById('waveform');
            
            if (floatingBtn && waveformContainer && wavesurfer) {
                let buttonPosition = 0; // Start at true 0%
                
                // Try multiple selectors to find WaveSurfer's cursor
                const possibleCursors = [
                    waveformContainer.querySelector('.wavesurfer-cursor'),
                    waveformContainer.querySelector('[style*="cursor"]'),
                    waveformContainer.querySelector('wave'),
                    ...waveformContainer.querySelectorAll('div')
                ].filter(el => el && el.style && el.style.left);
                
                let cursorElement = null;
                for (const cursor of possibleCursors) {
                    if (cursor.style.left && cursor.style.left.includes('%')) {
                        cursorElement = cursor;
                        break;
                    }
                }
                
                if (cursorElement) {
                    // Extract percentage from cursor's left style
                    const leftStyle = cursorElement.style.left;
                    const percentage = parseFloat(leftStyle.replace('%', ''));
                    
                    if (!isNaN(percentage)) {
                        // Use the exact cursor position without artificial limits
                        buttonPosition = percentage;
                        
                        console.log('Button position update (cursor-sync):', {
                            cursorLeft: leftStyle,
                            buttonPosition: buttonPosition.toFixed(2),
                            isPlaying: wavesurfer.isPlaying()
                        });
                    }
                } else {
                    // Fallback to time-based calculation
                    const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;
                    if (duration > 0) {
                        const currentTime = wavesurfer.getCurrentTime() || 0;
                        // Use exact percentage without artificial limits
                        buttonPosition = (currentTime / duration) * 100;
                        
                        console.log('Button position update (time-based):', {
                            currentTime,
                            duration,
                            buttonPosition: buttonPosition.toFixed(2),
                            isPlaying: wavesurfer.isPlaying()
                        });
                    }
                }
                
                // Only apply minimal edge protection to prevent button from going completely off-screen
                buttonPosition = Math.max(0, Math.min(100, buttonPosition));
                
                floatingBtn.style.left = `${buttonPosition}%`;
                
                // Update opacity
                if (wavesurfer.isPlaying()) {
                    floatingBtn.style.opacity = '1';
                } else {
                    floatingBtn.style.opacity = '0.7';
                }
            }
        };
        
        const startButtonPositionUpdates = () => {
            if (buttonUpdateAnimationId) return; // Already running
            
            const animate = () => {
                updateFloatingButtonPosition();
                if (wavesurfer && wavesurfer.isPlaying()) {
                    buttonUpdateAnimationId = requestAnimationFrame(animate);
                } else {
                    buttonUpdateAnimationId = null;
                }
            };
            
            buttonUpdateAnimationId = requestAnimationFrame(animate);
        };
        
        const stopButtonPositionUpdates = () => {
            if (buttonUpdateAnimationId) {
                cancelAnimationFrame(buttonUpdateAnimationId);
                buttonUpdateAnimationId = null;
            }
            // Update position one final time when stopped
            updateFloatingButtonPosition();
        };

        // Ensure current time display is always accurate
        const updateTimeDisplay = () => {
            if (!wavesurfer) return;

            // We need to check if getCurrentTime is null/undefined, not if it's falsy
            // since 0 is a valid time position but evaluates to false in JS
            const currentTime = wavesurfer.getCurrentTime() !== undefined ?
                wavesurfer.getCurrentTime() : lastPlayedPosition;

            // Only update lastPlayedPosition if we have a valid non-zero time or if we're at the start
            if (currentTime > 0 || wavesurfer.isPlaying()) {
                lastPlayedPosition = currentTime;
            }

            // Ensure duration is available, fallback if necessary
             const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

            document.getElementById('currentTime').textContent = formatTime(currentTime);
            document.getElementById('totalDuration').textContent = formatTime(duration);
            
            // Update floating button position if not playing (when playing, it's handled by animation frame)
            if (!wavesurfer || !wavesurfer.isPlaying()) {
                updateFloatingButtonPosition();
            }
        };

        wavesurfer.on('play', () => {
            console.log('WaveSurfer play event');

            // Start smooth button position updates
            startButtonPositionUpdates();

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: true }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackStarted'); // Decide if needed
        });

        wavesurfer.on('pause', () => {
            console.log('WaveSurfer pause event');

            // Stop smooth button position updates
            stopButtonPositionUpdates();

            // Explicitly grab the current time when pausing and store it
            const pausePosition = wavesurfer.getCurrentTime();
            if (pausePosition !== undefined && pausePosition >= 0) { // Check >= 0
                 lastPlayedPosition = pausePosition;
                 console.log('Storing pause position:', lastPlayedPosition);
             }

            // Make sure current time doesn't reset when pausing
            updateTimeDisplay();

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackPaused'); // Decide if needed
        });

        wavesurfer.on('finish', () => {
            console.log('WaveSurfer finish event');

            // Stop smooth button position updates
            stopButtonPositionUpdates();

            // Reset last played position to the start or end? Let's set to end.
             lastPlayedPosition = persistedDuration || 0;
             updateTimeDisplay(); // Show end time

            // Notify Alpine.js about the state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));

            // Notify Livewire
            // dispatchLivewireEvent('playbackFinished'); // Decide if needed
        });

        // Update button position when user seeks (drags playhead)
        wavesurfer.on('seek', () => {
            console.log('WaveSurfer seek event');
            
            // Update button position immediately when seeking
            updateFloatingButtonPosition();
            
            // Also update time display
            updateTimeDisplay();
        });

        // Also listen for interaction events as backup
        wavesurfer.on('interaction', () => {
            console.log('WaveSurfer interaction event');
            updateFloatingButtonPosition();
            updateTimeDisplay();
        });

        // Add click listener to waveform as additional backup
        const waveformClickTarget = document.getElementById('waveform');
        if (waveformClickTarget) {
            waveformClickTarget.addEventListener('click', () => {
                console.log('Waveform click event');
                // Small delay to ensure WaveSurfer has processed the click
                setTimeout(() => {
                    updateFloatingButtonPosition();
                    updateTimeDisplay();
                }, 50);
            });
        }

        // Update time display during playback
        wavesurfer.on('audioprocess', () => {
            const currentTime = wavesurfer.getCurrentTime();
            currentPlayerTime = currentTime; // Store locally without sending to server
             if (currentTime !== undefined && currentTime >= 0) {
                 lastPlayedPosition = currentTime; // Update the last position
             }

            // Update the time display
            updateTimeDisplay();
        });

        // Comment marker click handler
        window.addEventListener('comment-marker-clicked', event => {
            console.log('Comment marker clicked at timestamp:', event.detail.timestamp);
            const timestamp = event.detail.timestamp;
            const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

            if (duration <= 0) {
                 console.warn("Cannot seek, duration unknown or zero.");
                 return;
             }

            // Handle seeking for pre-generated peaks when audio isn't loaded yet
            if (hasPreGeneratedPeaks && !audioLoaded) {
                // Load audio first, then seek when ready
                console.log('Loading audio before seeking from comment marker');

                initializeAudio(audioUrl).then(() => { // Pass audioUrl
                    console.log('Audio loaded from comment marker, seeking to', timestamp);
                    const seekDuration = wavesurfer.getDuration(); // Get fresh duration after load
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                        console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused after seek
                    // Update display after seeking
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });

                // Notify Alpine.js about state change (to paused) immediately
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));

                return;
            }

            // Audio might be in process of loading even if audioLoaded flag is true
            if (audioLoadPromise && !audioLoadPromise.isResolved) {
                console.log('Audio still loading, waiting to seek from comment marker');
                audioLoadPromise.then(() => {
                    console.log('Seeking after audio load completed from comment marker');
                     const seekDuration = wavesurfer.getDuration();
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused after seek
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });
                return;
            }

            // Normal seeking when audio is already loaded
            console.log('Normal seeking from comment marker, audio already loaded');
            wavesurfer.seekTo(timestamp / duration);
            wavesurfer.pause();

            // Update time display
            updateTimeDisplay();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        // Livewire event listeners
        Livewire.on('seekToPosition', ({ timestamp }) => {
            console.log('Livewire event: seekToPosition', timestamp);
            const duration = persistedDuration || (wavesurfer.getDuration && wavesurfer.getDuration()) || 0;

             if (duration <= 0) {
                 console.warn("Cannot seek via Livewire, duration unknown or zero.");
                 return;
             }

            // Handle seeking for pre-generated peaks when audio isn't loaded yet
            if (hasPreGeneratedPeaks && !audioLoaded) {
                // Load audio first, then seek when ready
                console.log('Loading audio before seeking from seekToPosition event');

                initializeAudio(audioUrl).then(() => { // Pass audioUrl
                    console.log('Audio loaded from seekToPosition, seeking to', timestamp);
                    const seekDuration = wavesurfer.getDuration(); // Get fresh duration
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused
                    // Update display after seeking
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });

                // Notify Alpine.js about state change (to paused) immediately
                window.dispatchEvent(new CustomEvent('playback-state-changed', {
                    detail: { playing: false }
                }));

                return;
            }

            // Audio might be in process of loading even if audioLoaded flag is true
            if (audioLoadPromise && !audioLoadPromise.isResolved) {
                console.log('Audio still loading, waiting to seek from seekToPosition');
                audioLoadPromise.then(() => {
                    console.log('Seeking after audio load completed from seekToPosition');
                     const seekDuration = wavesurfer.getDuration();
                     if (seekDuration > 0) {
                         wavesurfer.seekTo(timestamp / seekDuration);
                     } else {
                         console.warn("Cannot seek after load, duration still zero.");
                     }
                     wavesurfer.pause(); // Ensure paused
                    updateTimeDisplay();
                     // Ensure Alpine state is paused
                     window.dispatchEvent(new CustomEvent('playback-state-changed', { detail: { playing: false } }));
                });
                return;
            }

            // Normal seeking when audio is already loaded
            console.log('Normal seeking from seekToPosition, audio already loaded');
            wavesurfer.seekTo(timestamp / duration);
            wavesurfer.pause();

            // Update time display
            updateTimeDisplay();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        Livewire.on('pausePlayback', () => {
            console.log('Livewire event: pausePlayback');
            wavesurfer.pause();

            // Notify Alpine.js about state change
            window.dispatchEvent(new CustomEvent('playback-state-changed', {
                detail: { playing: false }
            }));
        });

        // Listen for comment added event (if needed for refresh)
        Livewire.on('commentAdded', () => {
             console.log('Livewire event: commentAdded - triggering refresh');
             // Check if a full component refresh is desired/needed here
             // Maybe only re-fetch/re-render comments part?
             Livewire.dispatch('refresh'); // Or a more specific event
         });

        // Add timeline implementation
        const timeline = document.querySelector('#waveform-timeline');
        if (timeline) {
            const setupTimeline = (renderDuration) => {
                 if (!renderDuration || isNaN(renderDuration) || renderDuration <= 0) {
                     console.warn("Timeline setup skipped: Invalid duration", renderDuration);
                     return;
                 }
                 timeline.innerHTML = ''; // Clear any existing content

                // Create a container for the timeline
                const container = document.createElement('div');
                container.className = 'timeline-container';
                container.style.position = 'relative';
                container.style.height = '100%';

                // Determine appropriate time interval based on duration
                let interval = 30; // Default 30 seconds

                if (renderDuration < 60) {
                    interval = 10; // 10 seconds for short audio
                } else if (renderDuration < 180) {
                    interval = 20; // 20 seconds for medium audio
                } else if (renderDuration > 600) {
                    interval = 60; // 1 minute for long audio
                }

                // Create time marks at regular intervals
                for (let time = 0; time <= renderDuration; time += interval) {
                    if (time > renderDuration && time !== interval) break; // Avoid mark way past end unless it's the first

                    const percent = time / renderDuration;
                    const mark = document.createElement('div');
                    mark.className = 'timeline-mark';
                    mark.style.left = `${percent * 100}%`;
                    mark.textContent = formatTime(time);

                    container.appendChild(mark);
                }

                // Always add the end time if it's not exactly divisible by the interval and > 0
                 const lastTime = Math.floor(renderDuration);
                 if (lastTime > 0 && lastTime % interval !== 0) {
                     const percent = lastTime / renderDuration;
                     const mark = document.createElement('div');
                     mark.className = 'timeline-mark';
                     mark.style.left = `${percent * 100}%`;
                     // To avoid clutter, only show end time if sufficiently far from last interval mark
                     const lastIntervalTime = Math.floor(lastTime / interval) * interval;
                     if ((lastTime - lastIntervalTime) / renderDuration > 0.05) { // e.g., if end is > 5% past last mark
                         mark.textContent = formatTime(lastTime);
                     }
                     container.appendChild(mark);
                 }


                timeline.appendChild(container);
             };

             // Setup timeline if duration is known from pre-generated peaks
             if (hasPreGeneratedPeaks && persistedDuration > 0) {
                 console.log("Setting up timeline with pre-generated duration:", persistedDuration);
                 setupTimeline(persistedDuration);
             }

             // Also set up timeline when wavesurfer is ready (for non-pre-generated case)
            wavesurfer.on('ready', () => {
                 const readyDuration = wavesurfer.getDuration();
                 console.log("Setting up timeline on ready event with duration:", readyDuration);
                 setupTimeline(readyDuration);
             });

             // Re-render timeline if comments update causes duration change (unlikely but possible)
             Livewire.hook('morph.updated', ({ el, component }) => {
                 // Check if the duration property specifically changed if possible,
                 // or just re-render timeline if this component updates.
                 if (component.id === livewireComponentId) {
                     const currentDuration = persistedDuration || (wavesurfer && wavesurfer.getDuration());
                     if (currentDuration > 0) {
                        // Small delay to ensure DOM might be settled
                        setTimeout(() => setupTimeline(currentDuration), 50);
                     }
                 }
             });
        }
        
        // Keyboard shortcut support
        document.addEventListener('keydown', function(event) {
            // Only trigger if not typing in an input/textarea and 'C' key is pressed
            if (event.key.toLowerCase() === 'c' && 
                !event.target.matches('input, textarea, [contenteditable]') &&
                !event.ctrlKey && !event.metaKey && !event.altKey) {
                
                event.preventDefault();
                
                // Get current time and trigger comment form
                const currentTime = wavesurfer ? wavesurfer.getCurrentTime() : 0;
                
                // Dispatch to Livewire component
                if (window.Livewire) {
                    window.Livewire.find('{{ $this->getId() }}').call('toggleCommentForm', currentTime);
                }
            }
        });
        
        // Add hover effects for waveform
        const waveformHoverTarget = document.getElementById('waveform');
        const floatingBtn = document.getElementById('floating-comment-btn');
        
        if (waveformHoverTarget && floatingBtn) {
            waveformHoverTarget.addEventListener('mouseenter', function() {
                if (!wavesurfer.isPlaying()) {
                    floatingBtn.style.opacity = '1';
                }
            });
            
            waveformHoverTarget.addEventListener('mouseleave', function() {
                if (!wavesurfer.isPlaying()) {
                    floatingBtn.style.opacity = '0.7';
                }
            });
        }
    });
</script>
{{-- blade-formatter-enable --}}

<style>
    /* Enhanced WaveSurfer Styling */
    #waveform {
        opacity: 0;
        transition: all 0.5s ease-in-out;
        border-radius: 12px;
        overflow: hidden;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9, #e2e8f0);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
    }

    #waveform.loaded {
        opacity: 1;
        box-shadow: 
            inset 0 2px 4px rgba(0, 0, 0, 0.06),
            0 4px 6px -1px rgba(0, 0, 0, 0.1), 
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transform: translateY(0);
    }

    /* Enhanced Timeline Styling */
    #waveform-timeline {
        position: relative;
        height: 32px;
        margin-top: 16px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid rgba(168, 85, 247, 0.2);
        border-radius: 12px;
        padding: 6px 12px;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .timeline-mark {
        position: absolute;
        top: 4px;
        font-size: 11px;
        font-weight: 600;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        transform: translateX(-50%);
        text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
    }

    .timeline-container {
        background: linear-gradient(135deg, #faf5ff, #f3e8ff);
        border: 1px solid rgba(168, 85, 247, 0.15);
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* Enhanced Loading Animation */
    .audio-loading {
        background: linear-gradient(
            90deg, 
            rgba(168, 85, 247, 0.1) 25%, 
            rgba(139, 92, 246, 0.2) 50%, 
            rgba(168, 85, 247, 0.1) 75%
        );
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Enhanced Button Hover Effects */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Comment Marker Animations */
    .comment-markers .group:hover {
        z-index: 20;
    }

    /* Enhanced Glass Morphism Effects */
    .backdrop-blur-md {
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .backdrop-blur-sm {
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    /* Pulse Animation for Comment Markers */
    @keyframes pulse-subtle {
        0%, 100% { opacity: 0.8; }
        50% { opacity: 1; }
    }

    .animate-pulse {
        animation: pulse-subtle 2s infinite;
    }

    /* Focus States */
    .focus\:ring-2:focus {
        box-shadow: 0 0 0 2px rgba(168, 85, 247, 0.2);
    }

    /* Smooth Transitions */
    * {
        scroll-behavior: smooth;
    }

    /* Enhanced Modal Effects */
    .modal-backdrop {
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
</style>
@endpush