<div wire:poll.30s="updateUnreadCount">
    {{-- Floating Action Button --}}
    <button
        wire:click="openHub"
        x-on:click="$flux.modal('communication-hub').show()"
        class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg cursor-pointer hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 dark:focus:ring-purple-800"
        aria-label="Open Communication Hub"
    >
        {{-- Icon --}}
        <flux:icon name="rectangle-stack" class="h-6 w-6" />

        {{-- Unread Badge --}}
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white shadow-md animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>
</div>
