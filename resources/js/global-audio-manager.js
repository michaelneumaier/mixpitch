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
        const persistentAudio = document.getElementById('persistent-audio-element');

        if (!persistentContainer) {
            console.warn('Global Audio Manager: Persistent waveform container not found in DOM');
            return;
        }

        if (!persistentAudio) {
            console.warn('Global Audio Manager: Persistent audio element not found in DOM');
            return;
        }

        // Set up persistent audio element with proper attributes
        persistentAudio.preload = 'auto';
        persistentAudio.crossOrigin = 'anonymous';

        // Try to initialize WaveSurfer with persistent container if possible
        if (!this.isInitialized) {
            const success = this.initializeWaveSurfer('#global-waveform-persistent');
            if (success) {
                console.log('Global Audio Manager: Successfully initialized with persistent container');
            }
        }

        console.log('Global Audio Manager: Persistent container initialized');
    }

    initializeWaveSurfer(container = null) {
        // If WaveSurfer already exists and is playing, don't destroy it
        if (this.waveSurfer) {
            // Check if we need to switch containers
            if (container && container !== this.currentContainer) {
                console.log('Global Audio Manager: Switching WaveSurfer container from', this.currentContainer, 'to', container);
                return this.switchContainer(container);
            }
            // WaveSurfer already exists and no container switch needed
            console.log('Global Audio Manager: WaveSurfer already initialized, skipping');
            return true;
        }

        const containerSelector = container || this.config.container;
        let containerElement = document.querySelector(containerSelector);

        // If primary container not found, try fallback
        if (!containerElement && containerSelector !== this.config.fallbackContainer) {
            console.warn('Global Audio Manager: Primary container not found, trying fallback');
            containerElement = document.querySelector(this.config.fallbackContainer);
        }

        if (!containerElement) {
            console.warn('Global Audio Manager: No suitable container found, deferring initialization');
            return false;
        }

        try {
            // Store current container for future reference
            this.currentContainer = containerSelector;

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

            this.waveSurfer = WaveSurfer.create(config);
            console.log('Global Audio Manager: WaveSurfer created with persistent container:', containerSelector);

            // Set up WaveSurfer event listeners using shared method
            this.setupWaveSurferEvents();

            this.isInitialized = true;
            return true;

        } catch (error) {
            console.error('Global Audio Manager: Failed to initialize WaveSurfer:', error);
            return false;
        }
    }

    switchContainer(newContainerSelector) {
        if (!this.waveSurfer) {
            console.warn('Global Audio Manager: No WaveSurfer instance to switch');
            return false;
        }

        const newContainerElement = document.querySelector(newContainerSelector);
        if (!newContainerElement) {
            console.warn('Global Audio Manager: New container not found:', newContainerSelector);
            return false;
        }

        try {
            // Save current playback state
            const wasPlaying = this.playbackState.isPlaying;
            const currentPosition = this.playbackState.currentPosition;
            const currentTrack = this.currentTrack;

            console.log('Global Audio Manager: Switching container while preserving playback');

            // Get current audio element to preserve audio state
            const audioElement = this.waveSurfer.getMediaElement ? this.waveSurfer.getMediaElement() : null;

            // Create new WaveSurfer in new container without destroying audio
            const isFullPlayer = newContainerSelector.includes('full');
            const config = {
                ...this.config,
                container: newContainerElement,
                height: isFullPlayer ? 120 : 32,
                waveColor: isFullPlayer ? 'rgba(139, 92, 246, 0.2)' : 'rgba(139, 92, 246, 0.3)',
                progressColor: isFullPlayer ? 'rgba(139, 92, 246, 0.9)' : 'rgba(139, 92, 246, 0.8)',
                barWidth: isFullPlayer ? 3 : 2,
                barGap: isFullPlayer ? 2 : 1,
                media: audioElement // Reuse existing audio element if possible
            };

            // Destroy old visualization but keep audio playing
            const oldWaveSurfer = this.waveSurfer;
            this.waveSurfer = WaveSurfer.create(config);

            // Update current container reference
            this.currentContainer = newContainerSelector;

            // Set up event listeners for new instance
            this.setupWaveSurferEvents();

            // If we had a track and it was playing, maintain that state
            if (currentTrack && audioElement) {
                console.log('Global Audio Manager: Maintaining audio continuity during container switch');

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
            console.error('Global Audio Manager: Failed to switch container:', error);
            return false;
        }
    }

    setupWaveSurferEvents() {
        if (!this.waveSurfer) return;

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

    loadTrack(track) {
        if (!track || !track.url) {
            console.error('Global Audio Manager: Invalid track data provided', track);
            return;
        }

        // Update Alpine store with loading state
        if (this.alpineStore) {
            this.alpineStore.setLoading('Loading track...');
        }

        this.currentTrack = track;

        // Initialize WaveSurfer if not already done
        if (!this.isInitialized && !this.initializeWaveSurfer()) {
            console.error('Global Audio Manager: Could not initialize WaveSurfer for track loading');
            if (this.alpineStore) {
                this.alpineStore.clearLoading();
            }
            return;
        }

        // Load the audio file
        try {
            this.waveSurfer.load(track.url);

            // Update Media Session metadata
            this.updateMediaSessionMetadata(track);

            // Save state for PWA persistence
            this.saveCurrentTrack(track);

        } catch (error) {
            console.error('Global Audio Manager: Failed to load track:', error);
            if (this.alpineStore) {
                this.alpineStore.clearLoading();
            }
        }
    }

    play() {
        if (!this.waveSurfer) {
            console.warn('Global Audio Manager: No WaveSurfer instance available for play');
            return;
        }

        try {
            this.waveSurfer.play();
            this.pauseAllOtherPlayers();
        } catch (error) {
            console.error('Global Audio Manager: Failed to play:', error);
        }
    }

    pause() {
        if (!this.waveSurfer) {
            console.warn('Global Audio Manager: No WaveSurfer instance available for pause');
            return;
        }

        try {
            this.waveSurfer.pause();
        } catch (error) {
            console.error('Global Audio Manager: Failed to pause:', error);
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
            console.error('Global Audio Manager: Failed to stop:', error);
        }
    }

    seekTo(time) {
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
            console.error('Global Audio Manager: Failed to seek:', error);
        }
    }

    setVolume(volume) {
        if (!this.waveSurfer) {
            return;
        }

        try {
            this.waveSurfer.setVolume(volume);
            this.playbackState.volume = volume;
        } catch (error) {
            console.error('Global Audio Manager: Failed to set volume:', error);
        }
    }

    setPlaybackRate(rate) {
        if (!this.waveSurfer) {
            return;
        }

        try {
            this.waveSurfer.setPlaybackRate(rate);
            console.log('Global Audio Manager: Playback rate set to', rate);
        } catch (error) {
            console.error('Global Audio Manager: Failed to set playback rate:', error);
        }
    }

    setMute(muted) {
        if (!this.waveSurfer) {
            return;
        }

        try {
            this.waveSurfer.setMute(muted);
            this.playbackState.isMuted = muted;
        } catch (error) {
            console.error('Global Audio Manager: Failed to set mute:', error);
        }
    }

    // Pause all other audio players on the page
    pauseAllOtherPlayers() {
        // Pause native HTML5 audio elements
        document.querySelectorAll('audio').forEach(audio => {
            if (!audio.paused) {
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
            console.error('Global Audio Manager: Failed to update media session metadata:', error);
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
            console.error('Global Audio Manager: Failed to update media session playback state:', error);
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
            console.warn('Global Audio Manager: Could not save current track to session storage');
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
            console.warn('Global Audio Manager: Could not save playback state');
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
            console.warn('Global Audio Manager: Could not restore playback state');
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
            console.error(`Global Audio Manager: Failed to call Livewire method ${method}:`, error);
        }
    }

    // Register the Livewire component for direct method calls
    registerLivewireComponent(component) {
        this.livewireComponent = component;
        console.log('Global Audio Manager: Livewire component registered');
    }

    // Register the Alpine.js store for direct updates
    registerAlpineStore(store) {
        this.alpineStore = store;
        console.log('Global Audio Manager: Alpine store registered');
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
            console.warn(`Global Audio Manager: No registered component available, falling back to event dispatch for ${method}`);
            this.updateLivewireComponent(method, data);

        } catch (error) {
            console.error(`Global Audio Manager: Failed to call Livewire method ${method} directly:`, error);
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

            console.log('Global Audio Manager: A-B loop set', { start, end });
        }
    }

    clearLoop() {
        this.loopState.enabled = false;
        this.loopState.start = null;
        this.loopState.end = null;

        console.log('Global Audio Manager: A-B loop cleared');
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

            console.log('Global Audio Manager: Service worker communication initialized');
        }
    }

    handleServiceWorkerMessage(event) {
        const { type, payload } = event.data;

        switch (type) {
            case 'AUDIO_STATE_RESTORED':
                console.log('Global Audio Manager: Restored audio state from service worker');
                this.applyRestoredState(payload);
                break;

            case 'AUDIO_STATE_NOT_FOUND':
                console.log('Global Audio Manager: No saved audio state found');
                break;

            case 'AUDIO_STATE_ERROR':
                console.error('Global Audio Manager: Error restoring audio state:', payload);
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
            console.error('Global Audio Manager: Failed to restore audio state:', error);
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

            console.log('Global Audio Manager: Requested audio preload:', url);
        } catch (error) {
            console.error('Global Audio Manager: Failed to request audio preload:', error);
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
            console.warn('Global Audio Manager: No WaveSurfer instance available for play');
            return;
        }

        try {
            // Try normal WaveSurfer play first
            const playPromise = this.waveSurfer.play();

            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(error => {
                    if (error.name === 'NotAllowedError') {
                        console.log('Global Audio Manager: Autoplay blocked, using persistent audio fallback');
                        this.usePersistentAudioFallback();
                    } else {
                        console.error('Global Audio Manager: Failed to play:', error);
                    }
                });
            }

            this.pauseAllOtherPlayers();
        } catch (error) {
            console.error('Global Audio Manager: Failed to play:', error);
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
                console.log('Global Audio Manager: Using persistent audio element for playback');
                this.playbackState.isPlaying = true;

                // Update stores
                if (this.alpineStore) {
                    this.alpineStore.updatePlaybackState(true);
                }

                // Set up event listeners for persistent audio
                this.setupPersistentAudioEvents(persistentAudio);
            }).catch(error => {
                console.error('Global Audio Manager: Persistent audio fallback also failed:', error);
            });

        } catch (error) {
            console.error('Global Audio Manager: Failed to use persistent audio fallback:', error);
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
    }
}

// Initialize global instance
window.globalAudioManager = new GlobalAudioManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GlobalAudioManager;
}