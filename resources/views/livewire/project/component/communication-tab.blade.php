<div
    x-data="{
        isAtBottom: true,
        scrollToBottom(el) {
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
        checkIfAtBottom(el) {
            if (!el) return true;
            // Consider 'at bottom' if within 100px of the bottom
            return el.scrollHeight - el.scrollTop - el.clientHeight < 100;
        },
        handleScroll(el) {
            this.isAtBottom = this.checkIfAtBottom(el);
        }
    }"
    x-on:open-print-view.window="window.open($event.detail.url, '_blank')"
    x-on:scroll-to-bottom.window="$nextTick(() => {
        if ($refs.desktopMessages) $refs.desktopMessages.scrollTop = $refs.desktopMessages.scrollHeight;
        if ($refs.mobileMessages) $refs.mobileMessages.scrollTop = $refs.mobileMessages.scrollHeight;
    })"
    wire:poll.30s="markMessagesAsRead"
>
    {{-- Desktop Layout: Two-column (Messages + Sidebar) --}}
    <div class="hidden lg:grid lg:grid-cols-3 lg:gap-4">
        {{-- Main Messages Area (2/3 width) --}}
        <div class="lg:col-span-2">
            <flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
                {{-- Header --}}
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-indigo-600">
                            <flux:icon name="chat-bubble-left-right" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                                Messages
                            </flux:heading>
                            <p class="text-sm {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">
                                {{ $project->client_name ?? 'Client' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($this->unreadCount > 0)
                            <flux:badge color="red" size="sm">
                                {{ $this->unreadCount }} unread
                            </flux:badge>
                        @endif

                        {{-- Search Dropdown --}}
                        <div x-data="{ searchOpen: false }" class="contents">
                            <flux:dropdown position="bottom" align="end" x-model="searchOpen">
                                <flux:button variant="ghost" size="sm" icon="magnifying-glass" class="relative">
                                    @if (strlen($searchQuery) >= 2)
                                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-purple-500 px-1 text-xs text-white">
                                            {{ $this->chatMessages->count() }}
                                        </span>
                                    @endif
                                </flux:button>
                                <flux:menu class="w-72">
                                    <div class="p-2" x-effect="if (searchOpen) $nextTick(() => $el.querySelector('input')?.focus())">
                                        <flux:input
                                            wire:model.live.debounce.300ms="searchQuery"
                                            placeholder="Search messages..."
                                            icon="magnifying-glass"
                                            clearable
                                        />
                                        @if (strlen($searchQuery) >= 2)
                                            <p class="mt-2 text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">
                                                Found {{ $this->chatMessages->count() }} {{ Str::plural('message', $this->chatMessages->count()) }}
                                            </p>
                                        @endif
                                    </div>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

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

                {{-- Messages Thread --}}
                <div
                    x-ref="desktopMessages"
                    @scroll="handleScroll($refs.desktopMessages)"
                    class="mb-4 max-h-[400px] space-y-4 overflow-y-auto rounded-lg border {{ $workflowColors['accent_border'] ?? 'border-gray-200 dark:border-gray-700' }} bg-gray-50/50 p-4 dark:bg-gray-900/50"
                >
                    @include('livewire.project.component.communication-tab.messages-section')
                </div>

                {{-- Compose Footer --}}
                <div class="border-t {{ $workflowColors['accent_border'] ?? 'border-gray-200 dark:border-gray-700' }} pt-4">
                    <form wire:submit="sendMessage">
                        <div class="space-y-3">
                            <flux:textarea
                                wire:model="newMessage"
                                placeholder="Type your message to the client..."
                                rows="3"
                                class="resize-none"
                            />
                            @error('newMessage')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    {{-- Quick Templates Dropdown --}}
                                    <flux:dropdown position="top" align="start">
                                        <flux:button variant="ghost" size="sm" icon="bolt">
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

                                    <label class="flex items-center gap-2 text-sm {{ $workflowColors['text_muted'] ?? 'text-gray-600 dark:text-gray-400' }}">
                                        <flux:checkbox wire:model="isUrgent" />
                                        <span>Urgent</span>
                                    </label>
                                </div>

                                <flux:button type="submit" variant="primary" icon="paper-airplane">
                                    Send Message
                                </flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar (1/3 width) --}}
        <div class="space-y-4">
            @include('livewire.project.component.communication-tab.sidebar-panel')
        </div>
    </div>

    {{-- Mobile Layout: Segmented Control --}}
    <div x-data="{ activeSection: 'messages' }" class="lg:hidden">
        {{-- Segmented Control - NOT tabs --}}
        <div class="mb-4 flex overflow-hidden rounded-lg border {{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800">
            <button
                @click="activeSection = 'messages'"
                :class="activeSection === 'messages'
                    ? '{{ $workflowColors['accent_bg'] ?? 'bg-purple-100 dark:bg-purple-900/50' }} {{ $workflowColors['text_primary'] ?? 'text-purple-900 dark:text-purple-100' }} font-medium'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                class="relative flex-1 px-3 py-2.5 text-sm transition-colors"
            >
                Messages
                @if($this->unreadCount > 0)
                    <span class="absolute right-2 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-xs text-white">
                        {{ min($this->unreadCount, 99) }}{{ $this->unreadCount > 99 ? '+' : '' }}
                    </span>
                @endif
            </button>
            <button
                @click="activeSection = 'activity'"
                :class="activeSection === 'activity'
                    ? '{{ $workflowColors['accent_bg'] ?? 'bg-purple-100 dark:bg-purple-900/50' }} {{ $workflowColors['text_primary'] ?? 'text-purple-900 dark:text-purple-100' }} font-medium'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                class="flex-1 border-l {{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }} px-3 py-2.5 text-sm transition-colors"
            >
                Activity
            </button>
            <button
                @click="activeSection = 'actions'"
                :class="activeSection === 'actions'
                    ? '{{ $workflowColors['accent_bg'] ?? 'bg-purple-100 dark:bg-purple-900/50' }} {{ $workflowColors['text_primary'] ?? 'text-purple-900 dark:text-purple-100' }} font-medium'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                class="relative flex-1 border-l {{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }} px-3 py-2.5 text-sm transition-colors"
            >
                Actions
                @if($this->pendingActions->count() > 0)
                    <span class="absolute right-2 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-xs text-white">
                        {{ $this->pendingActions->count() }}
                    </span>
                @endif
            </button>
        </div>

        {{-- Mobile Content Panels --}}
        <div x-show="activeSection === 'messages'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
                {{-- Header --}}
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="chat-bubble-left-right" class="{{ $workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400' }} h-5 w-5" />
                        <flux:heading size="base" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                            Messages
                        </flux:heading>
                    </div>
                    <div class="flex items-center gap-1">
                        {{-- Search Dropdown --}}
                        <div x-data="{ mobileSearchOpen: false }" class="contents">
                            <flux:dropdown position="bottom" align="end" x-model="mobileSearchOpen">
                                <flux:button variant="ghost" size="sm" icon="magnifying-glass" class="relative">
                                    @if (strlen($searchQuery) >= 2)
                                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-purple-500 px-1 text-xs text-white">
                                            {{ $this->chatMessages->count() }}
                                        </span>
                                    @endif
                                </flux:button>
                                <flux:menu class="w-64">
                                    <div class="p-2" x-effect="if (mobileSearchOpen) $nextTick(() => $el.querySelector('input')?.focus())">
                                        <flux:input
                                            wire:model.live.debounce.300ms="searchQuery"
                                            placeholder="Search messages..."
                                            icon="magnifying-glass"
                                            clearable
                                            size="sm"
                                        />
                                        @if (strlen($searchQuery) >= 2)
                                            <p class="mt-2 text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">
                                                Found {{ $this->chatMessages->count() }} {{ Str::plural('message', $this->chatMessages->count()) }}
                                            </p>
                                        @endif
                                    </div>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        {{-- Export Dropdown --}}
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item wire:click="exportJson" icon="arrow-down-tray">Export</flux:menu.item>
                                <flux:menu.item wire:click="exportPrint" icon="printer">Print</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                {{-- Messages --}}
                <div
                    x-ref="mobileMessages"
                    @scroll="handleScroll($refs.mobileMessages)"
                    class="mb-4 max-h-[350px] space-y-4 overflow-y-auto rounded-lg border {{ $workflowColors['accent_border'] ?? 'border-gray-200 dark:border-gray-700' }} bg-gray-50/50 p-3 dark:bg-gray-900/50"
                >
                    @include('livewire.project.component.communication-tab.messages-section')
                </div>

                {{-- Compose --}}
                <form wire:submit="sendMessage">
                    <div class="space-y-3">
                        <flux:textarea
                            wire:model="newMessage"
                            placeholder="Type your message..."
                            rows="2"
                            class="resize-none"
                        />
                        @error('newMessage')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:dropdown position="top" align="start">
                                    <flux:button variant="ghost" size="xs" icon="bolt" />
                                    <flux:menu>
                                        @foreach ($this->getQuickTemplates() as $index => $template)
                                            <flux:menu.item wire:click="useTemplate({{ $index }})" icon="{{ $template['icon'] }}">
                                                {{ $template['label'] }}
                                            </flux:menu.item>
                                        @endforeach
                                    </flux:menu>
                                </flux:dropdown>
                                <label class="flex items-center gap-1 text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-600 dark:text-gray-400' }}">
                                    <flux:checkbox wire:model="isUrgent" />
                                    Urgent
                                </label>
                            </div>
                            <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane">
                                Send
                            </flux:button>
                        </div>
                    </div>
                </form>
            </flux:card>
        </div>

        <div x-show="activeSection === 'activity'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon name="clock" class="{{ $workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400' }} h-5 w-5" />
                    <flux:heading size="base" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                        Activity Timeline
                    </flux:heading>
                </div>
                @include('livewire.project.component.communication-tab.activity-section')
            </flux:card>
        </div>

        <div x-show="activeSection === 'actions'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon name="bell-alert" class="{{ $workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400' }} h-5 w-5" />
                    <flux:heading size="base" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                        Pending Actions
                    </flux:heading>
                    @if($this->pendingActions->count() > 0)
                        <flux:badge color="red" size="sm">{{ $this->pendingActions->count() }}</flux:badge>
                    @endif
                </div>
                @include('livewire.project.component.communication-tab.actions-section')
            </flux:card>
        </div>
    </div>
</div>
