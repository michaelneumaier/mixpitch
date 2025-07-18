<div class="annotation-summary">
    @if($compact ?? false)
        <!-- Compact Header for File Cards -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <h4 class="text-sm font-semibold text-gray-800">Client Feedback</h4>
                @if($totalComments > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $totalComments }} {{ Str::plural('comment', $totalComments) }}
                    </span>
                @endif
            </div>
            @if($unresolvedCount > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    {{ $unresolvedCount }} pending
                </span>
            @endif
        </div>
    @else
        <!-- Full Header for Standalone View -->
        <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">File Annotations</h3>
                    <p class="text-sm text-gray-600">{{ $pitchFile->file_name }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Stats -->
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600">{{ $totalComments }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-amber-600">{{ $unresolvedCount }}</div>
                        <div class="text-xs text-gray-500">Open</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600">{{ $resolvedCount }}</div>
                        <div class="text-xs text-gray-500">Resolved</div>
                    </div>
                </div>
            </div>
            
            <!-- Controls -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" 
                           wire:model="showResolved" 
                           wire:change="toggleShowResolved" 
                           class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Show resolved annotations</span>
                </label>
                
                <div class="text-sm text-gray-500">
                    @if(count($intervals) > 0)
                        {{ count($intervals) }} time {{ Str::plural('interval', count($intervals)) }}
                    @else
                        No annotations
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Annotations by Time Interval -->
    @forelse($intervals as $interval)
        @if($compact ?? false)
            <!-- Compact Comment Display -->
            <div class="space-y-2 mb-4">
                @foreach($interval['comments'] as $comment)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs font-medium text-blue-800">
                                        @if($comment['is_client_comment'])
                                            Client
                                        @else
                                            {{ $comment['user']['name'] ?? 'Producer' }}
                                        @endif
                                    </span>
                                    <span class="text-xs text-blue-600">{{ gmdate('i:s', $comment['timestamp']) }}</span>
                                    @if($comment['resolved'])
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>Done
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-700 leading-relaxed">{{ Str::limit($comment['comment'], 120) }}</p>
                                
                                <!-- Action Buttons for Compact Mode -->
                                <div class="flex items-center space-x-3 mt-2">
                                    @if(!$comment['resolved'] && !$comment['is_client_comment'])
                                        <button wire:click="resolveComment({{ $comment['id'] }})"
                                                class="text-xs px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded transition-colors">
                                            <i class="fas fa-check mr-1"></i>Mark Resolved
                                        </button>
                                    @endif
                                    <button onclick="expandComment{{ $pitchFile->id }}({{ $comment['id'] }})"
                                            class="text-xs px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition-colors">
                                        <i class="fas fa-expand mr-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                            <div class="ml-2 flex flex-col space-y-1">
                                <button wire:click="jumpToTimestamp({{ $comment['timestamp'] }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 transition-colors p-1">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Full Comment Display -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 overflow-hidden">
                <!-- Interval Header -->
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-white text-xs"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">
                                    {{ $interval['time_label'] }}
                                </h4>
                                <p class="text-xs text-gray-600">
                                    {{ $interval['comment_count'] }} {{ Str::plural('annotation', $interval['comment_count']) }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="jumpToTimestamp({{ $interval['start_time'] }})"
                                class="text-xs px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                            <i class="fas fa-play mr-1"></i>Jump to
                        </button>
                    </div>
                </div>
                
                <!-- Comments in this Interval -->
                <div class="p-4 space-y-4">
                    @foreach($interval['comments'] as $comment)
                        <div class="annotation-item {{ $comment['resolved'] ? 'opacity-60' : '' }}">
                        <div class="flex items-start space-x-3">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                @if($comment['is_client_comment'])
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-white text-xs"></i>
                                    </div>
                                @else
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-tie text-white text-xs"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Comment Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">
                                            @if($comment['is_client_comment'])
                                                {{ $comment['client_email'] }} <span class="text-blue-600">(Client)</span>
                                            @else
                                                {{ $comment['user']['name'] ?? 'Producer' }}
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ gmdate('i:s', $comment['timestamp']) }}
                                        </span>
                                        @if($comment['resolved'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>Resolved
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="jumpToTimestamp({{ $comment['timestamp'] }})"
                                                class="text-xs text-blue-600 hover:text-blue-800 transition-colors">
                                            <i class="fas fa-play mr-1"></i>Play
                                        </button>
                                        @if(!$comment['resolved'] && !$comment['is_client_comment'])
                                            <button wire:click="resolveComment({{ $comment['id'] }})"
                                                    class="text-xs text-green-600 hover:text-green-800 transition-colors">
                                                <i class="fas fa-check mr-1"></i>Resolve
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Comment Text -->
                                <p class="text-gray-700 text-sm leading-relaxed mb-2">{{ $comment['comment'] }}</p>
                                
                                <!-- Replies -->
                                @if(isset($comment['replies']) && count($comment['replies']) > 0)
                                    <div class="mt-3 pl-4 border-l-2 border-gray-200 space-y-2">
                                        @foreach($comment['replies'] as $reply)
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        @if($reply['is_client_comment'])
                                                            {{ $reply['client_email'] }} <span class="text-blue-600">(Client)</span>
                                                        @else
                                                            {{ $reply['user']['name'] ?? 'Producer' }}
                                                        @endif
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        {{ \Carbon\Carbon::parse($reply['created_at'])->format('M j, g:i A') }}
                                                    </span>
                                                </div>
                                                <p class="text-gray-700 text-sm">{{ $reply['comment'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @empty
        @if($compact ?? false)
            <!-- Compact Empty State -->
            <div class="text-center py-4">
                <div class="text-sm text-gray-500 mb-2">
                    <i class="fas fa-comment-dots text-gray-400 mr-1"></i>
                    No client feedback yet
                </div>
                <p class="text-xs text-gray-400">
                    Feedback will appear here when your client reviews this audio file
                </p>
            </div>
        @else
            <!-- Full Empty State -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comment-dots text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No annotations found</h3>
                    @if(!$showResolved)
                        <p class="text-gray-600 mb-4">
                            No unresolved annotations for this file. 
                            @if($resolvedCount > 0)
                                Check "Show resolved annotations" to see completed feedback.
                            @endif
                        </p>
                    @else
                        <p class="text-gray-600 mb-4">This file doesn't have any annotations yet.</p>
                    @endif
                    <div class="text-sm text-gray-500">
                        Annotations will appear here when added to the audio file with specific timestamps.
                    </div>
                </div>
            </div>
        @endif
    @endforelse

    <!-- Footer with Legend (only in full mode) -->
    @if(count($intervals) > 0 && !($compact ?? false))
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Legend</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-700">Client feedback</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-purple-500 rounded-full"></div>
                    <span class="text-gray-700">Producer notes</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                    <span class="text-gray-700">Resolved annotation</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-play text-blue-600 w-4"></i>
                    <span class="text-gray-700">Click to jump to timestamp</span>
                </div>
            </div>
        </div>
    @endif
</div>