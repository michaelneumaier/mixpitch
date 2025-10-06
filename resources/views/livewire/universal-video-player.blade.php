<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800">
    <div class="mx-auto">
        <div id="universal-video-player" class="universal-video-player" 
             x-data="universalVideoPlayerData()" 
             x-init="initPlayer()">
            
            <!-- Error Display -->
            <div x-show="showError" x-transition class="mb-4">
                <flux:card variant="danger" class="p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <flux:icon.exclamation-triangle class="text-red-600" />
                            <span x-text="errorMessage"></span>
                        </div>
                        <flux:button @click="showError = false; $wire.dismissError()" variant="ghost" size="sm" icon="x-mark" />
                    </div>
                </flux:card>
            </div>
                <!-- Video Container -->
                <div class="mb-4">
                    <div id="video-container-universal-player"
                        class="w-full overflow-hidden rounded-t-xl border border-gray-200 bg-black shadow-sm dark:border-gray-700"
                        wire:ignore style="min-height: 400px;">
                        <!-- Video.js player will be inserted here -->
                        <video
                            id="video-player-universal"
                            class="video-js vjs-default-skin w-full h-full"
                            controls
                            preload="metadata"
                            data-setup='{}'>
                            <source src="{{ $this->getFileUrl() }}" type="video/mp4">
                            <p class="vjs-no-js">
                                To view this video please enable JavaScript, and consider upgrading to a web browser that
                                <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>.
                            </p>
                        </video>
                    </div>

                    <!-- Comment Markers (Grouped by Timestamp) - Separate from video container -->
                    @if($duration > 0 && count($comments) > 0)
                        <div class="relative w-full h-6 bg-gradient-to-r from-gray-800/90 to-gray-900/90 rounded-xl border border-gray-200 dark:border-gray-700">
                            @foreach($this->getGroupedComments() as $group)
                                @php
                                    $position = ($group['timestamp'] / max(0.1, $duration)) * 100;
                                    $position = min(max($position, 0), 100);
                                @endphp
                                <div class="absolute top-1 h-4 w-2 cursor-pointer group transform hover:scale-110 transition-transform duration-200"
                                     style="left: {{ $position }}%; background: {{ $group['resolved'] ? 'linear-gradient(to bottom, #10b981, #059669)' : 'linear-gradient(to bottom, #8b5cf6, #7c3aed)' }}; box-shadow: 0 0 8px {{ $group['resolved'] ? 'rgba(16, 185, 129, 0.6)' : 'rgba(139, 92, 246, 0.6)' }};"
                                     x-data="{ showTooltip: false }"
                                     @mouseenter="showTooltip = true"
                                     @mouseleave="showTooltip = false"
                                     @click="seekTo({{ $group['timestamp'] }})">

                                    <!-- Marker Indicator with Count Badge -->
                                    <div class="relative absolute -top-1 left-1/2 transform -translate-x-1/2 w-3 h-3 rounded-full border-2 border-white {{ $group['resolved'] ? 'bg-emerald-500' : 'bg-violet-500' }} shadow-lg group-hover:scale-125 transition-transform duration-200">
                                        @if($group['count'] > 1)
                                            <div class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 text-white text-[8px] font-bold rounded-full flex items-center justify-center border border-white shadow-md">
                                                {{ $group['count'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Comment Tooltip (shows all comments in group) -->
                                    <div x-show="showTooltip" x-cloak
                                        class="absolute -top-64 p-4 bg-white/98 backdrop-blur-sm rounded-xl shadow-2xl border-2 {{ $group['resolved'] ? 'border-emerald-200' : 'border-violet-200' }} w-80 max-h-64 overflow-y-auto z-[9999] {{ $position < 15 ? 'left-0' : ($position > 85 ? 'right-0' : 'left-1/2 transform -translate-x-1/2') }}"
                                        style="filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.2));"
                                        @click.stop>
                                        
                                        <!-- Tooltip Arrow pointing down -->
                                        <div class="absolute -bottom-2 {{ $position < 15 ? 'left-4' : ($position > 85 ? 'right-4' : 'left-1/2 transform -translate-x-1/2') }} w-4 h-4 {{ $group['resolved'] ? 'bg-emerald-200' : 'bg-violet-200' }} rotate-45 border-r-2 border-b-2 {{ $group['resolved'] ? 'border-emerald-200' : 'border-violet-200' }}"></div>

                                        <!-- Group Header -->
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b {{ $group['resolved'] ? 'border-emerald-200' : 'border-violet-200' }}">
                                            <div class="text-xs font-semibold {{ $group['resolved'] ? 'text-emerald-600' : 'text-violet-600' }}">{{ $this->formatTime($group['timestamp']) }}</div>
                                            @if($group['count'] > 1)
                                                <div class="text-xs text-gray-600">{{ $group['count'] }} comments</div>
                                            @endif
                                        </div>

                                        <!-- All Comments in Group -->
                                        <div class="space-y-3">
                                            @foreach($group['comments'] as $comment)
                                                <div class="border-b border-gray-100 last:border-0 pb-3 last:pb-0">
                                                    <div class="flex items-center mb-2">
                                                        @if($comment->user ?? null)
                                                            <img src="{{ $comment->user->profile_photo_url ?? '' }}" alt="{{ $comment->user->name ?? '' }}" class="h-6 w-6 rounded-full mr-2 border {{ $comment->resolved ? 'border-emerald-200' : 'border-violet-200' }}">
                                                        @else
                                                            <div class="h-6 w-6 rounded-full {{ $comment->resolved ? 'bg-emerald-500' : 'bg-violet-500' }} flex items-center justify-center mr-2 border border-white shadow-sm">
                                                                <i class="fas fa-user text-white text-[10px]"></i>
                                                            </div>
                                                        @endif
                                                        <div class="flex-1">
                                                            <div class="text-xs font-bold text-gray-900">
                                                                {{ $comment->user->name ?? $comment->client_email ?? 'Client' }}
                                                            </div>
                                                        </div>
                                                        @if($comment->resolved ?? false)
                                                            <div class="text-[9px] text-green-600 font-medium">✓ Resolved</div>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-800 leading-relaxed font-medium">
                                                        {{ \Illuminate\Support\Str::limit($comment->comment ?? '', 120) }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>


                <!-- File Info -->
                <flux:card class="mb-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="base">
                                {{ $file->original_name ?? $file->file_name ?? 'Video File' }}
                            </flux:heading>
                            <flux:subheading>
                                @if ($fileType === 'pitch_file')
                                    <flux:badge size="sm" color="emerald" variant="filled">Pitch File</flux:badge>
                                @elseif($fileType === 'project_file')
                                    <flux:badge size="sm" color="blue" variant="filled">Project File</flux:badge>
                                @endif
                                @if(isset($file->size))
                                    <flux:badge size="sm" variant="outline">{{ $this->formatFileSize($file->size ?? 0) }}</flux:badge>
                                @endif
                            </flux:subheading>
                        </div>
                        <div class="flex gap-2">
                            <flux:button href="{{ $this->getDownloadUrl() }}" variant="outline" size="sm" icon="arrow-down-tray">
                                Download
                            </flux:button>
                        </div>
                    </div>
                </flux:card>

                <!-- Comments Section -->
                <div x-show="showComments" x-transition>
                    <flux:card class="p-4">
                        <flux:heading size="base" class="mb-4 flex items-center gap-2">
                            <flux:icon.chat-bubble-left class="text-green-600 dark:text-green-400" />
                            Comments
                            @if(count($comments) > 0)
                                <flux:badge size="sm" color="blue">{{ count($comments) }}</flux:badge>
                            @endif
                        </flux:heading>

                        @if($this->canAddComments())
                            <!-- Add Comment Button -->
                            <div class="mb-4">
                                <flux:button
                                    x-on:click="
                                        let ts = 0;
                                        if (videoPlayer && videoPlayer.currentTime) {
                                            ts = videoPlayer.currentTime();
                                        }
                                        $wire.toggleCommentForm(ts);
                                    "
                                    variant="filled" size="sm" icon="plus">
                                    Add Comment
                                </flux:button>
                            </div>
                        @endif

                        <!-- Add Comment Form -->
                        @if($showAddCommentForm)
                            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="mb-3">
                                    <flux:subheading>Add Comment at {{ $this->formatTime($commentTimestamp) }}</flux:subheading>
                                </div>
                                
                                <form wire:submit.prevent="addComment">
                                    <div class="mb-3">
                                        <flux:textarea
                                            wire:model="newComment"
                                            placeholder="Share your thoughts about this video..."
                                            rows="3"
                                            required />
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <flux:button type="submit" variant="filled" size="sm" icon="paper-airplane">
                                            Add Comment
                                        </flux:button>
                                        <flux:button type="button" wire:click="toggleCommentForm" variant="outline" size="sm">
                                            Cancel
                                        </flux:button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <!-- Comments List -->
                        @if(count($comments) > 0)
                            <div class="space-y-4">
                                @foreach($comments as $comment)
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <!-- Comment Header -->
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center gap-3">
                                                @if($comment->user)
                                                    <img src="{{ $comment->user->profile_photo_url }}"
                                                         alt="{{ $comment->user->name }}"
                                                         class="h-8 w-8 rounded-full">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                                        <flux:icon.user class="text-white" size="sm" />
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->client_email ?? 'Client' }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Timestamp Badge -->
                                            <flux:button 
                                                @click="seekTo({{ $comment->timestamp }})" 
                                                variant="outline" 
                                                size="xs"
                                                icon="play">
                                                {{ $this->formatTime($comment->timestamp) }}
                                            </flux:button>
                                        </div>

                                        <!-- Comment Content -->
                                        <div class="mb-3">
                                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $comment->comment }}</p>
                                        </div>

                                        <!-- Resolved Badge (for pitch files only) -->
                                        @if($fileType === 'pitch_file' && $comment->resolved)
                                            <div class="mb-3">
                                                <flux:badge color="green" size="sm" icon="check-circle">
                                                    Resolved
                                                </flux:badge>
                                            </div>
                                        @endif

                                        <!-- Action Buttons (for pitch files only) -->
                                        @if($fileType === 'pitch_file' && ($this->canAddComments() || $clientMode))
                                            <div class="flex gap-2">
                                                @if($this->canAddComments())
                                                    <!-- Reply Button -->
                                                    <flux:button wire:click="toggleReplyForm({{ $comment->id }})" variant="outline" size="xs" icon="arrow-uturn-left">
                                                        Reply
                                                    </flux:button>

                                                    <!-- Resolve/Unresolve Button -->
                                                    @php
                                                        $canResolve = false;
                                                        if ($clientMode) {
                                                            $canResolve = ($comment->is_client_comment && $comment->client_email === $clientEmail) 
                                                                       || ($comment->user_id === $file->pitch->user_id);
                                                        } else {
                                                            $canResolve = Auth::id() === $comment->user_id || Auth::id() === $file->pitch->user_id;
                                                        }
                                                    @endphp
                                                    
                                                    @if($canResolve)
                                                        <flux:button 
                                                            wire:click="toggleResolveComment({{ $comment->id }})" 
                                                            variant="{{ $comment->resolved ? 'filled' : 'outline' }}" 
                                                            size="xs"
                                                            color="{{ $comment->resolved ? 'gray' : 'green' }}"
                                                            icon="{{ $comment->resolved ? 'arrow-path' : 'check' }}">
                                                            {{ $comment->resolved ? 'Unresolve' : 'Resolve' }}
                                                        </flux:button>
                                                    @endif

                                                    <!-- Delete Button -->
                                                    @if(Auth::id() === $comment->user_id || Auth::id() === $file->pitch->user_id)
                                                        <flux:button 
                                                            wire:click="confirmDelete({{ $comment->id }})" 
                                                            variant="outline" 
                                                            size="xs"
                                                            color="red"
                                                            icon="trash">
                                                            Delete
                                                        </flux:button>
                                                    @endif
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Replies -->
                                        @if($comment->replies && $comment->replies->count() > 0)
                                            <div class="mt-4 ml-6 space-y-3 border-l-2 border-gray-200 pl-4 dark:border-gray-700">
                                                @foreach($comment->replies as $reply)
                                                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700">
                                                        <!-- Reply Header -->
                                                        <div class="flex items-start justify-between mb-2">
                                                            <div class="flex items-center gap-2">
                                                                @if($reply->user)
                                                                    <img src="{{ $reply->user->profile_photo_url }}" 
                                                                         alt="{{ $reply->user->name }}" 
                                                                         class="h-6 w-6 rounded-full">
                                                                    <div>
                                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $reply->user->name }}</div>
                                                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</div>
                                                                    </div>
                                                                @else
                                                                    <div class="h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                                                                        <flux:icon.user class="text-white" size="xs" />
                                                                    </div>
                                                                    <div>
                                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $reply->client_email ?? 'Client' }}</div>
                                                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            
                                                            @if($fileType === 'pitch_file' && (Auth::id() === $reply->user_id || Auth::id() === $file->pitch->user_id))
                                                                <flux:button 
                                                                    wire:click="confirmDelete({{ $reply->id }})" 
                                                                    variant="ghost" 
                                                                    size="xs"
                                                                    color="red"
                                                                    icon="trash" />
                                                            @endif
                                                        </div>

                                                        <!-- Reply Content -->
                                                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $reply->comment }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Reply Form -->
                                        @if($showReplyForm && $replyToCommentId === $comment->id)
                                            <div class="mt-4 ml-6 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                                                <form wire:submit.prevent="submitReply">
                                                    <div class="mb-3">
                                                        <flux:textarea
                                                            wire:model="replyText"
                                                            placeholder="Write your reply..."
                                                            rows="2"
                                                            required />
                                                    </div>
                                                    
                                                    <div class="flex gap-2">
                                                        <flux:button type="submit" variant="filled" size="xs" icon="paper-airplane">
                                                            Reply
                                                        </flux:button>
                                                        <flux:button type="button" wire:click="toggleReplyForm" variant="outline" size="xs">
                                                            Cancel
                                                        </flux:button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Empty State -->
                            <div class="py-8 text-center">
                                <flux:icon.chat-bubble-left-ellipsis
                                    class="mx-auto mb-2 text-gray-400 dark:text-gray-500" size="lg" />
                                <p class="text-gray-600 dark:text-gray-400">No comments yet.</p>
                                @if($this->canAddComments())
                                    <flux:button wire:click="toggleCommentForm" variant="outline" size="sm" class="mt-3" icon="plus">
                                        Add the first comment
                                    </flux:button>
                                @endif
                            </div>
                        @endif
                    </flux:card>
                </div>

                <!-- Delete Confirmation Modal -->
                @if($showDeleteConfirmation)
                    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 m-4 max-w-sm w-full dark:bg-gray-800">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Delete Comment</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to delete this comment? This action cannot be undone.</p>
                            <div class="flex justify-end space-x-2">
                                <flux:button wire:click="cancelDelete" variant="outline" size="sm">
                                    Cancel
                                </flux:button>
                                <flux:button wire:click="deleteComment" variant="filled" size="sm" color="red">
                                    Delete
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif
        </div>
    </div>

<script>
    // Alpine.js data function for Universal Video Player
    function universalVideoPlayerData() {
        return {
            playerState: {
                isPlaying: @json($isPlaying),
                isReady: false,
                currentTime: '00:00',
                totalDuration: '00:00',
                duration: @json($duration),
                isFullscreen: @json($isFullscreen),
                volume: @json($volume),
                isMuted: @json($isMuted),
                playbackRate: @json($playbackRate)
            },
            instanceId: 'universal-video-player',
            videoPlayer: null,
            showComments: @json($showComments),
            errorMessage: @json($errorMessage),
            showError: @json($showError),
            
            getCurrentPosition() {
                return this.videoPlayer?.currentTime() || 0;
            },
            
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '00:00';
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = Math.floor(seconds % 60);
                
                if (hours > 0) {
                    return hours.toString().padStart(2, '0') + ':' + 
                           minutes.toString().padStart(2, '0') + ':' + 
                           secs.toString().padStart(2, '0');
                }
                return minutes.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
            },
            
            seekTo(timestamp) {
                if (this.videoPlayer && this.playerState.isReady) {
                    this.videoPlayer.currentTime(timestamp);
                }
            },
            
            initPlayer() {
                this.$nextTick(() => {
                    if (typeof initializeUniversalVideoPlayer === 'function') {
                        initializeUniversalVideoPlayer();
                    }
                });
            }
        };
    }

    // Initialize Universal Video Player
    function initializeUniversalVideoPlayer() {
        const videoUrl = {!! json_encode($this->getFileUrl()) !!};
        const livewireComponentId = '{{ $this->getId() }}';
        
        // Find the container and Alpine component
        const container = document.getElementById('universal-video-player');
        if (!container) {
            console.error('Video container not found');
            return;
        }

        // Check if Alpine is available
        if (typeof Alpine === 'undefined') {
            console.error('Alpine is not available');
            return;
        }

        // Find the Alpine component
        let alpineComponent = Alpine.$data(container);
        if (!alpineComponent) {
            console.log('Alpine component not ready, retrying in 100ms...');
            setTimeout(() => initializeUniversalVideoPlayer(), 100);
            return;
        }

        // Initialize Video.js
        if (typeof videojs === 'undefined') {
            console.error('Video.js not loaded');
            return;
        }

        const videoElement = document.getElementById('video-player-universal');
        if (!videoElement) {
            console.error('Video element not found');
            return;
        }

        // Initialize Video.js player
        const player = videojs(videoElement, {
            fluid: true,
            responsive: true,
            playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],
            controls: true,
            preload: 'metadata'
        });

        // Store player reference in Alpine component
        alpineComponent.videoPlayer = player;

        // Set up event listeners
        player.ready(() => {
            console.log('Video.js player ready');
            alpineComponent.playerState.isReady = true;
            
            // Set initial volume
            if (alpineComponent.playerState.volume > 0) {
                player.volume(alpineComponent.playerState.volume);
            }
            
            // Set initial mute state
            if (alpineComponent.playerState.isMuted) {
                player.muted(true);
            }
            
            // Set initial playback rate
            if (alpineComponent.playerState.playbackRate !== '1.0') {
                player.playbackRate(parseFloat(alpineComponent.playerState.playbackRate));
            }
        });

        player.on('loadedmetadata', () => {
            const duration = player.duration();
            alpineComponent.playerState.duration = duration;
            alpineComponent.playerState.totalDuration = alpineComponent.formatTime(duration);
            
            // Notify Livewire component about duration
            @this.updateDuration(duration);
        });

        player.on('play', () => {
            alpineComponent.playerState.isPlaying = true;
            @this.onPlaybackStarted();
        });

        player.on('pause', () => {
            alpineComponent.playerState.isPlaying = false;
            @this.onPlaybackPaused();
        });

        player.on('ended', () => {
            alpineComponent.playerState.isPlaying = false;
            alpineComponent.playerState.currentTime = alpineComponent.formatTime(0);
            @this.onVideoEnded();
        });

        player.on('timeupdate', () => {
            const currentTime = player.currentTime();
            alpineComponent.playerState.currentTime = alpineComponent.formatTime(currentTime);
            
            // Notify Livewire component about position
            @this.updatePosition(currentTime);
            
            // Dispatch custom event for timestamp-based features
            window.dispatchEvent(new CustomEvent('video-timeupdate', {
                detail: { currentTime: currentTime }
            }));
        });

        player.on('volumechange', () => {
            alpineComponent.playerState.volume = player.volume();
            alpineComponent.playerState.isMuted = player.muted();
        });

        player.on('fullscreenchange', () => {
            alpineComponent.playerState.isFullscreen = player.isFullscreen();
        });

        player.on('ratechange', () => {
            alpineComponent.playerState.playbackRate = player.playbackRate().toString();
        });

        player.on('error', (error) => {
            console.error('Video.js error:', error);
            const errorData = player.error();
            let errorMessage = 'An error occurred while playing the video.';
            
            if (errorData) {
                switch (errorData.code) {
                    case 1:
                        errorMessage = 'Video loading was aborted.';
                        break;
                    case 2:
                        errorMessage = 'Network error while loading video.';
                        break;
                    case 3:
                        errorMessage = 'Video format not supported.';
                        break;
                    case 4:
                        errorMessage = 'Video source not found.';
                        break;
                }
            }
            
            alpineComponent.errorMessage = errorMessage;
            alpineComponent.showError = true;
            @this.showVideoError(errorMessage);
        });

        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('startVideoPlayback', () => {
                if (player && !player.paused()) return;
                player.play();
            });

            Livewire.on('pauseVideoPlayback', () => {
                if (player && player.paused()) return;
                player.pause();
            });

            Livewire.on('seekToVideoPosition', (event) => {
                if (player && event.timestamp !== undefined) {
                    player.currentTime(event.timestamp);
                }
            });

            Livewire.on('videoVolumeChanged', (event) => {
                if (player && event.volume !== undefined) {
                    player.volume(event.volume);
                }
            });

            Livewire.on('videoMuteToggled', (event) => {
                if (player && event.muted !== undefined) {
                    player.muted(event.muted);
                }
            });

            Livewire.on('videoFullscreenToggled', (event) => {
                if (player) {
                    if (event.fullscreen) {
                        player.requestFullscreen();
                    } else {
                        player.exitFullscreen();
                    }
                }
            });

            Livewire.on('videoPlaybackRateChanged', (event) => {
                if (player && event.rate !== undefined) {
                    player.playbackRate(event.rate);
                }
            });
        });

        console.log('Universal Video Player initialized successfully');
    }

    // Format file size helper
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>

<style>
    /* Video.js custom styling */
    .video-js {
        width: 100% !important;
        height: 400px !important;
    }

    .video-js .vjs-big-play-button {
        background-color: rgba(59, 130, 246, 0.8);
        border: none;
        border-radius: 50%;
        width: 80px;
        height: 80px;
        line-height: 80px;
        font-size: 2.5rem;
    }

    .video-js .vjs-big-play-button:hover {
        background-color: rgba(59, 130, 246, 1);
    }

    .video-js .vjs-control-bar {
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    }

    .video-js .vjs-progress-control .vjs-progress-holder {
        height: 6px;
    }

    .video-js .vjs-progress-control .vjs-play-progress {
        background-color: #3b82f6;
    }

    /* Dark mode adjustments */
    .dark .video-js .vjs-control-bar {
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    }

    /* Comment markers styling */
    .comment-marker {
        position: absolute;
        top: 0;
        height: 100%;
        width: 2px;
        z-index: 10;
        cursor: pointer;
    }

    /* Responsive video */
    @media (max-width: 768px) {
        .video-js {
            height: 250px !important;
        }
    }
</style>
</div>
