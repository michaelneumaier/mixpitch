<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Notification Bell with Count --}}
    <button @click="open = !open" class="flex items-center text-gray-600 hover:text-blue-600 focus:outline-none transition-all duration-200 p-2 rounded-xl hover:bg-blue-50/50">
        <livewire:notification-count />
    </button>

    {{-- Notification Dropdown --}}
    <div
        x-show="open"
        @click.away="open = false"
        class="absolute -right-12 mt-3 w-80 md:w-96 max-w-[calc(100vw-2rem)] bg-white/90 backdrop-blur-lg border border-white/30 rounded-2xl shadow-2xl overflow-hidden z-50"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 transform scale-95 translate-y-2"
        style="display: none;"
    >
        <div class="flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-blue-500/10 backdrop-blur-sm border-b border-white/20">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        <i class="fas fa-bell mr-2"></i>
                        Notifications
                    </h3>
                    @if($hasUnread)
                    <button 
                        wire:click="markAllAsRead"
                        class="px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-105"
                    >
                        <i class="fas fa-check-double mr-1"></i>
                        Mark all read
                    </button>
                    @endif
                </div>
            </div>

            <!-- Notifications List -->
            <div class="max-h-80 overflow-y-auto">
                @forelse($notifications as $notification)
                <div class="group relative border-b border-white/10 last:border-0 hover:bg-gradient-to-r hover:from-blue-50/30 hover:to-purple-50/30 transition-all duration-200 {{ $notification->read_at ? 'bg-white/20' : 'bg-gradient-to-r from-blue-50/50 to-purple-50/50' }}">
                    <a 
                        href="{{ $notification->getUrl() }}"
                        wire:click="markAsRead({{ $notification->id }})"
                        class="flex-grow block px-6 py-4"
                    >
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                @if($notification->type === \App\Models\Notification::TYPE_PITCH_STATUS_CHANGE)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg">
                                        <i class="fas fa-sync-alt text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PITCH_COMPLETED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-green-400 to-green-600 text-white shadow-lg">
                                        <i class="fas fa-check-circle text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_APPROVED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg">
                                        <i class="fas fa-thumbs-up text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_DENIED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 text-white shadow-lg">
                                        <i class="fas fa-thumbs-down text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_SNAPSHOT_REVISIONS_REQUESTED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg">
                                        <i class="fas fa-pencil-alt text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PITCH_COMMENT)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 text-white shadow-lg">
                                        <i class="fas fa-comment text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PITCH_SUBMITTED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-yellow-400 to-orange-500 text-white shadow-lg">
                                        <i class="fas fa-file-signature text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_CONTEST_PAYOUT_SCHEDULED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 text-white shadow-lg">
                                        <i class="fas fa-clock text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PAYOUT_COMPLETED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 text-white shadow-lg">
                                        <i class="fas fa-money-bill-wave text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PAYOUT_FAILED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 text-white shadow-lg">
                                        <i class="fas fa-exclamation-triangle text-sm"></i>
                                    </div>
                                @elseif($notification->type === \App\Models\Notification::TYPE_PAYOUT_CANCELLED)
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-lg">
                                        <i class="fas fa-ban text-sm"></i>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-lg">
                                        <i class="fas fa-bell text-sm"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4 flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 leading-relaxed">
                                    {{ $notification->getReadableDescription() }}
                                </p>
                                <div class="flex items-center mt-2">
                                    <p class="text-xs text-gray-500 flex items-center">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                    @if(!$notification->read_at)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                                        New
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Delete Button -->
                    <button 
                        wire:click.prevent="deleteNotification({{ $notification->id }})"
                        wire:loading.attr="disabled"
                        aria-label="Delete notification"
                        class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-200 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50/50 rounded-lg focus:outline-none focus:text-red-600"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-12 px-6">
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 mb-4">
                        <i class="fas fa-bell-slash text-2xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-600 mb-2">No notifications yet</h4>
                    <p class="text-sm text-gray-500 text-center">When you have new activity, notifications will appear here</p>
                </div>
                @endforelse
            </div>
            
            <!-- Load More Footer -->
            @if(count($notifications) > 5)
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm border-t border-white/20">
                <button 
                    wire:click="loadMoreNotifications"
                    class="w-full px-4 py-2 text-sm font-medium bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <i class="fas fa-chevron-down mr-2"></i>
                    Load more notifications
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
