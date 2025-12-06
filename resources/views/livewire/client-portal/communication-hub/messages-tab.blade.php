{{-- Client Portal Messages Tab Content --}}
<div class="flex flex-col gap-4 p-4">
    {{-- Search Bar --}}
    <div class="relative">
        <flux:input
            wire:model.live.debounce.300ms="searchQuery"
            placeholder="Search messages..."
            icon="magnifying-glass"
            clearable
        />
        @if (strlen($searchQuery) >= 2)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Found {{ $chatMessages->count() }} {{ Str::plural('message', $chatMessages->count()) }}
            </div>
        @endif
    </div>

    @forelse ($chatMessages as $message)
        @php
            $isClient = $message->event_type === \App\Models\PitchEvent::TYPE_CLIENT_MESSAGE;
            $isOwnMessage = ($message->metadata['client_email'] ?? null) === $clientEmail;
        @endphp

        <div class="flex {{ $isClient ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] {{ $isClient ? 'order-2' : 'order-1' }}">
                {{-- Message Bubble --}}
                <div class="{{ $isClient ? 'bg-blue-100 dark:bg-blue-900/50' : 'bg-purple-100 dark:bg-purple-900/50' }} rounded-2xl px-4 py-3 {{ $isClient ? 'rounded-br-md' : 'rounded-bl-md' }}">
                    {{-- Urgent Badge --}}
                    @if ($message->is_urgent)
                        <div class="mb-2 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" class="h-3.5 w-3.5" />
                            Urgent from Producer
                        </div>
                    @endif

                    {{-- Message Content --}}
                    <p class="{{ $isClient ? 'text-blue-900 dark:text-blue-100' : 'text-purple-900 dark:text-purple-100' }} whitespace-pre-wrap text-sm">{{ $message->comment }}</p>
                </div>

                {{-- Message Meta --}}
                <div class="mt-1 flex items-center gap-2 px-2 {{ $isClient ? 'justify-end' : 'justify-start' }}">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($isClient)
                            You
                        @else
                            {{ $message->user?->name ?? 'Producer' }}
                        @endif
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $message->created_at->diffForHumans() }}
                    </span>

                    {{-- Read Receipt (for own messages) --}}
                    @if ($isOwnMessage)
                        @if ($message->delivery_status === \App\Models\PitchEvent::DELIVERY_READ)
                            <span class="text-xs text-green-600 dark:text-green-400" title="Seen by producer">
                                <flux:icon name="check-circle" class="h-3.5 w-3.5" />
                            </span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500" title="Delivered">
                                <flux:icon name="check" class="h-3.5 w-3.5" />
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Avatar --}}
            <div class="flex-shrink-0 {{ $isClient ? 'order-1 mr-2' : 'order-2 ml-2' }}">
                @if ($isClient)
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-200 dark:bg-blue-800">
                        <span class="text-xs font-medium text-blue-700 dark:text-blue-300">
                            {{ substr($clientName ?? 'C', 0, 1) }}
                        </span>
                    </div>
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-200 dark:bg-purple-800">
                        <span class="text-xs font-medium text-purple-700 dark:text-purple-300">
                            {{ substr($message->user?->name ?? 'P', 0, 1) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <flux:icon name="chat-bubble-left-right" class="h-8 w-8 text-gray-400" />
            </div>
            <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">No messages yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Send a message to your producer to start the conversation.
            </p>
        </div>
    @endforelse
</div>
