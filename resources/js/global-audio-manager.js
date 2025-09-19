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
            this.loadTrack(event.track);

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

    initializeWaveSurfer(container = null) {
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
                this.usingMediaElement = true;
            } else {
                this.waveSurfer = WaveSurfer.create(config);
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

    loadTrack(track) {
        if (!track || !track.url) {
            return;
        }

        // Update Alpine store with loading state
        if (this.alpineStore) {
            this.alpineStore.setLoading('Loading track...');
        }

        this.currentTrack = track;

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
        if (!this.waveSurfer) {
            return;
        }

        try {
            // Try normal WaveSurfer play first
            const playPromise = this.waveSurfer.play();

            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(error => {
                    if (error.name === 'NotAllowedError') {
                        this.usePersistentAudioFallback();
                    }
                });
            }

            this.pauseAllOtherPlayers();
        } catch (error) {
            // Try fallback
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

        // Create markers for each comment
        comments.forEach(comment => {
            const position = (comment.timestamp / duration) * 100;
            const clampedPosition = Math.min(Math.max(position, 0), 100);
            
            const marker = document.createElement('div');
            marker.className = 'comment-marker absolute h-full w-1 cursor-pointer group';
            marker.style.left = `${clampedPosition}%`;
            marker.style.background = comment.resolved 
                ? 'linear-gradient(to bottom, #22c55e, #10b981)' 
                : 'linear-gradient(to bottom, #7c3aed, #4f46e5)';
            marker.style.pointerEvents = 'auto';
            marker.style.zIndex = '10';

            // Create marker dot
            const markerDot = document.createElement('div');
            markerDot.className = `h-4 w-4 rounded-full -ml-1.5 ${comment.resolved ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600'} border-2 border-white shadow-lg absolute -top-1 group-hover:scale-125 transition-all duration-200`;
            
            const pulse = document.createElement('div');
            pulse.className = 'absolute inset-0 rounded-full bg-white/30 animate-pulse';
            markerDot.appendChild(pulse);
            marker.appendChild(markerDot);

            // Add click handler for seeking
            marker.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.seekToCommentTimestamp(comment.timestamp);
            });

            // Add tooltip with comment preview
            marker.title = `${this.formatTime(comment.timestamp)} - ${comment.comment.substring(0, 50)}${comment.comment.length > 50 ? '...' : ''}`;

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
        // Try persistent container first, then fallback
        let container = document.querySelector(this.config.container);
        if (!container) {
            container = document.querySelector(this.config.fallbackContainer);
        }
        return container;
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
        // Check if global player is visible and has a pitch file loaded
        if (this.currentTrack && this.currentTrack.type === 'pitch_file') {
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