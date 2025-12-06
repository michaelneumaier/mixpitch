{{-- Livewire FileList Component --}}
<div x-data="{
    selectedFiles: @entangle('selectedFileIds'),

    get selectedCount() {
        return this.selectedFiles.length;
    },

    get selectedSize() {
        if (this.selectedFiles.length === 0) return 0;
        return this.selectedFiles.reduce((total, id) => {
            const fileEl = document.querySelector(`[data-file-id='${id}']`);
            const size = parseInt(fileEl?.dataset?.fileSize || 0);
            return total + size;
        }, 0);
    },

    get selectedSizeFormatted() {
        const bytes = this.selectedSize;
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    },

    get zipDisabled() {
        const maxSize = 4 * 1024 * 1024 * 1024; // 4GB
        return this.selectedSize > maxSize;
    },

    toggleFile(fileId) {
        const index = this.selectedFiles.indexOf(fileId);
        if (index === -1) {
            this.selectedFiles.push(fileId);
        } else {
            this.selectedFiles.splice(index, 1);
        }
    },

    toggleAll() {
        const allFileIds = Array.from(document.querySelectorAll('[data-file-id]'))
            .map(el => parseInt(el.dataset.fileId));

        if (this.selectedFiles.length === allFileIds.length) {
            this.selectedFiles = [];
        } else {
            this.selectedFiles = allFileIds;
        }
    },

    isSelected(fileId) {
        return this.selectedFiles.includes(fileId);
    },

    get allSelected() {
        const allFileIds = Array.from(document.querySelectorAll('[data-file-id]'))
            .map(el => parseInt(el.dataset.fileId));
        return allFileIds.length > 0 && this.selectedFiles.length === allFileIds.length;
    }
}">
    <!-- Files List Section Header -->
    <div class="flex items-center gap-3 mb-2">
        @if($enableBulkActions)
            <!-- Bulk Selection Checkbox -->
            <div class="flex items-center">
                <label class="relative"
                       aria-label="Select all files">
                    <input
                        type="checkbox"
                        @change="toggleAll()"
                        :checked="allSelected"
                        class="sr-only"
                        aria-describedby="select-all-description"
                        @if($files->isEmpty()) disabled @endif
                    />
                    <div class="w-5 h-5 border-2 rounded transition-all duration-200 cursor-pointer flex items-center justify-center
                        {{ $files->isEmpty() ? 'opacity-50 cursor-not-allowed' : 'hover:border-gray-400 dark:hover:border-gray-500' }}"
                        :class="allSelected ? '{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['accent_border'] }}' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                        <template x-if="allSelected">
                            <flux:icon.check class="w-3 h-3 text-white" />
                        </template>
                        <template x-if="!allSelected && selectedCount > 0">
                            <div class="w-2 h-0.5 bg-gray-400 dark:bg-gray-500"></div>
                        </template>
                    </div>
                </label>
            </div>
        @endif

        <flux:icon name="{{ $headerIcon }}" variant="solid" class="w-6 h-6 {{ $this->resolvedColorScheme['icon'] }}" />
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
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

            {{-- Bulk Upload Button (only for pitch files) --}}
            @if($modelType === 'pitch' && $files->count() > 0 && !$isClientPortal && $showBulkUploadVersions)
                <flux:button
                    type="button"
                    wire:click="openBulkVersionUpload"
                    variant="ghost"
                    size="sm"
                    icon="arrow-up-tray"
                    class="flex-shrink-0">
                    <span class="hidden sm:inline">Bulk Upload Versions</span>
                    <span class="sm:hidden">Bulk Upload</span>
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Bulk Actions Toolbar -->
    @if($enableBulkActions)
        <div x-show="selectedCount > 0"
             x-cloak
             class="mb-4 p-3 {{ $this->resolvedColorScheme['accent_bg'] }} border {{ $this->resolvedColorScheme['accent_border'] }} rounded-lg
                    animate-in slide-in-from-top-2 duration-300 ease-out">
            <div class="flex items-center justify-between gap-2">
                <!-- Selection Info -->
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:subheading class="{{ $this->resolvedColorScheme['text_primary'] }} font-medium">
                        <span x-text="selectedCount"></span> file<span x-text="selectedCount !== 1 ? 's' : ''"></span> selected
                    </flux:subheading>
                    <flux:subheading class="{{ $this->resolvedColorScheme['text_muted'] }}" x-show="selectedSize > 0">
                        (<span x-text="selectedSizeFormatted"></span>)
                    </flux:subheading>
                </div>

                <!-- Bulk Actions - Dropdown for all screen sizes -->
                <div class="flex items-center gap-2">
                    <flux:dropdown align="end">
                        <flux:button
                            variant="primary"
                            size="sm"
                            icon:trailing="chevron-down">
                            Actions
                        </flux:button>

                        <flux:menu>
                            @if(in_array('download', $bulkActions) && $canDownload)
                                <div x-show="selectedCount === 1" x-cloak>
                                    <flux:menu.item
                                        wire:click="bulkDownloadIndividual"
                                        icon="arrow-down-tray">
                                        Download File
                                    </flux:menu.item>
                                </div>

                                <div x-show="selectedCount > 1" x-cloak>
                                    <flux:menu.item
                                        wire:click="bulkDownloadIndividual"
                                        icon="arrow-down-tray">
                                        <span>Download Files (<span x-text="selectedCount"></span>)</span>
                                    </flux:menu.item>

                                    <flux:menu.item
                                        wire:click="bulkDownloadAsZip"
                                        icon="archive-box"
                                        x-bind:disabled="zipDisabled">
                                        <span>Download as ZIP</span>
                                        <span x-show="zipDisabled" x-cloak class="ml-2">
                                            <flux:badge size="sm" color="red">4GB Limit</flux:badge>
                                        </span>
                                    </flux:menu.item>
                                </div>
                            @endif

                            @if(in_array('delete', $bulkActions) && $canDelete)
                                <flux:menu.item wire:click="bulkDeleteSelected" icon="trash" variant="danger">
                                    Delete Files
                                </flux:menu.item>
                            @endif

                            @if(in_array('removeFromVersion', $bulkActions) && $canDelete)
                                <flux:menu.item wire:click="bulkExcludeFromVersion" icon="minus-circle">
                                    Move to File Library
                                </flux:menu.item>
                            @endif

                            @if(in_array('addToVersion', $bulkActions) && $canDelete)
                                <flux:menu.item wire:click="bulkIncludeInVersion" icon="plus-circle">
                                    Move to Deliverables
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>

                    <!-- Clear Selection - Always visible outside dropdown -->
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
            <div x-data="{
                     showComments: false,
                     fileId: {{ $file->id }},
                     init() {
                         // Keep comment section expanded if this file was just commented on
                         if (window.lastCommentedFileId === this.fileId) {
                             this.showComments = true;
                             // Clear the flag after a longer delay to ensure state is preserved
                             setTimeout(() => {
                                 if (window.lastCommentedFileId === this.fileId) {
                                     window.lastCommentedFileId = null;
                                 }
                             }, 2000); // Increased from 100ms to 2000ms
                         }
                     }
                 }"
                 data-file-id="{{ $file->id }}"
                 data-file-size="{{ $file->size }}"
                 data-file-name="{{ $file->file_name }}"
                 class="track-item @if ($showAnimations && in_array($file->id, $newlyUploadedFileIds)) animate-fade-in @endif
                        @if(!empty($file->deleted_at)) opacity-60 @endif
                        group transition-colors duration-200"
                 :class="@if($enableBulkActions) isSelected({{ $file->id }}) ? '{{ $this->resolvedColorScheme['accent_bg'] }} border-l-4 {{ $this->resolvedColorScheme['accent_border'] }}' : '' @else '' @endif">
                
                <!-- Main File Display Row -->
                <div class="flex items-center justify-between py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <!-- Selection Checkbox (if bulk actions enabled) -->
                @if($enableBulkActions)
                    <div class="flex items-center px-2 group-hover:opacity-100 transition-opacity duration-200"
                         :class="isSelected({{ $file->id }}) || {{ $isSelectMode ? 'true' : 'false' }} ? 'opacity-100' : 'opacity-0'">
                        <label class="relative cursor-pointer"
                               aria-label="Select file {{ $file->file_name }}">
                            <input
                                type="checkbox"
                                @change="toggleFile({{ $file->id }})"
                                :checked="isSelected({{ $file->id }})"
                                class="sr-only"
                                aria-describedby="file-{{ $file->id }}-info"
                            />
                            <div class="w-4 h-4 border-2 rounded transition-all duration-200 flex items-center justify-center hover:border-gray-400 dark:hover:border-gray-500"
                                 :class="isSelected({{ $file->id }}) ? '{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['accent_border'] }}' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                                <template x-if="isSelected({{ $file->id }})">
                                    <flux:icon.check class="w-2.5 h-2.5 text-white" />
                                </template>
                            </div>
                        </label>
                    </div>
                @endif

                <div class="track-info flex flex-1 items-center overflow-hidden pr-4">
                    @if (!empty($file->deleted_at))
                        {{-- Deleted File Indicator --}}
                        <div class="mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30" title="This file was deleted">
                            <flux:icon.x-mark class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                    @elseif ($canPlay && $this->isAudioFile($file))
                        <button wire:click="playFile({{ $file->id }})" class="{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg hover:bg-opacity-80 transition-colors cursor-pointer">
                            <flux:icon.play class="w-5 h-5" />
                        </button>
                    @else
                        <div class="{{ $this->resolvedColorScheme['accent_bg'] }} {{ $this->resolvedColorScheme['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg">
                            @if ($this->isAudioFile($file))
                                <flux:icon.musical-note class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && str_starts_with($file->mime_type, 'video/'))
                                <flux:icon.play class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && str_starts_with($file->mime_type, 'image/'))
                                <flux:icon.photo class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && $file->mime_type === 'application/pdf')
                                <flux:icon.document-text class="w-5 h-5" />
                            @elseif (isset($file->mime_type) && $file->mime_type === 'application/zip')
                                <flux:icon.archive-box class="w-5 h-5" />
                            @else
                                <flux:icon.document class="w-5 h-5" />
                            @endif
                        </div>
                    @endif
                    
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            {{-- Client Approval Indicator --}}
                            @if(isset($file->client_approval_status) && $file->client_approval_status === 'approved')
                                <flux:icon.check-circle
                                    variant="solid"
                                    class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0"
                                    title="Approved by client" />
                            @endif

                            @php
                                $audioPlayerUrl = $canPlay ? $this->getUniversalAudioPlayerUrl($file) : null;
                                $videoPlayerUrl = $canPlay ? $this->getUniversalVideoPlayerUrl($file) : null;
                                $playerUrl = $audioPlayerUrl ?: $videoPlayerUrl;
                                $playerType = $audioPlayerUrl ? 'audio' : 'video';
                            @endphp
                            @if($playerUrl)
                                <a href="{{ $playerUrl }}"
                                   class="truncate text-base font-semibold {{ !empty($file->deleted_at) ? 'line-through text-gray-500 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400' }} transition-colors cursor-pointer"
                                   id="file-{{ $file->id }}-info"
                                   title="Open {{ $file->file_name }} in full {{ $playerType }} player">
                                    {{ $file->file_name }}
                                </a>
                            @else
                                <span class="truncate text-base font-semibold {{ !empty($file->deleted_at) ? 'line-through text-gray-500 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100' }}"
                                      id="file-{{ $file->id }}-info">
                                    {{ $file->file_name }}
                                </span>
                            @endif

                            {{-- Version Badge --}}
                            @if(method_exists($file, 'hasMultipleVersions') && $file->hasMultipleVersions())
                                <div class="ml-2 flex-shrink-0">
                                    @if($enableVersionSwitching)
                                        {{-- Interactive dropdown for version switching --}}
                                        <livewire:components.version-history-dropdown
                                            :file="$file"
                                            :triggerType="'badge'"
                                            :key="'version-badge-' . $file->id" />
                                    @else
                                        {{-- Static badge (non-interactive) --}}
                                        <flux:badge size="sm" color="indigo">
                                            {{ $file->getVersionLabel() }}
                                        </flux:badge>
                                    @endif
                                </div>
                            @endif

                            {{-- Deleted Badge --}}
                            @if(!empty($file->deleted_at))
                                <flux:badge variant="danger" size="sm" class="ml-2 flex-shrink-0">
                                    Deleted
                                </flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center justify-between md:justify-start gap-2 md:gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <flux:icon.calendar class="w-3 h-3 hidden md:inline" />
                                <span>{{ $file->created_at->format('M d, Y') }}</span>
                            </div>
                            @if (isset($file->size))
                                <div class="flex items-center gap-1">
                                    <flux:icon.scale class="w-3 h-3 hidden md:inline" />
                                    <span>{{ isset($file->formatted_size) ? $file->formatted_size : $this->formatFileSize($file->size) }}</span>
                                </div>
                            @endif
                            @if (isset($file->is_watermarked) && $file->is_watermarked && isset($file->audio_processed) && $file->audio_processed)
                                @php
                                    $shouldShowWatermarkBadge = false;
                                    if ($isClientPortal ?? false) {
                                        // In client portal, check if watermarked version should be served based on payment
                                        $clientPortalProject = isset($clientPortalProjectId)
                                            ? \App\Models\Project::find($clientPortalProjectId)
                                            : null;
                                        // Fetch snapshot if we have an ID, otherwise pass null (virtual snapshots are null)
                                        $currentSnapshot = isset($currentSnapshotId) && $currentSnapshotId
                                            ? \App\Models\PitchSnapshot::find($currentSnapshotId)
                                            : null;
                                        $shouldShowWatermarkBadge = $file->shouldServeWatermarked(Auth::user(), $clientPortalProject, $currentSnapshot);
                                    } elseif (Auth::check()) {
                                        // In main app, check Gate permission
                                        $shouldShowWatermarkBadge = Gate::allows('receivesWatermarked', $file);
                                    }
                                @endphp
                                @if ($shouldShowWatermarkBadge)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                        <i class="fas fa-shield-alt mr-1"></i>Protected
                                    </span>
                                @endif
                            @endif
                            @if (empty($file->deleted_at) && $showComments && ($this->getFileCommentCount($file->id) > 0 || $enableCommentCreation))
                                <div class="flex items-center gap-1">
                                    <flux:icon.chat-bubble-left-ellipsis class="w-3 h-3 hidden md:inline" />
                                    <button
                                        @click="showComments = !showComments"
                                        class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer">
                                        @if ($this->getFileCommentCount($file->id) > 0)
                                            {{ $this->getFileCommentCount($file->id) }} comment{{ $this->getFileCommentCount($file->id) > 1 ? 's' : '' }}
                                        @else
                                            Add comment
                                        @endif
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                @php
                    // Determine available actions for THIS specific file
                    $canDownloadThisFile = false;
                    if ($canDownload) {
                        $canDownloadThisFile = $canDownload;

                        // In client portal with snapshot context, check file-level access
                        if ($isClientPortal && isset($currentSnapshotId) && $currentSnapshotId && $canDownload) {
                            $clientPortalProject = \App\Models\Project::find($clientPortalProjectId);
                            // Fetch snapshot if not already fetched
                            $currentSnapshot = isset($currentSnapshot) && $currentSnapshot
                                ? $currentSnapshot
                                : \App\Models\PitchSnapshot::find($currentSnapshotId);
                            $canDownloadThisFile = $file->canAccessOriginalFile(null, $clientPortalProject, $currentSnapshot);
                        }
                    }

                    // Show dropdown only if at least one action is available for this file
                    $hasAnyAction = $canDownloadThisFile || $canDelete;
                @endphp

                @if(empty($file->deleted_at) && $hasAnyAction)
                    <div class="track-actions flex items-center gap-2">

                        {{-- File Actions Dropdown --}}
                        @if($hasAnyAction || Gate::allows('uploadVersion', $file))
                            <flux:dropdown>
                                <x-slot name="trigger">
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-vertical">
                                        <span class="sr-only">File actions</span>
                                    </flux:button>
                                </x-slot>

                                <flux:menu>
                                    {{-- Upload New Version --}}
                                    @can('uploadVersion', $file)
                                        <flux:menu.item wire:click="uploadNewVersion({{ $file->id }})" icon="arrow-up-tray" class="text-indigo-600 dark:text-indigo-400">
                                            Upload New Version
                                        </flux:menu.item>

                                        {{-- Delete All Versions (only show if file has versions) --}}
                                        @if($file->hasMultipleVersions())
                                            <flux:menu.item wire:click="confirmDeleteAllVersions({{ $file->id }})" variant="danger" icon="trash">
                                                Delete All Versions
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />
                                    @endcan

                                    @if($canDownloadThisFile)
                                        <flux:menu.item wire:click="downloadFile({{ $file->id }})" icon="arrow-down-tray">
                                            Download
                                        </flux:menu.item>
                                    @endif
                                    @if($canDelete && isset($file->included_in_working_version))
                                        @if($file->included_in_working_version)
                                            <flux:menu.item wire:click="excludeFileFromVersion({{ $file->id }})" icon="minus-circle">
                                                Move to File Library
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item wire:click="includeFileInVersion({{ $file->id }})" icon="plus-circle">
                                                Move to Deliverables
                                            </flux:menu.item>
                                        @endif
                                    @endif
                                    @if($canDelete)
                                        <flux:menu.item wire:click="confirmDeleteFile({{ $file->id }})" variant="danger" icon="trash">
                                            Delete Permanently
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </div>
                @endif
                </div>

                <!-- Comments Section -->
                @if (empty($file->deleted_at) && $showComments && ($this->getFileCommentCount($file->id) > 0 || $enableCommentCreation))
                    <div x-show="showComments" x-collapse
                         class="border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700">
                        <div class="mb-3">
                            <flux:text weight="semibold" size="sm"
                                       class="flex items-center text-gray-900 dark:text-gray-100">
                                <flux:icon name="chat-bubble-left-ellipsis"
                                           class="mr-2 text-blue-600" />
                                Comments for this File
                            </flux:text>
                        </div>
                        <div class="space-y-3">
                            @foreach ($this->getFileComments($file->id) as $comment)
                                <div class="rounded-lg border p-3 
                                    @if ($comment->is_client_comment ?? false)
                                        border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800
                                    @else
                                        border-purple-200 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/20
                                    @endif">
                                    <div class="mb-2 flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            @if ($comment->is_client_comment ?? false)
                                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500">
                                                    <flux:icon name="user" size="xs" class="text-white" />
                                                </div>
                                            @else
                                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500">
                                                    <flux:icon name="musical-note" size="xs" class="text-white" />
                                                </div>
                                            @endif
                                            <div>
                                                <flux:text weight="medium" size="sm"
                                                           class="text-gray-900 dark:text-gray-100">
                                                    @if ($comment->is_client_comment ?? false)
                                                        {{ $comment->client_email ?? $comment->client_name ?? $comment->metadata['client_name'] ?? 'Client' }}
                                                    @else
                                                        {{ $comment->producer_name ?? $comment->metadata['producer_name'] ?? 'Producer' }}
                                                    @endif
                                                </flux:text>
                                                <div class="flex items-center gap-2">
                                                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                                                        {{ $comment->created_at_for_user->diffForHumans() }}
                                                    </flux:text>
                                                    @if(isset($comment->timestamp) && $comment->timestamp)
                                                        @php
                                                            $formattedTime = $comment->formatted_timestamp ?? sprintf('%02d:%02d', floor($comment->timestamp / 60), $comment->timestamp % 60);
                                                        @endphp
                                                        <flux:text size="xs" class="text-blue-600 dark:text-blue-400">
                                                            {{ $formattedTime }}
                                                        </flux:text>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if($comment->resolved ?? false)
                                                @php
                                                    $canUnresolveThisComment = false;
                                                    if ($isClientPortal ?? false) {
                                                        // In client portal, only allow unresolving client's own comments
                                                        $canUnresolveThisComment = ($comment->is_client_comment ?? false) &&
                                                                                  isset($clientPortalProjectId) &&
                                                                                  ($comment->client_email ?? null) === (\App\Models\Project::find($clientPortalProjectId)->client_email ?? null);
                                                    } else {
                                                        // In main app, check if current user can unresolve
                                                        $canUnresolveThisComment = Auth::check() && (
                                                            // User can unresolve their own comments
                                                            (($comment->user_id ?? null) === Auth::id()) ||
                                                            // Or if they have admin/project owner permissions
                                                            (Auth::user()->role === 'admin') ||
                                                            // Or if this is the project owner (for producer comments)
                                                            (isset($modelId) && Auth::user()->projects()->where('id', $modelId)->exists())
                                                        );
                                                    }
                                                @endphp

                                                @if($canUnresolveThisComment)
                                                    <button
                                                        wire:click="confirmUnresolveComment({{ $comment->id }})"
                                                        @click="window.lastCommentedFileId = {{ $file->id }}"
                                                        class="inline-flex items-center transition-opacity hover:opacity-70 cursor-pointer"
                                                        title="Click to mark as unresolved">
                                                        <flux:badge variant="success" size="sm">
                                                            <flux:icon name="check" variant="micro" class="mr-1" />Resolved
                                                        </flux:badge>
                                                    </button>
                                                @else
                                                    <flux:badge variant="success" size="sm">
                                                        <flux:icon name="check" variant="micro" class="mr-1" />Resolved
                                                    </flux:badge>
                                                @endif
                                            @elseif(($comment->metadata['type'] ?? null) === 'revision_request' || ($comment->is_client_comment ?? false))
                                                <flux:badge variant="warning" size="sm">
                                                    <flux:icon name="pencil" variant="micro" class="mr-1" />Needs Response
                                                </flux:badge>
                                            @elseif(($comment->metadata['type'] ?? null) === 'approval')
                                                <flux:badge variant="success" size="sm">
                                                    <flux:icon name="check" variant="micro" class="mr-1" />Approved
                                                </flux:badge>
                                            @endif
                                            
                                            <!-- Delete Comment Button (with authorization check) -->
                                            @php
                                                $canDeleteThisComment = false;
                                                if ($isClientPortal ?? false) {
                                                    // In client portal, only allow deleting client's own comments
                                                    $canDeleteThisComment = ($comment->is_client_comment ?? false) && 
                                                                          isset($clientPortalProjectId) &&
                                                                          ($comment->client_email ?? null) === (\App\Models\Project::find($clientPortalProjectId)->client_email ?? null);
                                                } else {
                                                    // In main app, check if current user can delete (existing logic)
                                                    $canDeleteThisComment = Auth::check() && (
                                                        // User can delete their own comments
                                                        (($comment->user_id ?? null) === Auth::id()) ||
                                                        // Or if they have admin/project owner permissions
                                                        (Auth::user()->role === 'admin') ||
                                                        // Or if this is the project owner (for producer comments)
                                                        (isset($modelId) && Auth::user()->projects()->where('id', $modelId)->exists())
                                                    );
                                                }
                                            @endphp
                                            
                                            @if ($canDeleteThisComment)
                                                <button
                                                    wire:click="confirmDeleteComment({{ $comment->id }})"
                                                    @click="window.lastCommentedFileId = {{ $file->id }}"
                                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 transition-colors group"
                                                    title="Delete comment">
                                                    <flux:icon name="x-mark" variant="micro" class="text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <flux:text size="sm" class="leading-relaxed text-gray-800 dark:text-gray-200">
                                        {{ $comment->comment }}
                                    </flux:text>

                                    {{-- Quick Response for Unresolved Comments --}}
                                    @if (!($comment->resolved ?? false) && (
                                        ($comment->is_client_comment ?? false) ||
                                        ($comment->metadata['type'] ?? null) === 'revision_request' ||
                                        (($isClientPortal ?? false) && !($comment->is_client_comment ?? false)) ||
                                        (!($isClientPortal ?? false) && !($comment->is_client_comment ?? false))
                                    ))
                                        <div class="mt-3 border-t border-blue-200 pt-3" x-data="{ showResponse: false }">
                                            <div class="flex gap-2">
                                                <button
                                                    @click="window.lastCommentedFileId = {{ $file->id }}"
                                                    wire:click="markFileCommentResolved({{ $comment->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="markFileCommentResolved({{ $comment->id }})"
                                                    class="inline-flex items-center rounded-md bg-green-100 px-3 py-1 text-xs font-medium text-green-800 transition-colors hover:bg-green-200 disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="markFileCommentResolved({{ $comment->id }})">
                                                        <i class="fas fa-check mr-1"></i>Mark as Addressed
                                                    </span>
                                                    <span wire:loading wire:target="markFileCommentResolved({{ $comment->id }})">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i>Addressing...
                                                    </span>
                                                </button>
                                                <button
                                                    @click="showResponse = !showResponse"
                                                    class="inline-flex items-center rounded-md bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 transition-colors hover:bg-blue-200">
                                                    <i class="fas fa-reply mr-1"></i>
                                                    @if (($isClientPortal ?? false) && !($comment->is_client_comment ?? false))
                                                        Reply to Producer
                                                    @else
                                                        Respond
                                                    @endif
                                                </button>
                                            </div>

                                            <div x-show="showResponse"
                                                 x-collapse class="mt-3">
                                                <form wire:submit.prevent="respondToFileComment({{ $comment->id }})" 
                                                      @submit="window.lastCommentedFileId = {{ $file->id }}">
                                                    <textarea wire:model.defer="fileCommentResponse.{{ $comment->id }}" rows="2"
                                                              class="w-full rounded-md border border-blue-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                              placeholder="@if (($isClientPortal ?? false) && !($comment->is_client_comment ?? false))Write your reply to the producer...@else Explain how you've addressed this feedback...@endif"></textarea>
                                                    <div class="mt-2 flex gap-2">
                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                                                                @click="window.lastCommentedFileId = {{ $file->id }}">
                                                            <i class="fas fa-paper-plane mr-1"></i>Send Response
                                                        </button>
                                                        <button type="button"
                                                                @click="showResponse = false"
                                                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Display Replies --}}
                                    @if(isset($comment->replies) && $comment->replies->count() > 0)
                                        <div class="mt-3 border-t border-gray-200 pt-3">
                                            <flux:text size="xs" class="text-gray-600 dark:text-gray-400 mb-2">
                                                {{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'reply' : 'replies' }}
                                            </flux:text>
                                            @foreach($comment->replies as $reply)
                                                <div class="ml-4 pl-3 border-l-2 border-gray-200 mb-2 last:mb-0">
                                                    <div class="flex items-start gap-2">
                                                        @if ($reply->is_client_comment ?? false)
                                                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-blue-400">
                                                                <flux:icon name="user" size="xs" class="text-white" />
                                                            </div>
                                                        @else
                                                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-purple-400">
                                                                <flux:icon name="musical-note" size="xs" class="text-white" />
                                                            </div>
                                                        @endif
                                                        <div class="flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex items-center gap-2">
                                                                    <flux:text weight="medium" size="xs" class="text-gray-800 dark:text-gray-200">
                                                                        @if ($reply->is_client_comment ?? false)
                                                                            {{ $reply->client_email ?? $reply->client_name ?? $reply->metadata['client_name'] ?? 'Client' }}
                                                                        @else
                                                                            {{ $reply->producer_name ?? $reply->metadata['producer_name'] ?? 'Producer' }}
                                                                        @endif
                                                                    </flux:text>
                                                                    <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                                                        {{ $reply->created_at->diffForHumans() }}
                                                                    </flux:text>
                                                                </div>

                                                                <!-- Delete Reply Button (with authorization check) -->
                                                                @php
                                                                    $canDeleteThisReply = false;
                                                                    if ($isClientPortal ?? false) {
                                                                        // In client portal, only allow deleting client's own replies
                                                                        $canDeleteThisReply = ($reply->is_client_comment ?? false) && 
                                                                                              isset($clientPortalProjectId) &&
                                                                                              ($reply->client_email ?? null) === (\App\Models\Project::find($clientPortalProjectId)->client_email ?? null);
                                                                    } else {
                                                                        // In main app, check if current user can delete (existing logic)
                                                                        $canDeleteThisReply = Auth::check() && (
                                                                            // User can delete their own replies
                                                                            (($reply->user_id ?? null) === Auth::id()) ||
                                                                            // Or if they have admin/project owner permissions
                                                                            (Auth::user()->role === 'admin') ||
                                                                            // Or if this is the project owner (for producer replies)
                                                                            (isset($modelId) && Auth::user()->projects()->where('id', $modelId)->exists())
                                                                        );
                                                                    }
                                                                @endphp
                                                                
                                                                @if ($canDeleteThisReply)
                                                                    <button
                                                                        wire:click="confirmDeleteComment({{ $reply->id }})"
                                                                        @click="window.lastCommentedFileId = {{ $file->id }}"
                                                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 transition-colors group"
                                                                        title="Delete reply">
                                                                        <flux:icon name="x-mark" variant="micro" class="text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300" />
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <flux:text size="xs" class="text-gray-700 dark:text-gray-300 mt-1">
                                                                {{ $reply->comment }}
                                                            </flux:text>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Add New Comment Section -->
                        @if ($enableCommentCreation)
                            <div class="mt-3 border-t border-blue-200 pt-3">
                                <form wire:submit.prevent="createFileComment({{ $file->id }})" 
                                      @submit="window.lastCommentedFileId = {{ $file->id }}">
                                    <div class="mb-2">
                                        <label for="newComment{{ $file->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Add Comment for this File
                                        </label>
                                        <textarea 
                                            wire:model.defer="newFileComment.{{ $file->id }}" 
                                            id="newComment{{ $file->id }}"
                                            rows="2"
                                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm 
                                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Add your comment about this specific file..."></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                                wire:loading.attr="disabled"
                                                wire:target="createFileComment({{ $file->id }})"
                                                @click="window.lastCommentedFileId = {{ $file->id }}">
                                            <span wire:loading.remove wire:target="createFileComment({{ $file->id }})">
                                                <i class="fas fa-plus mr-1"></i>Add Comment
                                            </span>
                                            <span wire:loading wire:target="createFileComment({{ $file->id }})">
                                                <i class="fas fa-spinner fa-spin mr-1"></i>Adding...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <div class="mt-3 border-t border-blue-200 pt-3">
                            <p class="text-xs text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                This feedback is specific to the "{{ $file->file_name }}" file.
                                General project communication should use the main message area below.
                            </p>
                        </div>
                    </div>
                @endif
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

    <!-- Delete Comment Confirmation Modal -->
    <flux:modal name="delete-comment" class="max-w-md">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                <flux:heading size="lg">Delete Comment</flux:heading>
            </div>

            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this comment? This action cannot be undone.
            </flux:subheading>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="cancelDeleteComment">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button
                    x-data=""
                    @click="window.lastCommentedFileId = {{ \Illuminate\Support\Js::from($commentFileIdPendingDeletion) }}"
                    wire:click="deleteComment" variant="danger" icon="trash" wire:loading.attr="disabled" wire:target="deleteComment">
                    <span wire:loading.remove wire:target="deleteComment">Delete Comment</span>
                    <span wire:loading wire:target="deleteComment">Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Unresolve Comment Confirmation Modal -->
    <flux:modal name="unresolve-comment" class="max-w-md">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:icon.arrow-path class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                <flux:heading size="lg">Mark as Unresolved</flux:heading>
            </div>

            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Are you sure you want to mark this comment as unresolved? This will move it back to the active feedback list.
            </flux:subheading>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="cancelUnresolveComment">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button
                    x-data=""
                    @click="window.lastCommentedFileId = {{ \Illuminate\Support\Js::from($commentFileIdPendingUnresolve) }}"
                    wire:click="unresolveComment"
                    variant="primary"
                    color="amber"
                    icon="arrow-path"
                    wire:loading.attr="disabled"
                    wire:target="unresolveComment">
                    <span wire:loading.remove wire:target="unresolveComment">Mark as Unresolved</span>
                    <span wire:loading wire:target="unresolveComment">Marking...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Version Confirmation Modal --}}
    <flux:modal name="delete-version-confirmation-{{ $this->getId() }}" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="!mb-0">Delete Version</flux:heading>
                </div>
            </div>

            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this version? This action cannot be undone.
            </flux:subheading>

            <flux:callout variant="warning">
                <div class="flex items-start gap-2">
                    <flux:icon.information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div class="text-sm">
                        Only this specific version will be deleted. The root file and other versions will remain intact.
                    </div>
                </div>
            </flux:callout>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:button
                    type="button"
                    wire:click="cancelDeleteVersion"
                    variant="ghost">
                    Cancel
                </flux:button>
                <flux:button
                    type="button"
                    wire:click="deleteFileVersion"
                    variant="primary"
                    color="red"
                    icon="trash">
                    Delete Version
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete All Versions Modal --}}
    <flux:modal name="delete-all-versions-confirmation" :open="$deleteAllVersionsFileId !== null" wire:model="deleteAllVersionsFileId" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="!mb-0">Delete All Versions</flux:heading>
                </div>
            </div>

            <flux:callout variant="danger">
                <div class="flex items-start gap-2">
                    <flux:icon.exclamation-triangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div class="text-sm">
                        <div class="font-medium mb-1">Warning: Permanent Deletion</div>
                        <div>This will permanently delete ALL versions of this file except the original. This action cannot be undone.</div>
                    </div>
                </div>
            </flux:callout>

            @if($deleteAllVersionsFileId)
                @php
                    $fileToDelete = $files->firstWhere('id', $deleteAllVersionsFileId);
                @endphp
                @if($fileToDelete)
                    <div>
                        <flux:field>
                            <flux:label for="deleteAllConfirmation">
                                Type <strong>{{ $fileToDelete->file_name }}</strong> to confirm
                            </flux:label>
                            <flux:input
                                type="text"
                                wire:model.live="deleteAllConfirmationInput"
                                id="deleteAllConfirmation"
                                placeholder="Type file name here"
                                class="font-mono" />
                        </flux:field>
                    </div>
                @endif
            @endif

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:button
                    type="button"
                    wire:click="cancelDeleteAllVersions"
                    variant="ghost">
                    Cancel
                </flux:button>
                <flux:button
                    type="button"
                    wire:click="deleteAllFileVersions"
                    variant="primary"
                    color="red"
                    icon="trash"
                    :disabled="!$deleteAllVersionsFileId || $deleteAllConfirmationInput !== ($files->firstWhere('id', $deleteAllVersionsFileId)->file_name ?? '')">
                    Delete All Versions
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirmation Modal for Bulk Individual Downloads --}}
    <flux:modal name="confirm-bulk-download" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="!mb-0">Confirm Multiple Downloads</flux:heading>
                </div>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="mb-2">
                    You're about to download <strong>{{ $confirmDownloadFileCount }}</strong> files
                    totaling <strong>{{ $confirmDownloadTotalSize }}</strong>.
                </p>
            </div>

            <flux:callout variant="warning">
                <div class="flex items-start gap-2">
                    <flux:icon.information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div class="text-sm">
                        Your browser will attempt to download each file individually.
                        This may trigger multiple download prompts depending on your browser settings.
                    </div>
                </div>
            </flux:callout>

            <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                💡 <strong>Tip:</strong> For a better experience with many files,
                use "Download as ZIP" to get all files in one archive.
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button
                        variant="primary"
                        wire:click="proceedWithIndividualDownloads">
                        Continue Download
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    @script
    <script>
        $wire.on('confirm-bulk-download', (event) => {
            // Event data is passed as an array, get the first element
            const data = event[0] || event;
            $wire.confirmDownloadFileCount = data.fileCount;
            $wire.confirmDownloadTotalSize = data.totalSize;
            $flux.modal('confirm-bulk-download').show();
        });
    </script>
    @endscript
</div>
