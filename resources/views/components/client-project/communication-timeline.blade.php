@props(['component', 'conversationItems', 'workflowColors' => [], 'semanticColors' => []])

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <flux:icon.chat-bubble-left-ellipsis variant="solid" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            <div>
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Communication Timeline</flux:heading>
                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Track all project communications and updates</flux:text>
            </div>
        </div>
    </div>
    
    <div class="p-6">
    
    <div class="space-y-4 max-h-96 overflow-y-auto">
        @forelse($conversationItems as $item)
        <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 {{ $this->getEventBorderColor($item) }} border-l-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $this->getEventBgColor($item) }}">
                            <i class="{{ $this->getEventIcon($item) }} text-white text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <flux:text weight="semibold" class="text-gray-900 dark:text-gray-100">{{ $this->getEventTitle($item) }}</flux:text>
                            <div class="flex items-center gap-2 mt-1">
                                @if($item['user'])
                                <flux:text size="xs" class="text-gray-600 dark:text-gray-400 font-medium">
                                    by {{ $item['user']->name }}
                                </flux:text>
                                @endif
                                <flux:text size="xs" class="text-gray-500">
                                    {{ $item['date']->diffForHumans() }}
                                </flux:text>
                                <flux:text size="xs" class="text-gray-400">
                                    {{ $item['date']->format('M d, Y \a\t g:i A') }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                    
                    @if($item['content'])
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 ml-13">
                        <flux:text size="sm" class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $item['content'] }}</flux:text>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mx-auto mb-4">
                <flux:icon.chat-bubble-left-ellipsis class="w-8 h-8 text-gray-400" />
            </div>
            <flux:heading size="lg" class="text-gray-900 dark:text-gray-100 mb-2">No communication yet</flux:heading>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Messages and updates will appear here as you communicate with your client</flux:text>
        </div>
        @endforelse
    </div>
    </div>
</div> 