@props(['component', 'conversationItems'])

<div class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 shadow-xl backdrop-blur-md">
    <div class="border-b border-white/20 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-blue-500/10 p-4 lg:p-6 backdrop-blur-sm">
        <div class="flex items-center">
            <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                <i class="fas fa-comments text-lg text-white"></i>
            </div>
            <div>
                <h4 class="text-lg font-bold text-blue-800">Communication Timeline</h4>
                <p class="text-sm text-blue-600">Track all project communications and updates</p>
            </div>
        </div>
    </div>
    
    <div class="p-2 md:p-4 lg:p-6">
    
    <div class="space-y-4 max-h-96 overflow-y-auto">
        @forelse($conversationItems as $item)
        <div class="relative bg-white/80 backdrop-blur-sm border border-white/50 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200 {{ $component->getEventBorderColor($item) }} border-l-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $component->getEventBgColor($item) }} shadow-md">
                            <i class="{{ $component->getEventIcon($item) }} text-white text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">{{ $component->getEventTitle($item) }}</span>
                            <div class="flex items-center space-x-2 mt-1">
                                @if($item['user'])
                                <span class="text-xs text-gray-600 font-medium">
                                    by {{ $item['user']->name }}
                                </span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    {{ $item['date']->diffForHumans() }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $item['date']->format('M d, Y \a\t g:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($item['content'])
                    <div class="bg-gradient-to-r from-blue-50/60 to-indigo-50/60 border border-blue-200/30 rounded-xl p-4 ml-12 backdrop-blur-sm">
                        <p class="text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $item['content'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full mx-auto mb-6">
                <i class="fas fa-comments text-blue-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No communication yet</h3>
            <p class="text-sm text-gray-600">Messages and updates will appear here as you communicate with your client</p>
        </div>
        @endforelse
    </div>
    </div>
</div> 