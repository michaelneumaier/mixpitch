<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Notification Bell with Count --}}
    <button @click="open = !open" class="flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
        <livewire:notification-count />
    </button>

    {{-- Notification Dropdown --}}
    <div
        x-show="open"
        @click.away="open = false"
        class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-20"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        style="display: none;"
    >
        <div class="flex flex-col">
            <div class="px-4 py-3 bg-gradient-to-r from-primary/10 to-secondary/10 flex justify-between items-center border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800">Notifications</h3>
                @if($hasUnread)
                <button 
                    wire:click="markAllAsRead"
                    class="text-xs text-primary hover:text-primary/80 transition-colors duration-150"
                >
                    Mark all as read
                </button>
                @endif
            </div>

            <div class="max-h-72 overflow-y-auto">
                @forelse($notifications as $notification)
                <div class="border-b border-gray-100 last:border-0">
                    <a 
                        href="{{ $notification->getUrl() }}"
                        wire:click="markAsRead({{ $notification->id }})"
                        class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}"
                    >
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                @if($notification->type === \App\Models\Notification::TYPE_PITCH_STATUS_CHANGE)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-500">
                                        <i class="fas fa-sync-alt"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PITCH_COMPLETED)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100 text-green-500">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_APPROVED)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100 text-green-500">
                                        <i class="fas fa-thumbs-up"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_DENIED)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-500">
                                        <i class="fas fa-thumbs-down"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_REVISIONS_REQUESTED)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-amber-100 text-amber-500">
                                        <i class="fas fa-pencil-alt"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PITCH_COMMENT)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-purple-100 text-purple-500">
                                        <i class="fas fa-comment"></i>
                                    </span>
                                @elseif($notification->type === \App\Models\Notification::TYPE_NEW_SUBMISSION)
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100 text-yellow-500">
                                        <i class="fas fa-file-alt"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-gray-500">
                                        <i class="fas fa-bell"></i>
                                    </span>
                                @endif
                            </div>
                            <div class="ml-3 w-0 flex-1">
                                <p class="text-sm leading-5 font-medium text-gray-900">
                                    {{ $notification->getReadableDescription() }}
                                </p>
                                <p class="text-xs leading-5 text-gray-500 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-8 px-4">
                    <span class="text-gray-300 mb-2">
                        <i class="fas fa-bell-slash text-3xl"></i>
                    </span>
                    <p class="text-gray-500 text-sm">No notifications yet</p>
                </div>
                @endforelse
            </div>
            
            @if(count($notifications) > 5)
            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 text-center">
                <button 
                    wire:click="loadMoreNotifications"
                    class="text-xs text-primary hover:text-primary/80 transition-colors duration-150"
                >
                    Load more notifications
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
