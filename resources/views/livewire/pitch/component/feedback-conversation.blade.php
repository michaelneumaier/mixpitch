<div class="feedback-conversation">
    <div class="mb-3">
        <h3 class="text-xl font-semibold mb-1 flex items-center">
            <i class="fas fa-comments mr-2 text-blue-600"></i>Feedback & Revision History
        </h3>
        <p class="text-gray-600 text-sm">A history of communication between the project owner and pitch creator.</p>
    </div>

    @if(count($this->conversationItems) > 0)
    <div class="space-y-2">
        @foreach($this->conversationItems as $item)
        <div class="w-full rounded-lg shadow-sm 
            @if($item['type'] === 'feedback') 
                @if($item['snapshot']->status === 'revisions_requested' || 
                    ($item['feedback_type'] ?? 'revision') === 'revision')
                    bg-amber-50 border border-amber-200
                @else
                    bg-red-50 border border-red-200
                @endif
            @elseif($item['type'] === 'completion')
                bg-success/10 border border-success/30
            @else
                bg-blue-50 border border-blue-200
            @endif">

            <!-- Header with user info and date -->
            <div class="flex items-center justify-between px-2.5 py-1.5 border-b 
                @if($item['type'] === 'feedback')
                    @if($item['snapshot']->status === 'revisions_requested' || 
                        ($item['feedback_type'] ?? 'revision') === 'revision')
                        border-amber-200 bg-amber-100/50
                    @else
                        border-red-200 bg-red-100/50
                    @endif
                @elseif($item['type'] === 'completion')
                    border-success/30 bg-success/20
                @else
                    border-blue-200 bg-blue-100/50
                @endif">

                <div class="flex items-center">
                    @if(isset($item['user']))
                    <img class="h-5 w-5 rounded-full object-cover mr-2 border border-gray-200"
                        src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" />
                    <span class="font-medium text-gray-900 text-sm">{{ $item['user']->name }}</span>
                    @else
                    <span class="font-medium text-gray-900 text-sm">{{ $item['type'] === 'feedback' ? 'Project Owner' :
                        'Pitch Creator' }}</span>
                    @endif

                    <span class="mx-2 text-xs text-gray-500">•</span>

                    <span class="inline-flex items-center text-xs">
                        @if($item['type'] === 'feedback')
                        @if($item['snapshot']->status === 'revisions_requested' ||
                        ($item['feedback_type'] ?? 'revision') === 'revision')
                        <i class="fas fa-comment-dots mr-1 text-amber-600"></i>
                        Revision Request for v{{ $item['snapshot']->snapshot_data['version'] ?? '?' }}
                        @else
                        <i class="fas fa-times-circle mr-1 text-red-600"></i>
                        Denial Reason for v{{ $item['snapshot']->snapshot_data['version'] ?? '?' }}
                        @endif
                        @elseif($item['type'] === 'completion')
                        <i class="fas fa-trophy mr-1 text-success"></i>
                        Completion Feedback for v{{ $item['snapshot']->snapshot_data['version'] ?? '?' }}
                        @elseif($item['type'] === 'response')
                        <i class="fas fa-reply mr-1 text-blue-600"></i>
                        @if(isset($item['previous_snapshot_id']))
                        @php
                        $previousSnapshot = $pitch->snapshots()->find($item['previous_snapshot_id']);
                        $previousVersion = $previousSnapshot ? ($previousSnapshot->snapshot_data['version'] ?? '?') :
                        '?';
                        @endphp
                        Response to v{{ $previousVersion }} feedback
                        @else
                        Response for v{{ $item['snapshot']->snapshot_data['version'] ?? '?' }}
                        @endif
                        @endif
                    </span>
                </div>

                <div class="flex items-center">
                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $item['snapshot']->id]) }}"
                        class="text-xs text-blue-600 hover:text-blue-800 mr-3">
                        <i class="fas fa-eye mr-1"></i> View version
                    </a>

                    <span class="text-xs text-gray-500">
                        @if(is_object($item['date']) && method_exists($item['date'], 'format'))
                            {{ $item['date']->format('M j, g:i a') }}
                        @else
                            {{ is_string($item['date']) ? $item['date'] : date('M j, g:i a') }}
                        @endif
                    </span>
                </div>
            </div>

            <!-- Content -->
            <div class="p-2.5 whitespace-pre-wrap text-gray-700 text-sm">
                {{ $item['content'] }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center p-6 bg-gray-50 rounded-lg border border-gray-200">
        <i class="fas fa-comments text-gray-300 text-4xl mb-2"></i>
        <p class="text-gray-500">No feedback or revision messages yet.</p>
    </div>
    @endif
</div>