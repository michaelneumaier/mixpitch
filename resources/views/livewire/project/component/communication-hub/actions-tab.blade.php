{{-- Actions Tab Content --}}
<div class="p-4">
    @forelse ($pendingActions as $action)
        @php
            $priorityClass = $this->getPriorityClass($action['priority']);
        @endphp

        <div class="mb-4 last:mb-0">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start gap-3">
                    {{-- Priority Icon --}}
                    <div class="flex-shrink-0">
                        @switch($action['type'])
                            @case('revision_pending')
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                                    <flux:icon name="arrow-path" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                @break
                            @case('unread_messages')
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                                    <flux:icon name="chat-bubble-left" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                @break
                            @case('unresolved_comments')
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/50">
                                    <flux:icon name="annotation" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                @break
                            @default
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                    <flux:icon name="bell" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                </div>
                        @endswitch
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $action['title'] }}
                            </h4>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $priorityClass }}">
                                {{ ucfirst($action['priority']) }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $action['description'] }}
                        </p>

                        @if (isset($action['action_url']))
                            <div class="mt-3">
                                @if ($action['type'] === 'revision_pending')
                                    <flux:button
                                        x-on:click="$dispatch('close-modal', { name: 'communication-hub' })"
                                        variant="primary"
                                        size="sm"
                                    >
                                        <flux:icon name="arrow-right" class="mr-1.5 h-4 w-4" />
                                        Respond to Feedback
                                    </flux:button>
                                @elseif ($action['type'] === 'unread_messages')
                                    <flux:button
                                        wire:click="setActiveTab('messages')"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        View Messages
                                    </flux:button>
                                @elseif ($action['type'] === 'unresolved_comments')
                                    <flux:button
                                        x-on:click="$dispatch('close-modal', { name: 'communication-hub' })"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        View Comments
                                    </flux:button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                <flux:icon name="check-circle" class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
            <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">All caught up!</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                You have no pending actions at this time.
            </p>
        </div>
    @endforelse
</div>
