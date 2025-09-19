// Simplified WaveSurfer implementation - the working section only

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

            } else if (peaks && Array.isArray(peaks)) {
                // Single array format
                console.log('Using single array peaks format');
                
                const storedDuration = @js($file->duration ?? null);
                const estimatedDuration = peaks.length > 0 ? (peaks.length / 67) * 60 : 60;
                const displayDuration = storedDuration || estimatedDuration;

                // Create WaveSurfer with single peaks array
                if (wavesurfer) {
                    wavesurfer.destroy();
                }
                
                wavesurfer = WaveSurfer.create({
                    container: containerElement,
                    url: audioUrl,
                    peaks: [new Float32Array(peaks)],
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

                // Store reference and add event listeners (same as above)
                alpineComponent.wavesurfer = wavesurfer;
                
                // Add the same event listeners as above
                wavesurfer.on('ready', () => {
                    const duration = wavesurfer.getDuration();
                    if (alpineComponent) {
                        alpineComponent.playerState.totalDuration = formatTime(duration);
                        alpineComponent.playerState.duration = duration;
                        alpineComponent.playerState.isReady = true;
                        alpineComponent.playerState.isPlaying = false;
                    }
                    
                    const waveformContainer = document.getElementById('waveform-' + instanceId + '-full');
                    if (waveformContainer) {
                        waveformContainer.classList.add('loaded');
                    }
                    
                    setupTimeline(duration);
                });

                wavesurfer.on('play', () => {
                    if (alpineComponent) {
                        alpineComponent.playerState.isPlaying = true;
                    }
                });

                wavesurfer.on('pause', () => {
                    if (alpineComponent) {
                        alpineComponent.playerState.isPlaying = false;
                    }
                });

                wavesurfer.on('audioprocess', () => {
                    const currentTime = wavesurfer.getCurrentTime();
                    if (alpineComponent) {
                        alpineComponent.playerState.currentTime = formatTime(currentTime);
                        alpineComponent.setCurrentPosition(currentTime);
                    }
                });
            }
        } else {
            console.log('No pre-generated waveform data, loading audio normally');