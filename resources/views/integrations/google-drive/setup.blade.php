<x-layouts.app-sidebar>
    <div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen p-2">
        <div class="mx-auto space-y-6">
            <!-- Header -->
            <flux:card class="mb-2 bg-white/50 backdrop-blur-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-purple-800 dark:from-gray-100 dark:to-purple-300 bg-clip-text text-transparent">
                            Google Drive Integration
                        </flux:heading>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Import audio files directly from your Google Drive
                        </p>
                    </div>
                    
                    @if($connectionStatus['connected'] ?? false)
                        <div class="flex items-center gap-3">
                            <flux:badge color="green" size="sm">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></div>
                                Connected
                            </flux:badge>
                            
                            <form action="{{ route('integrations.google-drive.disconnect') }}" method="POST" class="inline">
                                @csrf
                                <flux:button 
                                    type="submit" 
                                    variant="ghost" 
                                    size="sm"
                                    onclick="return confirm('Are you sure you want to disconnect Google Drive?')"
                                >
                                    Disconnect
                                </flux:button>
                            </form>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Flash Messages -->
            @if(session('success'))
                <flux:callout icon="check-circle" color="green">
                    <flux:callout.text>
                        {{ session('success') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if(session('error'))
                <flux:callout icon="exclamation-triangle" color="red">
                    <flux:callout.text>
                        {{ session('error') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if(!($connectionStatus['connected'] ?? false))
                <!-- Connection Setup -->
                <flux:card class="bg-white/50 backdrop-blur-lg">
                    <div class="text-center py-12">
                        <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mb-4">
                            <flux:icon.cloud-arrow-down class="size-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        
                        <flux:heading size="lg" class="mb-2">
                            Connect Your Google Drive
                        </flux:heading>
                        
                        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                            Connect your Google Drive account to import audio files directly into MixPitch. 
                            Your files remain secure and we only access what you explicitly import.
                        </p>
                        
                        <flux:button 
                            href="{{ route('integrations.google-drive.connect') }}"
                            variant="filled" 
                            color="blue"
                            icon="arrow-top-right-on-square"
                        >
                            Connect Google Drive
                        </flux:button>
                    </div>
                </flux:card>

                <!-- Features -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:card class="bg-white/50 backdrop-blur-lg text-center">
                        <div class="p-6">
                            <div class="mx-auto w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mb-3">
                                <flux:icon.bolt class="size-6 text-green-600 dark:text-green-400" />
                            </div>
                            <flux:heading size="md" class="mb-1">Fast Import</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Import large audio files directly without manual uploading
                            </p>
                        </div>
                    </flux:card>
                    
                    <flux:card class="bg-white/50 backdrop-blur-lg text-center">
                        <div class="p-6">
                            <div class="mx-auto w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-full flex items-center justify-center mb-3">
                                <flux:icon.shield-check class="size-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <flux:heading size="md" class="mb-1">Secure</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                We only access files you choose to import
                            </p>
                        </div>
                    </flux:card>
                    
                    <flux:card class="bg-white/50 backdrop-blur-lg text-center">
                        <div class="p-6">
                            <div class="mx-auto w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center mb-3">
                                <flux:icon.musical-note class="size-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <flux:heading size="md" class="mb-1">Audio Focus</flux:heading>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Automatically filters to show only audio files
                            </p>
                        </div>
                    </flux:card>
                </div>
            @else
                <!-- Connected State -->
                <div class="space-y-6">
                    <!-- Connection Info -->
                    <flux:callout icon="check-circle" color="green">
                        <flux:callout.heading>Google Drive Connected Successfully</flux:callout.heading>
                        <flux:callout.text>
                            @if($connectionStatus['connected_at'])
                                Connected {{ \Carbon\Carbon::parse($connectionStatus['connected_at'])->diffForHumans() }}
                            @endif
                        </flux:callout.text>
                    </flux:callout>

                    <!-- File Browser -->
                    <flux:card class="bg-white/50 backdrop-blur-lg">
                        <div class="border-b border-gray-200/50 dark:border-gray-700/50">
                            <flux:heading size="lg">Browse & Import Files</flux:heading>
                        </div>
                        <div>
                            @livewire('google-drive-file-browser')
                        </div>
                    </flux:card>

                    <!-- Usage Guidelines -->
                    <flux:card class="bg-white/50 backdrop-blur-lg">
                        <div class="border-b border-gray-200/50 dark:border-gray-700/50">
                            <flux:heading size="lg">Import Guidelines</flux:heading>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <flux:heading size="md" class="mb-2">
                                        Supported Formats
                                    </flux:heading>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• MP3, WAV, FLAC</li>
                                        <li>• AAC, OGG</li>
                                        <li>• Maximum {{ config('googledrive.file_handling.max_file_size_mb') }}MB per file</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <flux:heading size="md" class="mb-2">
                                        Storage Notes
                                    </flux:heading>
                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>• Files count toward your storage quota</li>
                                        <li>• Original files remain on Google Drive</li>
                                        <li>• Imported files are processed for playback</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-sidebar>