@props(['pitch', 'workflowColors' => null, 'semanticColors' => null])

@php
    $project = $pitch->project;
    $user = $pitch->user;
    
    // If colors not passed, define defaults
    if (!$workflowColors) {
        $workflowColors = [
            'bg' => 'bg-gray-50 dark:bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
            'accent_border' => 'border-gray-200 dark:border-gray-800',
            'icon' => 'text-gray-600 dark:text-gray-400'
        ];
    }
@endphp

<flux:card>
    <!-- Compact Header -->
    <div class="mb-4 flex items-center gap-3">
        <flux:icon.information-circle variant="solid" class="{{ $workflowColors['icon'] }} h-6 w-6" />
        <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">Pitch Details</flux:heading>
    </div>

    <!-- Producer Info - Compact -->
    <div class="mb-4 flex items-center gap-3 {{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} border rounded-lg p-3">
        <img class="h-8 w-8 rounded-full object-cover border {{ $workflowColors['border'] }}"
             src="{{ $user->profile_photo_url }}"
             alt="{{ $user->name }}" />
        <div class="flex-1 min-w-0">
            <flux:text class="{{ $workflowColors['text_primary'] }} font-medium truncate">{{ $user->name }}</flux:text>
            @if($user->username)
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">@{{ $user->username }}</flux:text>
            @else
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">Producer</flux:text>
            @endif
        </div>
    </div>

    <!-- Project Details - Single Column Layout -->
    <div class="space-y-2">
        @if($project->artist_name)
            <div class="flex items-center gap-2">
                <flux:icon.microphone class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">Artist</flux:text>
                    <flux:text size="sm" class="{{ $workflowColors['text_primary'] }} font-medium truncate">{{ $project->artist_name }}</flux:text>
                </div>
            </div>
        @endif
        
        @if($project->genre)
            <div class="flex items-center gap-2">
                <flux:icon.musical-note class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">Genre</flux:text>
                    <flux:text size="sm" class="{{ $workflowColors['text_primary'] }} font-medium truncate">{{ $project->genre }}</flux:text>
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2">
            <flux:icon.tag class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
            <div class="min-w-0 flex-1">
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">Type</flux:text>
                <flux:text size="sm" class="{{ $workflowColors['text_primary'] }} font-medium truncate">{{ ucwords(str_replace('_', ' ', $project->project_type)) }}</flux:text>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:icon.currency-dollar class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
            <div class="min-w-0 flex-1">
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">Budget</flux:text>
                <flux:text size="sm" class="{{ $workflowColors['text_primary'] }} font-medium">
                    {{ $project->budget == 0 ? 'Free' : '$'.number_format($project->budget, 0) }}
                    @if($project->budget > 0)
                        <span class="font-normal {{ $workflowColors['text_muted'] }}">USD</span>
                    @endif
                </flux:text>
            </div>
        </div>

        @if($project->isContest() ? $project->submission_deadline : $project->deadline)
            <div class="flex items-center gap-2">
                <flux:icon.calendar class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">
                        {{ $project->isContest() ? 'Deadline' : 'Deadline' }}
                    </flux:text>
                    @php $deadline = $project->isContest() ? $project->submission_deadline : $project->deadline; @endphp
                    <flux:text size="sm" class="{{ $deadline->isPast() ? 'text-red-600 dark:text-red-400' : $workflowColors['text_primary'] }} font-medium">
                        {{ $deadline->format('M d, Y') }}
                        @if($deadline->isPast())
                            <flux:icon.exclamation-triangle class="inline h-3 w-3 ml-1" />
                        @endif
                    </flux:text>
                </div>
            </div>
        @endif
        
        <div class="flex items-center gap-2">
            <flux:icon.clock class="{{ $workflowColors['icon'] }} h-4 w-4 flex-shrink-0" />
            <div class="min-w-0 flex-1">
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide">Created</flux:text>
                <flux:text size="sm" class="{{ $workflowColors['text_primary'] }} font-medium">{{ $pitch->created_at->format('M d, Y') }}</flux:text>
            </div>
        </div>

        <!-- Services/Collaboration Types (if available) - Compact Tags -->
        @if($project->collaboration_type && count(array_filter($project->collaboration_type)) > 0)
            <div class="pt-2 border-t {{ $workflowColors['border'] }}">
                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} uppercase tracking-wide mb-2 block">Services Needed</flux:text>
                <div class="flex flex-wrap gap-1">
                    @foreach($project->collaboration_type as $key => $value)
                        @php
                            $collaborationType = '';
                            if (is_string($key) && $value && $value !== false) {
                                $collaborationType = $key;
                            } elseif (is_string($value) && !empty($value)) {
                                $collaborationType = $value;
                            } elseif (is_numeric($key) && is_string($value) && !empty($value)) {
                                $collaborationType = $value;
                            }
                            
                            if ($collaborationType) {
                                $collaborationType = str_replace('_', ' ', $collaborationType);
                                $collaborationType = ucwords(strtolower($collaborationType));
                            }
                        @endphp
                        
                        @if($collaborationType)
                            <flux:badge size="sm" class="text-xs">
                                {{ $collaborationType }}
                            </flux:badge>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</flux:card>