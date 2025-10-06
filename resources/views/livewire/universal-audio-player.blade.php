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

                <!-- Waveform Container -->
                <div class="relative mb-2">
                    <div id="waveform-universal-player-full"
                        class="w-full overflow-hidden rounded-xl border border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm dark:border-gray-700 dark:from-gray-800 dark:to-slate-800"
                        wire:ignore style="height: 128px; min-height: 128px;">
                        <!-- This will be replaced by WaveSurfer -->
                        <div class="waveform-marker-overlay"></div>
                    </div>

                    <!-- Comment Markers (Grouped by Timestamp) -->
                    @if($duration > 0 && count($comments) > 0)
                        <div class="absolute inset-0 pointer-events-none z-10">
                            @foreach($this->getGroupedComments() as $group)
                                @php
                                    $position = ($group['timestamp'] / max(0.1, $duration)) * 100;
                                    $position = min(max($position, 0), 100);
                                @endphp
                                <div class="absolute h-full w-1 cursor-pointer pointer-events-auto group"
                                     style="left: {{ $position }}%; background: {{ $group['resolved'] ? 'linear-gradient(to bottom, #22c55e, #10b981)' : 'linear-gradient(to bottom, #7c3aed, #4f46e5)' }};"
                                     x-data="{ showTooltip: false }"
                                     @mouseenter="showTooltip = true"
                                     @mouseleave="showTooltip = false"
                                     @click="$wire.seekTo({{ $group['timestamp'] }})">

                                    <!-- Comment Marker with Count Badge -->
                                    <div class="relative h-4 w-4 -ml-1.5 {{ $group['resolved'] ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600' }} rounded-full border-2 border-white shadow-lg absolute -top-1 group-hover:scale-125 transition-all duration-200">
                                        <div class="absolute inset-0 rounded-full bg-white/30 animate-pulse"></div>
                                        @if($group['count'] > 1)
                                            <div class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border border-white shadow-md">
                                                {{ $group['count'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Comment Tooltip (shows all comments in group) -->
                                    <div x-show="showTooltip" x-cloak
                                        class="absolute top-2 p-3 bg-white/95 backdrop-blur-md rounded-xl shadow-xl border border-white/20 w-80 max-h-80 overflow-y-auto z-[9999] {{ $position < 15 ? 'left-0 transform-none' : ($position > 85 ? 'left-auto right-0 transform-none' : 'left-1/2 transform -translate-x-1/2') }}"
                                        @click.stop>
                                        <!-- Group Header -->
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200">
                                            <div class="text-xs font-semibold text-purple-600">{{ sprintf('%02d:%02d', floor($group['timestamp'] / 60), $group['timestamp'] % 60) }}</div>
                                            @if($group['count'] > 1)
                                                <div class="text-xs text-gray-500">{{ $group['count'] }} comments</div>
                                            @endif
                                        </div>

                                        <!-- All Comments in Group -->
                                        <div class="space-y-3">
                                            @foreach($group['comments'] as $comment)
                                                <div class="border-b border-gray-100 last:border-0 pb-3 last:pb-0">
                                                    <!-- Comment Header -->
                                                    <div class="flex items-center mb-2">
                                                        @if($comment->user ?? null)
                                                            <img src="{{ $comment->user->profile_photo_url ?? '' }}"
                                                                alt="{{ $comment->user->name ?? '' }}"
                                                                class="h-5 w-5 rounded-full border border-purple-200 mr-2">
                                                        @else
                                                            <div class="h-5 w-5 rounded-full border border-blue-200 mr-2 bg-blue-500 flex items-center justify-center">
                                                                <i class="fas fa-user text-white text-[8px]"></i>
                                                            </div>
                                                        @endif
                                                        <div class="flex-1">
                                                            <div class="text-xs font-semibold text-gray-900">
                                                                {{ $comment->user->name ?? $comment->client_email ?? 'Client' }}
                                                            </div>
                                                        </div>
                                                        @if($comment->resolved ?? false)
                                                            <div class="text-[10px] text-green-600 font-medium">✓ Resolved</div>
                                                        @endif
                                                    </div>
                                                    <!-- Comment Content -->
                                                    <div class="text-xs text-gray-800 bg-gradient-to-r from-purple-50/50 to-indigo-50/50 rounded-lg p-2">
                                                        {{ \Illuminate\Support\Str::limit($comment->comment ?? '', 140) }}
                                                    </div>
                                                    <!-- Reply Preview -->
                                                    @if(isset($comment->replies) && $comment->replies->count() > 0)
                                                        <div class="mt-2 pl-3 border-l-2 border-purple-200">
                                                            <div class="text-[10px] text-gray-500 mb-1">{{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'reply' : 'replies' }}</div>
                                                            @foreach($comment->replies->take(2) as $reply)
                                                                <div class="text-[10px] text-gray-600 mb-1">
                                                                    <span class="font-medium">{{ $reply->user->name ?? $reply->client_email ?? 'Client' }}:</span>
                                                                    {{ \Illuminate\Support\Str::limit($reply->comment ?? '', 80) }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
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

                <!-- Timeline removed -->
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
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 font-mono tabular-nums">
                            <span x-text="playerState.currentTime">00:00</span> 
                            <span class="mx-1">/</span>
                            <span x-text="playerState.totalDuration">00:00</span>
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 font-mono tabular-nums">
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
                            <flux:heading size="base">
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
                        <flux:heading size="base" class="mb-4 flex items-center gap-2">
                            <flux:icon name="list-bullet" class="text-blue-600 dark:text-blue-400" />
                            Queue
                        </flux:heading>

                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <div class="flex items-center gap-3">
                                    <flux:icon name="play" class="text-blue-600 dark:text-blue-400" />
                                    <span
                                        class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $file->original_filename ?? ($file->filename ?? 'Current File') }}</span>
                                </div>
                                <flux:badge color="blue" size="sm">Now Playing</flux:badge>
                            </div>
                        </div>
                    </flux:card>
                </div>

                <!-- Comments Section -->
                <div x-show="showComments" x-transition>
                    <flux:card class="p-4">
                        <flux:heading size="base" class="mb-4 flex items-center gap-2">
                            <flux:icon name="chat-bubble-left" class="text-green-600 dark:text-green-400" />
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
                                        // Determine current playhead from WaveSurfer/Audio
                                        (() => {
                                            let ts = 0;
                                            if (window.globalAudioManager?.usingMediaElement) {
                                                const a = document.getElementById('persistent-audio-element');
                                                if (a) ts = a.currentTime || 0;
                                            } else if (window.globalAudioManager?.waveSurfer?.getCurrentTime) {
                                                ts = window.globalAudioManager.waveSurfer.getCurrentTime() || 0;
                                            } else if ($data?.getCurrentPosition) {
                                                ts = $data.getCurrentPosition();
                                            }
                                            $wire.toggleCommentForm(ts);
                                        })()
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
                                    <flux:subheading>Add Comment at {{ sprintf("%02d:%02d", floor($commentTimestamp / 60), $commentTimestamp % 60) }}</flux:subheading>
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
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                                        <flux:icon name="user" class="text-white" size="sm" />
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $comment->client_email ?? 'Client' }}</div>
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
        </div> <!-- End of universal-audio-player div (x-data) -->
    </div> <!-- End of mx-auto container -->

<script>
    // Initialize Universal Audio Player (following pitch-file-player pattern)
    function initializeUniversalPlayer_universal() {
        const instanceId = 'universal-player';
        const livewireComponentId = '{{ $this->getId() }}';
        const audioUrl = {!! json_encode($this->getFileUrl()) !!};
        const storedDurationFromDb = @js($file->duration ?? null);

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

        // Prefill total duration from DB if available
        if (storedDurationFromDb && alpineComponent) {
            alpineComponent.playerState.duration = storedDurationFromDb;
            alpineComponent.playerState.totalDuration = formatTime(storedDurationFromDb);
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

        let wavesurfer = window.globalAudioManager.waveSurfer;
        let useGlobalAudioManager = true;

        if (!wavesurfer) {
            console.error('No WaveSurfer instance from GlobalAudioManager');
            return;
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

                // Draw existing loop markers if any
                try { if (alpineComponent && typeof alpineComponent.updateLoopMarkers === 'function') { alpineComponent.updateLoopMarkers(); } } catch (e) {}
                setupTimeline(duration);
            });

            wavesurfer.on('play', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = true;
                }
                console.log('GlobalAudioManager: Play event fired');
                
                // Keep time display in sync using the persistent audio element
                const audioEl = document.getElementById('persistent-audio-element');
                if (audioEl && alpineComponent) {
                    alpineComponent.playerState.currentTime = formatTime(audioEl.currentTime || 0);
                }
            });

            wavesurfer.on('pause', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
                console.log('GlobalAudioManager: Pause event fired');
                
                // Ensure current time stays accurate when paused
                const audioEl = document.getElementById('persistent-audio-element');
                if (audioEl && alpineComponent) {
                    alpineComponent.playerState.currentTime = formatTime(audioEl.currentTime || 0);
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

            // Bind to persistent audio element for reliable time/duration updates with MediaElement backend
            const audioEl = document.getElementById('persistent-audio-element');
            if (audioEl) {
                audioEl.addEventListener('loadedmetadata', () => {
                    if (!alpineComponent) return;
                    const dur = audioEl.duration || 0;
                    alpineComponent.playerState.duration = dur;
                    alpineComponent.playerState.totalDuration = formatTime(dur);
                    alpineComponent.playerState.isReady = true;
                }, { once: true });

                const updateFromAudio = () => {
                    if (!alpineComponent) return;
                    const ct = audioEl.currentTime || 0;
                    alpineComponent.playerState.currentTime = formatTime(ct);
                    alpineComponent.setCurrentPosition(ct);
                };
                audioEl.addEventListener('timeupdate', updateFromAudio);
                audioEl.addEventListener('seeking', updateFromAudio);
            }
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
                // Single-path: delegate to GlobalAudioManager with peaks
                const peaksArray = [new Float32Array(waveformPeaks)];
                const trackData = {
                    url: audioUrl,
                    title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                    artist: 'Universal Player',
                    id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                    type: '{{ $fileType }}'
                };
                window.globalAudioManager.loadTrackWithPeaks(trackData, peaksArray, displayDuration, containerSelector);
                alpineComponent.wavesurfer = window.globalAudioManager.waveSurfer;

                // Mark container loaded
                const wf = document.getElementById('waveform-' + instanceId + '-full');
                if (wf) wf.classList.add('loaded');
                // Prefill UI duration immediately
                if (alpineComponent) {
                    alpineComponent.playerState.duration = displayDuration;
                    alpineComponent.playerState.totalDuration = formatTime(displayDuration);
                }
                return;

            } else if (peaks && Array.isArray(peaks)) {
                // Single array format - treat as positive peaks only
                console.log('Converting single array to min/max peaks format');
                
                // Convert single array to min/max format (assume negative mirror)
                const minPeaks = peaks.map(peak => -Math.abs(peak));
                const maxPeaks = peaks.map(peak => Math.abs(peak));
                const storedDuration = @js($file->duration ?? null);
                const estimatedDuration = peaks.length > 0 ? (peaks.length / 67) * 60 : 60;
                const displayDuration = storedDuration || estimatedDuration;

                const peaksArray = [new Float32Array(maxPeaks)];
                const trackData = {
                    url: audioUrl,
                    title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                    artist: 'Universal Player',
                    id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                    type: '{{ $fileType }}'
                };
                window.globalAudioManager.loadTrackWithPeaks(trackData, peaksArray, displayDuration, containerSelector);
                alpineComponent.wavesurfer = window.globalAudioManager.waveSurfer;
                const wf2 = document.getElementById('waveform-' + instanceId + '-full');
                if (wf2) wf2.classList.add('loaded');
                // Prefill UI duration immediately
                if (alpineComponent) {
                    alpineComponent.playerState.duration = displayDuration;
                    alpineComponent.playerState.totalDuration = formatTime(displayDuration);
                }
                return;
            } else {
                console.warn('Invalid peaks format, falling back to regular load');
                
                // Fallback to normal loading
                const trackData = {
                    url: audioUrl,
                    title: '{{ $file->original_filename ?? ($file->filename ?? 'Audio File') }}',
                    artist: 'Universal Player',
                    id: '{{ $fileType === 'pitch_file' ? $file->uuid : $file->id }}',
                    type: '{{ $fileType }}'
                };
                window.globalAudioManager.loadTrack(trackData);
                return;
            }
        } else {
            console.log('No pre-generated waveform data, generating placeholder and loading audio normally');
            
            // Estimate duration from DB or use reasonable default
            const estimatedDuration = storedDurationFromDb || 180; // 3 minutes default
            
            // Generate placeholder waveform data
            const placeholderPeaks = window.globalAudioManager.generatePlaceholderWaveform(estimatedDuration);
            
            // Load with placeholder peaks first for immediate visual feedback
            const trackData = {
                url: audioUrl,
                title: {!! json_encode($file->original_filename ?? ($file->filename ?? 'Audio File')) !!},
                artist: 'Universal Player',
                id: {!! json_encode($fileType === 'pitch_file' ? $file->uuid : $file->id) !!},
                type: {!! json_encode($fileType) !!}
            };
            
            // Use placeholder peaks with estimated duration
            window.globalAudioManager.loadTrackWithPeaks(trackData, placeholderPeaks, estimatedDuration, containerSelector);
            alpineComponent.wavesurfer = window.globalAudioManager.waveSurfer;
            
            // Mark container as loaded with placeholder
            const wf = document.getElementById('waveform-' + instanceId + '-full');
            if (wf) {
                wf.classList.add('loaded', 'placeholder-waveform');
            }
            
            // Show duration immediately
            if (alpineComponent) {
                alpineComponent.playerState.duration = estimatedDuration;
                alpineComponent.playerState.totalDuration = formatTime(estimatedDuration);
            }
            
            return;
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

    /* Placeholder waveform styling */
    #waveform-universal-player-full.placeholder-waveform {
        opacity: 0.7;
        background: linear-gradient(45deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
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