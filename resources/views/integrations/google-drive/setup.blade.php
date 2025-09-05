<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                Google Drive Integration
                            </h1>
                            <p class="text-slate-600 dark:text-slate-400 mt-1">
                                Import audio files directly from your Google Drive
                            </p>
                        </div>
                        
                        @if($connectionStatus['connected'] ?? false)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/20 rounded-full">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-green-700 dark:text-green-400 text-sm font-medium">Connected</span>
                                </div>
                                
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

                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                            <div class="flex">
                                <flux:icon.check-circle class="size-5 text-green-400 mr-3 mt-0.5" />
                                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                            <div class="flex">
                                <flux:icon.exclamation-triangle class="size-5 text-red-400 mr-3 mt-0.5" />
                                <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(!($connectionStatus['connected'] ?? false))
                        <!-- Connection Setup -->
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-6">
                            <div class="text-center">
                                <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mb-4">
                                    <flux:icon.cloud-arrow-down class="size-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                
                                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-2">
                                    Connect Your Google Drive
                                </h3>
                                
                                <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">
                                    Connect your Google Drive account to import audio files directly into MixPitch. 
                                    Your files remain secure and we only access what you explicitly import.
                                </p>
                                
                                <flux:button 
                                    href="{{ route('integrations.google-drive.connect') }}"
                                    variant="primary" 
                                    icon="arrow-top-right-on-square"
                                >
                                    Connect Google Drive
                                </flux:button>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="mx-auto w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mb-3">
                                    <flux:icon.bolt class="size-6 text-green-600 dark:text-green-400" />
                                </div>
                                <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-1">Fast Import</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Import large audio files directly without manual uploading
                                </p>
                            </div>
                            
                            <div class="text-center">
                                <div class="mx-auto w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-full flex items-center justify-center mb-3">
                                    <flux:icon.shield-check class="size-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-1">Secure</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    We only access files you choose to import
                                </p>
                            </div>
                            
                            <div class="text-center">
                                <div class="mx-auto w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center mb-3">
                                    <flux:icon.musical-note class="size-6 text-orange-600 dark:text-orange-400" />
                                </div>
                                <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-1">Audio Focus</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Automatically filters to show only audio files
                                </p>
                            </div>
                        </div>
                    @else
                        <!-- Connected State -->
                        <div class="space-y-6">
                            <!-- Connection Info -->
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center">
                                    <flux:icon.check-circle class="size-5 text-green-500 mr-3" />
                                    <div class="flex-1">
                                        <p class="text-green-700 dark:text-green-300 font-medium">
                                            Google Drive Connected Successfully
                                        </p>
                                        @if($connectionStatus['connected_at'])
                                            <p class="text-green-600 dark:text-green-400 text-sm mt-1">
                                                Connected {{ \Carbon\Carbon::parse($connectionStatus['connected_at'])->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- File Browser -->
                            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">
                                    Browse & Import Files
                                </h3>
                                
                                @livewire('google-drive-file-browser')
                            </div>

                            <!-- Usage Guidelines -->
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">
                                    Import Guidelines
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-2">
                                            Supported Formats
                                        </h4>
                                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                                            <li>• MP3, WAV, FLAC</li>
                                            <li>• AAC, OGG</li>
                                            <li>• Maximum {{ config('googledrive.file_handling.max_file_size_mb') }}MB per file</li>
                                        </ul>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-medium text-slate-900 dark:text-slate-100 mb-2">
                                            Storage Notes
                                        </h4>
                                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                                            <li>• Files count toward your storage quota</li>
                                            <li>• Original files remain on Google Drive</li>
                                            <li>• Imported files are processed for playback</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>