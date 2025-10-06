/**
 * Global Audio Manager
 * Coordinates audio playback across MixPitch PWA
 * Handles WaveSurfer instances, PWA media session, and cross-component communication
 */

class GlobalAudioManager {
    constructor() {
        this.waveSurfer = null;
        this.currentTrack = null;
        this.isInitialized = false;
        this.mediaSession = null;
        this.livewireComponent = null; // Store registered Livewire component
        this.alpineStore = null; // Store registered Alpine.js store
        this.lastPositionUpdate = 0; // For throttling position updates
        this.currentContainer = null; // Track current container for switching
        this.playbackState = {
            isPlaying: false,
            currentPosition: 0,
            duration: 0,
            volume: 1.0,
            isMuted: false
        };

        // A-B Loop state
        this.loopState = {
            enabled: false,
            start: null,
            end: null
        };

        // Visualization data (precomputed peaks & known duration)
        this.visualData = {
            peaks: null, // Array<Float32Array> or number[] per channel
            duration: null, // seconds
        };

        // Configuration
        this.config = {
            container: '#global-waveform-persistent', // Use persistent container
            fallbackContainer: '#global-waveform', // Fallback for existing implementations
            waveColor: 'rgba(139, 92, 246, 0.3)', // Purple with transparency
            progressColor: 'rgba(139, 92, 246, 0.8)', // Solid purple for progress
            cursorColor: '#8B5CF6', // Purple cursor
            height: 32,
            normalize: true,
            responsive: true,
            fillParent: true,
            interact: true, // Enable click-to-seek
            dragToSeek: true, // Enable drag-to-seek
            hideScrollbar: true,
            barWidth: 2,
            barGap: 1,
            barRadius: 2
        };

        this.init();
    }

    init() {
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
                this.initializePersistentContainer();
            });
        } else {
            this.setupEventListeners();
            this.initializePersistentContainer();
        }

        // Initialize Media Session API
        this.initializeMediaSession();

        // Handle page visibility changes for PWA
        document.addEventListener('visibilitychange', () => this.handleVisibilityChange());

        // Initialize service worker communication
        this.initializeServiceWorkerCommunication();

        // Setup comment marker listeners
        this.setupCommentMarkerListeners();

        // State restoration removed - persist directive handles this
        // this.restoreAudioState();
    }

    setupEventListeners() {
        // Listen for Livewire events from GlobalAudioPlayer component
        Livewire.on('globalPlayerTrackChanged', (event) => {
            const track = event.track;
            // Try to use precomputed peaks from the track if available
            const { peaks, duration } = this.extractVisualizationFromTrack(track);
            if (peaks && peaks.length) {
                this.loadTrackWithPeaks(track, peaks, duration || track?.duration || null);
            } else {
                this.loadTrack(track);
            }

            // Update queue in Alpine store if provided
            if (this.alpineStore && event.queue) {
                this.alpineStore.queue = event.queue;
                this.alpineStore.queuePosition = event.queuePosition || 0;
            }
        });

        Livewire.on('startPlayback', () => {
            this.play();
        });

        Livewire.on('pausePlayback', () => {
            this.pause();
        });

        Livewire.on('resumePlayback', () => {
            this.play();
        });

        Livewire.on('stopPlayback', () => {
            this.stop();
        });

        Livewire.on('seekToPosition', (event) => {
            this.seekTo(event.timestamp);
        });

        Livewire.on('volumeChanged', (event) => {
            this.setVolume(event.volume);
        });

        Livewire.on('muteToggled', (event) => {
            this.setMute(event.muted);
        });

        Livewire.on('updateMediaSession', (event) => {
            this.updateMediaSessionState(event);
        });

        // Navigation persistence removed - persist directive handles this
        // window.addEventListener('beforeunload', () => this.savePlaybackState());
        // window.addEventListener('load', () => this.restorePlaybackState());
        // document.addEventListener('livewire:navigating', ...) // Removed
        // document.addEventListener('livewire:navigated', ...) // Removed
    }

    initializePersistentContainer() {
        // Ensure the persistent container exists and is properly set up
        const persistentContainer = document.getElementById('persistent-waveform-container');
        let persistentAudio = document.getElementById('persistent-audio-element');

        if (!persistentContainer) {
            return;
        }

        if (!persistentAudio) {
            // Create the audio element if it doesn't exist
            persistentAudio = document.createElement('audio');
            persistentAudio.id = 'persistent-audio-element';
            persistentAudio.style.display = 'none';
            document.body.appendChild(persistentAudio);
        }

        // Set up persistent audio element with proper attributes
        persistentAudio.preload = 'metadata';
        persistentAudio.crossOrigin = 'anonymous';

        // Try to initialize WaveSurfer with persistent container if possible
        if (!this.isInitialized) {
            this.initializeWaveSurfer('#global-waveform-persistent');
        }
    }

    initializeWaveSurfer(container = null, options = {}) {
        // If WaveSurfer already exists and is playing, don't destroy it
        if (this.waveSurfer) {
            // Check if we need to switch containers
            if (container && container !== this.currentContainer) {
                return this.switchContainer(container);
            }
            // WaveSurfer already exists and no container switch needed
            return true;
        }

        const containerSelector = container || this.config.container;
        let containerElement = document.querySelector(containerSelector);

        // If primary container not found, try fallback
        if (!containerElement && containerSelector !== this.config.fallbackContainer) {
            containerElement = document.querySelector(this.config.fallbackContainer);
        }

        if (!containerElement) {
            return false;
        }

        try {
            // Store current container for future reference
            this.currentContainer = containerSelector;

            // Get the persistent audio element
            const audioElement = document.getElementById('persistent-audio-element');

            // Adjust config based on container type
            const isFullPlayer = containerSelector.includes('full');
            const config = {
                ...this.config,
                container: containerElement,
                height: isFullPlayer ? 120 : 32,
                waveColor: isFullPlayer ? 'rgba(139, 92, 246, 0.2)' : 'rgba(139, 92, 246, 0.3)',
                progressColor: isFullPlayer ? 'rgba(139, 92, 246, 0.9)' : 'rgba(139, 92, 246, 0.8)',
                barWidth: isFullPlayer ? 3 : 2,
                barGap: isFullPlayer ? 2 : 1
            };

            // Accept and store visualization data if provided
            if (options && (options.peaks || options.duration)) {
                this.visualData.peaks = options.peaks || this.visualData.peaks;
                this.visualData.duration = options.duration || this.visualData.duration;
            }

            // Apply precomputed peaks/duration to the visualization config if available
            if (this.visualData && (this.visualData.peaks || this.visualData.duration)) {
                if (this.visualData.peaks) {
                    // Normalize to array of Float32Array per channel as WaveSurfer expects
                    const peaks = Array.isArray(this.visualData.peaks) ? this.visualData.peaks : [];
                    config.peaks = peaks.map(channel => {
                        if (channel instanceof Float32Array) return channel;
                        return new Float32Array(channel);
                    });
                }
                if (this.visualData.duration) {
                    config.duration = this.visualData.duration;
                }
            }

            // Ensure audio element exists
            if (!audioElement) {
                audioElement = document.getElementById('persistent-audio-element');
                if (!audioElement) {
                    // Create it if it still doesn't exist
                    audioElement = document.createElement('audio');
                    audioElement.id = 'persistent-audio-element';
                    audioElement.style.display = 'none';
                    document.body.appendChild(audioElement);
                }
            }

            // Force MediaElement usage for streaming
            const shouldUseMediaElement = true;

            // Check if we should use media element for streaming
            if (shouldUseMediaElement && audioElement) {
                // Configure audio element for streaming
                audioElement.preload = 'metadata';
                audioElement.crossOrigin = 'anonymous';

                // Pass the audio element to WaveSurfer - it should use MediaElement automatically
                config.media = audioElement;

                this.waveSurfer = WaveSurfer.create(config);
                // Force immediate draw using provided peaks
                if (config.peaks && this.waveSurfer?.drawBuffer) {
                    try { this.waveSurfer.drawBuffer(); } catch (_) { }
                }
                this.usingMediaElement = true;
            } else {
                this.waveSurfer = WaveSurfer.create(config);
                if (config.peaks && this.waveSurfer?.drawBuffer) {
                    try { this.waveSurfer.drawBuffer(); } catch (_) { }
                }
                this.usingMediaElement = false;
            }

            // Set up WaveSurfer event listeners using shared method
            this.setupWaveSurferEvents();

            this.isInitialized = true;
            return true;

        } catch (error) {
            return false;
        }
    }

    switchContainer(newContainerSelector) {
        if (!this.waveSurfer) {
            return false;
        }

        const newContainerElement = document.querySelector(newContainerSelector);
        if (!newContainerElement) {
            return false;
        }

        try {
            // Save current playback state
            const wasPlaying = this.playbackState.isPlaying;
            const currentPosition = this.playbackState.currentPosition;
            const currentTrack = this.currentTrack;

            // Get current audio element to preserve audio state
            const audioElement = this.waveSurfer.getMediaElement ? this.waveSurfer.getMediaElement() : document.getElementById('persistent-audio-element');

            // Create new WaveSurfer in new container without destroying audio
            const isFullPlayer = newContainerSelector.includes('full');
            const config = {
                ...this.config,
                container: newContainerElement,
                height: isFullPlayer ? 120 : 32,
                waveColor: isFullPlayer ? 'rgba(139, 92, 246, 0.2)' : 'rgba(139, 92, 246, 0.3)',
                progressColor: isFullPlayer ? 'rgba(139, 92, 246, 0.9)' : 'rgba(139, 92, 246, 0.8)',
                barWidth: isFullPlayer ? 3 : 2,
                barGap: isFullPlayer ? 2 : 1
            };

            // Preserve visualization data (peaks/duration) when switching containers
            if (this.visualData && (this.visualData.peaks || this.visualData.duration)) {
                if (this.visualData.peaks) {
                    const peaks = Array.isArray(this.visualData.peaks) ? this.visualData.peaks : [];
                    config.peaks = peaks.map(channel => {
                        if (channel instanceof Float32Array) return channel;
                        return new Float32Array(channel);
                    });
                }
                if (this.visualData.duration) {
                    config.duration = this.visualData.duration;
                }
            }

            // Destroy old visualization but keep audio playing
            const oldWaveSurfer = this.waveSurfer;

            // If using MediaElement, pass the audio element
            if (this.usingMediaElement && audioElement) {
                config.media = audioElement;
            } else if (audioElement) {
                config.media = audioElement; // Fallback for regular mode
            }

            this.waveSurfer = WaveSurfer.create(config);

            // Update current container reference
            this.currentContainer = newContainerSelector;

            // Set up event listeners for new instance
            this.setupWaveSurferEvents();

            // If we had a track and it was playing, maintain that state
            if (currentTrack && audioElement) {
                // The audio element should continue playing seamlessly
                // Update the new WaveSurfer's state to match
                this.waveSurfer.on('ready', () => {
                    if (currentPosition > 0) {
                        this.seekTo(currentPosition);
                    }
                    if (wasPlaying && !this.playbackState.isPlaying) {
                        // Only resume if it's not already playing
                        this.play();
                    }
                });
            }

            // Now safely destroy the old visualization
            setTimeout(() => {
                if (oldWaveSurfer && oldWaveSurfer !== this.waveSurfer) {
                    oldWaveSurfer.destroy();
                }
            }, 100);

            return true;

        } catch (error) {
            return false;
        }
    }

    setupAudioElementEvents(audioElement) {
        if (!audioElement) return;

        // Remove existing listeners to avoid duplicates
        audioElement.removeEventListener('play', this.audioElementPlayHandler);
        audioElement.removeEventListener('pause', this.audioElementPauseHandler);
        audioElement.removeEventListener('ended', this.audioElementEndedHandler);
        audioElement.removeEventListener('timeupdate', this.audioElementTimeUpdateHandler);
        audioElement.removeEventListener('seeking', this.audioElementSeekingHandler);

        // Create bound handlers
        this.audioElementPlayHandler = () => {
            this.playbackState.isPlaying = true;
            if (this.alpineStore) {
                this.alpineStore.updatePlaybackState(true);
            }
            this.updateLivewireComponent('playbackStarted');
            this.updateMediaSessionPlaybackState('playing');
        };

        this.audioElementPauseHandler = () => {
            this.playbackState.isPlaying = false;
            if (this.alpineStore) {
                this.alpineStore.updatePlaybackState(false);
            }
            this.updateLivewireComponent('playbackPaused');
            this.updateMediaSessionPlaybackState('paused');
        };

        this.audioElementEndedHandler = () => {
            this.playbackState.isPlaying = false;
            this.playbackState.currentPosition = 0;
            this.updateLivewireComponent('trackEnded');
            this.updateMediaSessionPlaybackState('none');
        };

        this.audioElementTimeUpdateHandler = () => {
            this.playbackState.currentPosition = audioElement.currentTime;
            this.throttlePositionUpdate();

            // Handle A-B loop
            if (this.loopState.enabled && this.loopState.start !== null && this.loopState.end !== null) {
                if (this.playbackState.currentPosition >= this.loopState.end) {
                    audioElement.currentTime = this.loopState.start;
                }
            }
        };

        this.audioElementSeekingHandler = () => {
            this.playbackState.currentPosition = audioElement.currentTime;
            if (this.alpineStore) {
                this.alpineStore.currentPosition = this.playbackState.currentPosition;
            }
            this.callLivewireMethod('updatePosition', this.playbackState.currentPosition);
        };

        // Add event listeners
        audioElement.addEventListener('play', this.audioElementPlayHandler);
        audioElement.addEventListener('pause', this.audioElementPauseHandler);
        audioElement.addEventListener('ended', this.audioElementEndedHandler);
        audioElement.addEventListener('timeupdate', this.audioElementTimeUpdateHandler);
        audioElement.addEventListener('seeking', this.audioElementSeekingHandler);
    }

    setupWaveSurferEvents() {
        if (!this.waveSurfer) return;

        // Only set up WaveSurfer events if NOT using MediaElement
        // MediaElement events are handled by setupAudioElementEvents
        if (!this.usingMediaElement) {

            // Set up WaveSurfer event listeners (same as in initializeWaveSurfer)
            this.waveSurfer.on('ready', () => {
                this.playbackState.duration = this.waveSurfer.getDuration();

                if (this.alpineStore) {
                    this.alpineStore.duration = this.playbackState.duration;
                    this.alpineStore.clearLoading();
                }

                this.callLivewireMethod('updateDuration', this.playbackState.duration);
                this.updateLivewireComponent('waveformReady');
            });

            this.waveSurfer.on('play', () => {
                this.playbackState.isPlaying = true;

                if (this.alpineStore) {
                    this.alpineStore.updatePlaybackState(true);
                }

                this.updateLivewireComponent('playbackStarted');
                this.updateMediaSessionPlaybackState('playing');
            });

            this.waveSurfer.on('pause', () => {
                this.playbackState.isPlaying = false;

                if (this.alpineStore) {
                    this.alpineStore.updatePlaybackState(false);
                }

                this.updateLivewireComponent('playbackPaused');
                this.updateMediaSessionPlaybackState('paused');
            });

            this.waveSurfer.on('finish', () => {
                this.playbackState.isPlaying = false;
                this.playbackState.currentPosition = 0;
                this.updateLivewireComponent('trackEnded');
                this.updateMediaSessionPlaybackState('none');
            });

            this.waveSurfer.on('audioprocess', () => {
                this.playbackState.currentPosition = this.waveSurfer.getCurrentTime();
                this.throttlePositionUpdate();

                // Handle A-B loop
                if (this.loopState.enabled && this.loopState.start !== null && this.loopState.end !== null) {
                    if (this.playbackState.currentPosition >= this.loopState.end) {
                        this.seekTo(this.loopState.start);
                    }
                }
            });

            this.waveSurfer.on('seek', () => {
                this.playbackState.currentPosition = this.waveSurfer.getCurrentTime();

                if (this.alpineStore) {
                    this.alpineStore.currentPosition = this.playbackState.currentPosition;
                }

                this.callLivewireMethod('updatePosition', this.playbackState.currentPosition);
                // this.saveAudioState(); // Removed - persist handles this
            });
        }
    }

    loadTrack(track, options = {}) {
        if (!track || !track.url) {
            return;
        }

        // Update Alpine store with loading state
        if (this.alpineStore) {
            this.alpineStore.setLoading('Loading track...');
        }

        this.currentTrack = track;

        // If the caller didn't provide peaks/duration, but track contains them, apply them
        if (!options?.peaks && (track?.waveform_data || track?.waveform_peaks)) {
            const extracted = this.extractVisualizationFromTrack(track);
            if (extracted.peaks && extracted.peaks.length) {
                this.visualData.peaks = extracted.peaks;
                this.visualData.duration = extracted.duration || track.duration || this.visualData.duration;

                // Recreate the instance to apply peaks/duration if already initialized
                if (this.isInitialized) {
                    const container = this.currentContainer || this.config.container;
                    try { this.waveSurfer?.destroy(); } catch (_) { }
                    this.waveSurfer = null;
                    this.isInitialized = false;
                    this.initializeWaveSurfer(container, this.visualData);
                }
            }
        }

        // Update visualization data if provided
        if (options && (options.peaks || options.duration)) {
            this.visualData.peaks = options.peaks || this.visualData.peaks;
            this.visualData.duration = options.duration || this.visualData.duration;

            // If an instance exists, recreate it to apply peaks/duration reliably
            if (this.isInitialized) {
                const container = this.currentContainer || this.config.container;
                try {
                    if (this.waveSurfer) {
                        this.waveSurfer.destroy();
                    }
                } catch (e) { }
                this.waveSurfer = null;
                this.isInitialized = false;
                this.initializeWaveSurfer(container, this.visualData);
            }
        }

        // Initialize WaveSurfer if not already done
        if (!this.isInitialized && !this.initializeWaveSurfer()) {
            if (this.alpineStore) {
                this.alpineStore.clearLoading();
            }
            return;
        }

        // Load the audio file
        try {
            // If using MediaElement, set the source directly on the audio element only
            if (this.usingMediaElement) {
                const audioElement = document.getElementById('persistent-audio-element');
                if (audioElement) {
                    // Set source for streaming - this triggers the browser's native streaming
                    audioElement.src = track.url;

                    // Force WaveSurfer to use this audio element without downloading again
                    if (this.waveSurfer.setMediaElement && typeof this.waveSurfer.setMediaElement === 'function') {
                        this.waveSurfer.setMediaElement(audioElement);
                    }

                    // Set up audio element event listeners for MediaElement backend
                    this.setupAudioElementEvents(audioElement);

                    // Wait for metadata to load to get duration
                    audioElement.addEventListener('loadedmetadata', () => {
                        this.playbackState.duration = audioElement.duration || 0;
                        if (this.alpineStore) {
                            this.alpineStore.duration = this.playbackState.duration;
                            this.alpineStore.clearLoading();
                        }
                        this.callLivewireMethod('updateDuration', this.playbackState.duration);
                        this.updateLivewireComponent('waveformReady');
                    }, { once: true });

                } else {
                    this.waveSurfer.load(track.url);
                }
            } else {
                // Standard WebAudio loading
                this.waveSurfer.load(track.url);
            }

            // Update Media Session metadata
            this.updateMediaSessionMetadata(track);

            // Save state for PWA persistence
            this.saveCurrentTrack(track);

        } catch (error) {
            if (this.alpineStore) {
                this.alpineStore.clearLoading();
            }
        }
    }

    // Extract peaks/duration from a track object (supports multiple formats)
    extractVisualizationFromTrack(track) {
        try {
            let peaksSource = track?.waveform_peaks ?? track?.waveform_peaks_array ?? track?.waveform_data ?? null;
            let duration = track?.duration ?? null;
            if (!peaksSource) return { peaks: null, duration };

            // Parse JSON string if necessary
            if (typeof peaksSource === 'string') {
                try { peaksSource = JSON.parse(peaksSource); } catch (_) { }
            }

            // If object wrapper with data property
            if (peaksSource && peaksSource.data && Array.isArray(peaksSource.data)) {
                peaksSource = peaksSource.data;
            }

            // Normalize to array of channels as Float32Array
            if (Array.isArray(peaksSource) && peaksSource.length) {
                if (Array.isArray(peaksSource[0])) {
                    // Likely min/max pairs per pixel -> take max as single channel
                    const maxPeaks = peaksSource.map(p => Array.isArray(p) ? (p[1] ?? 0) : (p ?? 0));
                    return { peaks: [new Float32Array(maxPeaks)], duration };
                } else if (typeof peaksSource[0] === 'number') {
                    // Single channel array of peaks
                    return { peaks: [new Float32Array(peaksSource)], duration };
                } else if (peaksSource[0] instanceof Float32Array) {
                    return { peaks: peaksSource, duration };
                }
            }

            return { peaks: null, duration };
        } catch (_) {
            return { peaks: null, duration: track?.duration ?? null };
        }
    }

    // Generate placeholder waveform data for files without pre-generated peaks
    generatePlaceholderWaveform(duration = 180) {
        // Aim for roughly 67 samples per minute (similar to real waveform density)
        const samplesPerMinute = 67;
        const totalSamples = Math.floor((duration / 60) * samplesPerMinute);
        const peaks = new Float32Array(totalSamples);

        // Generate simple sine wave with random amplitude variations
        for (let i = 0; i < totalSamples; i++) {
            // Create base sine wave with varying frequency
            const baseWave = Math.sin(i * 0.1) * 0.6;
            // Add some randomness for more realistic appearance
            const randomVariation = (Math.random() - 0.5) * 0.4;
            // Combine and normalize to 0-1 range
            peaks[i] = Math.abs(baseWave + randomVariation);
        }

        return [peaks]; // Return as array for WaveSurfer compatibility
    }

    // Convenience method to load a track with precomputed peaks/duration and optional target container
    loadTrackWithPeaks(track, peaks, duration, container = null) {
        this.visualData.peaks = peaks || null;
        this.visualData.duration = duration || null;

        // Pick a visible container if none provided
        if (!container) {
            // Pick a visible container
            const isVisible = (el) => {
                if (!el) return false;
                const st = window.getComputedStyle(el);
                const r = el.getBoundingClientRect();
                return st.display !== 'none' && st.visibility !== 'hidden' && r.width > 0 && r.height > 0;
            };
            const fullEl = document.querySelector('#global-waveform-full');
            const miniEl = document.querySelector('#global-waveform');
            if (isVisible(fullEl)) container = '#global-waveform-full';
            else if (isVisible(miniEl)) container = '#global-waveform';
            else if (fullEl) container = '#global-waveform-full';
            else if (miniEl) container = '#global-waveform';
        }

        // Ensure WaveSurfer is initialized with visualization data
        if (!this.isInitialized) {
            this.initializeWaveSurfer(container || this.currentContainer || this.config.container, this.visualData);
        } else if (container && container !== this.currentContainer) {
            this.switchContainer(container);
        } else {
            // Recreate to apply peaks if already initialized without them
            const targetContainer = container || this.currentContainer || this.config.container;
            try {
                if (this.waveSurfer) {
                    this.waveSurfer.destroy();
                }
            } catch (e) { }
            this.waveSurfer = null;
            this.isInitialized = false;
            this.initializeWaveSurfer(targetContainer, this.visualData);
        }

        // Load audio for playback
        this.loadTrack(track);
    }

    play() {
        if (this.usingMediaElement) {
            // Use the audio element directly for MediaElement backend
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement) {
                try {
                    audioElement.play();
                    this.pauseAllOtherPlayers();
                } catch (error) {
                    // Silently handle autoplay restrictions
                }
            }
        } else {
            // Use WaveSurfer for WebAudio backend
            if (!this.waveSurfer) {
                return;
            }

            try {
                this.waveSurfer.play();
                this.pauseAllOtherPlayers();
            } catch (error) {
                // Silently handle play errors
            }
        }
    }

    pause() {
        if (this.usingMediaElement) {
            // Use the audio element directly for MediaElement backend
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement) {
                try {
                    audioElement.pause();
                } catch (error) {
                    // Silently handle pause errors
                }
            }
        } else {
            // Use WaveSurfer for WebAudio backend
            if (!this.waveSurfer) {
                return;
            }

            try {
                this.waveSurfer.pause();
            } catch (error) {
                // Silently handle pause errors
            }
        }
    }

    stop() {
        if (!this.waveSurfer) {
            return;
        }

        try {
            this.waveSurfer.stop();
            this.playbackState.currentPosition = 0;
            this.playbackState.isPlaying = false;
            this.updateMediaSessionPlaybackState('none');
        } catch (error) {
            // Silently handle stop errors
        }
    }

    seekTo(time) {
        if (this.usingMediaElement) {
            // Use the audio element directly for MediaElement backend
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement && !isNaN(time) && time >= 0) {
                try {
                    audioElement.currentTime = time;
                } catch (error) {
                    // Silently handle seek errors
                }
            }
        } else {
            // Use WaveSurfer for WebAudio backend
            if (!this.waveSurfer) {
                return;
            }

            try {
                const duration = this.waveSurfer.getDuration();
                if (duration > 0) {
                    const seekPosition = time / duration;
                    this.waveSurfer.seekTo(seekPosition);
                }
            } catch (error) {
                // Silently handle seek errors
            }
        }
    }

    setVolume(volume) {
        if (this.usingMediaElement) {
            // Use the audio element directly for MediaElement backend
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement) {
                try {
                    audioElement.volume = volume;
                    this.playbackState.volume = volume;
                } catch (error) {
                    // Silently handle volume errors
                }
            }
        } else {
            // Use WaveSurfer for WebAudio backend
            if (!this.waveSurfer) {
                return;
            }

            try {
                this.waveSurfer.setVolume(volume);
                this.playbackState.volume = volume;
            } catch (error) {
                // Silently handle volume errors
            }
        }
    }

    setPlaybackRate(rate) {
        if (!this.waveSurfer) {
            return;
        }

        try {
            this.waveSurfer.setPlaybackRate(rate);
        } catch (error) {
            // Silently handle playback rate errors
        }
    }

    setMute(muted) {
        if (this.usingMediaElement) {
            // Use the audio element directly for MediaElement backend
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement) {
                try {
                    audioElement.muted = muted;
                    this.playbackState.isMuted = muted;
                } catch (error) {
                    // Silently handle mute errors
                }
            }
        } else {
            // Use WaveSurfer for WebAudio backend
            if (!this.waveSurfer) {
                return;
            }

            try {
                this.waveSurfer.setMute(muted);
                this.playbackState.isMuted = muted;
            } catch (error) {
                // Silently handle mute errors
            }
        }
    }

    // Pause all other audio players on the page
    pauseAllOtherPlayers() {
        // Pause native HTML5 audio elements, but skip our persistent audio element
        const persistentAudio = document.getElementById('persistent-audio-element');
        document.querySelectorAll('audio').forEach(audio => {
            if (audio !== persistentAudio && !audio.paused) {
                audio.pause();
            }
        });

        // Dispatch event to pause other WaveSurfer instances
        Livewire.dispatch('pause-all-tracks', { source: 'global-player' });
    }

    // Media Session API integration for PWA
    initializeMediaSession() {
        if ('mediaSession' in navigator) {
            this.mediaSession = navigator.mediaSession;

            // Set up action handlers
            this.mediaSession.setActionHandler('play', () => {
                this.updateLivewireComponent('togglePlayback');
            });

            this.mediaSession.setActionHandler('pause', () => {
                this.updateLivewireComponent('togglePlayback');
            });

            this.mediaSession.setActionHandler('previoustrack', () => {
                this.updateLivewireComponent('previousTrack');
            });

            this.mediaSession.setActionHandler('nexttrack', () => {
                this.updateLivewireComponent('nextTrack');
            });

            this.mediaSession.setActionHandler('seekbackward', (details) => {
                const seekTime = Math.max(0, this.playbackState.currentPosition - (details.seekOffset || 10));
                this.callLivewireMethod('seekTo', seekTime);
            });

            this.mediaSession.setActionHandler('seekforward', (details) => {
                const seekTime = Math.min(this.playbackState.duration, this.playbackState.currentPosition + (details.seekOffset || 10));
                this.callLivewireMethod('seekTo', seekTime);
            });
        }
    }

    updateMediaSessionMetadata(track) {
        if (!this.mediaSession) {
            return;
        }

        try {
            this.mediaSession.metadata = new MediaMetadata({
                title: track.title || 'Unknown Title',
                artist: track.artist || 'Unknown Artist',
                album: track.project_title || 'MixPitch',
                artwork: [
                    { src: '/logo-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/logo-512x512.png', sizes: '512x512', type: 'image/png' }
                ]
            });
        } catch (error) {
            // Silently handle metadata errors
        }
    }

    updateMediaSessionPlaybackState(state) {
        if (!this.mediaSession) {
            return;
        }

        try {
            this.mediaSession.playbackState = state;

            if (state === 'playing' || state === 'paused') {
                // Only set position state if we have valid values
                const duration = this.playbackState.duration || 0;
                const position = this.playbackState.currentPosition || 0;

                if (duration > 0 && position >= 0 && position <= duration &&
                    !isNaN(duration) && !isNaN(position) &&
                    isFinite(duration) && isFinite(position)) {

                    this.mediaSession.setPositionState({
                        duration: duration,
                        playbackRate: 1.0,
                        position: position
                    });
                }
            }
        } catch (error) {
            // Silently handle playback state errors
        }
    }

    updateMediaSessionState(data) {
        this.playbackState.currentPosition = data.position || this.playbackState.currentPosition;
        this.playbackState.duration = data.duration || this.playbackState.duration;
        this.updateMediaSessionPlaybackState(this.playbackState.isPlaying ? 'playing' : 'paused');
    }

    // Handle page visibility changes for PWA
    handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden - save current state
            this.savePlaybackState();
            // this.saveAudioState(); // Removed - persist handles this
        } else {
            // Page is visible - restore state if needed
            this.restorePlaybackState();
        }
    }

    // Persistence methods for PWA navigation
    saveCurrentTrack(track) {
        try {
            sessionStorage.setItem('global-audio-current-track', JSON.stringify(track));
        } catch (error) {
            // Silently handle storage errors
        }
    }

    savePlaybackState() {
        if (!this.currentTrack) {
            return;
        }

        try {
            const state = {
                track: this.currentTrack,
                position: this.playbackState.currentPosition,
                isPlaying: this.playbackState.isPlaying,
                volume: this.playbackState.volume,
                isMuted: this.playbackState.isMuted,
                timestamp: Date.now()
            };

            sessionStorage.setItem('global-audio-playback-state', JSON.stringify(state));
        } catch (error) {
            // Silently handle storage errors
        }
    }

    restorePlaybackState() {
        try {
            const saved = sessionStorage.getItem('global-audio-playback-state');
            if (!saved) {
                return;
            }

            const state = JSON.parse(saved);

            // Only restore if the state is recent (within 5 minutes)
            if (Date.now() - state.timestamp > 300000) {
                sessionStorage.removeItem('global-audio-playback-state');
                return;
            }

            // Restore the track and state via Livewire
            if (state.track) {
                this.updateLivewireComponent('playTrack', state.track);

                setTimeout(() => {
                    if (state.position > 0) {
                        this.callLivewireMethod('seekTo', state.position);
                    }
                    if (state.volume !== 1.0) {
                        this.callLivewireMethod('setVolume', state.volume);
                    }
                    if (state.isMuted) {
                        this.updateLivewireComponent('toggleMute');
                    }
                    if (state.isPlaying) {
                        this.updateLivewireComponent('togglePlayback');
                    }
                }, 1000); // Allow time for track to load
            }

        } catch (error) {
            sessionStorage.removeItem('global-audio-playback-state');
        }
    }

    // Helper method to communicate with Livewire component
    updateLivewireComponent(method, data = null) {
        try {
            if (data !== null) {
                Livewire.dispatch(method, data);
            } else {
                Livewire.dispatch(method);
            }
        } catch (error) {
            // Silently handle Livewire errors
        }
    }

    // Register the Livewire component for direct method calls
    registerLivewireComponent(component) {
        this.livewireComponent = component;
    }

    // Register the Alpine.js store for direct updates
    registerAlpineStore(store) {
        this.alpineStore = store;
    }

    // Throttle position updates to avoid excessive calls
    throttlePositionUpdate() {
        const now = Date.now();
        // Update Alpine store immediately for smooth UI, Livewire less frequently
        if (this.alpineStore) {
            this.alpineStore.updatePosition(this.playbackState.currentPosition);
        }

        // Only update Livewire every 1 second to avoid excessive server calls
        if (now - this.lastPositionUpdate > 1000) {
            this.lastPositionUpdate = now;
            this.callLivewireMethod('updatePosition', this.playbackState.currentPosition);
        }
    }

    // Helper method to directly call Livewire component methods (avoids event dispatch issues)
    callLivewireMethod(method, data = null) {
        try {
            // Use the registered component if available
            if (this.livewireComponent && typeof this.livewireComponent.call === 'function') {
                if (data !== null) {
                    this.livewireComponent.call(method, data);
                } else {
                    this.livewireComponent.call(method);
                }
                return;
            }

            // Fallback to event dispatch if direct call fails
            this.updateLivewireComponent(method, data);

        } catch (error) {
            // Fallback to event dispatch
            this.updateLivewireComponent(method, data);
        }
    }

    // Public API methods
    getCurrentTrack() {
        return this.currentTrack;
    }

    getCurrentState() {
        return { ...this.playbackState };
    }

    isReady() {
        return this.isInitialized && this.waveSurfer !== null;
    }

    // A-B Loop Methods
    setLoop(start, end) {
        if (start !== null && end !== null && start < end) {
            this.loopState.start = start;
            this.loopState.end = end;
            this.loopState.enabled = true;
        }
    }

    clearLoop() {
        this.loopState.enabled = false;
        this.loopState.start = null;
        this.loopState.end = null;
    }

    getLoopState() {
        return { ...this.loopState };
    }

    // PWA Service Worker Communication
    initializeServiceWorkerCommunication() {
        if ('serviceWorker' in navigator) {
            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', event => {
                this.handleServiceWorkerMessage(event);
            });
        }
    }

    handleServiceWorkerMessage(event) {
        const { type, payload } = event.data;

        switch (type) {
            case 'AUDIO_STATE_RESTORED':
                this.applyRestoredState(payload);
                break;

            case 'AUDIO_STATE_NOT_FOUND':
                break;

            case 'AUDIO_STATE_ERROR':
                break;
        }
    }

    // saveAudioState method removed - persist directive handles persistence

    async restoreAudioState() {
        if (!navigator.serviceWorker) {
            return;
        }

        try {
            const sw = await navigator.serviceWorker.ready;
            const messageChannel = new MessageChannel();

            // Set up response handler
            messageChannel.port1.onmessage = (event) => {
                this.handleServiceWorkerMessage(event);
            };

            // Request state restoration
            sw.active.postMessage({
                type: 'RESTORE_AUDIO_STATE'
            }, [messageChannel.port2]);

        } catch (error) {
            // Silently handle restoration errors
        }
    }

    applyRestoredState(state) {
        if (!state || !state.track) {
            return;
        }

        // Restore track and queue
        if (this.alpineStore) {
            this.alpineStore.setTrack(state.track, state.queue || [], state.queuePosition || 0);
            this.alpineStore.volume = state.volume || 1.0;
            this.alpineStore.isMuted = state.isMuted || false;

            // Restore loop state
            if (state.loopState) {
                this.loopState = state.loopState;
                this.alpineStore.loopEnabled = state.loopState.enabled;
                this.alpineStore.loopStart = state.loopState.start;
                this.alpineStore.loopEnd = state.loopState.end;
            }
        }

        // Load the track
        this.loadTrack(state.track);

        // Seek to saved position once loaded
        if (this.waveSurfer) {
            this.waveSurfer.on('ready', () => {
                if (state.position > 0) {
                    this.seekTo(state.position);
                }
            }, { once: true });
        }
    }

    async preloadAudioFile(url) {
        if (!navigator.serviceWorker || !url) {
            return;
        }

        try {
            const sw = await navigator.serviceWorker.ready;
            sw.active.postMessage({
                type: 'PRELOAD_AUDIO',
                payload: url
            });

            // Audio preload requested
        } catch (error) {
            // Silently handle preload errors
        }
    }

    // Preload next track in queue for smooth transitions
    preloadNextTrack() {
        if (!this.alpineStore || this.alpineStore.queue.length === 0) {
            return;
        }

        const nextPosition = this.alpineStore.queuePosition + 1;
        if (nextPosition < this.alpineStore.queue.length) {
            const nextTrack = this.alpineStore.queue[nextPosition];
            if (nextTrack && nextTrack.url) {
                this.preloadAudioFile(nextTrack.url);
            }
        }
    }

    // Navigation persistence methods removed - persist directive handles this

    // Enhanced play method that uses persistent audio for autoplay restrictions
    playWithFallback() {
        // Prefer direct MediaElement playback when available
        if (this.usingMediaElement) {
            const audioElement = document.getElementById('persistent-audio-element');
            if (!audioElement) return;
            try {
                // Ensure src is set before playing
                if (this.currentTrack && audioElement.src !== this.currentTrack.url) {
                    audioElement.src = this.currentTrack.url;
                    // Make sure WaveSurfer is bound to this element
                    if (this.waveSurfer?.setMediaElement && typeof this.waveSurfer.setMediaElement === 'function') {
                        this.waveSurfer.setMediaElement(audioElement);
                    }
                    this.setupAudioElementEvents(audioElement);
                }

                // If metadata not loaded yet, wait for it then play
                if (isNaN(audioElement.duration) || !isFinite(audioElement.duration) || audioElement.duration === 0) {
                    audioElement.addEventListener('loadedmetadata', () => {
                        try { audioElement.play(); this.pauseAllOtherPlayers(); } catch (_) { this.usePersistentAudioFallback(); }
                    }, { once: true });
                }

                const playPromise = audioElement.play();
                if (playPromise && typeof playPromise.then === 'function') {
                    playPromise.catch(() => {
                        // If blocked, try WaveSurfer or fallback
                        try {
                            this.waveSurfer?.play();
                        } catch (_) {
                            this.usePersistentAudioFallback();
                        }
                    });
                }
                this.pauseAllOtherPlayers();
            } catch (_) {
                this.usePersistentAudioFallback();
            }
            return;
        }

        if (!this.waveSurfer) return;
        try {
            const playPromise = this.waveSurfer.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(error => {
                    if (error.name === 'NotAllowedError') this.usePersistentAudioFallback();
                });
            }
            this.pauseAllOtherPlayers();
        } catch (_) {
            this.usePersistentAudioFallback();
        }
    }

    usePersistentAudioFallback() {
        const persistentAudio = document.getElementById('persistent-audio-element');
        if (!persistentAudio || !this.currentTrack) {
            return;
        }

        try {
            // Set up persistent audio element
            if (persistentAudio.src !== this.currentTrack.url) {
                persistentAudio.src = this.currentTrack.url;
                persistentAudio.currentTime = this.playbackState.currentPosition;
            }

            persistentAudio.volume = this.playbackState.volume;
            persistentAudio.muted = this.playbackState.isMuted;

            // Play the persistent audio
            persistentAudio.play().then(() => {
                this.playbackState.isPlaying = true;

                // Update stores
                if (this.alpineStore) {
                    this.alpineStore.updatePlaybackState(true);
                }

                // Set up event listeners for persistent audio
                this.setupPersistentAudioEvents(persistentAudio);
            }).catch(error => {
                // Silently handle persistent audio errors
            });

        } catch (error) {
            // Silently handle fallback errors
        }
    }

    setupPersistentAudioEvents(audioElement) {
        // Remove existing listeners to avoid duplicates
        audioElement.removeEventListener('timeupdate', this.persistentAudioTimeUpdate);
        audioElement.removeEventListener('ended', this.persistentAudioEnded);

        // Create bound methods for easy removal
        this.persistentAudioTimeUpdate = () => {
            this.playbackState.currentPosition = audioElement.currentTime;
            this.throttlePositionUpdate();
        };

        this.persistentAudioEnded = () => {
            this.playbackState.isPlaying = false;
            this.playbackState.currentPosition = 0;
            this.updateLivewireComponent('trackEnded');
        };

        // Add event listeners
        audioElement.addEventListener('timeupdate', this.persistentAudioTimeUpdate);
        audioElement.addEventListener('ended', this.persistentAudioEnded);
    }

    // Override the original play method to use enhanced version
    play() {
        this.playWithFallback();
    }

    // Comment marker methods
    renderCommentMarkers(comments, duration) {
        if (!comments || !duration || duration <= 0) {
            return;
        }

        // Find the waveform container
        const waveformContainer = this.getCurrentWaveformContainer();
        if (!waveformContainer) {
            return;
        }

        // Remove existing comment markers
        this.clearCommentMarkers(waveformContainer);

        // Create comment markers overlay if it doesn't exist
        let markersOverlay = waveformContainer.querySelector('.comment-markers-overlay');
        if (!markersOverlay) {
            markersOverlay = document.createElement('div');
            markersOverlay.className = 'comment-markers-overlay absolute inset-0 pointer-events-none';
            markersOverlay.style.position = 'absolute';
            markersOverlay.style.top = '0';
            markersOverlay.style.left = '0';
            markersOverlay.style.right = '0';
            markersOverlay.style.bottom = '0';
            markersOverlay.style.pointerEvents = 'none';
            markersOverlay.style.zIndex = '10';
            waveformContainer.appendChild(markersOverlay);
        }

        // Create markers for each grouped comment (supports both old single comment format and new grouped format)
        comments.forEach(markerData => {
            // Handle both old format (single comment) and new format (grouped comments)
            const isGrouped = markerData.comments && Array.isArray(markerData.comments);
            const timestamp = markerData.timestamp;
            const position = (timestamp / duration) * 100;
            const clampedPosition = Math.min(Math.max(position, 0), 100);
            const resolved = markerData.resolved;
            const count = markerData.count || 1;

            const marker = document.createElement('div');
            marker.className = 'comment-marker absolute h-full w-1 cursor-pointer group';
            marker.style.left = `${clampedPosition}%`;
            marker.style.background = resolved
                ? 'linear-gradient(to bottom, #22c55e, #10b981)'
                : 'linear-gradient(to bottom, #7c3aed, #4f46e5)';
            marker.style.pointerEvents = 'auto';
            marker.style.zIndex = '10';

            // Create marker dot with count badge
            const markerDot = document.createElement('div');
            markerDot.className = `h-4 w-4 rounded-full -ml-1.5 ${resolved ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600'} border-2 border-white shadow-lg absolute -top-1 group-hover:scale-125 transition-all duration-200`;

            const pulse = document.createElement('div');
            pulse.className = 'absolute inset-0 rounded-full bg-white/30 animate-pulse';
            markerDot.appendChild(pulse);

            // Add count badge if multiple comments
            if (count > 1) {
                const countBadge = document.createElement('div');
                countBadge.className = 'absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border border-white shadow-md';
                countBadge.textContent = count;
                markerDot.appendChild(countBadge);
            }

            marker.appendChild(markerDot);

            // Tooltip container above the marker (bounded size)
            const tooltip = document.createElement('div');
            tooltip.className = 'comment-tooltip absolute mb-2 bg-white';
            // Place above the marker head
            tooltip.style.bottom = '1.75rem';
            // Position defaults; will be refined based on container width
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.width = '20rem';
            tooltip.style.maxHeight = '20rem';
            tooltip.style.overflowY = 'auto';
            tooltip.style.border = '1px solid #e5e7eb';
            tooltip.style.borderRadius = '0.75rem';
            tooltip.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
            tooltip.style.padding = '0.75rem';
            tooltip.style.zIndex = '50';
            tooltip.style.display = 'none';

            // Group header (timestamp and count)
            const groupHeader = document.createElement('div');
            groupHeader.className = 'flex items-center justify-between mb-3 pb-2 border-b border-gray-200';
            const timestampEl = document.createElement('div');
            timestampEl.className = 'text-xs font-semibold text-purple-600';
            timestampEl.textContent = markerData.formatted_timestamp || this.formatTime(timestamp);
            groupHeader.appendChild(timestampEl);

            if (count > 1) {
                const countEl = document.createElement('div');
                countEl.className = 'text-xs text-gray-500';
                countEl.textContent = `${count} comments`;
                groupHeader.appendChild(countEl);
            }
            tooltip.appendChild(groupHeader);

            // All comments in group
            const commentsContainer = document.createElement('div');
            commentsContainer.className = 'space-y-3';

            const commentsToShow = isGrouped ? markerData.comments : [markerData];
            commentsToShow.forEach((comment, idx) => {
                const commentDiv = document.createElement('div');
                commentDiv.className = 'border-b border-gray-100 last:border-0 pb-3 last:pb-0';

                // Comment header
                const commentHeader = document.createElement('div');
                commentHeader.className = 'flex items-center mb-2';
                const userName = document.createElement('div');
                userName.className = 'text-xs font-semibold text-gray-900 flex-1';
                userName.textContent = comment.user?.name || comment.client_email || 'Client';
                commentHeader.appendChild(userName);

                if (comment.resolved) {
                    const resolvedBadge = document.createElement('div');
                    resolvedBadge.className = 'text-[9px] text-green-600 font-medium';
                    resolvedBadge.textContent = '✓ Resolved';
                    commentHeader.appendChild(resolvedBadge);
                }
                commentDiv.appendChild(commentHeader);

                // Comment content
                const commentBody = document.createElement('div');
                commentBody.className = 'text-xs text-gray-800 bg-gradient-to-r from-purple-50/50 to-indigo-50/50 rounded-lg p-2';
                commentBody.textContent = (comment.comment || '').slice(0, 140);
                commentDiv.appendChild(commentBody);

                // Replies preview
                if (Array.isArray(comment.replies) && comment.replies.length) {
                    const repliesDiv = document.createElement('div');
                    repliesDiv.className = 'mt-2 pl-3 border-l-2 border-purple-200';

                    const repliesLabel = document.createElement('div');
                    repliesLabel.className = 'text-[10px] text-gray-500 mb-1';
                    repliesLabel.textContent = `${comment.replies.length} ${comment.replies.length === 1 ? 'reply' : 'replies'}`;
                    repliesDiv.appendChild(repliesLabel);

                    comment.replies.slice(0, 2).forEach(r => {
                        const replyDiv = document.createElement('div');
                        replyDiv.className = 'text-[10px] text-gray-600 mb-1';
                        const replyName = document.createElement('span');
                        replyName.className = 'font-medium';
                        replyName.textContent = (r.user?.name || r.client_email || 'Client') + ': ';
                        replyDiv.appendChild(replyName);
                        replyDiv.appendChild(document.createTextNode((r.comment || '').slice(0, 80)));
                        repliesDiv.appendChild(replyDiv);
                    });

                    commentDiv.appendChild(repliesDiv);
                }

                commentsContainer.appendChild(commentDiv);
            });

            tooltip.appendChild(commentsContainer);

            marker.appendChild(tooltip);

            // Hover handlers for tooltip with delay
            let hideTimeout = null;

            marker.addEventListener('mouseenter', () => {
                // Clear any pending hide timeout
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }

                // Edge-aware horizontal placement so it doesn't clip
                try {
                    const containerRect = waveformContainer.getBoundingClientRect();
                    const containerWidth = containerRect.width || waveformContainer.clientWidth || 0;
                    const markerLeftPx = (clampedPosition / 100) * containerWidth;
                    const tooltipWidthPx = 320; // 20rem baseline
                    const margin = 8;

                    // Reset
                    tooltip.style.right = 'auto';
                    tooltip.style.left = '50%';
                    tooltip.style.transform = 'translateX(-50%)';

                    if (markerLeftPx < (tooltipWidthPx / 2 + margin)) {
                        // Near left edge: align left at marker
                        tooltip.style.left = '0';
                        tooltip.style.right = 'auto';
                        tooltip.style.transform = 'translateX(0)';
                    } else if ((containerWidth - markerLeftPx) < (tooltipWidthPx / 2 + margin)) {
                        // Near right edge: align right at marker
                        tooltip.style.left = 'auto';
                        tooltip.style.right = '0';
                        tooltip.style.transform = 'translateX(0)';
                    }
                } catch (_) { }

                tooltip.style.display = 'block';
            });

            marker.addEventListener('mouseleave', () => {
                // Delay hiding tooltip by 1 second to allow moving to tooltip
                hideTimeout = setTimeout(() => {
                    tooltip.style.display = 'none';
                }, 1000);
            });

            // Keep tooltip visible when hovering over it
            tooltip.addEventListener('mouseenter', () => {
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }
            });

            // Hide tooltip immediately when leaving it
            tooltip.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });

            // Add click handler for seeking
            marker.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.seekToCommentTimestamp(timestamp);
            });

            markersOverlay.appendChild(marker);
        });
    }

    clearCommentMarkers(waveformContainer) {
        if (!waveformContainer) {
            waveformContainer = this.getCurrentWaveformContainer();
        }

        if (waveformContainer) {
            const markersOverlay = waveformContainer.querySelector('.comment-markers-overlay');
            if (markersOverlay) {
                markersOverlay.innerHTML = '';
            }
        }
    }

    getCurrentWaveformContainer() {
        const isVisible = (el) => {
            if (!el) return false;
            const style = window.getComputedStyle(el);
            const rect = el.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        };

        // Prefer active container if visible
        if (this.currentContainer) {
            const active = typeof this.currentContainer === 'string' ? document.querySelector(this.currentContainer) : this.currentContainer;
            if (isVisible(active)) return active;
        }

        // Prefer visible full player container
        let full = document.querySelector('#global-waveform-full');
        if (isVisible(full)) return full;

        // Then visible mini player container
        let mini = document.querySelector('#global-waveform');
        if (isVisible(mini)) return mini;

        // Fallbacks
        const fallback = document.querySelector(this.config.fallbackContainer);
        if (fallback) return fallback;
        const persistent = document.querySelector(this.config.container);
        return persistent || null;
    }

    findBestContainerSelector() {
        const el = this.getCurrentWaveformContainer();
        if (!el) return this.config.fallbackContainer;
        if (el.id) return `#${el.id}`;
        return this.config.fallbackContainer;
    }

    seekToCommentTimestamp(timestamp) {
        if (!this.waveSurfer || !this.playbackState.duration || this.playbackState.duration <= 0) {
            return;
        }

        const seekPosition = timestamp / this.playbackState.duration;

        if (this.usingMediaElement) {
            const audioElement = document.getElementById('persistent-audio-element');
            if (audioElement) {
                audioElement.currentTime = timestamp;
            }
        } else {
            this.waveSurfer.seekTo(seekPosition);
        }

        // Pause playback when seeking from comment
        this.pause();

        // Update position state
        this.playbackState.currentPosition = timestamp;
        if (this.alpineStore) {
            this.alpineStore.currentPosition = timestamp;
        }
    }

    updateCommentMarkers(comments) {
        if (!comments || !this.playbackState.duration) {
            this.clearCommentMarkers();
            return;
        }

        this.renderCommentMarkers(comments, this.playbackState.duration);
    }

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '00:00';
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    // Setup comment marker event listeners
    setupCommentMarkerListeners() {
        // Listen for comment updates from Livewire
        Livewire.on('commentMarkersUpdated', (event) => {
            this.updateCommentMarkers(event.comments);
        });

        // Listen for waveform ready to render comments
        this.waveSurfer?.on('ready', () => {
            // Request comment markers from Livewire component
            this.callLivewireMethod('calculateCommentMarkers');
        });

        // Listen for keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // 'C' key for adding comment at current position (only if global player is active)
            if (e.key.toLowerCase() === 'c' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const activeElement = document.activeElement;
                // Only trigger if not typing in an input
                if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA' &&
                    activeElement.contentEditable !== 'true') {
                    e.preventDefault();
                    this.triggerAddCommentAtCurrentPosition();
                }
            }
        });
    }

    triggerAddCommentAtCurrentPosition() {
        // Check if global player is visible and has a supported track loaded
        if (this.currentTrack && (this.currentTrack.type === 'pitch_file' || this.currentTrack.type === 'project_file')) {
            this.callLivewireMethod('toggleCommentForm', this.playbackState.currentPosition);
        }
    }

    // Cleanup method
    destroy() {
        if (this.waveSurfer) {
            this.waveSurfer.destroy();
            this.waveSurfer = null;
        }
        this.isInitialized = false;
        this.currentTrack = null;

        // Clean up persistent audio
        const persistentAudio = document.getElementById('persistent-audio-element');
        if (persistentAudio) {
            persistentAudio.pause();
            persistentAudio.src = '';
        }

        // Clear comment markers
        this.clearCommentMarkers();
    }
}

// Initialize global instance
window.globalAudioManager = new GlobalAudioManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GlobalAudioManager;
}