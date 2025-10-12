{{-- Version History Dropdown Component --}}
<div class="inline-block">
    <flux:dropdown>
        @if($triggerType === 'badge')
            {{-- Transparent Button with Badge Inside --}}
            <flux:button
                variant="ghost"
                size="sm"
                class="!p-0 !min-w-0 !h-auto !bg-transparent hover:!bg-transparent !border-0">
                <flux:badge size="sm" color="indigo">
                    {{ $file->getVersionLabel() }}
                </flux:badge>
            </flux:button>
        @else
            {{-- Button Trigger (original) --}}
            <flux:button
                variant="subtle"
                size="sm"
                icon="clock"
                class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                <span class="hidden sm:inline">Version History</span>
                <span class="sm:hidden">Versions</span>
            </flux:button>
        @endif

        <flux:menu class="w-80">
            {{-- Header --}}
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm" class="!mb-0">
                        Version History
                    </flux:heading>
                    <flux:badge size="sm" class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                        {{ $versions->count() }} version{{ $versions->count() !== 1 ? 's' : '' }}
                    </flux:badge>
                </div>
            </div>

            {{-- Version List --}}
            <div class="sm:max-h-96 sm:overflow-y-auto">
                @foreach($versions as $version)
                    <div class="px-4 py-3 transition-colors
                                @if($version->id === $currentVersionId)
                                    bg-indigo-50 dark:bg-indigo-950/30 hover:bg-indigo-100 dark:hover:bg-indigo-950/40
                                @elseif($loop->even)
                                    bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800/50
                                @else
                                    bg-gray-50 dark:bg-gray-800/30 hover:bg-gray-100 dark:hover:bg-gray-800/60
                                @endif">
                        <div class="flex items-start justify-between gap-3">
                            {{-- Version Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:badge
                                        size="sm"
                                        color="indigo"
                                        class="flex-shrink-0">
                                        {{ $version->getVersionLabel() ?? 'V' . $version->file_version_number }}
                                    </flux:badge>

                                    {{-- Client Approval Indicator --}}
                                    @if(isset($version->client_approval_status) && $version->client_approval_status === 'approved')
                                        <flux:icon.check-circle
                                            variant="solid"
                                            class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0"
                                            title="Approved by client" />
                                    @endif

                                    @if($version->trashed())
                                        <flux:badge size="sm" variant="danger">
                                            Deleted
                                        </flux:badge>
                                    @elseif($version->isLatestVersion())
                                        <flux:badge size="sm" variant="success">
                                            Latest
                                        </flux:badge>
                                    @endif

                                    @if($version->id === $currentVersionId)
                                        <flux:badge size="sm" color="indigo">
                                            Current
                                        </flux:badge>
                                    @elseif(!$version->trashed())
                                        {{-- "Use this version" button for non-current, non-deleted versions --}}
                                        <flux:button
                                            wire:click="useThisVersion({{ $version->id }})"
                                            variant="subtle"
                                            size="xs"
                                            class="!text-indigo-600 hover:!text-indigo-700 dark:!text-indigo-400 !bg-indigo-50 hover:!bg-indigo-100 dark:!bg-indigo-900/30 dark:hover:!bg-indigo-900/50">
                                            Use this version
                                        </flux:button>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                    {{-- File Name --}}
                                    <div class="flex items-center gap-1">
                                        <flux:icon.document class="w-3 h-3 flex-shrink-0" />
                                        <span class="truncate">{{ $version->file_name }}</span>
                                    </div>

                                    {{-- Upload Date --}}
                                    <div class="flex items-center gap-1">
                                        <flux:icon.clock class="w-3 h-3 flex-shrink-0" />
                                        <span>{{ $version->created_at->diffForHumans() }}</span>
                                    </div>

                                    {{-- File Size --}}
                                    @if($version->size)
                                        <div class="flex items-center gap-1">
                                            <flux:icon.scale class="w-3 h-3 flex-shrink-0" />
                                            <span>{{ $version->formattedSize }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions (only for non-deleted versions) --}}
                            @if($showActions && !$version->trashed())
                                <div class="flex flex-col gap-1">
                                    {{-- Play Button (for audio files) --}}
                                    @if($version->isAudioFile())
                                        <flux:button
                                            wire:click="playVersion({{ $version->id }})"
                                            variant="ghost"
                                            size="xs"
                                            icon="play"
                                            title="Play this version">
                                            <span class="sr-only">Play</span>
                                        </flux:button>
                                    @endif

                                    {{-- Download Button --}}
                                    <flux:button
                                        wire:click="downloadVersion({{ $version->id }})"
                                        variant="ghost"
                                        size="xs"
                                        icon="arrow-down-tray"
                                        title="Download this version">
                                        <span class="sr-only">Download</span>
                                    </flux:button>

                                    {{-- Delete Button (only for non-root versions) --}}
                                    @if($version->parent_file_id !== null)
                                        @can('deleteVersion', $version)
                                            <flux:button
                                                wire:click="deleteVersion({{ $version->id }})"
                                                variant="ghost"
                                                size="xs"
                                                icon="trash"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                title="Delete this version">
                                                <span class="sr-only">Delete</span>
                                            </flux:button>
                                        @endcan
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
