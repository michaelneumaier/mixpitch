@if ($variant === 'header')
    {{-- Header Dropdown Variant --}}
    <div wire:poll.60s="loadActiveSession">
        <flux:dropdown position="bottom" align="end">
            @php
                $badgeColor = 'zinc';
                if ($activeSession && $activeSession->isActive()) {
                    $badgeColor = 'green';
                } elseif ($activeSession && $activeSession->isPaused()) {
                    $badgeColor = 'amber';
                }
            @endphp
            <flux:badge
                as="button"
                variant="pill"
                size="lg"
                :color="$badgeColor"
                :icon="$activeSession ? null : 'clock'"
                class="cursor-pointer"
            >
                @if ($activeSession && $activeSession->isActive())
                    <span class="relative mr-1.5 flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                    </span>
                    {{ $duration }}
                @elseif ($activeSession && $activeSession->isPaused())
                    <flux:icon name="pause" class="mr-1 h-3.5 w-3.5" />
                    Paused
                @else
                    Work Session
                @endif
            </flux:badge>

            <flux:menu class="w-72">
                <flux:menu.heading>Work Session</flux:menu.heading>
                <div class="space-y-3 px-3 py-2">
                    @if (!$activeSession)
                        {{-- Pre-session options --}}
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <flux:checkbox wire:model="isVisibleToClient" />
                                <span>Visible to client</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <flux:checkbox wire:model="focusMode" />
                                <span>Focus mode</span>
                            </label>
                        </div>

                        <flux:button wire:click="startSession" variant="primary" size="sm" class="w-full" icon="play">
                            Start Session
                        </flux:button>
                    @else
                        {{-- Status Display --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full {{ $activeSession->isActive() ? 'bg-green-100 dark:bg-green-900/50' : 'bg-yellow-100 dark:bg-yellow-900/50' }}">
                                    @if ($activeSession->isActive())
                                        <flux:icon name="play" class="h-3 w-3 text-green-600 dark:text-green-400" />
                                    @else
                                        <flux:icon name="pause" class="h-3 w-3 text-yellow-600 dark:text-yellow-400" />
                                    @endif
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $activeSession->getStatusLabel() }}
                                </span>
                            </div>
                            <span class="text-sm font-medium {{ $activeSession->isActive() ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                                {{ $duration }}
                            </span>
                        </div>

                        {{-- Active session controls --}}
                        <div class="flex gap-2">
                            @if ($activeSession->isActive())
                                <flux:button wire:click="pauseSession" variant="ghost" size="sm" class="flex-1" icon="pause">
                                    Pause
                                </flux:button>
                            @else
                                <flux:button wire:click="resumeSession" variant="primary" size="sm" class="flex-1" icon="play">
                                    Resume
                                </flux:button>
                            @endif
                            <flux:button wire:click="endSession" variant="danger" size="sm" class="flex-1" icon="stop">
                                End
                            </flux:button>
                        </div>

                        {{-- Session options --}}
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <flux:checkbox wire:model="isVisibleToClient" wire:change="toggleVisibility" />
                                <span>Visible</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-xs {{ $focusMode ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400' }}">
                                <flux:checkbox wire:model="focusMode" wire:change="toggleFocusMode" />
                                <span>Focus</span>
                                @if ($focusMode)
                                    <flux:badge size="sm" color="purple">On</flux:badge>
                                @endif
                            </label>
                        </div>

                        {{-- Session notes --}}
                        <div class="space-y-1">
                            <div class="flex gap-2">
                                <flux:input
                                    wire:model="sessionNotes"
                                    placeholder="What are you working on?"
                                    size="sm"
                                    class="flex-1"
                                    x-on:keydown.stop
                                />
                                <flux:button wire:click="saveNotes" variant="ghost" size="sm" icon="check" />
                            </div>
                            @if ($activeSession->notes)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <flux:icon name="eye" class="mr-1 inline h-3 w-3" />
                                    Client sees: "{{ $activeSession->notes }}"
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Total time footer --}}
                    @if ($totalTime !== '0m')
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Total time: <span class="font-medium">{{ $totalTime }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </flux:menu>
        </flux:dropdown>
    </div>
@else
    {{-- Embedded Variant (original) --}}
    <div wire:poll.60s="loadActiveSession">
        {{-- Session Controls --}}
        <div class="space-y-3">
            {{-- Status Display --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full {{ $activeSession ? ($activeSession->isActive() ? 'bg-green-100 dark:bg-green-900/50' : 'bg-yellow-100 dark:bg-yellow-900/50') : 'bg-gray-100 dark:bg-gray-700' }}">
                        @if ($activeSession && $activeSession->isActive())
                            <flux:icon name="play" class="h-3 w-3 text-green-600 dark:text-green-400" />
                        @elseif ($activeSession && $activeSession->isPaused())
                            <flux:icon name="pause" class="h-3 w-3 text-yellow-600 dark:text-yellow-400" />
                        @else
                            <flux:icon name="stop" class="h-3 w-3 text-gray-400" />
                        @endif
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        @if ($activeSession)
                            {{ $activeSession->getStatusLabel() }}
                        @else
                            Not started
                        @endif
                    </span>
                </div>

                {{-- Timer Display --}}
                @if ($activeSession)
                    <div class="flex items-center gap-1.5 rounded-full {{ $activeSession->isActive() ? 'bg-green-100 dark:bg-green-900/50' : 'bg-yellow-100 dark:bg-yellow-900/50' }} px-2.5 py-1">
                        @if ($activeSession->isActive())
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                            </span>
                        @endif
                        <span class="text-xs font-medium {{ $activeSession->isActive() ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300' }}">{{ $duration }}</span>
                    </div>
                @endif
            </div>

            @if (!$activeSession)
                {{-- Pre-session options --}}
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                        <flux:checkbox wire:model="isVisibleToClient" />
                        <span>Visible to client</span>
                    </label>
                    <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                        <flux:checkbox wire:model="focusMode" />
                        <span>Focus mode</span>
                    </label>
                </div>

                <flux:button wire:click="startSession" variant="primary" size="sm" class="w-full" icon="play">
                    Start Work Session
                </flux:button>
            @else
                {{-- Active session controls --}}
                <div class="flex gap-2">
                    @if ($activeSession->isActive())
                        <flux:button wire:click="pauseSession" variant="ghost" size="sm" class="flex-1" icon="pause">
                            Pause
                        </flux:button>
                    @else
                        <flux:button wire:click="resumeSession" variant="primary" size="sm" class="flex-1" icon="play">
                            Resume
                        </flux:button>
                    @endif
                    <flux:button wire:click="endSession" variant="danger" size="sm" class="flex-1" icon="stop">
                        End
                    </flux:button>
                </div>

                {{-- Session options --}}
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                        <flux:checkbox wire:model="isVisibleToClient" wire:change="toggleVisibility" />
                        <span>Visible</span>
                    </label>
                    <label class="flex items-center gap-1.5 text-xs {{ $focusMode ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400' }}">
                        <flux:checkbox wire:model="focusMode" wire:change="toggleFocusMode" />
                        <span>Focus</span>
                        @if ($focusMode)
                            <flux:badge size="sm" color="purple">On</flux:badge>
                        @endif
                    </label>
                </div>

                {{-- Session notes --}}
                <div class="space-y-1">
                    <div class="flex gap-2">
                        <flux:input
                            wire:model="sessionNotes"
                            placeholder="What are you working on?"
                            size="sm"
                            class="flex-1"
                        />
                        <flux:button wire:click="saveNotes" variant="ghost" size="sm" icon="check" />
                    </div>
                    @if ($activeSession->notes)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <flux:icon name="eye" class="mr-1 inline h-3 w-3" />
                            Client sees: "{{ $activeSession->notes }}"
                        </p>
                    @endif
                </div>
            @endif

            {{-- Total time footer --}}
            @if ($totalTime !== '0m')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Total time: <span class="font-medium">{{ $totalTime }}</span>
                </p>
            @endif
        </div>
    </div>
@endif
