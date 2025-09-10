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
                            <flux:button variant="outline" size="sm" disabled>
                                <flux:icon.backward />
                            </flux:button>

                            <!-- Play/Pause -->
                            <flux:button variant="primary"
                                @click="playerState.isPlaying = !playerState.isPlaying; $dispatch('toggle-playback-universal-player', { playing: playerState.isPlaying })">
                                <flux:icon.play x-show="!playerState.isPlaying" />
                                <flux:icon.pause x-show="playerState.isPlaying" />
                            </flux:button>

                            <!-- Next Track -->
                            <flux:button variant="outline" size="sm" disabled>
                                <flux:icon.forward />
                            </flux:button>
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
                                title="Toggle A-B loop">
                                <flux:icon.arrow-path />
                            </flux:button>

                            <!-- Clear Loop -->
                            <flux:button size="xs" x-show="loopStart !== null || loopEnd !== null"
                                @click="clearLoop()" variant="ghost" title="Clear loop">
                                <flux:icon.x-mark />
                            </flux:button>
                        </div>

                        <!-- View & Volume Controls -->
                        <div class="flex items-center gap-3">
                            <!-- Queue Toggle -->
                            <flux:button @click="showQueue = !showQueue" size="sm"
                                x-bind:variant="showQueue ? 'filled' : 'outline'" title="Toggle queue">
                                <flux:icon.list-bullet />
                                <span class="hidden sm:inline ml-1">Queue</span>
                            </flux:button>

                            <!-- Comments Toggle -->
                            <flux:button @click="showComments = !showComments" size="sm"
                                x-bind:variant="showComments ? 'filled' : 'outline'" title="Toggle comments">
                                <flux:icon.chat-bubble-left />
                                <span class="hidden sm:inline ml-1">Comments</span>
                            </flux:button>

                            <!-- Volume Control -->
                            <div class="flex items-center gap-2">
                                <flux:icon.speaker-wave class="text-gray-600 dark:text-gray-400" size="sm" />
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
                            <flux:button href="{{ $this->getDownloadUrl() }}" variant="outline" size="sm">
                                <flux:icon.arrow-down-tray />
                                Download
                            </flux:button>
                        </div>
                    </div>
                </flux:card>

                <!-- Queue Section -->
                <div x-show="showQueue" x-transition class="mb-2">
                    <flux:card class="p-4">
                        <flux:heading size="lg" class="mb-4 flex items-center gap-2">
                            <flux:icon.list-bullet class="text-blue-600 dark:text-blue-400" />
                            Queue
                        </flux:heading>

                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <div class="flex items-center gap-3">
                                    <flux:icon.play class="text-blue-600 dark:text-blue-400" />
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
                            <flux:icon.chat-bubble-left class="text-green-600 dark:text-green-400" />
                            Comments
                        </flux:heading>

                        <div class="space-y-3">
                            <div class="py-8 text-center">
                                <flux:icon.chat-bubble-left-ellipsis
                                    class="mx-auto mb-2 text-gray-400 dark:text-gray-500" size="lg" />
                                <p class="text-gray-600 dark:text-gray-400">No comments yet.</p>
                            </div>
                        </div>
                    </flux:card>
                </div>
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
            });

            wavesurfer.on('pause', () => {
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
                console.log('GlobalAudioManager: Pause event fired');
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
                if (useGlobalAudioManager) {
                    if (event.detail.playing) {
                        window.globalAudioManager.play();
                    } else {
                        window.globalAudioManager.pause();
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

        // Load audio
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