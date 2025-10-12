<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <flux:heading size="lg" class="!mb-1">Version Comparison</flux:heading>
                <flux:subheading class="!mb-0">
                    {{ $file->file_name }}
                </flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                {{-- Swap Versions --}}
                <flux:button
                    type="button"
                    wire:click="swapVersions"
                    variant="ghost"
                    size="sm"
                    icon="arrow-path"
                    :disabled="!$versionAId || !$versionBId || $versionAId === $versionBId">
                    Swap
                </flux:button>

                {{-- Sync Playback Toggle --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="syncPlayback"
                        wire:change="toggleSyncPlayback"
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sync Playback</span>
                </label>

                {{-- Back to File --}}
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="arrow-left"
                    href="{{ route('pitch-files.show', $file->uuid) }}">
                    Back
                </flux:button>
            </div>
        </div>

        {{-- Version Stats --}}
        @if($this->versions->count() > 1)
            <div class="grid grid-cols-4 gap-4 text-center">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3">
                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $this->versions->count() }}</div>
                    <div class="text-xs text-indigo-700 dark:text-indigo-300">Total Versions</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $this->versions->first()->getVersionLabel() }}</div>
                    <div class="text-xs text-green-700 dark:text-green-300">Latest Version</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $this->versions->first()->created_at->diffForHumans($this->versions->last()->created_at, true) }}</div>
                    <div class="text-xs text-blue-700 dark:text-blue-300">Time Span</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format($this->versions->sum('size') / 1024 / 1024, 2) }} MB</div>
                    <div class="text-xs text-purple-700 dark:text-purple-300">Total Size</div>
                </div>
            </div>
        @endif
    </div>

    {{-- Comparison Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Version A --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" class="!mb-0">Version A</flux:heading>
                    <flux:badge size="sm" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">LEFT</flux:badge>
                </div>

                {{-- Version Selector --}}
                <flux:select
                    wire:model.live="versionAId"
                    wire:change="selectVersionA($event.target.value)"
                    class="w-full">
                    @foreach($this->versions as $version)
                        <option value="{{ $version->id }}">
                            {{ $version->getVersionLabel() }} - {{ $version->created_at->format('M j, Y') }}
                            @if($version->id === $file->id) (Current) @endif
                        </option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Version Details --}}
            @if($this->versionA)
                <div class="space-y-3 mb-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                        <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">File Details</div>
                        <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ $this->versionA->file_name }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            {{ $this->versionA->formattedSize }} • {{ $this->versionA->created_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>

                {{-- Audio Player --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/50"
                     x-data="{ playerId: 'player-a-{{ $this->versionA->id }}' }">
                    <livewire:pitch-file-player
                        :file="$this->versionA"
                        :key="'version-a-' . $this->versionA->id"
                        size="compact" />
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Select a version to compare
                </div>
            @endif
        </div>

        {{-- Version B --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm" class="!mb-0">Version B</flux:heading>
                    <flux:badge size="sm" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">RIGHT</flux:badge>
                </div>

                {{-- Version Selector --}}
                <flux:select
                    wire:model.live="versionBId"
                    wire:change="selectVersionB($event.target.value)"
                    class="w-full">
                    @foreach($this->versions as $version)
                        <option value="{{ $version->id }}">
                            {{ $version->getVersionLabel() }} - {{ $version->created_at->format('M j, Y') }}
                            @if($version->id === $file->id) (Current) @endif
                        </option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Version Details --}}
            @if($this->versionB)
                <div class="space-y-3 mb-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                        <div class="text-xs text-green-700 dark:text-green-300 mb-1">File Details</div>
                        <div class="text-sm font-medium text-green-900 dark:text-green-100">{{ $this->versionB->file_name }}</div>
                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                            {{ $this->versionB->formattedSize }} • {{ $this->versionB->created_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>

                {{-- Audio Player --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/50"
                     x-data="{ playerId: 'player-b-{{ $this->versionB->id }}' }">
                    <livewire:pitch-file-player
                        :file="$this->versionB"
                        :key="'version-b-' . $this->versionB->id"
                        size="compact" />
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Select a version to compare
                </div>
            @endif
        </div>
    </div>

    {{-- Version Timeline --}}
    @if($this->versions->count() > 1)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <flux:heading size="sm" class="!mb-4">Version Timeline</flux:heading>

            <div class="relative">
                {{-- Timeline Line --}}
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                {{-- Timeline Items --}}
                <div class="space-y-6">
                    @foreach($this->versions as $version)
                        <div class="relative flex items-start gap-4">
                            {{-- Timeline Dot --}}
                            <div class="relative z-10 flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center
                                    {{ $version->id === $this->versionAId ? 'bg-blue-500 text-white' : ($version->id === $this->versionBId ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                                    @if($version->id === $this->versionAId)
                                        <span class="text-xs font-bold">A</span>
                                    @elseif($version->id === $this->versionBId)
                                        <span class="text-xs font-bold">B</span>
                                    @else
                                        <flux:icon.musical-note class="w-4 h-4" />
                                    @endif
                                </div>
                            </div>

                            {{-- Timeline Content --}}
                            <div class="flex-1 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $version->getVersionLabel() }}</span>
                                        @if($version->id === $file->id)
                                            <flux:badge size="sm" class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">Current</flux:badge>
                                        @endif
                                        @if($loop->first)
                                            <flux:badge size="sm" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Latest</flux:badge>
                                        @endif
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $version->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $version->file_name }} • {{ $version->formattedSize }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Synchronized Playback Script --}}
@if($syncPlayback)
    @script
    <script>
        // Synchronized playback logic
        // This would be implemented with Alpine.js or vanilla JS to sync the two players
        console.log('Sync playback enabled');
    </script>
    @endscript
@endif
