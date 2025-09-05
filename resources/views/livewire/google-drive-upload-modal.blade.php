<div>
    <flux:modal show max-width="4xl" @close="closeModal">
        <div class="p-6">
            @if(!$isConnected)
                <!-- Connection Required State -->
                <div class="text-center py-8">
                    <div class="mb-6">
                        <svg class="w-16 h-16 mx-auto text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                            <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                            <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                            <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect Your Google Drive</h3>
                    <p class="text-gray-600 mb-6">
                        Connect your Google Drive account to easily import files into your project.
                    </p>
                    <div class="space-y-3">
                        <flux:button 
                            variant="primary" 
                            wire:click="connectGoogleDrive"
                            class="flex items-center gap-3"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                                <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                                <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                                <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                            </svg>
                            Connect Google Drive
                        </flux:button>
                        <flux:button variant="outline" wire:click="closeModal">
                            Cancel
                        </flux:button>
                    </div>
                </div>
            @else
                <!-- Connected State - File Browser -->
                <div class="space-y-4">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b pb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Import from Google Drive</h3>
                        <flux:button variant="outline" size="sm" wire:click="closeModal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </flux:button>
                    </div>

                    <!-- Search Bar -->
                    <div class="relative">
                        <flux:input 
                            wire:model.live.debounce.500ms="searchQuery"
                            placeholder="Search your Google Drive files..."
                            class="pl-10"
                        />
                        <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Breadcrumbs -->
                    @if(count($breadcrumbs) > 0)
                        <nav class="flex" aria-label="Breadcrumb">
                            <ol class="flex items-center space-x-2 text-sm">
                                @foreach($breadcrumbs as $index => $crumb)
                                    <li class="flex items-center">
                                        @if($index > 0)
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                        @if($index === count($breadcrumbs) - 1)
                                            <span class="text-gray-500">{{ $crumb['name'] }}</span>
                                        @else
                                            <button 
                                                wire:click="navigateToFolder('{{ $crumb['id'] }}')"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                {{ $crumb['name'] }}
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <!-- File List -->
                    <div class="border rounded-lg overflow-hidden" style="max-height: 400px; overflow-y: auto;">
                        @if($loading)
                            <div class="p-8 text-center">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p class="mt-2 text-gray-500">Loading files...</p>
                            </div>
                        @elseif(count($files) === 0)
                            <div class="p-8 text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500">No files found</p>
                            </div>
                        @else
                            <div class="divide-y">
                                @foreach($files as $file)
                                    <div 
                                        wire:click="selectFile({{ json_encode($file) }})"
                                        class="p-3 hover:bg-gray-50 cursor-pointer transition-colors {{ $selectedFile && $selectedFile['id'] === $file['id'] ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <!-- File Icon -->
                                            <div class="flex-shrink-0">
                                                @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                                    </svg>
                                                @elseif(str_starts_with($file['mimeType'], 'audio/'))
                                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z" clip-rule="evenodd" />
                                                    </svg>
                                                @elseif(str_starts_with($file['mimeType'], 'image/'))
                                                    <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="w-6 h-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                            
                                            <!-- File Info -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $file['name'] }}
                                                </p>
                                                <div class="flex items-center text-xs text-gray-500 space-x-2">
                                                    @if($file['mimeType'] !== 'application/vnd.google-apps.folder')
                                                        <span>{{ $file['size'] ?? 'Unknown size' }}</span>
                                                        <span>•</span>
                                                    @endif
                                                    <span>{{ $file['modifiedTime'] ?? 'Unknown date' }}</span>
                                                </div>
                                            </div>

                                            <!-- Action for Folders -->
                                            @if($file['mimeType'] === 'application/vnd.google-apps.folder')
                                                <button 
                                                    wire:click.stop="navigateToFolder('{{ $file['id'] }}')"
                                                    class="text-blue-600 hover:text-blue-800"
                                                >
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Footer Actions -->
                    @if($selectedFile && $selectedFile['mimeType'] !== 'application/vnd.google-apps.folder')
                        <div class="flex items-center justify-between border-t pt-4">
                            <div class="text-sm text-gray-600">
                                Selected: <strong>{{ $selectedFile['name'] }}</strong>
                            </div>
                            <div class="space-x-2">
                                <flux:button variant="outline" wire:click="closeModal">
                                    Cancel
                                </flux:button>
                                <flux:button 
                                    variant="primary" 
                                    wire:click="importSelectedFile"
                                    :disabled="$importing"
                                >
                                    @if($importing)
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Importing...
                                    @else
                                        Import File
                                    @endif
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </flux:modal>
</div>
