@auth
<div class="px-2">
    <!-- My Work Section Header -->
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">My Work</h3>
        @if($counts['total'] > 0)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                {{ $counts['total'] }}
            </span>
        @endif
    </div>

    <!-- Work Items Summary -->
    <div class="space-y-1 mb-4">
        @if($counts['projects'] > 0)
            <flux:navlist.item icon="folder" href="{{ route('dashboard') }}#projects" class="text-sm">
                <div class="flex items-center justify-between w-full">
                    <span>Projects</span>
                    <span class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded-full">{{ $counts['projects'] }}</span>
                </div>
            </flux:navlist.item>
        @endif

        @if($counts['pitches'] > 0)
            <flux:navlist.item icon="paper-airplane" href="{{ route('dashboard') }}#pitches" class="text-sm">
                <div class="flex items-center justify-between w-full">
                    <span>Pitches</span>
                    <span class="text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded-full">{{ $counts['pitches'] }}</span>
                </div>
            </flux:navlist.item>
        @endif

        @if($counts['contests'] > 0)
            <flux:navlist.item icon="trophy" href="{{ route('dashboard') }}#contests" class="text-sm">
                <div class="flex items-center justify-between w-full">
                    <span>Contests</span>
                    <span class="text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 px-2 py-0.5 rounded-full">{{ $counts['contests'] }}</span>
                </div>
            </flux:navlist.item>
        @endif

        @if($counts['client_projects'] > 0)
            <flux:navlist.item icon="briefcase" href="{{ route('dashboard') }}#client-projects" class="text-sm">
                <div class="flex items-center justify-between w-full">
                    <span>Client Work</span>
                    <span class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 px-2 py-0.5 rounded-full">{{ $counts['client_projects'] }}</span>
                </div>
            </flux:navlist.item>
        @endif
    </div>

    <!-- Recent Work Items -->
    @if($workItems->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Recent Activity</h4>
            <div class="space-y-1">
                @foreach($workItems as $item)
                    @php
                        $itemType = 'unknown';
                        $itemUrl = '#';
                        $itemIcon = 'document';
                        $itemStatus = '';
                        
                        if ($item instanceof \App\Models\Project) {
                            $itemType = $item->isContest() ? 'contest' : ($item->isClientManagement() ? 'client' : 'project');
                            $itemUrl = route('projects.show', $item);
                            $itemIcon = $item->isContest() ? 'trophy' : ($item->isClientManagement() ? 'briefcase' : 'folder');
                            $itemStatus = $item->status;
                        } elseif ($item instanceof \App\Models\Pitch) {
                            $itemType = 'pitch';
                            $itemUrl = route('pitches.show', $item);
                            $itemIcon = 'paper-airplane';
                            $itemStatus = $item->status;
                        }
                        
                        // Status colors
                        $statusColor = 'gray';
                        if (in_array($itemStatus, ['open', 'pending', 'in_progress'])) {
                            $statusColor = 'blue';
                        } elseif (in_array($itemStatus, ['completed', 'approved', 'contest_winner'])) {
                            $statusColor = 'green';
                        } elseif (in_array($itemStatus, ['revisions_requested', 'client_revisions_requested'])) {
                            $statusColor = 'yellow';
                        }
                    @endphp
                    
                    <a href="{{ $itemUrl }}" class="flex items-center p-2 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 group transition-colors">
                        <flux:icon :name="$itemIcon" class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-3 flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ $item->name ?? $item->title ?? 'Untitled' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <span class="w-2 h-2 rounded-full mr-1 bg-{{ $statusColor }}-400"></span>
                                {{ ucfirst(str_replace('_', ' ', $itemStatus)) }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- View All Link -->
    @if($counts['total'] > 0)
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('dashboard') }}" class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium">
                <span>View all work</span>
                <flux:icon name="arrow-right" class="w-4 h-4 ml-1" />
            </a>
        </div>
    @endif
</div>
@endauth