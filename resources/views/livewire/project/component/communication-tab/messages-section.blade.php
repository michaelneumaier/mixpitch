{{-- Messages Section Content --}}
@forelse ($this->chatMessages as $message)
    @php
        $isProducer = $message->event_type === \App\Models\PitchEvent::TYPE_PRODUCER_MESSAGE;
        $isOwnMessage = $message->created_by === auth()->id();
    @endphp

    <div class="flex {{ $isProducer ? 'justify-end' : 'justify-start' }}">
        <div class="max-w-[85%] {{ $isProducer ? 'order-2' : 'order-1' }}">
            {{-- Message Bubble --}}
            <div class="{{ $isProducer ? 'bg-purple-100 dark:bg-purple-900/50' : 'bg-blue-100 dark:bg-blue-900/50' }} rounded-2xl px-4 py-3 {{ $isProducer ? 'rounded-br-md' : 'rounded-bl-md' }}">
                {{-- Urgent Badge --}}
                @if ($message->is_urgent)
                    <div class="mb-2 flex items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                        <flux:icon name="exclamation-triangle" class="h-3.5 w-3.5" />
                        Urgent
                    </div>
                @endif

                {{-- Message Content --}}
                <p class="{{ $isProducer ? 'text-purple-900 dark:text-purple-100' : 'text-blue-900 dark:text-blue-100' }} whitespace-pre-wrap text-sm">{{ $message->comment }}</p>
            </div>

            {{-- Message Meta --}}
            <div class="mt-1 flex items-center gap-2 px-2 {{ $isProducer ? 'justify-end' : 'justify-start' }}">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($isProducer)
                        {{ $message->user?->name ?? 'You' }}
                    @else
                        {{ $message->metadata['client_name'] ?? 'Client' }}
                    @endif
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $message->created_at->diffForHumans() }}
                </span>

                {{-- Read Receipt --}}
                @if ($isProducer)
                    @if ($message->delivery_status === \App\Models\PitchEvent::DELIVERY_READ)
                        <span class="text-xs text-green-600 dark:text-green-400" title="Seen by client">
                            <flux:icon name="check-circle" class="h-3.5 w-3.5" />
                        </span>
                    @else
                        <span class="text-xs text-gray-400 dark:text-gray-500" title="Delivered">
                            <flux:icon name="check" class="h-3.5 w-3.5" />
                        </span>
                    @endif
                @endif

                {{-- Delete Button (own messages only) --}}
                @if ($isOwnMessage)
                    <button
                        wire:click="deleteMessage({{ $message->id }})"
                        wire:confirm="Are you sure you want to delete this message?"
                        class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                        title="Delete message"
                    >
                        <flux:icon name="trash" class="h-3.5 w-3.5" />
                    </button>
                @endif
            </div>
        </div>

        {{-- Avatar --}}
        <div class="flex-shrink-0 {{ $isProducer ? 'order-1 mr-2' : 'order-2 ml-2' }}">
            @if ($isProducer)
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-200 dark:bg-purple-800">
                    <span class="text-xs font-medium text-purple-700 dark:text-purple-300">
                        {{ substr($message->user?->name ?? 'P', 0, 1) }}
                    </span>
                </div>
            @else
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-200 dark:bg-blue-800">
                    <span class="text-xs font-medium text-blue-700 dark:text-blue-300">
                        {{ substr($message->metadata['client_name'] ?? 'C', 0, 1) }}
                    </span>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="flex flex-col items-center justify-center py-8 text-center">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
            <flux:icon name="chat-bubble-left-right" class="h-6 w-6 text-gray-400" />
        </div>
        <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">No messages yet</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Start the conversation by sending a message to your client.
        </p>
    </div>
@endforelse
