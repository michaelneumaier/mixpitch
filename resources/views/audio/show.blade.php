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

        <!-- Universal Audio Player Component -->
        <livewire:universal-audio-player 
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
<!-- WaveSurfer.js library -->
<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>

<script>
    // Enhanced functionality for the universal audio player
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

        // Listen for WaveSurfer ready event to update duration
        document.addEventListener('wavesurfer-ready', function(event) {
            const duration = event.detail.duration;
            const formattedDuration = formatDuration(duration);
            
            // Update duration displays
            const durationElements = document.querySelectorAll('#file-duration, #audio-duration');
            durationElements.forEach(el => {
                el.textContent = formattedDuration;
            });
        });

        // Format duration helper function
        function formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        // PWA integration for seamless navigation
        if ('serviceWorker' in navigator) {
            // Communicate with service worker for audio state persistence
            navigator.serviceWorker.ready.then(function(registration) {
                // Setup communication channel for audio state
                const channel = new MessageChannel();
                channel.port1.onmessage = function(event) {
                    if (event.data.type === 'AUDIO_STATE_UPDATE') {
                        // Handle audio state updates from service worker
                        window.dispatchEvent(new CustomEvent('pwa-audio-state', {
                            detail: event.data.state
                        }));
                    }
                };
                
                registration.active.postMessage({
                    type: 'SETUP_AUDIO_CHANNEL'
                }, [channel.port2]);
            });
        }

        // Keyboard shortcuts for audio control
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
            }
        });
    });
</script>
@endpush
</x-layouts.app-sidebar>