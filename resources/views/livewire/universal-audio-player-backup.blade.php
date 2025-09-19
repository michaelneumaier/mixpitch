<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800">
    <div class="mx-auto">
        <div id="universal-audio-player" class="universal-audio-player" x-data="{
            playerState: {
                isPlaying: false,
                isReady: false,
                currentTime: '00:00',
                totalDuration: '00:00',
                duration: 0
            },
            instanceId: 'universal-player',
            wavesurfer: null,
            currentPosition: 0,
            showQueue: true,
            showComments: true,
        
            // A-B Loop state
            loopEnabled: @json($loopEnabled),
            loopStart: @json($loopStart),
            loopEnd: @json($loopEnd),
            settingLoopPoint: null,
        
            getCurrentPosition() {
                return this.currentPosition || 0;
            },
            setCurrentPosition(time) {
                this.currentPosition = time;
            },
        
            // A-B Loop methods (using globalAudioManager when available, fallback to local implementation)
            setLoopPoint(point) {
                // Get the actual current position from WaveSurfer
                let currentPos = this.currentPosition;
                if (this.wavesurfer && typeof this.wavesurfer.getCurrentTime === 'function') {
                    currentPos = this.wavesurfer.getCurrentTime() || 0;
                }
        
                if (point === 'start') {
                    this.loopStart = currentPos;
                    if (this.loopEnd !== null && this.loopStart >= this.loopEnd) {
                        this.loopEnd = null;
                        this.loopEnabled = false;
                    }
        
                    // Update globalAudioManager if available
                    if (window.globalAudioManager && this.loopEnd !== null) {
                        window.globalAudioManager.setLoop(currentPos, this.loopEnd);
                    }
        
                    // $wire.setLoopStart(currentPos); // Skip Livewire sync to prevent state interference
                } else if (point === 'end') {
                    this.loopEnd = currentPos;
                    if (this.loopStart !== null && this.loopEnd <= this.loopStart) {
                        this.loopStart = null;
                        this.loopEnabled = false;
                    }
        
                    // Update globalAudioManager if available
                    if (window.globalAudioManager && this.loopStart !== null) {
                        window.globalAudioManager.setLoop(this.loopStart, currentPos);
                    }
        
                    // $wire.setLoopEnd(currentPos); // Skip Livewire sync to prevent state interference
                }
        
                this.settingLoopPoint = null;
        
                // Update the visual markers
                this.updateLoopMarkers();
        
                // Sync play state to ensure it's not disrupted
                this.$nextTick(() => {
                    if (typeof this.syncPlayState === 'function') {
                        this.syncPlayState();
                    }
                });
            },
        
            updateLoopMarkers() {
                // Update the waveform overlay markers
                const waveformContainer = document.getElementById('waveform-universal-player-full');
                const markerOverlay = waveformContainer?.querySelector('.waveform-marker-overlay');
        
                if (!markerOverlay || !this.playerState.duration) return;
        
                // Store current play state to prevent interference
                const wasPlaying = this.playerState.isPlaying;
        
                // Clear existing markers
                markerOverlay.innerHTML = '';
        
                // Add loop region highlight if both points are set
                if (this.loopStart !== null && this.loopEnd !== null) {
                    const leftPercent = (this.loopStart / this.playerState.duration) * 100;
                    const widthPercent = ((this.loopEnd - this.loopStart) / this.playerState.duration) * 100;
        
                    const loopRegion = document.createElement('div');
                    loopRegion.style.cssText = `
                                            position: absolute;
                                            top: 0;
                                            height: 100%;
                                            background: rgba(139, 92, 246, 0.2);
                                            left: ${leftPercent}%;
                                            width: ${widthPercent}%;
                                            border-radius: 4px;
                                        `;
                    markerOverlay.appendChild(loopRegion);
                }
        
                // Add start marker (A)
                if (this.loopStart !== null) {
                    const leftPercent = (this.loopStart / this.playerState.duration) * 100;
        
                    const startMarker = document.createElement('div');
                    startMarker.style.cssText = `
                                            position: absolute;
                                            top: 0;
                                            height: 100%;
                                            width: 2px;
                                            background: #10b981;
                                            left: ${leftPercent}%;
                                            box-shadow: 0 0 4px rgba(16, 185, 129, 0.6);
                                        `;
        
                    const startLabel = document.createElement('div');
                    startLabel.textContent = 'A';
                    startLabel.style.cssText = `
                                            position: absolute;
                                            top: -24px;
                                            left: 50%;
                                            transform: translateX(-50%);
                                            background: #10b981;
                                            color: white;
                                            font-size: 11px;
                                            font-weight: bold;
                                            padding: 2px 6px;
                                            border-radius: 4px;
                                            white-space: nowrap;
                                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                        `;
        
                    startMarker.appendChild(startLabel);
                    markerOverlay.appendChild(startMarker);
                }
        
                // Add end marker (B)
                if (this.loopEnd !== null) {
                    const leftPercent = (this.loopEnd / this.playerState.duration) * 100;
        
                    const endMarker = document.createElement('div');
                    endMarker.style.cssText = `
                                            position: absolute;
                                            top: 0;
                                            height: 100%;
                                            width: 2px;
                                            background: #ef4444;
                                            left: ${leftPercent}%;
                                            box-shadow: 0 0 4px rgba(239, 68, 68, 0.6);
                                        `;
        
                    const endLabel = document.createElement('div');
                    endLabel.textContent = 'B';
                    endLabel.style.cssText = `
                                            position: absolute;
                                            top: -24px;
                                            left: 50%;
                                            transform: translateX(-50%);
                                            background: #ef4444;
                                            color: white;
                                            font-size: 11px;
                                            font-weight: bold;
                                            padding: 2px 6px;
                                            border-radius: 4px;
                                            white-space: nowrap;
                                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                        `;
        
                    endMarker.appendChild(endLabel);
                    markerOverlay.appendChild(endMarker);
                }
        
                // Restore play state if it got disrupted
                this.$nextTick(() => {
                    if (this.playerState.isPlaying !== wasPlaying) {
                        console.log('Correcting play state after loop marker update:', wasPlaying);
                        this.playerState.isPlaying = wasPlaying;
                    }
                });
            },
        
            toggleLoop() {
                if (this.loopStart !== null && this.loopEnd !== null) {
                    this.loopEnabled = !this.loopEnabled;
        
                    if (this.loopEnabled) {
                        // Enable loop in globalAudioManager if available
                        if (window.globalAudioManager) {
                            window.globalAudioManager.setLoop(this.loopStart, this.loopEnd);
                        }
                    } else {
                        // Disable loop in globalAudioManager if available
                        if (window.globalAudioManager) {
                            window.globalAudioManager.clearLoop();
                        }
                    }
        
                    // $wire.toggleLoop(); // Skip Livewire sync to prevent state interference
                }
            },
        
            clearLoop() {
                this.loopStart = null;
                this.loopEnd = null;
                this.loopEnabled = false;
                this.settingLoopPoint = null;
        
                // Clear loop in globalAudioManager if available
                if (window.globalAudioManager) {
                    window.globalAudioManager.clearLoop();
                }
        
                // Clear the visual markers
                this.updateLoopMarkers();
        
                // $wire.clearLoop(); // Skip Livewire sync to prevent state interference
            },
        
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '00:00';
                const minutes = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return minutes.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
            },
        
            // Method to ensure play state stays in sync with actual WaveSurfer state
            syncPlayState() {
                if (this.wavesurfer) {
                    const actuallyPlaying = this.wavesurfer.isPlaying && this.wavesurfer.isPlaying();
                    if (this.playerState.isPlaying !== actuallyPlaying) {
                        console.log('Play state out of sync, correcting:', actuallyPlaying);
                        this.playerState.isPlaying = actuallyPlaying;
                    }
                }
            }
        }" x-init="$nextTick(() => {
            if (typeof initializeUniversalPlayer_universal === 'function') {
                initializeUniversalPlayer_universal();
            }
        });
        
        // Store reference to component for setInterval
        const component = $data;
        
        // Periodically sync play state to ensure it stays accurate
        setInterval(() => {
            if (component && typeof component.syncPlayState === 'function') {
                component.syncPlayState();
            }
        }, 1000);">
            <flux:card class="p-4 mb-2 lg:p-6 xl:p-8">

                <!-- Waveform Container -->
                <div class="relative mb-2">
                    <div id="waveform-universal-player-full"
                        class="w-full overflow-hidden rounded-xl border border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm dark:border-gray-700 dark:from-gray-800 dark:to-slate-800"
                        wire:ignore style="height: 128px; min-height: 128px;">
                        <!-- This will be replaced by WaveSurfer -->
                    </div>

                    <!-- Comment Markers -->
                    @if($duration > 0 && count($comments) > 0)
                        <div class="absolute inset-0 pointer-events-none z-10">
                            @foreach($comments as $comment)
                                @php
                                    $position = ($comment->timestamp / max(0.1, $duration)) * 100;
                                    $position = min(max($position, 0), 100);
                                @endphp
                                <div class="absolute h-full w-1 z-10 cursor-pointer pointer-events-auto group"
                                     style="left: {{ $position }}%; background: {{ $comment->resolved ? 'linear-gradient(to bottom, #22c55e, #10b981)' : 'linear-gradient(to bottom, #7c3aed, #4f46e5)' }};"
                                     x-data="{ showTooltip: false }" 
                                     @mouseenter="showTooltip = true"
                                     @mouseleave="showTooltip = false"
                                     @click="$wire.seekTo({{ $comment->timestamp }})">
                                    
                                    <!-- Comment Marker -->
                                    <div class="h-4 w-4 rounded-full -ml-1.5 {{ $comment->resolved ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600' }} border-2 border-white shadow-lg absolute -top-1 group-hover:scale-125 transition-all duration-200">
                                        <div class="absolute inset-0 rounded-full bg-white/30 animate-pulse"></div>
                                    </div>

                                    <!-- Comment Tooltip -->
                                    <div x-show="showTooltip" x-cloak
                                        class="absolute bottom-full mb-3 p-3 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20 w-72 z-50 {{ $position < 15 ? 'left-0 transform-none' : ($position > 85 ? 'left-auto right-0 transform-none' : 'left-0 transform -translate-x-1/2') }}"
                                        @click.stop>
                                        <!-- Tooltip Header -->
                                        <div class="flex items-center mb-2">
                                            @if($comment->user)
                                                <img src="{{ $comment->user->profile_photo_url }}"
                                                    alt="{{ $comment->user->name }}" 
                                                    class="h-6 w-6 rounded-full border-2 border-purple-200 mr-2">
                                            @else
                                                <div class="h-6 w-6 rounded-full border-2 border-blue-200 mr-2 bg-blue-500 flex items-center justify-center">
                                                    <flux:icon name="user" class="text-white text-xs" />
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    @if($comment->user)
                                                        {{ $comment->user->name }}
                                                    @else
                                                        {{ $comment->client_email ?? 'Client' }}
                                                    @endif
                                                </div>
                                                <div class="text-xs text-purple-600 font-medium">{{ $comment->formatted_timestamp }}</div>
                                            </div>
                                            @if($comment->resolved && $fileType === 'pitch_file')
                                                <flux:badge color="green" size="xs" icon="check-circle">
                                                    Resolved
                                                </flux:badge>
                                            @endif
                                        </div>
                                        <!-- Comment Content -->
                                        <div class="text-sm text-gray-800 bg-gradient-to-r from-purple-50/50 to-indigo-50/50 rounded-lg p-2">
                                            {{ \Illuminate\Support\Str::limit($comment->comment, 120) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Loop markers - positioned as overlay using pointer-events-none -->
                    <div x-show="loopStart !== null || loopEnd !== null"
                        class="pointer-events-none absolute left-0 top-0 h-32 w-full" style="z-index: 1000;">
                        <!-- Loop region highlight -->
                        <div x-show="loopStart !== null && loopEnd !== null"
                            class="absolute top-0 h-full rounded-xl bg-purple-200 opacity-30"
                            :style="{
                                left: ((loopStart / (playerState.duration || 1)) * 100) + '%',
                                width: (((loopEnd - loopStart) / (playerState.duration || 1)) * 100) + '%'
                            }">
                        </div>

                        <!-- Start marker -->
                        <div x-show="loopStart !== null" class="absolute top-0 h-full w-1 bg-green-500 shadow-lg"
                            :style="{ left: ((loopStart / (playerState.duration || 1)) * 100) + '%' }">
                            <div
                                class="absolute -top-6 left-1/2 -translate-x-1/2 transform whitespace-nowrap rounded border border-green-300 bg-green-100 px-1.5 py-0.5 text-xs font-bold text-green-600 shadow-md">
                                A
                            </div>
                        </div>

                        <!-- End marker -->
                        <div x-show="loopEnd !== null" class="absolute top-0 h-full w-1 bg-red-500 shadow-lg"
                            :style="{ left: ((loopEnd / (playerState.duration || 1)) * 100) + '%' }">
                            <div
                                class="absolute -top-6 left-1/2 -translate-x-1/2 transform whitespace-nowrap rounded border border-red-300 bg-red-100 px-1.5 py-0.5 text-xs font-bold text-red-600 shadow-md">
                                B
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div id="waveform-timeline-universal-player"
                        class="timeline-container mb-4 mt-4 hidden h-8 rounded-lg border border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 dark:border-gray-700 dark:from-gray-800 dark:to-slate-800">
                        <!-- Timeline markers will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Main Controls -->
                <flux:card class="mb-2 p-4">
                    <!-- Primary Controls Row -->
                    <div class="flex items-center justify-center md:justify-between gap-4 mb-4 md:mb-0">
                        <!-- Transport Controls -->
                        <div class="flex items-center gap-3">
                            <!-- Previous Track -->
                            <flux:button variant="outline" size="sm" disabled icon="backward" />

                            <!-- Play/Pause -->
                            <flux:button variant="primary"
                                @click="playerState.isPlaying = !playerState.isPlaying; $dispatch('toggle-playback-universal-player', { playing: playerState.isPlaying })">
                                <flux:icon name="play" x-show="!playerState.isPlaying" />
                                <flux:icon name="pause" x-show="playerState.isPlaying" />
                            </flux:button>

                            <!-- Next Track -->
                            <flux:button variant="outline" size="sm" disabled icon="forward" />
                        </div>

                        <!-- Time Display (Desktop) -->
                        <div class="hidden md:flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                            <span x-text="playerState.currentTime">00:00</span> 
                            <span class="mx-1">/</span>
                            <span x-text="playerState.totalDuration">00:00</span>
                        </div>
                    </div>

                    <!-- Time Display (Mobile) -->
                    <div class="flex md:hidden justify-center mb-4">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <span x-text="playerState.currentTime">00:00</span> 
                            <span class="mx-1">/</span>
                            <span x-text="playerState.totalDuration">00:00</span>
                        </div>
                    </div>

                    <!-- Secondary Controls Row -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <!-- A-B Loop Controls -->
                        <div class="flex items-center gap-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2 hidden sm:inline">A-B Loop:</span>
                            <!-- Loop Start (A) -->
                            <flux:button size="xs" @click="setLoopPoint('start')"
                                x-bind:variant="settingLoopPoint === 'start' ? 'primary' : (loopStart !== null ? 'filled' : 'ghost')"
                                x-bind:title="loopStart !== null ? 'Loop start: ' + formatTime(loopStart) : 'Set loop start'">
                                A
                            </flux:button>

                            <!-- Loop End (B) -->
                            <flux:button size="xs" @click="setLoopPoint('end')"
                                x-bind:variant="settingLoopPoint === 'end' ? 'primary' : (loopEnd !== null ? 'filled' : 'ghost')"
                                x-bind:title="loopEnd !== null ? 'Loop end: ' + formatTime(loopEnd) : 'Set loop end'">
                                B
                            </flux:button>

                            <!-- Loop Toggle -->
                            <flux:button size="xs" x-show="loopStart !== null && loopEnd !== null"
                                @click="toggleLoop()" x-bind:variant="loopEnabled ? 'filled' : 'ghost'"
                                title="Toggle A-B loop" icon="arrow-path" />

                            <!-- Clear Loop -->
                            <flux:button size="xs" x-show="loopStart !== null || loopEnd !== null"
                                @click="clearLoop()" variant="ghost" title="Clear loop" icon="x-mark" />
                        </div>

                        <!-- View & Volume Controls -->
                        <div class="flex items-center gap-3">
                            <!-- Queue Toggle -->
                            <flux:button @click="showQueue = !showQueue" size="sm"
                                x-bind:variant="showQueue ? 'filled' : 'outline'" title="Toggle queue"
                                icon="list-bullet">
                                <span class="hidden sm:inline ml-1">Queue</span>
                            </flux:button>

                            <!-- Comments Toggle -->
                            <flux:button @click="showComments = !showComments" size="sm"
                                x-bind:variant="showComments ? 'filled' : 'outline'" title="Toggle comments"
                                icon="chat-bubble-left">
                                <span class="hidden sm:inline ml-1">Comments</span>
                            </flux:button>

                            <!-- Volume Control -->
                            <div class="flex items-center gap-2">
                                <flux:icon name="speaker-wave" class="text-gray-600 dark:text-gray-400" size="sm" />
                                <input type="range" min="0" max="100" value="80"
                                    class="h-2 w-12 sm:w-16 cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                </flux:card>

                <!-- File Info -->
                <flux:card class="mb-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="lg">
                                {{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}</flux:heading>
                            <flux:subheading>
                                @if ($fileType === 'pitch_file')
                                    <flux:badge size="sm" color="emerald" variant="filled">Pitch File</flux:badge>
                                @elseif($fileType === 'project_file')
                                    <flux:badge size="sm" color="blue" variant="filled">Project File</flux:badge>
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

                <!-- Queue Section -->
                <div x-show="showQueue" x-transition class="mb-2">
                    <flux:card class="p-4">
                        <flux:heading size="lg" class="mb-4 flex items-center gap-2">
                            <flux:icon name="list-bullet" class="text-blue-600 dark:text-blue-400" />
                            Queue
                        </flux:heading>

                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <div class="flex items-center gap-3">
                                    <flux:icon name="play" class="text-blue-600 dark:text-blue-400" />
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-100">{{ $file->original_filename ?? ($file->filename ?? 'Current File') }}</span>
                                </div>
                                <flux:badge color="blue" size="sm">Now Playing</flux:badge>
                            </div>
                        </div>
                    </flux:card>
                </div>

                <!-- Comments Section -->
                <div x-show="showComments" x-transition>
                    <flux:card class="p-4">
                        <flux:heading size="lg" class="mb-4 flex items-center gap-2">
                            <flux:icon name="chat-bubble-left" class="text-green-600 dark:text-green-400" />
                            Comments
                            @if(count($comments) > 0)
                                <flux:badge size="sm" color="blue">{{ count($comments) }}</flux:badge>
                            @endif
                        </flux:heading>

                        @if($this->canAddComments())
                            <!-- Add Comment Button -->
                            <div class="mb-4">
                                <flux:button wire:click="toggleCommentForm" variant="filled" size="sm" icon="plus">
                                    Add Comment
                                </flux:button>
                            </div>
                        @endif

                        <!-- Add Comment Form -->
                        @if($showAddCommentForm)
                            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="mb-3">
                                    <flux:heading size="sm">Add Comment at {{ sprintf("%02d:%02d", floor($commentTimestamp / 60), $commentTimestamp % 60) }}</flux:heading>
                                </div>
                                
                                <form wire:submit.prevent="addComment">
                                    <div class="mb-3">
                                        <flux:textarea
                                            wire:model="newComment"
                                            placeholder="Share your thoughts about this audio..."
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
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                                        <flux:icon name="user" class="text-white" size="sm" />
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $comment->client_email ?? 'Client' }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Timestamp Badge -->
                                            <flux:button 
                                                wire:click="seekTo({{ $comment->timestamp }})" 
                                                variant="outline" 
                                                size="xs"
                                                icon="play">
                                                {{ $comment->formatted_timestamp }}
                                            </flux:button>
                                        </div>

                                        <!-- Comment Content -->
                                        <div class="mb-3">
                                            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $comment->comment }}</p>
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
                                                                        <flux:icon name="user" class="text-white" size="xs" />
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
                                <flux:icon name="chat-bubble-left-ellipsis"
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
                        <div class="bg-white rounded-lg p-6 m-4 max-w-sm w-full">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Comment</h3>
                            <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this comment? This action cannot be undone.</p>
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
            </flux:card> <!-- End of main flux:card -->
        </div> <!-- End of universal-audio-player div (x-data) -->
    </div> <!-- End of mx-auto container -->

<script>
    // Initialize Universal Audio Player (following pitch-file-player pattern)
    function initializeUniversalPlayer_universal() {
        const instanceId = 'universal-player';
        const livewireComponentId = '{{ $this->getId() }}';
        const audioUrl = {!! json_encode($this->getFileUrl()) !!};

        // Find the container and Alpine component (exactly like pitch-file-player)
        const container = document.getElementById('universal-audio-player');
        if (!container) {
            console.error('Container not found for UniversalAudioPlayer');
            return;
        }

        // Check if Alpine is available
        if (typeof Alpine === 'undefined') {
            console.error('Alpine is not available');
            return;
        }

        // Find the Alpine component using Alpine.$data
        let alpineComponent = Alpine.$data(container);
        console.log('Raw Alpine.$data result:', alpineComponent);

        // If Alpine component is not ready, retry after a short delay
        if (!alpineComponent) {
            console.log('Alpine component not ready, retrying in 100ms...');
            setTimeout(() => initializeUniversalPlayer_universal(), 100);
            return;
        }

        const instanceIdFromAlpine = alpineComponent?.instanceId || instanceId;

        // Debug Alpine component access
        console.log(`Alpine component found for instance ${instanceIdFromAlpine}:`, alpineComponent);
        console.log(`Initial currentPosition for instance ${instanceIdFromAlpine}:`, alpineComponent?.currentPosition);

        // Ensure properties are properly initialized
        if (alpineComponent && alpineComponent.currentPosition === undefined) {
            alpineComponent.setCurrentPosition(0);
        }

        // Initialize globalAudioManager with the universal player container
        if (!window.globalAudioManager) {
            console.error('Global Audio Manager not available');
            return;
        }

        // Check if WaveSurfer needs to be re-initialized for the universal player container
        const containerSelector = '#waveform-' + instanceId + '-full';
        const containerElement = document.querySelector(containerSelector);

        if (!containerElement) {
            console.error('Universal player container not found:', containerSelector);
            return;
        }

        // Re-initialize WaveSurfer with the universal player container
        console.log('Initializing WaveSurfer for universal player container:', containerSelector);
        const waveSurferInitialized = window.globalAudioManager.initializeWaveSurfer(containerSelector);

        let wavesurfer;
        let useGlobalAudioManager = true;

        if (!waveSurferInitialized) {
            console.error('Failed to initialize WaveSurfer via globalAudioManager');
            // Fallback: create our own WaveSurfer instance
            console.log('Creating fallback WaveSurfer instance');
            useGlobalAudioManager = false;

            if (typeof WaveSurfer === 'undefined') {
                console.error('WaveSurfer not loaded');
                return;
            }

            wavesurfer = WaveSurfer.create({
                container: containerElement,
                waveColor: 'rgba(167, 139, 250, 0.3)',
                progressColor: 'rgba(99, 102, 241, 0.8)',
                cursorColor: 'rgb(67, 56, 202)',
                barWidth: 3,
                barRadius: 1,
                barGap: 2,
                responsive: true,
                height: 120,
                normalize: true,
                backend: 'WebAudio',
                mediaControls: false,
            });

            // Add event listeners for fallback WaveSurfer instance
            wavesurfer.on('ready', () => {
                const duration = wavesurfer.getDuration();

                // Update Alpine state
                if (alpineComponent) {
                    alpineComponent.playerState.totalDuration = formatTime(duration);
                    alpineComponent.playerState.duration = duration;
                    alpineComponent.playerState.isReady = true;
                    alpineComponent.playerState.isPlaying = false;
                }

                console.log('Fallback WaveSurfer ready, duration:', duration);

                // Show waveform and setup timeline
                const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                if (waveformContainer) {
                    waveformContainer.classList.add('loaded');

                    // Add marker overlay
                    let markerOverlay = waveformContainer.querySelector('.waveform-marker-overlay');
                    if (!markerOverlay) {
                        markerOverlay = document.createElement('div');
                        markerOverlay.className = 'waveform-marker-overlay';
                        markerOverlay.style.cssText = `
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            pointer-events: none;
                            z-index: 1000;
                        `;
                        waveformContainer.appendChild(markerOverlay);
                    }
                }

                setupTimeline(duration);
            });

            wavesurfer.on('play', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = true;
                }
                console.log('Fallback WaveSurfer: Play event fired');
            });

            wavesurfer.on('pause', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
                console.log('Fallback WaveSurfer: Pause event fired');
            });

            wavesurfer.on('audioprocess', () => {
                const currentTime = wavesurfer.getCurrentTime();
                if (alpineComponent) {
                    alpineComponent.playerState.currentTime = formatTime(currentTime);
                    alpineComponent.setCurrentPosition(currentTime);

                    // Handle A-B loop for fallback
                    if (alpineComponent.loopEnabled &&
                        alpineComponent.loopStart !== null &&
                        alpineComponent.loopEnd !== null &&
                        currentTime >= alpineComponent.loopEnd) {
                        wavesurfer.seekTo(alpineComponent.loopStart / wavesurfer.getDuration());
                    }
                }
            });
        } else {
            wavesurfer = window.globalAudioManager.waveSurfer;
        }

        // Store wavesurfer reference
        alpineComponent.wavesurfer = wavesurfer;

        // Make sure we have a valid wavesurfer instance before proceeding
        console.log('WaveSurfer instance check:', {
            wavesurfer: wavesurfer,
            type: typeof wavesurfer,
            hasDrawBuffer: wavesurfer && typeof wavesurfer.drawBuffer,
            hasBackend: wavesurfer && typeof wavesurfer.backend,
            hasOptions: wavesurfer && typeof wavesurfer.options,
            usingGlobalManager: useGlobalAudioManager
        });
        
        if (!wavesurfer) {
            console.error('No valid WaveSurfer instance available');
            return;
        }

        // Format time helper
        function formatTime(seconds) {
            if (!seconds || isNaN(seconds) || seconds < 0) return '00:00';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return minutes.toString().padStart(2, '0') + ':' + remainingSeconds.toString().padStart(2, '0');
        }

        // Setup timeline
        function setupTimeline(duration) {
            const timeline = document.getElementById('waveform-timeline-' + instanceId);
            if (!timeline || !duration || duration <= 0) return;

            timeline.innerHTML = '';
            const container = document.createElement('div');
            container.className = 'timeline-container relative h-full';

            const interval = duration > 60 ? 30 : 10;

            for (let time = 0; time <= duration; time += interval) {
                if (time === 0) continue;

                const percent = (time / duration) * 100;
                const mark = document.createElement('div');
                mark.className = 'timeline-mark absolute text-xs font-medium text-indigo-600';
                mark.style.left = `${percent}%`;
                mark.style.transform = 'translateX(-50%)';
                mark.style.top = '2px';
                mark.textContent = formatTime(time);
                container.appendChild(mark);
            }

            timeline.appendChild(container);
            timeline.classList.remove('hidden');
        }

        // Setup event listeners for globalAudioManager case
        if (useGlobalAudioManager) {
            // For globalAudioManager, we need to set up listeners differently
            wavesurfer.on('ready', () => {
                const duration = wavesurfer.getDuration();

                // Update Alpine state
                if (alpineComponent) {
                    alpineComponent.playerState.totalDuration = formatTime(duration);
                    alpineComponent.playerState.duration = duration;
                    alpineComponent.playerState.isReady = true;
                    alpineComponent.playerState.isPlaying = false;
                }

                console.log('GlobalAudioManager WaveSurfer ready, duration:', duration);

                // Show waveform
                const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                if (waveformContainer) {
                    waveformContainer.classList.add('loaded');

                    // Add marker overlay
                    let markerOverlay = waveformContainer.querySelector('.waveform-marker-overlay');
                    if (!markerOverlay) {
                        markerOverlay = document.createElement('div');
                        markerOverlay.className = 'waveform-marker-overlay';
                        markerOverlay.style.cssText = `
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            pointer-events: none;
                            z-index: 1000;
                        `;
                        waveformContainer.appendChild(markerOverlay);
                    }
                }

                setupTimeline(duration);
            });

            wavesurfer.on('play', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = true;
                }
                console.log('GlobalAudioManager: Play event fired');
                
                // Ensure timeline stays visible during playback
                const timeline = document.getElementById('waveform-timeline-' + instanceId);
                if (timeline) {
                    timeline.classList.remove('hidden');
                }
            });

            wavesurfer.on('pause', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
                console.log('GlobalAudioManager: Pause event fired');
                
                // Ensure timeline stays visible when paused
                const timeline = document.getElementById('waveform-timeline-' + instanceId);
                if (timeline) {
                    timeline.classList.remove('hidden');
                }
            });

            wavesurfer.on('audioprocess', () => {
                const currentTime = wavesurfer.getCurrentTime();
                if (alpineComponent) {
                    alpineComponent.playerState.currentTime = formatTime(currentTime);
                    alpineComponent.setCurrentPosition(currentTime);
                    // A-B loop handled by globalAudioManager
                }
            });
        }

        // Listen for Alpine dispatch events (like pitch-file-player)
        document.addEventListener('toggle-playback-' + instanceId, function(event) {
            try {
                console.log('Toggle playback event received:', event.detail);
                
                if (useGlobalAudioManager) {
                    console.log('Using Global Audio Manager for playback control');
                    console.log('Global Audio Manager state:', {
                        isReady: window.globalAudioManager?.isReady,
                        currentTrack: window.globalAudioManager?.currentTrack,
                        waveSurfer: window.globalAudioManager?.waveSurfer,
                        isPlaying: window.globalAudioManager?.waveSurfer?.isPlaying(),
                        hasAudioBuffer: window.globalAudioManager?.waveSurfer?.backend?.buffer,
                        duration: window.globalAudioManager?.waveSurfer?.getDuration(),
                        currentTime: window.globalAudioManager?.waveSurfer?.getCurrentTime()
                    });
                    
                    if (event.detail.playing) {
                        console.log('Attempting to play via Global Audio Manager');
                        try {
                            const result = window.globalAudioManager.play();
                            console.log('Play result:', result);
                        } catch (error) {
                            console.error('Error calling globalAudioManager.play():', error);
                        }
                    } else {
                        console.log('Attempting to pause via Global Audio Manager');
                        try {
                            const result = window.globalAudioManager.pause();
                            console.log('Pause result:', result);
                        } catch (error) {
                            console.error('Error calling globalAudioManager.pause():', error);
                        }
                    }
                } else {
                    // Use local WaveSurfer instance
                    if (event.detail.playing) {
                        wavesurfer.play();
                    } else {
                        wavesurfer.pause();
                    }
                }
            } catch (error) {
                console.error('Playback error:', error);
                // Update Alpine state on error
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
            }
        });

        // Check if we have pre-generated waveform data
        const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);

        if (hasPreGeneratedPeaks) {
            console.log('Using pre-generated waveform data for Universal Audio Player');
            const peaks = @js($file->waveform_peaks_array);
            const storedDuration = @js($file->duration ?? null);

            // Debug the peaks data
            console.log('Peaks data type:', typeof peaks);
            console.log('Is peaks an array?', Array.isArray(peaks));
            console.log('Peaks sample:', peaks && peaks.length > 0 ? peaks.slice(0, 10) : peaks);

            if (peaks && Array.isArray(peaks[0])) {
                // We have min/max peaks format - convert to WaveSurfer format
                const waveformPeaks = peaks.map(point => point[1]); // Use max peaks for visualization
                
                console.log('Converting min/max peaks to WaveSurfer format');
                console.log('WaveformPeaks sample:', waveformPeaks.slice(0, 10));
                console.log('WaveformPeaks range:', Math.min(...waveformPeaks), 'to', Math.max(...waveformPeaks));

                // Use duration from database or estimate
                const estimatedDuration = waveformPeaks.length > 0 ? (waveformPeaks.length / 67) * 60 : 60;
                const displayDuration = storedDuration || estimatedDuration;
                
                console.log('Duration info:', { stored: storedDuration, estimated: estimatedDuration, using: displayDuration });

                // Use WaveSurfer's proper pre-decoded peaks API
                if (wavesurfer) {
                    wavesurfer.destroy();
                }
                
                wavesurfer = WaveSurfer.create({
                    container: containerElement,
                    url: audioUrl,
                    peaks: [new Float32Array(waveformPeaks)], // Single channel
                    duration: displayDuration,
                    waveColor: 'rgba(167, 139, 250, 0.3)',
                    progressColor: 'rgba(99, 102, 241, 0.8)',
                    cursorColor: 'rgb(67, 56, 202)',
                    barWidth: 3,
                    barRadius: 1,
                    barGap: 2,
                    responsive: true,
                    height: 120,
                    normalize: true,
                    mediaControls: false,
                });

                // Store wavesurfer reference
                alpineComponent.wavesurfer = wavesurfer;
                
                // Add event listeners
                wavesurfer.on('ready', () => {
                    const duration = wavesurfer.getDuration();
                    console.log('WaveSurfer ready with pre-decoded peaks, duration:', duration);

                    // Update Alpine state
                    if (alpineComponent) {
                        alpineComponent.playerState.totalDuration = formatTime(duration);
                        alpineComponent.playerState.duration = duration;
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.isPlaying = false;
                    }

                    // Show waveform and setup timeline
                    const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                    if (waveformContainer) {
                        waveformContainer.classList.add('loaded');

                        // Add marker overlay
                        let markerOverlay = waveformContainer.querySelector('.waveform-marker-overlay');
                        if (!markerOverlay) {
                            markerOverlay = document.createElement('div');
                            markerOverlay.className = 'waveform-marker-overlay';
                            markerOverlay.style.cssText = `
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                pointer-events: none;
                                z-index: 1000;
                            `;
                            waveformContainer.appendChild(markerOverlay);
                        }
                    }

                    setupTimeline(duration);
                });

                wavesurfer.on('play', () => {
                    if (alpineComponent) {
                        alpineComponent.playerState.isPlaying = true;
                    }
                    console.log('WaveSurfer: Play event fired');
                });

                wavesurfer.on('pause', () => {
                    if (alpineComponent) {
                        alpineComponent.playerState.isPlaying = false;
                    }
                    console.log('WaveSurfer: Pause event fired');
                });

                wavesurfer.on('audioprocess', () => {
                    const currentTime = wavesurfer.getCurrentTime();
                    if (alpineComponent) {
                        alpineComponent.playerState.currentTime = formatTime(currentTime);
                        alpineComponent.setCurrentPosition(currentTime);

                        // Handle A-B loop
                        if (alpineComponent.loopEnabled &&
                            alpineComponent.loopStart !== null &&
                            alpineComponent.loopEnd !== null &&
                            currentTime >= alpineComponent.loopEnd) {
                            wavesurfer.seekTo(alpineComponent.loopStart / wavesurfer.getDuration());
                        }
                    }
                });

                    // Modern WaveSurfer (v7+) approach
                    if (wavesurfer.decodedData && typeof wavesurfer.decodedData.setLength === 'function') {
                        console.log('Using modern WaveSurfer API for peaks');
                        
                        // Create fake decoded data for visualization
                        const channels = 1;
                        const length = maxPeaks.length;
                        const sampleRate = 44100;
                        
                        // Create a fake decoded data object
                        const fakeDecodedData = {
                            getChannelData: (channel) => {
                                if (channel === 0) {
                                    // Return max peaks as the channel data
                                    return new Float32Array(maxPeaks);
                                }
                                return new Float32Array(length);
                            },
                            length: length,
                            numberOfChannels: channels,
                            sampleRate: sampleRate,
                            duration: displayDuration
                        };
                        
                        // Set the decoded data
                        wavesurfer.decodedData = fakeDecodedData;
                        
                        // Force redraw
                        if (typeof wavesurfer.setDecodedData === 'function') {
                            console.log('Setting decoded data');
                            wavesurfer.setDecodedData(fakeDecodedData);
                        } else if (typeof wavesurfer.zoom === 'function') {
                            console.log('Triggering zoom to force redraw');
                            wavesurfer.zoom(1);
                        }
                        
                    } else if (wavesurfer.backend) {
                        console.log('Using legacy WaveSurfer backend API');
                        // Legacy approach for older WaveSurfer versions
                        wavesurfer.backend.peaks = [minPeaks, maxPeaks];
                        wavesurfer.backend.duration = displayDuration;
                        
                        if (typeof wavesurfer.drawBuffer === 'function') {
                            wavesurfer.drawBuffer();
                        }
                    } else {
                        console.log('Using fallback manual canvas drawing');
                        // Manual canvas manipulation as last resort
                        const canvas = wavesurfer.getWrapper().querySelector('canvas');
                        if (canvas) {
                            const ctx = canvas.getContext('2d');
                            const width = canvas.width;
                            const height = canvas.height;
                            
                            // Clear canvas
                            ctx.clearRect(0, 0, width, height);
                            
                            // Draw waveform
                            ctx.fillStyle = 'rgba(167, 139, 250, 0.3)';
                            ctx.strokeStyle = 'rgba(99, 102, 241, 0.8)';
                            ctx.lineWidth = 2;
                            
                            const barWidth = Math.max(1, width / maxPeaks.length);
                            
                            for (let i = 0; i < maxPeaks.length; i++) {
                                const x = (i / maxPeaks.length) * width;
                                const minY = (height / 2) + (minPeaks[i] * height / 2);
                                const maxY = (height / 2) + (maxPeaks[i] * height / 2);
                                
                                ctx.fillRect(x, Math.min(minY, maxY), barWidth, Math.abs(maxY - minY));
                            }
                            
                            console.log('Manual canvas drawing completed');
                        }
                    }
                } catch (error) {
                    console.error('Error setting peaks:', error);
                }

                // Mark waveform as "ready-like" state for the visualization
                setTimeout(() => {
                    const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                    if (waveformContainer) {
                        waveformContainer.classList.add('loaded');
                        
                        // Add marker overlay
                        let markerOverlay = waveformContainer.querySelector('.waveform-marker-overlay');
                        if (!markerOverlay) {
                            markerOverlay = document.createElement('div');
                            markerOverlay.className = 'waveform-marker-overlay';
                            markerOverlay.style.cssText = `
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                pointer-events: none;
                                z-index: 1000;
                            `;
                            waveformContainer.appendChild(markerOverlay);
                        }
                    }
                    
                    // Update Alpine state
                    if (alpineComponent) {
                        alpineComponent.playerState.totalDuration = formatTime(displayDuration);
                        alpineComponent.playerState.duration = displayDuration;
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.isPlaying = false;
                        // Initialize currentPosition to 0
                        alpineComponent.setCurrentPosition(0);
                        console.log(`Pre-generated peaks loaded for Universal Audio Player - currentPosition for instance ${instanceId}:`, alpineComponent.currentPosition);
                    }

                    // Setup timeline with the duration
                    setupTimeline(displayDuration);
                }, 100);

                // For pre-generated peaks, we need to load audio first, then overlay peaks
                console.log('Loading audio first, then overlaying pre-generated peaks');
                
                if (useGlobalAudioManager) {
                    const trackData = {
                        url: audioUrl,
                        title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                        artist: 'Universal Player',
                        id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                        type: '{{ $fileType }}'
                    };
                    
                    console.log('Loading track through Global Audio Manager first:', trackData);
                    
                    // Load the audio first
                    window.globalAudioManager.loadTrack(trackData);
                    
                    // Wait for the audio to be ready, then overlay our peaks
                    const waitForAudioReady = () => {
                        if (wavesurfer && wavesurfer.getDuration && wavesurfer.getDuration() > 0) {
                            console.log('Audio loaded, now overlaying pre-generated peaks');
                            
                            // Now overlay our pre-generated peaks for visual display
                            setTimeout(() => {
                                console.log('Applying visual peaks overlay');
                                
                                // Try multiple approaches to overlay the waveform
                                console.log('Attempting to overlay pre-generated waveform');
                                
                                // Method 1: Try to set decoded data if possible
                                if (wavesurfer.decodedData) {
                                    console.log('Method 1: Setting decodedData');
                                    try {
                                        // Create proper AudioBuffer-like structure
                                        const fakeBuffer = {
                                            getChannelData: (channel) => {
                                                if (channel === 0) {
                                                    return new Float32Array(maxPeaks);
                                                }
                                                return new Float32Array(maxPeaks.length);
                                            },
                                            length: maxPeaks.length,
                                            numberOfChannels: 1,
                                            sampleRate: 44100,
                                            duration: displayDuration
                                        };
                                        
                                        // Try to replace the decoded data
                                        wavesurfer.decodedData = fakeBuffer;
                                        
                                        // Force a redraw
                                        if (typeof wavesurfer.zoom === 'function') {
                                            wavesurfer.zoom(1);
                                        }
                                        
                                        console.log('DecodedData method applied');
                                    } catch (e) {
                                        console.log('DecodedData method failed:', e);
                                    }
                                }
                                
                                // Method 2: Canvas overlay (with protection against overwriting)
                                const applyCanvasOverlay = () => {
                                    console.log('Method 2: Canvas overlay');
                                    
                                    // More robust canvas finding for different WaveSurfer versions
                                    let canvas = null;
                                    let container = null;
                                    
                                    // Try different methods to find the canvas
                                    if (wavesurfer.getWrapper) {
                                        container = wavesurfer.getWrapper();
                                        canvas = container.querySelector('canvas');
                                        console.log('Method 2a: Found via getWrapper:', !!canvas);
                                    }
                                    
                                    if (!canvas && wavesurfer.container) {
                                        container = wavesurfer.container;
                                        canvas = container.querySelector('canvas');
                                        console.log('Method 2b: Found via container:', !!canvas);
                                    }
                                    
                                    if (!canvas) {
                                        // Try finding canvas in the waveform container directly
                                        container = document.getElementById('waveform-' + instanceId + '-full');
                                        if (container) {
                                            canvas = container.querySelector('canvas');
                                            console.log('Method 2c: Found in waveform container:', !!canvas);
                                        }
                                    }
                                    
                                    if (!canvas) {
                                        console.log('Canvas not found via any method. Available containers:', {
                                            hasWrapper: !!wavesurfer.getWrapper,
                                            hasContainer: !!wavesurfer.container,
                                            waveformContainer: !!document.getElementById('waveform-' + instanceId + '-full')
                                        });
                                        return false;
                                    }
                                    
                                    if (canvas) {
                                        console.log('Canvas found, dimensions:', canvas.width, 'x', canvas.height);
                                        
                                        const ctx = canvas.getContext('2d');
                                        const width = canvas.width;
                                        const height = canvas.height;
                                        
                                        // Clear and draw our peaks
                                        ctx.clearRect(0, 0, width, height);
                                        
                                        // Draw our pre-generated waveform
                                        ctx.fillStyle = 'rgba(167, 139, 250, 0.6)';
                                        
                                        const barWidth = Math.max(2, width / maxPeaks.length);
                                        
                                        for (let i = 0; i < maxPeaks.length; i++) {
                                            const x = (i / maxPeaks.length) * width;
                                            const centerY = height / 2;
                                            const minY = centerY + (minPeaks[i] * height / 2);
                                            const maxY = centerY + (maxPeaks[i] * height / 2);
                                            
                                            const barHeight = Math.abs(maxY - minY);
                                            const barTop = Math.min(minY, maxY);
                                            
                                            ctx.fillRect(x, barTop, barWidth, barHeight);
                                        }
                                        
                                        console.log('Canvas overlay applied successfully');
                                        return true;
                                    } else {
                                        console.log('Canvas not found');
                                        return false;
                                    }
                                };
                                
                                // Apply canvas overlay multiple times to ensure it sticks
                                let overlayAttempts = 0;
                                const maxOverlayAttempts = 5;
                                
                                const persistentOverlay = () => {
                                    if (overlayAttempts < maxOverlayAttempts) {
                                        if (applyCanvasOverlay()) {
                                            overlayAttempts++;
                                            
                                            // Reapply after a delay to prevent WaveSurfer from overwriting
                                            setTimeout(persistentOverlay, 200);
                                        } else {
                                            // Retry if canvas not ready
                                            setTimeout(persistentOverlay, 100);
                                        }
                                    }
                                };
                                
                                persistentOverlay();
                                
                                // Add protection against WaveSurfer overwriting our canvas during playback
                                const protectWaveformDisplay = () => {
                                    if (wavesurfer) {
                                        // Listen for draw events that might overwrite our visualization
                                        const originalDraw = wavesurfer.drawBuffer || wavesurfer.draw;
                                        if (originalDraw) {
                                            console.log('Setting up waveform protection');
                                            const protectedDraw = function(...args) {
                                                // Call original draw method
                                                const result = originalDraw.apply(this, args);
                                                
                                                // Immediately reapply our overlay
                                                setTimeout(() => {
                                                    console.log('Reapplying waveform overlay after draw');
                                                    applyCanvasOverlay();
                                                }, 10);
                                                
                                                return result;
                                            };
                                            
                                            if (wavesurfer.drawBuffer) {
                                                wavesurfer.drawBuffer = protectedDraw;
                                            } else if (wavesurfer.draw) {
                                                wavesurfer.draw = protectedDraw;
                                            }
                                        }
                                        
                                        // Also listen for any redraw events
                                        if (wavesurfer.on) {
                                            ['redraw', 'waveform-ready', 'audioprocess'].forEach(eventName => {
                                                wavesurfer.on(eventName, () => {
                                                    setTimeout(() => {
                                                        console.log(`Reapplying overlay after ${eventName}`);
                                                        applyCanvasOverlay();
                                                    }, 50);
                                                });
                                            });
                                        }
                                    }
                                };
                                
                                protectWaveformDisplay();
                                
                                // Method 3: Create overlay canvas if direct manipulation doesn't work
                                setTimeout(() => {
                                    console.log('Method 3: Creating overlay canvas');
                                    
                                    // Use the same robust container finding logic
                                    let container = null;
                                    if (wavesurfer.getWrapper) {
                                        container = wavesurfer.getWrapper();
                                    } else if (wavesurfer.container) {
                                        container = wavesurfer.container;
                                    } else {
                                        container = document.getElementById('waveform-' + instanceId + '-full');
                                    }
                                    
                                    if (!container) {
                                        console.log('No container found for overlay canvas');
                                        return;
                                    }
                                    
                                    let overlayCanvas = container.querySelector('.peaks-overlay-canvas');
                                    
                                    if (!overlayCanvas) {
                                        overlayCanvas = document.createElement('canvas');
                                        overlayCanvas.className = 'peaks-overlay-canvas';
                                        overlayCanvas.style.cssText = `
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            width: 100%;
                                            height: 100%;
                                            pointer-events: none;
                                            z-index: 10;
                                        `;
                                        
                                        const originalCanvas = container.querySelector('canvas');
                                        if (originalCanvas) {
                                            overlayCanvas.width = originalCanvas.width;
                                            overlayCanvas.height = originalCanvas.height;
                                            container.appendChild(overlayCanvas);
                                            
                                            const ctx = overlayCanvas.getContext('2d');
                                            const width = overlayCanvas.width;
                                            const height = overlayCanvas.height;
                                            
                                            // Draw our waveform on the overlay
                                            ctx.fillStyle = 'rgba(167, 139, 250, 0.8)';
                                            const barWidth = Math.max(2, width / maxPeaks.length);
                                            
                                            for (let i = 0; i < maxPeaks.length; i++) {
                                                const x = (i / maxPeaks.length) * width;
                                                const centerY = height / 2;
                                                const minY = centerY + (minPeaks[i] * height / 2);
                                                const maxY = centerY + (maxPeaks[i] * height / 2);
                                                
                                                const barHeight = Math.abs(maxY - minY);
                                                const barTop = Math.min(minY, maxY);
                                                
                                                ctx.fillRect(x, barTop, barWidth, barHeight);
                                            }
                                            
                                            console.log('Overlay canvas created and drawn');
                                        }
                                    }
                                }, 500);
                                
                                // Ensure timeline stays visible
                                const timeline = document.getElementById('waveform-timeline-' + instanceId);
                                if (timeline) {
                                    timeline.classList.remove('hidden');
                                }
                            }, 100);
                        } else {
                            console.log('Waiting for audio to load...');
                            setTimeout(waitForAudioReady, 100);
                        }
                    };
                    
                    waitForAudioReady();
                    
                } else {
                    // For fallback WaveSurfer, we need to load audio without destroying the waveform
                    console.log('Loading audio for fallback WaveSurfer while preserving peaks');
                    
                    // Store our visualization state
                    const preservedPeaks = [minPeaks, maxPeaks];
                    const preservedDuration = displayDuration;
                    
                    // Load the audio
                    wavesurfer.load(audioUrl);
                    
                    // Restore peaks after audio loads
                    wavesurfer.once('ready', () => {
                        console.log('Audio loaded, restoring peaks for fallback WaveSurfer');
                        wavesurfer.backend.peaks = preservedPeaks;
                        wavesurfer.backend.duration = preservedDuration;
                        if (typeof wavesurfer.drawBuffer === 'function') {
                            wavesurfer.drawBuffer();
                        }
                    });
                }

            } else if (peaks && Array.isArray(peaks)) {
                // Single array format - treat as positive peaks only
                console.log('Converting single array to min/max peaks format');
                
                // Convert single array to min/max format (assume negative mirror)
                const minPeaks = peaks.map(peak => -Math.abs(peak));
                const maxPeaks = peaks.map(peak => Math.abs(peak));

                wavesurfer.options.peaks = [minPeaks, maxPeaks];
                
                const storedDuration = @js($file->duration ?? null);
                const estimatedDuration = peaks.length > 0 ? (peaks.length / 67) * 60 : 60;
                const displayDuration = storedDuration || estimatedDuration;
                
                wavesurfer.options.duration = displayDuration;
                
                if (typeof wavesurfer.drawBuffer === 'function') {
                    wavesurfer.drawBuffer();
                }

                // Same ready state handling as above
                setTimeout(() => {
                    const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                    if (waveformContainer) {
                        waveformContainer.classList.add('loaded');
                    }
                    
                    if (alpineComponent) {
                        alpineComponent.playerState.totalDuration = formatTime(displayDuration);
                        alpineComponent.playerState.duration = displayDuration;
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.isPlaying = false;
                        alpineComponent.setCurrentPosition(0);
                    }

                    setupTimeline(displayDuration);
                }, 100);

                // Load audio for playback
                if (useGlobalAudioManager) {
                    const trackData = {
                        url: audioUrl,
                        title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                        artist: 'Universal Player',
                        id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                        type: '{{ $fileType }}'
                    };
                    window.globalAudioManager.loadTrack(trackData);
                } else {
                    wavesurfer.load(audioUrl);
                }
            } else {
                console.warn('Invalid peaks format, falling back to regular load');
                
                // Fallback to normal loading
                if (useGlobalAudioManager) {
                    const trackData = {
                        url: audioUrl,
                        title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                        artist: 'Universal Player',
                        id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                        type: '{{ $fileType }}'
                    };
                    window.globalAudioManager.loadTrack(trackData);
                } else {
                    wavesurfer.load(audioUrl);
                }
            }
        } else {
            console.log('No pre-generated waveform data, loading audio normally');
            
            // Load audio normally
            if (useGlobalAudioManager) {
                // Load audio via globalAudioManager
                const trackData = {
                    url: audioUrl,
                    title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                    artist: 'Universal Player',
                    id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                    type: '{{ $fileType }}'
                };

                window.globalAudioManager.loadTrack(trackData);
            } else {
                // Load audio directly
                wavesurfer.load(audioUrl);
            }
        }
    }
</script>

<style>
    /* Waveform styling to match pitch-file-player */
    #waveform-universal-player-full {
        opacity: 0;
        transition: all 0.3s ease-in-out;
        position: relative;
        z-index: 1;
    }

    #waveform-universal-player-full.loaded {
        opacity: 1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Ensure WaveSurfer canvas doesn't override our markers */
    #waveform-universal-player-full {
        position: relative;
        z-index: 1;
    }

    #waveform-universal-player-full canvas {
        position: relative !important;
        z-index: 1 !important;
    }

    /* Force loop markers to appear above WaveSurfer canvas */
    .loop-markers-overlay {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 128px !important;
        z-index: 999 !important;
        pointer-events: none !important;
    }

    /* Individual marker styling */
    .loop-marker {
        position: absolute !important;
        z-index: 1000 !important;
        pointer-events: none !important;
    }

    .loop-marker-label {
        position: absolute !important;
        z-index: 1001 !important;
        pointer-events: auto !important;
    }

    .timeline-container {
        min-height: 32px;
        position: relative;
        padding: 4px 8px;
    }

    .timeline-mark {
        position: absolute;
        top: 4px;
        font-size: 11px;
        color: #4f46e5;
        font-weight: 500;
    }

    /* Dark mode timeline marks */
    .dark .timeline-mark {
        color: #a5b4fc;
    }

    /* A-B Loop disabled state styling */
    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* A-B Loop visual indicators */
    .loop-marker {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        z-index: 10;
        pointer-events: none;
    }

    .loop-start {
        background: linear-gradient(to bottom, #10b981, #059669);
        box-shadow: 0 0 4px rgba(16, 185, 129, 0.5);
    }

    .loop-end {
        background: linear-gradient(to bottom, #ef4444, #dc2626);
        box-shadow: 0 0 4px rgba(239, 68, 68, 0.5);
    }
</style>
</div>