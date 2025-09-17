{{-- Livewire FileList Component --}}
<div>
    <!-- Files List Section Header -->
    <div class="flex items-center gap-3 mb-2">
        @if($enableBulkActions)
            <!-- Bulk Selection Checkbox -->
            <div class="flex items-center">
                <label class="relative" 
                       aria-label="Select all files">
                    <input 
                        type="checkbox" 
                        wire:model.live="allFilesSelected"
                        wire:click="toggleSelectAll"
                        class="sr-only"
                        aria-describedby="select-all-description"
                        @if($files->isEmpty()) disabled @endif
                    />
                    <div class="w-5 h-5 border-2 rounded transition-all duration-200 cursor-pointer flex items-center justify-center
                        {{ $this->allFilesSelected ? $this->resolvedColorScheme['accent_bg'] . ' ' . $this->resolvedColorScheme['accent_border'] : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }}
                        {{ $files->isEmpty() ? 'opacity-50 cursor-not-allowed' : 'hover:border-gray-400 dark:hover:border-gray-500' }}">
                        @if($this->allFilesSelected)
                            <flux:icon.check class="w-3 h-3 text-white" />
                        @elseif($this->hasSelectedFiles)
                            <div class="w-2 h-0.5 bg-gray-400 dark:bg-gray-500"></div>
                        @endif
                    </div>
                </label>
            </div>
        @endif

        <flux:icon name="{{ $headerIcon }}" variant="solid" class="w-6 h-6 {{ $this->resolvedColorScheme['icon'] }}" />
        <div class="flex items-center justify-between w-full">
            @if($showFileCount)
                <flux:heading size="base" class="!mb-0 {{ $this->resolvedColorScheme['text_primary'] }}">
                    Files ({{ $files->count() }})
                </flux:heading>
            @else
                <flux:heading size="base" class="!mb-0 {{ $this->resolvedColorScheme['text_primary'] }}">
                    Files
                </flux:heading>
            @endif
            
            @if ($showTotalSize && $files->count() > 0)
                <flux:subheading class="{{ $this->resolvedColorScheme['text_muted'] }}">
                    Total: {{ $this->formatFileSize($this->totalFileSize) }}
                </flux:subheading>
            @endif
        </div>
    </div>

    <!-- Bulk Actions Toolbar -->
    @if($enableBulkActions && $this->hasSelectedFiles)
        <div class="mb-4 p-3 {{ $this->resolvedColorScheme['accent_bg'] }} border {{ $this->resolvedColorScheme['accent_border'] }} rounded-lg 
                    animate-in slide-in-from-top-2 duration-300 ease-out">
            <div class="flex items-center justify-between">
                <!-- Selection Info -->
                <div class="flex items-center gap-3">
                    <flux:subheading class="{{ $this->resolvedColorScheme['text_primary'] }} font-medium">
                        {{ $this->selectedFileCount }} file{{ $this->selectedFileCount !== 1 ? 's' : '' }} selected
                    </flux:subheading>
                    @if($this->selectedFileSize > 0)
                        <flux:subheading class="{{ $this->resolvedColorScheme['text_muted'] }}">
                            ({{ $this->formatFileSize($this->selectedFileSize) }})
                        </flux:subheading>
                    @endif
                </div>

                <!-- Bulk Actions -->
                <div class="flex items-center gap-2">
                    <!-- Desktop Actions -->
                    <div class="hidden sm:flex items-center gap-2">
                        @if(in_array('download', $bulkActions) && $canDownload)
                            <flux:button 
                                wire:click="bulkDownloadSelected" 
                                variant="outline" 
                                size="sm"
                                icon="arrow-down-tray">
                                Download
                            </flux:button>
                        @endif

                        @if(in_array('delete', $bulkActions) && $canDelete)
                            <flux:button 
                                wire:click="bulkDeleteSelected" 
                                variant="danger" 
                                size="sm"
                                icon="trash">
                                Delete
                            </flux:button>
                        @endif

                        <!-- Clear Selection -->
                        <flux:button 
                            wire:click="clearSelection" 
                            variant="ghost" 
                            size="sm"
                            icon="x-mark"
                            class="ml-2">
                            <span class="sr-only">Clear selection</span>
                        </flux:button>
                    </div>

                    <!-- Mobile Actions - Bottom Sheet Trigger -->
                    <div class="sm:hidden flex items-center gap-2">
                        <flux:button 
                            x-data=""
                            @click="$dispatch('show-bulk-actions-sheet')" 
                            variant="outline" 
                            size="sm"
                            icon="ellipsis-horizontal">
                            Actions
                        </flux:button>
                        
                        <!-- Clear Selection -->
                        <flux:button 
                            wire:click="clearSelection" 
                            variant="ghost" 
                            size="sm"
                            icon="x-mark">
                            <span class="sr-only">Clear selection</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Screen Reader Descriptions -->
    <div class="sr-only">
        <div id="select-all-description">
            Select or deselect all files in the list
        </div>
    </div>

    <!-- Files List -->
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($files as $file)
            <div class="track-item @if ($showAnimations && in_array($file->id, $newlyUploadedFileIds)) animate-fade-in @endif 
                        @if($enableBulkActions && $this->isFileSelected($file->id)) {{ $this->resolvedColorScheme['accent_bg'] }} border-l-4 {{ $this->resolvedColorScheme['accent_border'] }} @endif
                        group flex items-center justify-between py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                
                <!-- Selection Checkbox (if bulk actions enabled) -->
                @if($enableBulkActions)
                    <div class="flex items-center px-2 group-hover:opacity-100 
                                {{ $this->isFileSelected($file->id) || $isSelectMode ? 'opacity-100' : 'opacity-0' }} 
                                transition-opacity duration-200">
                        <label class="relative cursor-pointer"
                               aria-label="Select file {{ $file->file_name }}">
                            <input 
                                type="checkbox" 
                                wire:click="toggleFileSelection({{ $file->id }})"
                                {{ $this->isFileSelected($file->id) ? 'checked' : '' }}
                                class="sr-only"
                                aria-describedby="file-{{ $file->id }}-info"
                            />
                            <div class="w-4 h-4 border-2 rounded transition-all duration-200 flex items-center justify-center
                                {{ $this->isFileSelected($file->id) ? $this->resolvedColorScheme['accent_bg'] . ' ' . $this->resolvedColorScheme['accent_border'] : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }}
                                hover:border-gray-400 dark:hover:border-gray-500">
                                @if($this->isFileSelected($file->id))
                                    <flux:icon.check class="w-2.5 h-2.5 text-white" />
                                @endif
                            </div>
                        </label>
                    </div>
                @endif

                <div class="track-info flex flex-1 items-center overflow-hidden pr-4">
                    @if ($canPlay && $this->isAudioFile($file))
                        <button wire:click="playFile({{ $file->id }})" class="{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg hover:bg-opacity-80 transition-colors cursor-pointer">
                            <flux:icon.play class="w-5 h-5" />
                        </button>
                    @else
                        <div class="{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg">
                            @if ($this->isAudioFile($file))
                                <flux:icon.musical-note class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && str_starts_with($file->mime_type, 'image/'))
                                <flux:icon.photo class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && $file->mime_type === 'application/pdf')
                                <flux:icon.document-text class="w-5 h-5" />
                            @else
                                <flux:icon.document class="w-5 h-5" />
                            @endif
                        </div>
                    @endif
                    
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-base font-semibold text-gray-900 dark:text-gray-100"
                                  id="file-{{ $file->id }}-info">
                                {{ $file->file_name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <flux:icon.calendar class="w-3 h-3" />
                                <span>{{ $file->created_at->format('M d, Y') }}</span>
                            </div>
                            @if (isset($file->size))
                                <div class="flex items-center gap-1">
                                    <flux:icon.scale class="w-3 h-3" />
                                    <span>{{ isset($file->formatted_size) ? $file->formatted_size : $this->formatFileSize($file->size) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="track-actions flex items-center">
                    <flux:dropdown>
                        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical">
                        </flux:button>
                        <flux:menu>
                            @if($canDownload)
                                <flux:menu.item wire:click="downloadFile({{ $file->id }})" icon="arrow-down-tray">
                                    Download
                                </flux:menu.item>
                            @endif
                            @if($canDelete)
                                <flux:menu.item wire:click="confirmDeleteFile({{ $file->id }})" variant="danger" icon="trash">
                                    Delete
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="p-8 text-center">
                <flux:icon name="{{ $emptyIcon }}" class="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                <flux:heading size="lg" class="text-gray-800 dark:text-gray-200 mb-2">
                    {{ $emptyStateMessage }}
                </flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400">
                    {{ $emptyStateSubMessage }}
                </flux:subheading>
            </div>
        @endforelse
    </div>

    <!-- Mobile Bulk Actions Bottom Sheet -->
    @if($enableBulkActions)
        <div x-data="{ showBulkSheet: false }" 
             @show-bulk-actions-sheet.window="showBulkSheet = true"
             @keydown.escape.window="showBulkSheet = false">
            
            <!-- Backdrop -->
            <div x-show="showBulkSheet" 
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showBulkSheet = false"
                 class="fixed inset-0 bg-black bg-opacity-50 z-40 sm:hidden">
            </div>

            <!-- Bottom Sheet -->
            <div x-show="showBulkSheet"
                 x-transition:enter="transition-transform ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition-transform ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full"
                 class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-xl shadow-xl z-50 sm:hidden">
                
                <!-- Handle -->
                <div class="flex justify-center pt-3 pb-2">
                    <div class="w-8 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>

                <!-- Content -->
                <div class="p-4 pb-safe">
                    <div class="mb-4">
                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                            Bulk Actions
                        </flux:heading>
                        <flux:subheading class="text-gray-600 dark:text-gray-400">
                            {{ $this->selectedFileCount }} file{{ $this->selectedFileCount !== 1 ? 's' : '' }} selected
                        </flux:subheading>
                    </div>

                    <div class="space-y-3">
                        @if(in_array('download', $bulkActions) && $canDownload)
                            <button 
                                wire:click="bulkDownloadSelected"
                                @click="showBulkSheet = false"
                                class="w-full flex items-center gap-3 p-4 text-left bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <flux:icon.arrow-down-tray class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Download Files</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Download selected files as archive</div>
                                </div>
                            </button>
                        @endif

                        @if(in_array('delete', $bulkActions) && $canDelete)
                            <button 
                                wire:click="bulkDeleteSelected"
                                @click="showBulkSheet = false"
                                class="w-full flex items-center gap-3 p-4 text-left bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                                <flux:icon.trash class="w-5 h-5 text-red-600 dark:text-red-400" />
                                <div>
                                    <div class="font-medium text-red-900 dark:text-red-100">Delete Files</div>
                                    <div class="text-sm text-red-600 dark:text-red-400">Permanently delete selected files</div>
                                </div>
                            </button>
                        @endif

                        <!-- Cancel -->
                        <button 
                            @click="showBulkSheet = false"
                            class="w-full flex items-center justify-center gap-3 p-4 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
