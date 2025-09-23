<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg overflow-hidden">
    <!-- Video Player Container -->
    <div class="video-player-container relative bg-black" x-data="{
        video: null,
        isPlaying: @json($isPlaying),
        currentPosition: @json($currentPosition),
        duration: @json($duration),
        volume: @json($volume),
        isMuted: @json($isMuted),
        isFullscreen: @json($isFullscreen),
        showControls: true,
        controlsTimeout: null,

        init() {
            this.video = this.$refs.videoElement;
            
            // Video event listeners
            this.video.addEventListener('loadedmetadata', () => {
                this.duration = this.video.duration;
                $wire.updateDuration(this.duration);
                $wire.dispatch('videoReady');
            });

            this.video.addEventListener('timeupdate', () => {
                this.currentPosition = this.video.currentTime;
                $wire.updatePosition(this.currentPosition);
            });

            this.video.addEventListener('play', () => {
                this.isPlaying = true;
                $wire.onPlaybackStarted();
            });

            this.video.addEventListener('pause', () => {
                this.isPlaying = false;
                $wire.onPlaybackPaused();
            });

            this.video.addEventListener('ended', () => {
                this.isPlaying = false;
                $wire.onVideoEnded();
            });

            this.video.addEventListener('volumechange', () => {
                this.volume = this.video.volume;
                this.isMuted = this.video.muted;
            });

            // Auto-hide controls
            this.setupControlsAutoHide();
        },

        togglePlayback() {
            if (this.video.paused) {
                this.video.play();
            } else {
                this.video.pause();
            }
        },

        seekTo(position) {
            this.video.currentTime = position;
        },

        setVolume(volume) {
            this.video.volume = Math.max(0, Math.min(1, volume));
        },

        toggleMute() {
            this.video.muted = !this.video.muted;
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                this.$el.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        },

        setupControlsAutoHide() {
            const container = this.$el;
            
            container.addEventListener('mousemove', () => {
                this.showControls = true;
                clearTimeout(this.controlsTimeout);
                this.controlsTimeout = setTimeout(() => {
                    if (this.isPlaying) {
                        this.showControls = false;
                    }
                }, 3000);
            });

            container.addEventListener('mouseleave', () => {
                if (this.isPlaying) {
                    this.showControls = false;
                }
            });
        },

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    }" 
    x-on:start-video-playback.window="video.play()"
    x-on:pause-video-playback.window="video.pause()"
    x-on:seek-to-position.window="seekTo($event.detail.timestamp)"
    x-on:volume-changed.window="setVolume($event.detail.volume)"
    x-on:mute-toggled.window="video.muted = $event.detail.muted"
    x-on:fullscreen-toggled.window="toggleFullscreen()">

        <!-- Video Element -->
        <video 
            x-ref="videoElement"
            class="w-full h-auto max-h-[70vh]"
            :src="'{{ $this->getFileUrl() }}'"
            preload="metadata"
            @click="togglePlayback()"
            poster="">
            Your browser does not support the video tag.
        </video>

        <!-- Video Controls Overlay -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none"
             x-show="!isPlaying"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-75"
             x-transition:enter-end="opacity-100 scale-100">
            <button @click="togglePlayback()" 
                    class="pointer-events-auto bg-black bg-opacity-50 text-white rounded-full p-4 hover:bg-opacity-75 transition-all">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Bottom Controls -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4"
             x-show="showControls || !isPlaying"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- Progress Bar -->
            <div class="mb-3">
                <div class="flex items-center space-x-2 text-white text-sm">
                    <span x-text="formatTime(currentPosition)">00:00</span>
                    
                    <div class="flex-1 bg-gray-600 rounded-full h-1 cursor-pointer"
                         @click="seekTo((($event.offsetX / $el.offsetWidth) * duration))">
                        <div class="bg-blue-500 h-1 rounded-full transition-all duration-100"
                             :style="`width: ${duration > 0 ? (currentPosition / duration) * 100 : 0}%`"></div>
                    </div>
                    
                    <span x-text="formatTime(duration)">00:00</span>
                </div>
            </div>

            <!-- Control Buttons -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <!-- Play/Pause -->
                    <button @click="togglePlayback()" 
                            class="text-white hover:text-gray-300 transition-colors">
                        <svg x-show="!isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Volume -->
                    <div class="flex items-center space-x-2">
                        <button @click="toggleMute()" class="text-white hover:text-gray-300 transition-colors">
                            <svg x-show="!isMuted && volume > 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.776L5.67 14H4a2 2 0 01-2-2V8a2 2 0 012-2h1.67l2.713-2.776a1 1 0 011.617.776zM16 8a2 2 0 11-4 0 2 2 0 014 0zM14 8a2 2 0 00-2-2V6a4 4 0 110 8v0a2 2 0 002-2z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="isMuted || volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.776L5.67 14H4a2 2 0 01-2-2V8a2 2 0 012-2h1.67l2.713-2.776a1 1 0 011.617.776zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <input type="range" 
                               min="0" 
                               max="1" 
                               step="0.1" 
                               :value="volume"
                               @input="setVolume($event.target.value)"
                               class="w-16 h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer">
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Comment Button -->
                    @if($this->canAddComments())
                        <button wire:click="toggleCommentForm()" 
                                class="text-white hover:text-gray-300 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif

                    <!-- Download Button -->
                    <a href="{{ $this->getDownloadUrl() }}" 
                       target="_blank"
                       class="text-white hover:text-gray-300 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>

                    <!-- Fullscreen Button -->
                    <button @click="toggleFullscreen()" 
                            class="text-white hover:text-gray-300 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    @if($showComments && $this->canShowComments())
        <div class="p-6 border-t">
            <h3 class="text-lg font-semibold mb-4">Comments</h3>
            
            <!-- Comment markers on timeline -->
            @if(!empty($commentMarkers))
                <div class="mb-4">
                    <div class="relative h-2 bg-gray-200 rounded">
                        @foreach($commentMarkers as $marker)
                            <div class="absolute top-0 w-1 h-2 bg-yellow-500 rounded cursor-pointer"
                                 style="left: {{ $marker['position'] }}%"
                                 title="{{ $marker['user'] }}: {{ $marker['comment'] }}"
                                 wire:click="seekTo({{ $marker['timestamp'] }})">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Add Comment Form -->
            @if($showAddCommentForm)
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Comment at {{ $this->formatTime($commentTimestamp) }}
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
            <div class="space-y-4">
                @foreach($comments as $comment)
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <strong>{{ $comment->user->name ?? 'Client' }}</strong>
                                <span class="text-sm text-gray-500 ml-2">
                                    at {{ $this->formatTime($comment->timestamp) }}
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