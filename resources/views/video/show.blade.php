<x-layouts.app-sidebar>
<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <!-- Breadcrumb Navigation -->
        <flux:card class="mb-2 !p-2">
            <nav class="flex items-center text-sm">
                <flux:button href="{{ $breadcrumbs['url'] }}" variant="ghost" size="sm">
                    @if(str_contains($breadcrumbs['icon'], 'folder'))
                        <flux:icon.folder />
                    @elseif(str_contains($breadcrumbs['icon'], 'paper-plane'))
                        <flux:icon.paper-airplane />
                    @elseif(str_contains($breadcrumbs['icon'], 'briefcase'))
                        <flux:icon.briefcase />
                    @elseif(str_contains($breadcrumbs['icon'], 'video'))
                        <flux:icon.play />
                    @else
                        <flux:icon.home />
                    @endif
                    {{ $breadcrumbs['title'] }}
                </flux:button>
                <flux:icon.chevron-right class="text-gray-400" size="xs" />
                <flux:heading size="sm" class="text-gray-700">
                    {{ $fileType === 'pitch_file' ? ($file->original_name ?? $file->file_name) : $file->file_name }}
                </flux:heading>
            </nav>
        </flux:card>

        <!-- Universal Video Player Component -->
        <livewire:universal-video-player 
            :file="$file" 
            :file-type="$fileType"
            :breadcrumbs="$breadcrumbs" />

        <!-- Navigation & Back Button -->
        <flux:card>
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-center sm:text-left">
                    <flux:heading size="lg" class="mb-1">Ready to continue?</flux:heading>
                    <flux:subheading>
                        @if($fileType === 'pitch_file')
                            Return to your pitch to manage more files and settings
                        @else
                            Return to your project to continue working
                        @endif
                    </flux:subheading>
                </div>
                
                <flux:button href="{{ $breadcrumbs['url'] }}" variant="primary">
                    <flux:icon.arrow-left />
                    @if($fileType === 'pitch_file')
                        Back to Pitch
                    @else  
                        Back to Project
                    @endif
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>


@push('scripts')
<!-- Video.js library -->
<link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet">
<script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>

<script>
    // Enhanced functionality for the universal video player
    document.addEventListener('DOMContentLoaded', function() {
        // Copy to clipboard functionality
        window.copyToClipboard = function(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-2"></i> Copied!';
                button.classList.add('bg-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-600');
                }, 2000);
            });
        };

        // Listen for Video.js ready event to update duration
        document.addEventListener('videojs-ready', function(event) {
            const duration = event.detail.duration;
            const formattedDuration = formatDuration(duration);
            
            // Update duration displays
            const durationElements = document.querySelectorAll('#file-duration, #video-duration');
            durationElements.forEach(el => {
                el.textContent = formattedDuration;
            });
        });

        // Format duration helper function
        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        // PWA integration for seamless navigation
        if ('serviceWorker' in navigator) {
            // Communicate with service worker for video state persistence
            navigator.serviceWorker.ready.then(function(registration) {
                // Setup communication channel for video state
                const channel = new MessageChannel();
                channel.port1.onmessage = function(event) {
                    if (event.data.type === 'VIDEO_STATE_UPDATE') {
                        // Handle video state updates from service worker
                        window.dispatchEvent(new CustomEvent('pwa-video-state', {
                            detail: event.data.state
                        }));
                    }
                };
                
                registration.active.postMessage({
                    type: 'SETUP_VIDEO_CHANNEL'
                }, [channel.port2]);
            });
        }

        // Keyboard shortcuts for video control
        document.addEventListener('keydown', function(e) {
            // Only activate shortcuts when not typing in an input
            if (e.target.tagName.toLowerCase() === 'input' || 
                e.target.tagName.toLowerCase() === 'textarea') {
                return;
            }

            switch(e.code) {
                case 'Space':
                    e.preventDefault();
                    // Trigger play/pause on the Livewire component
                    window.Livewire.emit('togglePlayback');
                    break;
                case 'ArrowLeft':
                    if (e.shiftKey) {
                        e.preventDefault();
                        // Skip back 10 seconds
                        window.Livewire.emit('skipBackward', 10);
                    }
                    break;
                case 'ArrowRight':
                    if (e.shiftKey) {
                        e.preventDefault();
                        // Skip forward 10 seconds
                        window.Livewire.emit('skipForward', 10);
                    }
                    break;
                case 'KeyF':
                    if (!e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                        // Toggle fullscreen
                        window.Livewire.emit('toggleFullscreen');
                    }
                    break;
            }
        });

        // Video-specific events
        document.addEventListener('video-timeupdate', function(event) {
            // Handle video time updates for commenting timestamps
            const currentTime = event.detail.currentTime;
            window.currentVideoTime = currentTime;
        });

        // Listen for video events to update UI
        document.addEventListener('video-loadeddata', function(event) {
            const videoData = event.detail;
            console.log('Video loaded:', videoData);
        });

        document.addEventListener('video-error', function(event) {
            const error = event.detail;
            console.error('Video error:', error);
            
            // Show user-friendly error message
            if (window.Livewire) {
                window.Livewire.emit('showVideoError', error.message);
            }
        });
    });
</script>
@endpush
</x-layouts.app-sidebar>