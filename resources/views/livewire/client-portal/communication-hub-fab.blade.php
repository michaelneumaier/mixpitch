<div wire:poll.30s="updateUnreadCount, updatePresence">
    {{-- Floating Action Button for Client Portal --}}
    <div class="fixed bottom-6 right-6 z-50">
        {{-- Producer Status Label (shown when working on this project) --}}
        @if (($producerPresence['status'] ?? 'offline') === 'working')
            <div class="absolute -top-8 right-0 max-w-48 truncate rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 shadow-md dark:bg-green-900/80 dark:text-green-300" title="{{ $producerPresence['label'] ?? 'Working' }}">
                <span class="mr-1.5 inline-block h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                {{ Str::limit($producerPresence['label'] ?? 'Working', 30) }}
            </div>
        @endif

        <button
            wire:click="openHub"
            x-on:click="$flux.modal('communication-hub').show()"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800"
            aria-label="Open Communication Hub"
        >
            {{-- Icon --}}
            <flux:icon name="chat-bubble-left-right" class="h-6 w-6" />

            {{-- Unread Badge --}}
            @if ($unreadCount > 0)
                <span class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white shadow-md animate-pulse">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif

            {{-- Presence Indicator Dot --}}
            @php
                $statusColor = match($producerPresence['status'] ?? 'offline') {
                    'working' => 'bg-green-500',
                    'online' => 'bg-green-400',
                    'busy' => 'bg-yellow-500',
                    'away' => 'bg-yellow-400',
                    'offline' => 'bg-gray-400',
                    default => 'bg-gray-400',
                };
            @endphp
            @if (($producerPresence['status'] ?? 'offline') !== 'hidden')
                <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-white dark:border-gray-900 dark:bg-gray-900">
                    <span class="{{ $statusColor }} h-2.5 w-2.5 rounded-full"></span>
                </span>
            @endif
        </button>
    </div>
</div>
