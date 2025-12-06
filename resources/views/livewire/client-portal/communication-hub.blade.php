<div x-on:open-print-view.window="window.open($event.detail.url, '_blank')">
    {{-- Client Communication Hub Modal --}}
    <flux:modal name="communication-hub" variant="flyout" position="right" class="w-full md:w-[28rem] lg:w-[32rem]">
        <div class="flex h-full flex-col">
            {{-- Header --}}
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-indigo-600">
                            <flux:icon name="chat-bubble-left-right" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Messages</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Chat with your producer</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($unreadCount > 0)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                {{ $unreadCount }} new
                            </span>
                        @endif

                        {{-- Export Dropdown --}}
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item wire:click="exportJson" icon="arrow-down-tray">
                                    Export as JSON
                                </flux:menu.item>
                                <flux:menu.item wire:click="exportPrint" icon="printer">
                                    Print / Save as PDF
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>

            {{-- Producer Status Banner --}}
            @if(($producerPresence['status'] ?? 'offline') === 'working')
                <div class="border-b border-green-200 bg-green-50 px-4 py-2 dark:border-green-800 dark:bg-green-900/30">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-500"></span>
                        </span>
                        <span class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ $producerPresence['label'] ?? 'Producer is working on your project' }}
                        </span>
                        @if($producerPresence['duration'] ?? null)
                            <span class="ml-auto text-xs text-green-600 dark:text-green-400">
                                {{ $producerPresence['duration'] }}
                            </span>
                        @endif
                    </div>
                    @if($producerPresence['notes'] ?? null)
                        <p class="mt-1 text-xs text-green-700 dark:text-green-300">
                            Currently: {{ $producerPresence['notes'] }}
                        </p>
                    @endif
                </div>
            @elseif(($producerPresence['status'] ?? 'offline') === 'online')
                <div class="border-b border-blue-200 bg-blue-50 px-4 py-1.5 dark:border-blue-800 dark:bg-blue-900/30">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                        <span class="text-xs text-blue-700 dark:text-blue-300">Producer is online</span>
                    </div>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="border-b border-gray-200 px-4 dark:border-gray-700">
                <nav class="-mb-px flex gap-4" aria-label="Tabs">
                    <button
                        wire:click="setActiveTab('messages')"
                        class="{{ $activeTab === 'messages' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    >
                        <flux:icon name="chat-bubble-left-right" class="mr-1.5 inline-block h-4 w-4" />
                        Messages
                    </button>
                    <button
                        wire:click="setActiveTab('activity')"
                        class="{{ $activeTab === 'activity' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    >
                        <flux:icon name="clock" class="mr-1.5 inline-block h-4 w-4" />
                        Activity
                    </button>
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto">
                @if ($activeTab === 'messages')
                    @include('livewire.client-portal.communication-hub.messages-tab')
                @else
                    @include('livewire.client-portal.communication-hub.activity-tab')
                @endif
            </div>

            {{-- Message Compose Footer (only on Messages tab) --}}
            @if ($activeTab === 'messages')
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    <form wire:submit="sendMessage">
                        <div class="space-y-3">
                            <div>
                                <flux:textarea
                                    wire:model="newMessage"
                                    placeholder="Type your message..."
                                    rows="2"
                                    class="resize-none"
                                />
                                @error('newMessage')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex justify-end">
                                <flux:button type="submit" variant="primary" size="sm">
                                    <flux:icon name="paper-airplane" class="mr-1.5 h-4 w-4" />
                                    Send Message
                                </flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
