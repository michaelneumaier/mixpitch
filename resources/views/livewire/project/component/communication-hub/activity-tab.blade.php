{{-- Activity Tab Content --}}
<div class="p-4">
    @forelse ($activityTimeline as $event)
        @php
            $displayType = $this->getEventDisplayType($event);
            $colorClass = $this->getEventColorClass($displayType);
            $icon = $this->getEventIcon($displayType);
        @endphp

        <div class="relative pb-6 last:pb-0">
            {{-- Timeline Line --}}
            @if (!$loop->last)
                <span class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
            @endif

            <div class="relative flex items-start gap-3">
                {{-- Icon --}}
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $colorClass }}">
                    <flux:icon :name="$icon" class="h-4 w-4" />
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            @switch($displayType)
                                @case('client_message')
                                    {{ $event->metadata['client_name'] ?? 'Client' }} sent a message
                                    @break
                                @case('producer_message')
                                    {{ $event->user?->name ?? 'You' }} sent a message
                                    @break
                                @case('approval')
                                    Client approved the deliverables
                                    @break
                                @case('revision_request')
                                    Client requested revisions
                                    @break
                                @case('status_change')
                                    Status changed to {{ ucfirst(str_replace('_', ' ', $event->status)) }}
                                    @break
                                @case('file_uploaded')
                                    File uploaded
                                    @break
                                @case('recall')
                                    Submission recalled
                                    @break
                                @case('work_session_completed')
                                    Work session completed
                                    @if($event->metadata['duration_formatted'] ?? null)
                                        <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                            ({{ $event->metadata['duration_formatted'] }})
                                        </span>
                                    @endif
                                    @break
                                @default
                                    Activity recorded
                            @endswitch
                        </span>
                        @if ($event->is_urgent)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                Urgent
                            </span>
                        @endif
                    </div>

                    @if ($event->comment && !in_array($displayType, ['client_message', 'producer_message']))
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $event->comment }}
                        </p>
                    @elseif ($displayType === 'work_session_completed' && ($event->metadata['notes'] ?? null))
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $event->metadata['notes'] }}
                        </p>
                    @endif

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                        {{ $event->created_at->diffForHumans() }}
                        @if ($event->created_at->isToday())
                            <span class="text-gray-400">&middot;</span>
                            {{ $event->created_at->format('g:i A') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <flux:icon name="clock" class="h-8 w-8 text-gray-400" />
            </div>
            <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-white">No activity yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Activity will appear here as you work on this project.
            </p>
        </div>
    @endforelse
</div>
