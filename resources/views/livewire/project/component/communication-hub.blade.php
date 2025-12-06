<div x-on:open-print-view.window="window.open($event.detail.url, '_blank')">
    {{-- Communication Hub Modal --}}
    <flux:modal name="communication-hub" variant="flyout" position="right" class="w-full md:w-[28rem] lg:w-[32rem]">
        <div class="flex h-full flex-col">
            {{-- Header --}}
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-indigo-600">
                            <flux:icon name="chat-bubble-left-right" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Communication Hub</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($unreadCount > 0)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                {{ $unreadCount }} unread
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

            {{-- Tabs --}}
            <div class="border-b border-gray-200 px-4 dark:border-gray-700">
                <nav class="-mb-px flex gap-4" aria-label="Tabs">
                    <button
                        wire:click="setActiveTab('messages')"
                        class="{{ $activeTab === 'messages' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    >
                        <flux:icon name="chat-bubble-left-right" class="mr-1.5 inline-block h-4 w-4" />
                        Messages
                    </button>
                    <button
                        wire:click="setActiveTab('activity')"
                        class="{{ $activeTab === 'activity' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    >
                        <flux:icon name="clock" class="mr-1.5 inline-block h-4 w-4" />
                        Activity
                    </button>
                    <button
                        wire:click="setActiveTab('actions')"
                        class="{{ $activeTab === 'actions' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }} relative whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    >
                        <flux:icon name="bell-alert" class="mr-1.5 inline-block h-4 w-4" />
                        Actions
                        @if ($pendingActions->count() > 0)
                            <span class="absolute -right-1 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                {{ $pendingActions->count() }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto">
                @if ($activeTab === 'messages')
                    @include('livewire.project.component.communication-hub.messages-tab')
                @elseif ($activeTab === 'activity')
                    @include('livewire.project.component.communication-hub.activity-tab')
                @else
                    @include('livewire.project.component.communication-hub.actions-tab')
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
                                    placeholder="Type your message to the client..."
                                    rows="3"
                                    class="resize-none"
                                />
                                @error('newMessage')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    {{-- Quick Templates Dropdown --}}
                                    <flux:dropdown position="top" align="start">
                                        <flux:button variant="ghost" size="sm" icon="bolt" title="Quick templates">
                                            Templates
                                        </flux:button>
                                        <flux:menu>
                                            <flux:menu.heading>Quick Templates</flux:menu.heading>
                                            @foreach ($this->getQuickTemplates() as $index => $template)
                                                <flux:menu.item wire:click="useTemplate({{ $index }})" icon="{{ $template['icon'] }}">
                                                    {{ $template['label'] }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>

                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <flux:checkbox wire:model="isUrgent" />
                                        <span>Urgent</span>
                                    </label>
                                </div>
                                <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane">
                                    Send
                                </flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
