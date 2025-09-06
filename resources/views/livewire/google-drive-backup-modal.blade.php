<div>
    <flux:modal name="syncOptions" :class="$isConnected ? 'w-full max-w-[1400px]' : 'w-full max-w-md'">
        @if(!$isConnected)
            <!-- Connection Required State -->
            <div class="p-8 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-20 h-20 mx-auto mb-6 bg-white rounded-2xl shadow-lg flex items-center justify-center">
                        <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">Connect Google Drive</h2>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Connect your Google Drive to backup project files safely and securely. 
                        Your files will be uploaded to your personal Google Drive account.
                    </p>
                    <flux:button 
                        variant="primary" 
                        wire:click="connectGoogleDrive"
                    >
                        <div class="flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span class="text-base font-medium">Connect to Google Drive</span>
                        </div>
                    </flux:button>
                </div>
            </div>
        @else
            <!-- Connected State - Backup Interface -->
            <div class="flex flex-col max-h-[80vh]">
                <!-- Mobile Tab Navigation (hidden on desktop) -->
                <div class="md:hidden border-b border-gray-200">
                    <nav class="flex" role="tablist">
                        <button 
                            wire:click="switchTab('files')"
                            class="flex-1 px-4 py-3 text-sm font-medium text-center transition-colors duration-200 {{ $activeTab === 'files' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                            role="tab"
                            aria-selected="{{ $activeTab === 'files' ? 'true' : 'false' }}"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full {{ $activeTab === 'files' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">1</span>
                                <span>Select Files</span>
                                @if(count($selectedFiles) > 0)
                                    <span class="ml-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                                        {{ count($selectedFiles) }}
                                    </span>
                                @endif
                            </div>
                        </button>
                        <button 
                            wire:click="switchTab('destination')"
                            class="flex-1 px-4 py-3 text-sm font-medium text-center transition-colors duration-200 {{ $activeTab === 'destination' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                            role="tab"
                            aria-selected="{{ $activeTab === 'destination' ? 'true' : 'false' }}"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full {{ $activeTab === 'destination' ? 'bg-blue-600 text-white' : ($selectedFolder ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600') }}">
                                    @if($selectedFolder && $activeTab !== 'destination')
                                        ✓
                                    @else
                                        2
                                    @endif
                                </span>
                                <span>Destination</span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Main Content Area -->
                <div class="md:flex flex-1 min-h-0">
                    <!-- Left Side - Files to Backup -->
                    <div class="w-full md:w-1/2 md:border-r border-gray-200 flex flex-col {{ $activeTab === 'files' ? '' : 'hidden md:flex' }}">
                        <div class="p-6 border-b bg-gray-50 flex-shrink-0">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">Files to Backup</h3>
                                <!-- Continue Button (mobile only) -->
                                <flux:button 
                                    wire:click="continueToDestination"
                                    variant="primary" 
                                    size="sm"
                                    class="md:hidden"
                                    :disabled="empty($selectedFiles)"
                                >
                                    Continue
                                </flux:button>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Select files to backup to Google Drive</p>
                            
                            <!-- File Selection Controls -->
                            <div class="flex items-center gap-2 mt-4">
                            <flux:button 
                                wire:click="selectAllFiles" 
                                variant="outline" 
                                size="xs"
                            >
                                Select All
                            </flux:button>
                            <flux:button 
                                wire:click="deselectAllFiles" 
                                variant="outline" 
                                size="xs"
                            >
                                Deselect All
                            </flux:button>
                                <span class="text-xs text-gray-500 ml-2">
                                    {{ count($selectedFiles) }} of {{ count($filesToBackup) }} selected
                                </span>
                            </div>
                        </div>
                        
                        <!-- Files List -->
                        <div class="flex-1 overflow-y-auto min-h-0">
                        @if(count($filesToBackup) === 0)
                            <div class="flex flex-col items-center justify-center h-full p-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                                    <flux:icon name="document" class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No files to backup</h3>
                                <p class="text-gray-600 text-center">
                                    This {{ $model instanceof \App\Models\Project ? 'project' : 'pitch' }} doesn't have any files to backup.
                                </p>
                            </div>
                        @else
                            <div class="p-4">
                                <div class="space-y-2">
                                    @foreach($filesToBackup as $file)
                                        <div class="group relative p-3 rounded-lg border {{ in_array($file['id'], $selectedFiles) ? 'bg-blue-50 border-blue-200' : 'border-gray-200 hover:border-gray-300' }} cursor-pointer transition-all duration-200"
                                             wire:click="toggleFileSelection({{ $file['id'] }})">
                                            <div class="flex items-center space-x-3">
                                                <!-- Checkbox -->
                                                <div class="flex-shrink-0">
                                                    <div class="w-5 h-5 rounded border-2 {{ in_array($file['id'], $selectedFiles) ? 'bg-blue-600 border-blue-600' : 'border-gray-300' }} flex items-center justify-center">
                                                        @if(in_array($file['id'], $selectedFiles))
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- File Icon -->
                                                <div class="flex-shrink-0">
                                                    <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                                        <flux:icon name="musical-note" class="w-4 h-4" />
                                                    </div>
                                                </div>
                                                
                                                <!-- File Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 truncate text-sm">{{ $file['name'] }}</h4>
                                                    <div class="flex items-center text-xs text-gray-500 space-x-2 mt-1">
                                                        <span>{{ $file['formatted_size'] }}</span>
                                                        <span>•</span>
                                                        <span>{{ $file['created_at'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>

                    <!-- Right Side - Google Drive Folder Browser -->
                    <div class="w-full md:w-1/2 flex flex-col {{ $activeTab === 'destination' ? '' : 'hidden md:flex' }}">
                        <div class="p-6 border-b bg-gray-50 flex-shrink-0">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">Select Backup Destination</h3>
                                <flux:button 
                                    wire:click="toggleCreateFolderForm"
                                    variant="outline" 
                                    size="sm"
                                    icon="folder-plus"
                                    :disabled="$showCreateFolderForm"
                                >
                                    New Folder
                                </flux:button>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Choose a folder in your Google Drive</p>
                            
                            <!-- Create Folder Form -->
                            @if($showCreateFolderForm)
                                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon name="folder-plus" class="w-4 h-4 text-blue-600" />
                                        <span class="text-sm font-medium text-blue-900">Create New Folder</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:field class="flex-1">
                                            <flux:input 
                                                wire:model.live="newFolderName"
                                                placeholder="Folder name..."
                                                size="sm"
                                                wire:keydown.enter="createFolder"
                                            />
                                            <flux:error name="newFolderName" />
                                        </flux:field>
                                        <flux:button 
                                            wire:click="createFolder"
                                            variant="primary" 
                                            size="sm"
                                            :disabled="$creatingFolder || !$newFolderName"
                                        >
                                            @if($creatingFolder)
                                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            @else
                                                Create
                                            @endif
                                        </flux:button>
                                        <flux:button 
                                            wire:click="cancelCreateFolder"
                                            variant="ghost" 
                                            size="sm"
                                        >
                                            Cancel
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Search Bar -->
                            <div class="relative">
                                <flux:input 
                                    wire:model.live.debounce.500ms="searchQuery"
                                    placeholder="Search folders..."
                                    class="pl-10 text-sm"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <flux:icon name="magnifying-glass" class="w-4 h-4 text-gray-400" />
                                </div>
                            </div>
                        </div>

                        <!-- Breadcrumbs -->
                        @if(count($breadcrumbs) > 0)
                            <div class="px-6 py-3 bg-white border-b flex-shrink-0">
                            <nav class="flex items-center space-x-1 text-sm" aria-label="Breadcrumb">
                                @foreach($breadcrumbs as $index => $crumb)
                                    @if($index > 0)
                                        <flux:icon name="chevron-right" class="w-3 h-3 text-gray-300" />
                                    @endif
                                    @if($index === count($breadcrumbs) - 1)
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">{{ $crumb['name'] }}</span>
                                    @else
                                        <button 
                                            wire:click="navigateToFolder('{{ $crumb['id'] }}')"
                                            class="px-2 py-1 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded text-xs transition-colors"
                                        >
                                            {{ $crumb['name'] }}
                                        </button>
                                    @endif
                                @endforeach
                                
                                <!-- Select Current Folder Button -->
                                <flux:button 
                                    wire:click="selectCurrentFolder"
                                    variant="outline" 
                                    size="xs"
                                    class="ml-3"
                                >
                                    Use This Folder
                                </flux:button>
                            </nav>
                            </div>
                        @endif

                        <!-- Folders List -->
                        <div class="flex-1 overflow-y-auto bg-white min-h-0">
                        @if($loading)
                            <div class="flex flex-col items-center justify-center h-full p-8">
                                <div class="w-12 h-12 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                                <p class="text-gray-600 font-medium">Loading folders...</p>
                            </div>
                        @elseif(count($folders) === 0)
                            <div class="flex flex-col items-center justify-center h-full p-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                                    <flux:icon name="folder" class="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No folders found</h3>
                                <p class="text-gray-600 text-center">
                                    @if($searchQuery)
                                        No folders match your search.
                                    @else
                                        This location doesn't contain any folders.
                                    @endif
                                </p>
                                @if($currentFolder === 'root')
                                    <flux:button 
                                        wire:click="selectCurrentFolder"
                                        variant="outline" 
                                        size="sm"
                                        class="mt-4"
                                    >
                                        Use My Drive Root
                                    </flux:button>
                                @endif
                            </div>
                        @else
                            <div class="p-4">
                                <div class="space-y-2">
                                    @foreach($folders as $folder)
                                        <div class="group relative p-3 rounded-lg border {{ $selectedFolder && $selectedFolder['id'] === $folder['id'] ? 'bg-blue-50 border-blue-200' : 'border-gray-200 hover:border-gray-300' }} transition-all duration-200">
                                            <div class="flex items-center space-x-3">
                                                <!-- Folder Icon -->
                                                <div class="flex-shrink-0">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                                        <flux:icon name="folder" class="w-4 h-4" />
                                                    </div>
                                                </div>
                                                
                                                <!-- Folder Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 truncate text-sm">{{ $folder['name'] }}</h4>
                                                </div>

                                                <!-- Actions -->
                                                <div class="flex items-center space-x-2">
                                                    <flux:button 
                                                        wire:click="selectFolder({{ json_encode($folder) }})"
                                                        variant="{{ $selectedFolder && $selectedFolder['id'] === $folder['id'] ? 'primary' : 'outline' }}" 
                                                        size="xs"
                                                    >
                                                        {{ $selectedFolder && $selectedFolder['id'] === $folder['id'] ? 'Selected' : 'Select' }}
                                                    </flux:button>
                                                    <flux:button 
                                                        wire:click="navigateToFolder('{{ $folder['id'] }}')"
                                                        variant="ghost" 
                                                        size="xs"
                                                        icon="arrow-right"
                                                    >
                                                    </flux:button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t bg-gray-50 px-4 md:px-6 py-4 flex-shrink-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Destination Info -->
                    <div class="text-sm text-gray-600 min-w-0">
                        @if($selectedFolder)
                            <span class="flex items-center gap-2">
                                <flux:icon name="folder" class="w-4 h-4 text-blue-600 flex-shrink-0" />
                                <span class="truncate">
                                    Destination: <strong class="text-gray-900">{{ $selectedFolder['name'] }}</strong>
                                </span>
                            </span>
                        @else
                            <span class="text-gray-500">No destination folder selected</span>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <flux:modal.close>
                            <flux:button variant="outline">
                                Cancel
                            </flux:button>
                        </flux:modal.close>
                        
                        <flux:button 
                            variant="primary" 
                            wire:click="backupSelectedFiles"
                            :disabled="$backingUp || empty($selectedFiles) || !$selectedFolder"
                        >
                            <div class="flex items-center gap-2">
                                @if($backingUp)
                                    <svg class="animate-spin h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Backing up...</span>
                                @else
                                    <flux:icon name="cloud-arrow-up" class="w-4 h-4" />
                                    <span class="hidden sm:inline">Backup to Google Drive</span>
                                    <span class="sm:hidden">Backup</span>
                                @endif
                            </div>
                        </flux:button>
                    </div>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>