<div id="snapshot-player-{{ $file->id }}"
    class="waveform-mini-player bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow mb-4"
    x-data="{ 
        playerState: { 
            isPlaying: false,
            isReady: false,
            currentTime: '00:00',
            totalDuration: '00:00'
        },
        instanceId: '{{ $file->id }}'
    }">

    <!-- File Header -->
    <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
        <div class="flex items-center flex-1 min-w-0">
            <i class="fas fa-music text-purple-500 mr-3"></i>
            <div class="truncate">
                <h3 class="text-sm font-medium text-gray-800 truncate"><span class="font-bold">{{ $file->name()
                        }}</span><span class="text-gray-500">.{{ $file->extension() }}</span></h3>
                <p class="text-xs text-gray-500">{{ $file->formattedSize }}</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('pitch-files.show', $file) }}"
                class="btn btn-xs bg-blue-500 hover:bg-blue-700 text-white">
                <i class="fas fa-eye mr-1"></i> View
            </a>
            @if($showDownloadButton)
            <a href="{{ route('download.pitch-file', $file->id) }}"
                class="btn btn-xs bg-green-500 hover:bg-green-700 text-white">
                <i class="fas fa-download mr-1"></i> Download
            </a>
            @endif
        </div>
    </div>

    <!-- Audio Waveform Container -->
    <div class="px-4 py-3">
        <div class="relative" >
            <!-- Waveform Visualization -->
            <div :id="'waveform-' + instanceId" class="h-24 rounded-md overflow-hidden bg-gray-50" wire:ignore>
            </div>

            <!-- Comment Markers -->
            <div class="comment-markers absolute inset-0 pointer-events-none" wire:ignore.self>
                @if($duration > 0)
                @foreach($comments as $comment)
                @php
                $position = ($comment->timestamp / max(0.1, $duration)) * 100;
                $position = min(max($position, 0), 100);
                $tooltipClass = $position < 15 ? 'left-0 transform-none' : ($position> 85 ? 'left-auto right-0
                    transform-none' : 'left-0 transform -translate-x-1/2');
                    @endphp
                    <div class="absolute h-full w-0.5 z-10 cursor-pointer pointer-events-auto group"
                        style="left: {{ $position }}%; background-color: rgba(59, 130, 246, 0.6);"
                        x-data="{ showTooltip: false }" @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        @click.stop="$dispatch('comment-marker-clicked-' + instanceId, { timestamp: {{ $comment->timestamp }} })">
                        <div
                            class="h-2 w-2 rounded-full -ml-0.5 bg-blue-500 absolute top-0 group-hover:scale-110 transition-transform">
                        </div>

                        <!-- Comment Tooltip -->
                        <div x-show="showTooltip" x-cloak
                            class="absolute bottom-full mb-2 p-2 bg-white rounded-lg shadow-lg border border-gray-200 w-56 z-50 {{ $tooltipClass }}"
                            @click.stop>
                            <div class="text-xs text-gray-500 flex items-center mb-1">
                                <img src="{{ $comment->user->profile_photo_url }}" alt="{{ $comment->user->name }}"
                                    class="h-4 w-4 rounded-full mr-1">
                                <span class="font-medium">{{ $comment->user->name }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ $comment->formattedTimestamp }}</span>
                            </div>
                            <div class="text-xs text-gray-800">
                                {{ \Illuminate\Support\Str::limit($comment->comment, 60) }}
                            </div>
                            @if($comment->resolved)
                            <div class="mt-1 text-xs text-green-600 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Resolved
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endif
            </div>
        </div>
    </div>

    <!-- Playback Controls -->
    <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <button :id="'playPauseBtn-' + instanceId"
                @click="playerState.isPlaying = !playerState.isPlaying; $dispatch('toggle-playback-' + instanceId, { playing: playerState.isPlaying })"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white hover:bg-primary-focus transition-colors">
                <!-- Play icon (shown when paused) -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" x-show="!playerState.isPlaying" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <!-- Pause icon (shown when playing) -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" x-show="playerState.isPlaying" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <div class="flex items-center space-x-1 text-xs">
                <span x-text="playerState.currentTime" class="font-medium">00:00</span>
                <span class="text-gray-500">/</span>
                <span x-text="playerState.totalDuration" class="text-gray-500">00:00</span>
            </div>
        </div>

        <div class="text-xs text-gray-500">
            {{ count($comments) }} {{ Str::plural('comment', count($comments)) }}
        </div>
    </div>
</div>

<!-- Initialize WaveSurfer for this instance -->
@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        // Get a unique ID for this instance
        const fileId = '{{ $file->id }}';
        const container = document.getElementById('snapshot-player-' + fileId);

        // Find the Alpine component
        const alpineComponent = Alpine.$data(container);
        const instanceId = alpineComponent.instanceId;

        // Initialize WaveSurfer with unique element IDs
        const waveformId = `waveform-${instanceId}`;
        const playPauseBtnId = `playPauseBtn-${instanceId}`;

        // Track audio loading state
        let audioLoaded = false;
        let audioLoadPromise = null;
        let persistedDuration = 0;
        let readyFired = false;

        // Create wavesurfer instance
        const wavesurfer = WaveSurfer.create({
            container: `#${waveformId}`,
            waveColor: '#d1d5db',
            progressColor: '#4f46e5',
            cursorColor: '#4f46e5',
            barWidth: 4,
            barRadius: 2,
            cursorWidth: 1,
            height: 96,
            //barGap: 1,
            normalize: true,
            responsive: true,
            fillParent: true
        });

        // Audio URL path
        const audioUrl = "{{ $file->fullFilePath }}";

        // Helper function to format time
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        // Helper function to update comment markers when duration changes
        const updateCommentMarkers = (duration) => {
            // Update the Livewire component's duration property
            Livewire.find('{{ $_instance->getId() }}').set('duration', duration);
            
            // Force a refresh to update the comment markers
            Livewire.dispatch('refresh');
            console.log(`Updating comment markers for instance ${instanceId} with duration:`, duration);
        };

        // Initialize audio loading function
        const initializeAudio = () => {
            // If we've already started loading the audio, return the existing promise
            if (audioLoadPromise) {
                console.log(`Audio loading already in progress for instance ${instanceId}, reusing promise`);
                return audioLoadPromise;
            }
            
            // If already loaded, just resolve immediately
            if (audioLoaded) {
                console.log(`Audio already loaded for instance ${instanceId}, resolving immediately`);
                return Promise.resolve();
            }
            
            console.log(`Starting new audio load for instance ${instanceId}`);
            
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
                    console.log(`WaveSurfer ready event fired from audio load for instance ${instanceId}`);
                    persistedDuration = wavesurfer.getDuration();
                    
                    // Update Alpine state
                    if (alpineComponent) {
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.totalDuration = formatTime(persistedDuration);
                    }
                    
                    // Update comment markers with the actual duration
                    updateCommentMarkers(persistedDuration);
                    
                    // Mark our promise as resolved - this is a custom property
                    audioLoadPromise.isResolved = true;
                    
                    resolve();
                });
                
                // Then load the URL
                console.log(`Calling WaveSurfer load with URL for instance ${instanceId}`);
                wavesurfer.load(audioUrl);
            });
            
            return audioLoadPromise;
        };

        // Check if we have pre-generated waveform data
        const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);
        if (hasPreGeneratedPeaks) {
            console.log(`Using pre-generated waveform data for instance ${instanceId}`);
            // Load audio with pre-generated peaks
            const peaks = @js($file->waveform_peaks_array);
            
            // Debug the peaks data
            console.log('Peaks data type:', typeof peaks);
            console.log('Is peaks an array?', Array.isArray(peaks));
            console.log('Peaks sample:', peaks && peaks.length > 0 ? peaks.slice(0, 10) : peaks);
            
            // Set peaks directly - this will visualize without loading audio
            if (Array.isArray(peaks[0])) {
                // We have min/max peaks format
                const minPeaks = peaks.map(point => point[0]); 
                const maxPeaks = peaks.map(point => point[1]);
                
                // Initialize the waveform with the pre-generated peaks
                wavesurfer.options.peaks = [minPeaks, maxPeaks];
                console.log(`Set min/max peaks for instance ${instanceId}`);
                
                // Use the stored duration if available, otherwise estimate
                const storedDuration = @js($file->duration);
                // Set a fake duration based on the peaks array length if stored one is not available
                const fakeLength = maxPeaks && maxPeaks.length ? maxPeaks.length : 0;
                // Avoid division by zero and ensure we have a positive value
                const estimatedDuration = fakeLength > 0 ? (fakeLength / 67) * 60 : 60; // Default to 60 seconds if we can't calculate
                
                // Use actual duration if available, otherwise use estimate
                const displayDuration = storedDuration || estimatedDuration;
                console.log(`Duration info for ${instanceId}:`, { stored: storedDuration, estimated: estimatedDuration, using: displayDuration });
                
                wavesurfer.options.duration = displayDuration;
                persistedDuration = displayDuration;
                
                // Mark waveform as "ready-like" state for the visualization
                setTimeout(() => {
                    document.getElementById(waveformId).classList.add('loaded');
                    
                    // Update Alpine state with accurate duration
                    if (alpineComponent) {
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.totalDuration = formatTime(displayDuration);
                    }
                    
                    // Update comment markers with the duration
                    updateCommentMarkers(displayDuration);
                }, 100);
            } else {
                console.log(`Peaks format is not as expected for instance ${instanceId}, falling back to normal loading`);
                initializeAudio();
            }
            
            // Audio will be loaded on first play
        } else {
            console.log(`No pre-generated waveform data available for instance ${instanceId}, generating on the fly`);
            initializeAudio();
        }

        // WaveSurfer events
        wavesurfer.on('ready', () => {
            if (!readyFired) {
                readyFired = true;
                console.log(`WaveSurfer ready for instance ${instanceId}`);
                const duration = wavesurfer.getDuration();
                persistedDuration = duration;

                // Update Alpine state
                if (alpineComponent) {
                    alpineComponent.playerState.isReady = true;
                    alpineComponent.playerState.totalDuration = formatTime(duration);
                }

                // Update comment markers
                updateCommentMarkers(duration);

                // Update Livewire prop through a custom event instead of direct property setting
                setTimeout(() => {
                    Livewire.dispatch('waveformReady');
                }, 10);
            }
        });

        wavesurfer.on('play', () => {
            console.log(`WaveSurfer play for instance ${instanceId}`);

            // Update Alpine state
            if (alpineComponent) {
                alpineComponent.playerState.isPlaying = true;
            }
        });

        wavesurfer.on('pause', () => {
            console.log(`WaveSurfer pause for instance ${instanceId}`);

            // Update Alpine state
            if (alpineComponent) {
                alpineComponent.playerState.isPlaying = false;
            }
        });

        wavesurfer.on('finish', () => {
            console.log(`WaveSurfer finish for instance ${instanceId}`);

            // Update Alpine state
            if (alpineComponent) {
                alpineComponent.playerState.isPlaying = false;
            }
        });

        // Update time display during playback
        wavesurfer.on('audioprocess', () => {
            const currentTime = wavesurfer.getCurrentTime();

            // Update Alpine state
            if (alpineComponent) {
                alpineComponent.playerState.currentTime = formatTime(currentTime);
            }
        });

        // Comment marker click handler - using instance-specific event name
        window.addEventListener(`comment-marker-clicked-${instanceId}`, event => {
            console.log(`Comment marker clicked at timestamp for instance ${instanceId}:`, event.detail.timestamp);
            const timestamp = event.detail.timestamp;
            
            // Handle seeking for pre-generated peaks when audio isn't loaded yet
            const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);
            if (hasPreGeneratedPeaks && !audioLoaded) {
                // Load audio first, then seek when ready
                console.log(`Loading audio before seeking from comment marker for instance ${instanceId}`);
                
                initializeAudio().then(() => {
                    console.log(`Audio loaded from comment marker for instance ${instanceId}, seeking to`, timestamp);
                    wavesurfer.seekTo(timestamp / wavesurfer.getDuration());
                });
                
                // Update Alpine state
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
                
                return;
            }
            
            // Audio might be in process of loading even if audioLoaded flag is true
            if (audioLoadPromise && !audioLoadPromise.isResolved) {
                console.log(`Audio still loading for instance ${instanceId}, waiting to seek from comment marker`);
                audioLoadPromise.then(() => {
                    console.log(`Seeking after audio load completed from comment marker for instance ${instanceId}`);
                    wavesurfer.seekTo(timestamp / wavesurfer.getDuration());
                });
                return;
            }
            
            // Normal seeking when audio is already loaded
            wavesurfer.seekTo(timestamp / wavesurfer.getDuration());
            wavesurfer.pause();

            // Update Alpine state
            if (alpineComponent) {
                alpineComponent.playerState.isPlaying = false;
            }
        });

        // Listen for play/pause toggle - using instance-specific event name
        window.addEventListener(`toggle-playback-${instanceId}`, (event) => {
            console.log(`Toggle playback event for instance ${instanceId}:`, event.detail.playing);

            if (event.detail.playing) {
                const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);
                // Check if audio is already loaded
                if (hasPreGeneratedPeaks && !audioLoaded) {
                    console.log(`First play for instance ${instanceId} - loading audio`);
                    
                    // Initialize audio and then play
                    initializeAudio().then(() => {
                        console.log(`Audio loaded for instance ${instanceId}, starting playback`);
                        setTimeout(() => {
                            wavesurfer.play();
                        }, 100);
                    });
                    
                    return; // Wait for audio to load before playing
                }
                
                // Audio might be in process of loading even if audioLoaded flag is true
                if (audioLoadPromise && !audioLoadPromise.isResolved) {
                    console.log(`Audio still loading for instance ${instanceId}, waiting to play`);
                    audioLoadPromise.then(() => {
                        console.log(`Now playing after audio load completed for instance ${instanceId}`);
                        wavesurfer.play();
                    });
                    return;
                }
                
                // Normal play when audio is already loaded
                wavesurfer.play();
            } else {
                wavesurfer.pause();
            }
        });

        // Livewire event listeners
        Livewire.on('seekToPosition', (data) => {
            // Only respond if this event is for this file instance
            if (data.fileId == parseInt(instanceId)) {
                console.log(`Livewire seekToPosition for file ${data.fileId}`);
                
                const hasPreGeneratedPeaks = @js($file->waveform_processed && $file->waveform_peaks);
                // Handle seeking for pre-generated peaks when audio isn't loaded yet
                if (hasPreGeneratedPeaks && !audioLoaded) {
                    // Load audio first, then seek when ready
                    console.log(`Loading audio before seeking from Livewire event for instance ${instanceId}`);
                    
                    initializeAudio().then(() => {
                        console.log(`Audio loaded from Livewire event for instance ${instanceId}, seeking to`, data.timestamp);
                        wavesurfer.seekTo(data.timestamp / wavesurfer.getDuration());
                        wavesurfer.pause();
                    });
                    
                    // Update Alpine state
                    if (alpineComponent) {
                        alpineComponent.playerState.isPlaying = false;
                    }
                    
                    return;
                }
                
                // Audio might be in process of loading even if audioLoaded flag is true
                if (audioLoadPromise && !audioLoadPromise.isResolved) {
                    console.log(`Audio still loading for instance ${instanceId}, waiting to seek from Livewire event`);
                    audioLoadPromise.then(() => {
                        console.log(`Seeking after audio load completed from Livewire event for instance ${instanceId}`);
                        wavesurfer.seekTo(data.timestamp / wavesurfer.getDuration());
                        wavesurfer.pause();
                    });
                    return;
                }
                
                // Normal seeking when audio is already loaded
                wavesurfer.seekTo(data.timestamp / wavesurfer.getDuration());
                wavesurfer.pause();

                // Update Alpine state
                if (alpineComponent) {
                    alpineComponent.playerState.isPlaying = false;
                }
            }
        });
    });
</script>
@endpush