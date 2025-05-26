@props(['component', 'conversationItems'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
    <h4 class="text-lg font-semibold mb-4 flex items-center">
        <i class="fas fa-comments text-blue-500 mr-2"></i>
        Communication Timeline
    </h4>
    
    <div class="space-y-4 max-h-96 overflow-y-auto">
        @forelse($conversationItems as $item)
        <div class="border-l-4 pl-4 py-3 {{ $component->getEventBorderColor($item) }}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $component->getEventBgColor($item) }}">
                            <i class="{{ $component->getEventIcon($item) }} text-white text-xs"></i>
                        </div>
                        <span class="font-medium text-sm">{{ $component->getEventTitle($item) }}</span>
                        <span class="text-xs text-gray-500">
                            {{ $item['date']->diffForHumans() }}
                        </span>
                    </div>
                    
                    @if($item['content'])
                    <div class="bg-gray-50 rounded-lg p-3 ml-10">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item['content'] }}</p>
                    </div>
                    @endif
                    
                    <div class="ml-10 mt-2 flex items-center space-x-2">
                        @if($item['user'])
                        <span class="text-xs text-gray-500">
                            by {{ $item['user']->name }}
                        </span>
                        @endif
                        <span class="text-xs text-gray-400">
                            {{ $item['date']->format('M d, Y \a\t g:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-comments text-4xl text-gray-300 mb-3"></i>
            <p>No communication yet</p>
            <p class="text-sm">Messages and updates will appear here</p>
        </div>
        @endforelse
    </div>
</div> 