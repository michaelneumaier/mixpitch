<div wire:poll.30s="loadActiveSession">
    @if ($activeSession)
        @php
            $project = $activeSession->pitch->project;
            $badgeColor = $activeSession->isActive() ? 'green' : 'amber';
        @endphp

        <div class="px-4 pb-2 -my-5">
            <a href="{{ route('projects.manage', $project) }}" class="block" wire:navigate>
                <flux:badge
                    variant="pill"
                    size="lg"
                    :color="$badgeColor"
                    class="w-full cursor-pointer justify-start"
                >
                    @if ($activeSession->isActive())
                        <span class="relative mr-1.5 flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                        </span>
                        {{ $activeSession->getFormattedDuration() }}
                    @else
                        <flux:icon name="pause" class="mr-1 h-3.5 w-3.5" />
                        Paused
                    @endif
                    <span class="ml-1.5 truncate text-xs opacity-80">
                        {{ Str::limit($project->name, 15) }}
                    </span>
                </flux:badge>
            </a>
        </div>
    @endif
</div>
