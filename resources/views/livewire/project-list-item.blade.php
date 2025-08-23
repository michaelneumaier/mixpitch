<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md dark:hover:shadow-lg transition-all duration-200 overflow-hidden">
    <div class="flex flex-col md:flex-row">
        <!-- Project Image -->
        <div class="relative md:w-48 lg:w-56 h-48 md:h-auto bg-gray-100 dark:bg-gray-700 overflow-hidden">
            @if($project->image_path)
                <img src="{{ $project->imageUrl }}" 
                     alt="{{ $project->name }}" 
                     class="w-full h-full object-cover">
            @else
                <div class="w-full h-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 flex items-center justify-center">
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
                    $config = $statusConfig[$project->status] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-800 dark:text-gray-200', 'icon' => 'fa-question-circle'];
                @endphp
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }} border border-white/20 shadow-sm">
                    <i class="fas {{ $config['icon'] }} mr-1"></i>
                    {{ ucfirst($project->status) }}
                </span>
            </div>
            
            <!-- Preview Track Player -->
            @if($project->hasPreviewTrack())
                <div class="absolute bottom-3 left-3">
                    @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
                </div>
            @endif
        </div>

        <!-- Project Content -->
        <div class="flex-1 p-4 md:p-6">
            <div class="flex flex-col h-full">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row lg:items-start justify-between mb-4">
                    <!-- Project Title and Creator -->
                    <div class="flex-1 mb-3 lg:mb-0 lg:pr-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-2">
                            <a href="{{ route('projects.show', $project) }}" class="hover:underline">
                                {{ $project->name }}
                            </a>
                        </h3>
                        
                        <!-- Project Owner -->
                        <div class="flex items-center text-gray-600 dark:text-gray-400 mb-2">
                            <img class="h-6 w-6 rounded-full object-cover mr-2 border border-gray-200 dark:border-gray-600"
                                 src="{{ $project->user->profile_photo_url }}" 
                                 alt="{{ $project->user->name }}">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                <x-user-link :user="$project->user" />
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">Project Owner</span>
                        </div>
                        
                        <!-- Genre and Workflow Type Badges -->
                        <div class="flex items-center gap-2 mb-3">
                            <!-- Workflow Type -->
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
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }}">
                                <i class="fas {{ $workflowStyle['icon'] }} mr-1"></i>{{ $workflowStyle['label'] }}
                            </span>
                            
                            <!-- Genre -->
                            @if($project->genre)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-800 text-purple-800 dark:text-purple-200">
                                    <i class="fas fa-music mr-1"></i>{{ $project->genre }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Budget and Deadline -->
                    <div class="flex flex-col items-start lg:items-end space-y-2 lg:min-w-[160px]">
                        @if($project->isContest() && $project->hasPrizes())
                            <!-- Contest Prizes Summary -->
                            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900 dark:to-yellow-900 border border-amber-200 dark:border-amber-700 rounded-lg px-3 py-2 text-center">
                                <div class="flex items-center text-amber-800 dark:text-amber-200 font-semibold text-sm mb-1">
                                    <i class="fas fa-trophy mr-1"></i>
                                    Contest Prizes
                                </div>
                                <div class="text-xs text-amber-700 dark:text-amber-300">
                                    @php
                                        $prizes = $project->getPrizeSummary();
                                        $totalCash = $project->getTotalPrizeBudget();
                                        $totalValue = $project->getTotalPrizeValue();
                                    @endphp
                                    {{ count($prizes) }} tiers • ${{ number_format($totalCash) }} cash
                                </div>
                                <div class="flex justify-center mt-1 space-x-1">
                                    @foreach(array_slice($prizes, 0, 3) as $prize)
                                        <span class="text-sm">{{ $prize['emoji'] }}</span>
                                    @endforeach
                                    @if(count($prizes) > 3)
                                        <span class="text-xs text-amber-600 dark:text-amber-400">+{{ count($prizes) - 3 }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- Standard Budget -->
                            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200 font-semibold px-3 py-2 rounded-lg text-sm">
                                @if(is_numeric($project->budget) && $project->budget > 0)
                                    ${{ number_format((float)$project->budget) }}
                                @elseif($project->budget === 0 || $project->budget === '0')
                                    Free
                                @else
                                    Price TBD
                                @endif
                            </div>
                        @endif
                        
                        @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                <i class="fas fa-calendar-alt mr-1 text-gray-400 dark:text-gray-500"></i>
                                <div>
                                    @if($project->isContest())
                                        <div><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" /></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" /></div>
                                    @else
                                        <div><x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" /></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"><x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" /></div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Project Description -->
                @if($project->description)
                    <div class="mb-4">
                        <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-2">
                            {{ $project->description }}
                        </p>
                    </div>
                @endif

                <!-- Project Metadata -->
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <!-- Project Type -->
                    <div class="flex items-center">
                        <i class="fas fa-tag mr-1 text-gray-400 dark:text-gray-500"></i>
                        <span>{{ Str::title($project->project_type) }}</span>
                    </div>
                    
                    <!-- Pitches Count -->
                    <div class="flex items-center">
                        <i class="fas fa-paper-plane mr-1 text-gray-400 dark:text-gray-500"></i>
                        <span>{{ $project->pitches->count() }} {{ Str::plural('pitch', $project->pitches->count()) }}</span>
                    </div>
                    
                    <!-- Files Count -->
                    <div class="flex items-center">
                        <i class="fas fa-file mr-1 text-gray-400 dark:text-gray-500"></i>
                        <span>{{ $project->files->count() }} {{ Str::plural('file', $project->files->count()) }}</span>
                    </div>
                    
                    <!-- Created Date -->
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-1 text-gray-400 dark:text-gray-500"></i>
                        <span>{{ $project->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <!-- View Project Button -->
                        <a href="{{ route('projects.show', $project) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-md transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Project
                        </a>
                        
                        <!-- Additional Actions for Owner -->
                        @if(auth()->check() && $project->user_id === auth()->id())
                            <a href="{{ route('projects.manage', $project) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-cog mr-2"></i>Manage
                            </a>
                        @endif
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                        @if($project->isStandard() || $project->isContest())
                            <div class="flex items-center">
                                <div class="w-2 h-2 rounded-full {{ $project->status === 'open' ? 'bg-green-500' : 'bg-gray-400 dark:bg-gray-500' }} mr-1"></div>
                                <span class="capitalize">{{ $project->status }}</span>
                            </div>
                        @endif
                        
                        @if($project->pitches->where('status', 'approved')->count() > 0)
                            <div class="flex items-center text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle mr-1"></i>
                                <span>{{ $project->pitches->where('status', 'approved')->count() }} Approved</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>