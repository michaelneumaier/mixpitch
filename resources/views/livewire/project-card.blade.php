<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md dark:hover:shadow-lg transition-all duration-200 overflow-hidden">
    <!-- Project Image -->
    <div class="relative aspect-video bg-gray-100 overflow-hidden">
        @if($project->image_path)
            <img src="{{ $project->imageUrl }}" 
                 alt="{{ $project->name }}" 
                 class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-200"
                 wire:click="cardClickRoute()">
        @else
            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 flex items-center justify-center cursor-pointer hover:from-blue-200 hover:to-indigo-200 dark:hover:from-blue-800 dark:hover:to-indigo-800 transition-colors duration-200"
                 wire:click="cardClickRoute()">
                <i class="fas fa-music text-4xl text-blue-400 dark:text-blue-300"></i>
            </div>
        @endif
        
        <!-- Status Badge -->
        <div class="absolute top-3 right-3">
            @php
                $statusConfig = [
                    'unpublished' => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-800 dark:text-gray-200', 'icon' => 'fa-eye-slash'],
                    'open' => ['bg' => 'bg-green-100 dark:bg-green-800', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'fa-check-circle'],
                    'review' => ['bg' => 'bg-blue-100 dark:bg-blue-800', 'text' => 'text-blue-800 dark:text-blue-200', 'icon' => 'fa-eye'],
                    'completed' => ['bg' => 'bg-purple-100 dark:bg-purple-800', 'text' => 'text-purple-800 dark:text-purple-200', 'icon' => 'fa-check'],
                    'closed' => ['bg' => 'bg-red-100 dark:bg-red-800', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'fa-times-circle'],
                ];
                $config = $statusConfig[$project->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question-circle'];
            @endphp
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }} border border-white/20 shadow-sm">
                <i class="fas {{ $config['icon'] }} mr-1"></i>
                {{ ucfirst($project->status) }}
            </span>
        </div>
        
        <!-- Workflow Type Badge -->
        <div class="absolute bottom-3 left-3">
            @php
                $workflowConfig = [
                    'standard' => ['bg' => 'bg-blue-100 dark:bg-blue-800', 'text' => 'text-blue-800 dark:text-blue-200', 'icon' => 'fa-users', 'label' => 'Standard'],
                    'contest' => ['bg' => 'bg-purple-100 dark:bg-purple-800', 'text' => 'text-purple-800 dark:text-purple-200', 'icon' => 'fa-trophy', 'label' => 'Contest'],
                    'direct_hire' => ['bg' => 'bg-green-100 dark:bg-green-800', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'fa-user-check', 'label' => 'Direct Hire'],
                    'client_management' => ['bg' => 'bg-orange-100 dark:bg-orange-800', 'text' => 'text-orange-800 dark:text-orange-200', 'icon' => 'fa-briefcase', 'label' => 'Client Project'],
                ];
                $workflowType = $project->workflow_type ?? 'standard';
                $workflowStyle = $workflowConfig[$workflowType] ?? $workflowConfig['standard'];
            @endphp
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }} border border-white/20 shadow-sm">
                <i class="fas {{ $workflowStyle['icon'] }} mr-1"></i>{{ $workflowStyle['label'] }}
            </span>
        </div>
        
        <!-- Preview Track Player -->
        @if($project->hasPreviewTrack())
            <div class="absolute bottom-3 right-3" onclick="event.stopPropagation();">
                @livewire('audio-player', [
                    'audioUrl' => $project->previewTrackPath(), 
                    'isInCard' => true,
                    'trackTitle' => $project->project_title . ' - Preview',
                    'trackArtist' => $project->user->name ?? 'Unknown Artist',
                    'projectTitle' => $project->project_title,
                    'fileId' => $project->id
                ])
            </div>
        @endif
    </div>
    
    <!-- Card Content -->
    <div class="p-4">
        <!-- Project Title -->
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 line-clamp-2 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer"
            wire:click="cardClickRoute()">
            {{ $project->name }}
        </h3>
        
        <!-- Genre -->
        @if($project->genre)
            <div class="mb-3">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-800 text-purple-800 dark:text-purple-200">
                    <i class="fas fa-music mr-1"></i>{{ $project->genre }}
                </span>
            </div>
        @endif
        
        <!-- Project Owner -->
        <div class="flex items-center mb-3">
            <img class="h-8 w-8 rounded-full object-cover mr-2 border border-gray-200 dark:border-gray-600" 
                 src="{{ $project->user->profile_photo_url }}" 
                 alt="{{ $project->user->name }}">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    <x-user-link :user="$project->user" />
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Project Owner</p>
            </div>
        </div>
        
        <!-- Project Metadata -->
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4">
            <div class="flex items-center space-x-4">
                <!-- Workflow Type -->
                @php
                    $workflowConfig = [
                        'standard' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-users', 'label' => 'Standard'],
                        'contest' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-trophy', 'label' => 'Contest'],
                        'direct_hire' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-user-check', 'label' => 'Direct Hire'],
                        'client_management' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'fa-briefcase', 'label' => 'Client Project'],
                    ];
                    $workflowType = $project->workflow_type ?? 'standard';
                    $workflowStyle = $workflowConfig[$workflowType] ?? $workflowConfig['standard'];
                @endphp
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }}">
                    <i class="fas {{ $workflowStyle['icon'] }} mr-1"></i>{{ $workflowStyle['label'] }}
                </span>
                
                <!-- Project Type -->
                @if($project->project_type)
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        <i class="fas fa-tag mr-1"></i>{{ Str::title($project->project_type) }}
                    </span>
                @endif
            </div>
            
            <!-- Pitches Count -->
            <span class="inline-flex items-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-paper-plane mr-1"></i>
                {{ $project->pitches->count() }} {{ Str::plural('pitch', $project->pitches->count()) }}
            </span>
        </div>
        
        <!-- Budget/Prize Information -->
        @if($project->isContest() && $project->hasPrizes())
            <!-- Contest Prizes (Compact) -->
            <x-contest.prize-display :project="$project" :compact="true" :showTitle="false" />
        @else
            <!-- Standard Budget -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-dollar-sign text-gray-400 dark:text-gray-500 mr-2"></i>
                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        @if(is_numeric($project->budget) && $project->budget > 0)
                            ${{ number_format((float)$project->budget) }}
                        @elseif($project->budget === 0 || $project->budget === '0')
                            Free
                        @else
                            Price TBD
                        @endif
                    </span>
                </div>
                
                @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <div>
                            @if($project->isContest())
                                <div><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" /></div>
                                <div class="text-xs"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" /></div>
                            @else
                                <div><x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" /></div>
                                <div class="text-xs"><x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" /></div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
        
        @if($isDashboardView)
            <!-- Dashboard Stats -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mb-4">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">{{ $project->files->count() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Files</div>
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $project->pitches->count() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Pitches</div>
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-purple-600 dark:text-purple-400">{{ $project->created_at->diffInDays(now()) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Days</div>
                    </div>
                </div>
            </div>
            
            <!-- Manage Button -->
            <a href="{{ route('projects.manage', $project) }}" 
               wire:navigate
               class="block w-full text-center bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-cog mr-2"></i>Manage Project
            </a>
        @else
            <!-- View Project Button -->
            <button wire:click="cardClickRoute()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-eye mr-2"></i>View Project
            </button>
        @endif
    </div>
</div>