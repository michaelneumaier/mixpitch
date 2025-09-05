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
        @if($currentFolderId)
            <nav class="flex items-center space-x-1 text-sm text-slate-500 dark:text-slate-400 mb-4">
                <button wire:click="navigateToFolder(null)" class="hover:text-slate-700 dark:hover:text-slate-200">
                    <flux:icon.home class="size-4" />
                </button>
                <span>/</span>
                <span>Folder</span>
            </nav>
        @endif

        <!-- Loading State -->
        @if($isLoading && empty($files))
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-slate-600 dark:text-slate-400">Loading files...</span>
            </div>
        @endif

        <!-- File Grid -->
        @if(!empty($files))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($files as $file)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <!-- File Icon -->
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-lg {{ $file['isAudio'] ? 'bg-blue-100 dark:bg-blue-900/20' : 'bg-slate-100 dark:bg-slate-700' }}">
                            @if($file['isAudio'])
                                <flux:icon.musical-note class="size-6 text-blue-600 dark:text-blue-400" />
                            @elseif($file['mimeType'] === 'application/vnd.google-apps.folder')
                                <flux:icon.folder class="size-6 text-yellow-600 dark:text-yellow-400" />
                            @else
                                <flux:icon.document class="size-6 text-slate-600 dark:text-slate-400" />
                            @endif
                        </div>

                        <!-- File Name -->
                        <h4 class="text-sm font-medium text-slate-900 dark:text-slate-100 text-center mb-2 line-clamp-2" title="{{ $file['name'] }}">
                            {{ $file['name'] }}
                        </h4>

                        <!-- File Info -->
                        <div class="text-xs text-slate-500 dark:text-slate-400 text-center mb-3">
                            @if(isset($file['formattedSize']))
                                <span>{{ $file['formattedSize'] }}</span>
                            @endif
                            @if(isset($file['modifiedTime']))
                                <br>
                                <span>{{ \Carbon\Carbon::parse($file['modifiedTime'])->format('M j, Y') }}</span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-center space-x-2">
                            @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                <flux:button 
                                    wire:click="navigateToFolder('{{ $file['id'] }}')" 
                                    variant="ghost" 
                                    size="sm"
                                    icon="folder-open"
                                >
                                    Open
                                </flux:button>
                            @elseif($file['isAudio'])
                                <flux:button 
                                    wire:click="selectFileForImport({{ json_encode($file) }})" 
                                    variant="primary" 
                                    size="sm"
                                    icon="arrow-down-tray"
                                >
                                    Import
                                </flux:button>
                            @else
                                <span class="text-xs text-slate-400">Not supported</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Load More Button -->
            @if($hasMoreFiles)
                <div class="text-center mt-6">
                    <flux:button 
                        wire:click="loadMoreFiles" 
                        variant="ghost" 
                        :disabled="$isLoading"
                    >
                        @if($isLoading)
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2"></div>
                        @endif
                        Load More Files
                    </flux:button>
                </div>
            @endif
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