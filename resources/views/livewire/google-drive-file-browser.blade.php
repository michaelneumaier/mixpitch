<div class="google-drive-file-browser" wire:poll.30s="checkConnectionStatus">
    <!-- Connection Status -->
    @if(!$isConnected)
        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-6 mb-6">
            <div class="flex items-center gap-3">
                <flux:icon.cloud-arrow-down class="size-6 text-slate-400" />
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                        Connect Google Drive
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 mt-1">
                        @if($needsReauth)
                            Your Google Drive connection expired. Please reconnect to continue importing files.
                        @else
                            Connect your Google Drive account to import audio files directly into your projects.
                        @endif
                    </p>
                </div>
                <flux:button 
                    href="{{ $this->getConnectionUrl() }}" 
                    variant="primary" 
                    size="sm"
                    icon="arrow-top-right-on-square"
                >
                    {{ $needsReauth ? 'Reconnect' : 'Connect' }} Google Drive
                </flux:button>
            </div>
            
            @if($connectionError)
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                    <p class="text-red-700 dark:text-red-400 text-sm">{{ $connectionError }}</p>
                </div>
            @endif
        </div>
    @else
        <!-- Search and Controls -->
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-1">
                <flux:input 
                    wire:model.live.debounce.500ms="searchQuery"
                    placeholder="Search Google Drive files..."
                    icon="magnifying-glass"
                />
            </div>
            <flux:button wire:click="refreshFiles" variant="ghost" size="sm" icon="arrow-path">
                Refresh
            </flux:button>
        </div>

        <!-- Breadcrumb Navigation -->
        @if(count($breadcrumbs) > 0)
            <nav class="flex items-center space-x-1 text-sm text-slate-500 dark:text-slate-400 mb-4" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $index => $crumb)
                    @if($index > 0)
                        <svg class="w-4 h-4 text-slate-300 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    @if($index === count($breadcrumbs) - 1)
                        <span class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-md font-medium">{{ $crumb['name'] }}</span>
                    @else
                        <button 
                            wire:click="navigateToFolder('{{ $crumb['id'] }}')"
                            class="px-3 py-1.5 text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-md transition-colors"
                        >
                            {{ $crumb['name'] }}
                        </button>
                    @endif
                @endforeach
            </nav>
        @endif

        <!-- Loading State -->
        @if($isLoading && empty($files))
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-slate-600 dark:text-slate-400">Loading files...</span>
            </div>
        @endif

        <!-- File List -->
        @if(!empty($files))
            <div class="space-y-2">
                @foreach($files as $file)
                    <div 
                        wire:click="selectFile({{ json_encode($file) }})"
                        class="group relative p-4 rounded-lg border transition-all duration-200 cursor-pointer
                               {{ isset($selectedFile) && $selectedFile && $selectedFile['id'] === $file['id'] 
                                  ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 shadow-sm' 
                                  : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-sm bg-white dark:bg-slate-800' }}"
                    >
                        <div class="flex items-center space-x-4">
                            <!-- File Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center 
                                            {{ $file['mimeType'] === 'application/vnd.google-apps.folder' 
                                               ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' 
                                               : (str_starts_with($file['mimeType'], 'audio/') 
                                                  ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400' 
                                                  : (str_starts_with($file['mimeType'], 'image/') 
                                                     ? 'bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' 
                                                     : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400')) }}">
                                    @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                        <flux:icon.folder class="w-5 h-5" />
                                    @elseif(str_starts_with($file['mimeType'], 'audio/'))
                                        <flux:icon.musical-note class="w-5 h-5" />
                                    @elseif(str_starts_with($file['mimeType'], 'image/'))
                                        <flux:icon.photo class="w-5 h-5" />
                                    @else
                                        <flux:icon.document class="w-5 h-5" />
                                    @endif
                                </div>
                            </div>
                            
                            <!-- File Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h4 class="font-medium text-slate-900 dark:text-slate-100 truncate">
                                        {{ $file['name'] }}
                                    </h4>
                                    @if(isset($selectedFile) && $selectedFile && $selectedFile['id'] === $file['id'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-400">
                                            Selected
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center text-sm text-slate-500 dark:text-slate-400 space-x-3">
                                    @if($file['mimeType'] !== 'application/vnd.google-apps.folder')
                                        <span class="flex items-center space-x-1">
                                            <flux:icon.document class="w-3 h-3" />
                                            <span>{{ $file['formattedSize'] ?? 'Unknown size' }}</span>
                                        </span>
                                    @endif
                                    <span class="flex items-center space-x-1">
                                        <flux:icon.calendar class="w-3 h-3" />
                                        <span>{{ $file['modifiedTime'] ?? 'Unknown date' }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-2">
                                @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                    <flux:button 
                                        wire:click.stop="navigateToFolder('{{ $file['id'] }}')"
                                        variant="ghost" 
                                        size="sm"
                                        icon="folder-open"
                                    >
                                        Open
                                    </flux:button>
                                @else
                                    @if(isset($selectedFile) && $selectedFile && $selectedFile['id'] === $file['id'])
                                        <div class="p-2 text-blue-600 dark:text-blue-400">
                                            <flux:icon.check class="w-4 h-4" />
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @elseif(!$isLoading)
            <!-- Empty State -->
            <div class="text-center py-12">
                <flux:icon.folder-open class="mx-auto h-12 w-12 text-slate-400" />
                <h3 class="mt-4 text-sm font-medium text-slate-900 dark:text-slate-100">No files found</h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    @if($searchQuery)
                        No files match your search query.
                    @else
                        This folder appears to be empty or contains no audio files.
                    @endif
                </p>
                @if($searchQuery)
                    <flux:button wire:click="$set('searchQuery', '')" variant="ghost" size="sm" class="mt-3">
                        Clear search
                    </flux:button>
                @endif
            </div>
        @endif

        <!-- Import Selected File Section -->
        @if(isset($selectedFile) && $selectedFile && $selectedFile['mimeType'] !== 'application/vnd.google-apps.folder')
            <div class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-6 py-4 mt-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-md flex items-center justify-center 
                                    {{ str_starts_with($selectedFile['mimeType'], 'audio/') 
                                       ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400' 
                                       : (str_starts_with($selectedFile['mimeType'], 'image/') 
                                          ? 'bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' 
                                          : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400') }}">
                            @if(str_starts_with($selectedFile['mimeType'], 'audio/'))
                                <flux:icon.musical-note class="w-4 h-4" />
                            @elseif(str_starts_with($selectedFile['mimeType'], 'image/'))
                                <flux:icon.photo class="w-4 h-4" />
                            @else
                                <flux:icon.document class="w-4 h-4" />
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-slate-100 text-sm">{{ $selectedFile['name'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $selectedFile['formattedSize'] ?? 'Unknown size' }}
                                @if(isset($selectedFile['modifiedTime']))
                                    • {{ $selectedFile['modifiedTime'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <flux:button 
                            wire:click="$set('selectedFile', [])" 
                            variant="ghost" 
                            size="sm"
                        >
                            Clear Selection
                        </flux:button>
                        <flux:button 
                            wire:click="selectFileForImport({{ json_encode($selectedFile) }})" 
                            variant="primary" 
                            size="sm"
                            icon="arrow-down-tray"
                        >
                            Import File
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Import Confirmation Modal -->
    @if($showImportModal && !empty($selectedFile))
        <flux:modal :show="$showImportModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                        Import File from Google Drive
                    </h3>
                    <flux:button wire:click="closeImportModal" variant="ghost" size="sm" icon="x-mark" />
                </div>
                
                <div class="space-y-4 mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <flux:icon.musical-note class="size-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                                {{ $selectedFile['name'] ?? '' }}
                            </h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $selectedFile['formattedSize'] ?? '' }} • {{ $selectedFile['mimeType'] ?? '' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-md p-3">
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            This file will be downloaded from Google Drive and imported into your account. 
                            It will count toward your storage quota.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <flux:button wire:click="closeImportModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="importSelectedFile" variant="primary" :disabled="$isLoading">
                        @if($isLoading)
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2"></div>
                        @endif
                        Import File
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>