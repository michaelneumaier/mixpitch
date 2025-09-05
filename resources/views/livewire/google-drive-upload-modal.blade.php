<div>
    <flux:modal name="google-drive-modal" max-width="7xl">
        @if(!$isConnected)
            <!-- Connection Required State -->
            <div class="p-8 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-green-100 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                            <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                            <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                            <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">Connect Google Drive</h2>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Access your Google Drive files and import them directly into your project. 
                        Your files stay secure and private.
                    </p>
                    <flux:button 
                        variant="primary" 
                        wire:click="connectGoogleDrive"
                        class="px-8 py-3 text-lg font-medium"
                    >
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                            <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                            <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                            <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                        </svg>
                        Connect to Google Drive
                    </flux:button>
                </div>
            </div>
        @else
            <!-- Connected State - File Browser -->
            <div class="h-[600px] flex flex-col">
                <!-- Header with Search -->
                <div class="p-6 border-b bg-gray-50">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Import from Google Drive</h2>
                            <p class="text-sm text-gray-600 mt-1">Choose files to import into your project</p>
                        </div>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative">
                        <flux:input 
                            wire:model.live.debounce.500ms="searchQuery"
                            placeholder="Search your Google Drive files..."
                            class="pl-12 pr-4 py-3 w-full text-sm"
                        />
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        @if($searchQuery)
                            <button 
                                wire:click="$set('searchQuery', '')"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Navigation & Content -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Breadcrumbs -->
                    @if(count($breadcrumbs) > 0)
                        <div class="px-6 py-3 bg-white border-b">
                            <nav class="flex items-center space-x-1 text-sm" aria-label="Breadcrumb">
                                @foreach($breadcrumbs as $index => $crumb)
                                    @if($index > 0)
                                        <svg class="w-4 h-4 text-gray-300 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                    @if($index === count($breadcrumbs) - 1)
                                        <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md font-medium">{{ $crumb['name'] }}</span>
                                    @else
                                        <button 
                                            wire:click="navigateToFolder('{{ $crumb['id'] }}')"
                                            class="px-3 py-1.5 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-md transition-colors"
                                        >
                                            {{ $crumb['name'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </nav>
                        </div>
                    @endif

                    <!-- File List -->
                    <div class="flex-1 overflow-y-auto bg-white">
                        @if($loading)
                            <div class="flex flex-col items-center justify-center h-full p-8">
                                <div class="relative">
                                    <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                                            <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                                            <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                                            <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-4 text-gray-600 font-medium">Loading your Google Drive files...</p>
                                <p class="mt-1 text-sm text-gray-500">This may take a moment</p>
                            </div>
                        @elseif(count($files) === 0)
                            <div class="flex flex-col items-center justify-center h-full p-8">
                                <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($searchQuery)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        @endif
                                    </svg>
                                </div>
                                @if($searchQuery)
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No files found</h3>
                                    <p class="text-gray-600 text-center mb-4">
                                        No files match your search for "<span class="font-medium">{{ $searchQuery }}</span>"
                                    </p>
                                    <button 
                                        wire:click="$set('searchQuery', '')"
                                        class="text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        Clear search
                                    </button>
                                @else
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">This folder is empty</h3>
                                    <p class="text-gray-600 text-center">
                                        There are no files in this location.
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-2">
                                @foreach($files as $file)
                                    <div 
                                        wire:click="selectFile({{ json_encode($file) }})"
                                        class="group relative p-4 rounded-lg border transition-all duration-200 cursor-pointer
                                               {{ $selectedFile && $selectedFile['id'] === $file['id'] 
                                                  ? 'bg-blue-50 border-blue-200 shadow-sm' 
                                                  : 'border-gray-200 hover:border-gray-300 hover:shadow-sm' }}"
                                    >
                                        <div class="flex items-center space-x-4">
                                            <!-- File Icon -->
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center 
                                                            {{ $file['mimeType'] === 'application/vnd.google-apps.folder' 
                                                               ? 'bg-blue-100 text-blue-600' 
                                                               : (str_starts_with($file['mimeType'], 'audio/') 
                                                                  ? 'bg-green-100 text-green-600' 
                                                                  : (str_starts_with($file['mimeType'], 'image/') 
                                                                     ? 'bg-purple-100 text-purple-600' 
                                                                     : 'bg-gray-100 text-gray-600')) }}">
                                                    @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                                        </svg>
                                                    @elseif(str_starts_with($file['mimeType'], 'audio/'))
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z" clip-rule="evenodd" />
                                                        </svg>
                                                    @elseif(str_starts_with($file['mimeType'], 'image/'))
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- File Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2 mb-1">
                                                    <h4 class="font-medium text-gray-900 truncate">
                                                        {{ $file['name'] }}
                                                    </h4>
                                                    @if($selectedFile && $selectedFile['id'] === $file['id'])
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            Selected
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center text-sm text-gray-500 space-x-3">
                                                    @if($file['mimeType'] !== 'application/vnd.google-apps.folder')
                                                        <span class="flex items-center space-x-1">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                            </svg>
                                                            <span>{{ $file['formattedSize'] ?? 'Unknown size' }}</span>
                                                        </span>
                                                    @endif
                                                    <span class="flex items-center space-x-1">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>{{ $file['modifiedTime'] ?? 'Unknown date' }}</span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center space-x-2">
                                                @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                                    <button 
                                                        wire:click.stop="navigateToFolder('{{ $file['id'] }}')"
                                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                                                        title="Open folder"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    @if($selectedFile && $selectedFile['id'] === $file['id'])
                                                        <div class="p-2 text-blue-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                <!-- Footer Actions -->
                @if($selectedFile && $selectedFile['mimeType'] !== 'application/vnd.google-apps.folder')
                    <div class="border-t bg-gray-50 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-md flex items-center justify-center 
                                            {{ str_starts_with($selectedFile['mimeType'], 'audio/') 
                                               ? 'bg-green-100 text-green-600' 
                                               : (str_starts_with($selectedFile['mimeType'], 'image/') 
                                                  ? 'bg-purple-100 text-purple-600' 
                                                  : 'bg-gray-100 text-gray-600') }}">
                                    @if(str_starts_with($selectedFile['mimeType'], 'audio/'))
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z" clip-rule="evenodd" />
                                        </svg>
                                    @elseif(str_starts_with($selectedFile['mimeType'], 'image/'))
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ $selectedFile['name'] }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $selectedFile['formattedSize'] ?? 'Unknown size' }}
                                        @if(isset($selectedFile['modifiedTime']))
                                            • {{ $selectedFile['modifiedTime'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <flux:modal.close>
                                    <flux:button variant="outline">
                                        Cancel
                                    </flux:button>
                                </flux:modal.close>
                                <flux:button 
                                    variant="primary" 
                                    wire:click="importSelectedFile"
                                    :disabled="$importing"
                                    class="relative"
                                >
                                    @if($importing)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Importing...
                                    @else
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                        </svg>
                                        Import File
                                    @endif
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
