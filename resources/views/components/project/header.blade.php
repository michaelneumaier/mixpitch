@props([
    'project', 
    'hasPreviewTrack' => false, 
    'showEditButton' => true,
    'context' => 'view', // 'view', 'manage', 'client'
    'showActions' => true,
    'userPitch' => null,
    'canPitch' => false
])

<!-- Modern Project Header with Glass Morphism -->
<div class="relative mb-6 sm:mb-8 lg:mb-12">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-purple-50/20 to-pink-50/30 rounded-2xl"></div>
    <div class="absolute top-4 left-4 w-32 h-32 bg-blue-400/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-4 right-4 w-24 h-24 bg-purple-400/10 rounded-full blur-xl"></div>
    
    <!-- Main Header Card -->
    <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        <div class="flex flex-col lg:flex-row min-h-[320px] lg:min-h-[400px]">
            <!-- Project Image Section -->
            <div x-data="{ lightbox: { isOpen: false } }" class="relative lg:w-80 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 flex flex-col">
                @if($project->image_path)
                    <img @click="lightbox.isOpen = true" 
                         src="{{ $project->imageUrl }}" 
                         alt="{{ $project->name }}"
                         class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-all duration-300 ease-out flex-1">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-blue-100 via-purple-100 to-indigo-100 flex items-center justify-center flex-1">
                        <div class="text-center">
                            <i class="fas fa-music text-6xl lg:text-7xl text-blue-400/60 mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">No Image</p>
                        </div>
                    </div>
                @endif
                
                <!-- Workflow Type Badge -->
                <div class="absolute bottom-4 left-4">
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
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }} border border-white/20 shadow-lg backdrop-blur-sm">
                        <i class="fas {{ $workflowStyle['icon'] }} mr-2"></i>
                        {{ $workflowStyle['label'] }}
                    </span>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    @php
                        $statusConfig = [
                            'unpublished' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-eye-slash'],
                            'open' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
                            'review' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-eye'],
                            'completed' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-check'],
                            'closed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times-circle'],
                        ];
                        $config = $statusConfig[$project->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question-circle'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium {{ $config['bg'] }} {{ $config['text'] }} border border-white/20 shadow-lg backdrop-blur-sm">
                        <i class="fas {{ $config['icon'] }} mr-2"></i>
                        {{ ucfirst($project->status) }}
                    </span>
                </div>
                
                <!-- Preview Track Player -->
                @if($hasPreviewTrack)
                    <div class="absolute bottom-4 right-4">
                        @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
                    </div>
                @endif

                <!-- Image Upload Button (Manage Context Only) -->
                @if($context === 'manage' && auth()->check() && $project->isOwnedByUser(auth()->user()))
                    <!-- Desktop: Bottom-right corner -->
                    <div class="hidden sm:block absolute bottom-4 right-4 z-10">
                        <button @click="$wire.showImageUpload()" 
                                class="group relative bg-white/90 backdrop-blur-md hover:bg-white border-2 border-white/20 hover:border-blue-300 rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110">
                            <!-- Button Content -->
                            <div class="flex items-center justify-center w-8 h-8">
                                @if($project->image_path)
                                    <i class="fas fa-edit text-blue-600 group-hover:text-blue-700 transition-colors"></i>
                                @else
                                    <i class="fas fa-camera text-blue-600 group-hover:text-blue-700 transition-colors"></i>
                                @endif
                            </div>
                            
                            <!-- Tooltip -->
                            <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block">
                                <div class="bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap shadow-lg">
                                    {{ $project->image_path ? 'Update Image' : 'Add Image' }}
                                    <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                            
                            <!-- Hover Ring -->
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 opacity-0 group-hover:opacity-20 transition-opacity duration-200"></div>
                        </button>
                    </div>
                    
                    <!-- Mobile: Full-width button at bottom -->
                    <div class="sm:hidden absolute bottom-0 left-0 right-0 p-4">
                        <button @click="$wire.showImageUpload()" 
                                class="w-full bg-white/95 backdrop-blur-md hover:bg-white border border-white/40 hover:border-blue-300 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-200">
                            <div class="flex items-center justify-center">
                                @if($project->image_path)
                                    <i class="fas fa-edit text-blue-600 mr-3"></i>
                                    <span class="text-blue-600 font-medium">Update Image</span>
                                @else
                                    <i class="fas fa-camera text-blue-600 mr-3"></i>
                                    <span class="text-blue-600 font-medium">Add Image</span>
                                @endif
                            </div>
                        </button>
                    </div>
                @endif

                <!-- Lightbox -->
                @if($project->image_path)
                    <div x-cloak x-show="lightbox.isOpen" 
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm transition-all duration-300"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="relative max-w-4xl mx-auto p-4">
                            <img class="max-h-[90vh] max-w-[90vw] object-contain shadow-2xl rounded-xl" 
                                 src="{{ $project->imageUrl }}" 
                                 alt="{{ $project->name }}">
                            <button @click="lightbox.isOpen = false" 
                                    class="absolute top-6 right-6 text-white bg-gray-900/50 hover:bg-gray-900/75 rounded-full p-3 transition-all duration-200 hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Project Details Section -->
            <div class="flex-1 p-6 lg:p-8 flex flex-col">
                <!-- Title and Artist -->
                <div class="mb-6">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 leading-tight">
                        @if($context === 'view')
                            {{ $project->name }}
                        @else
                            <a href="{{ route('projects.show', $project) }}" 
                               class="hover:text-blue-600 transition-colors duration-200">
                                {{ $project->name }}
                            </a>
                        @endif
                    </h1>
                    @if($project->artist_name)
                        <div class="flex items-center text-gray-600 mb-4">
                            <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full mr-3">
                                <i class="fas fa-microphone text-purple-600 text-sm"></i>
                            </div>
                            <span class="text-lg font-medium">{{ $project->artist_name }}</span>
                        </div>
                    @endif
                </div>

                <!-- Project Owner -->
                <div class="flex items-center mb-6">
                    <img class="h-12 w-12 rounded-full object-cover mr-4 border-2 border-white shadow-md" 
                         src="{{ $project->user->profile_photo_url }}" 
                         alt="{{ $project->user->name }}">
                    <div>
                        <p class="text-lg font-semibold text-gray-900">
                            @if(isset($components) && isset($components['user-link']))
                                <x-user-link :user="$project->user" />
                            @else
                                {{ $project->user->name }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-500">Project Owner</p>
                    </div>
                </div>

                <!-- Project Metadata Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Project Type -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200/50">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-tag text-blue-600 mr-2"></i>
                            <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Type</span>
                        </div>
                        <div class="text-sm font-bold text-blue-900">{{ Str::title($project->project_type) }}</div>
                    </div>

                    <!-- Budget/Prizes -->
                    @if($project->isContest())
                        <!-- Contest Prizes -->
                        <div class="bg-gradient-to-br from-amber-50 to-yellow-100 rounded-xl p-4 border border-amber-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-trophy text-amber-600 mr-2"></i>
                                <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Prizes</span>
                            </div>
                            <div class="text-sm font-bold text-amber-900">
                                @if($project->hasPrizes())
                                    @php $totalCash = $project->getTotalPrizeBudget(); @endphp
                                    @if($totalCash > 0)
                                        ${{ number_format($totalCash) }}+
                                    @else
                                        {{ $project->contestPrizes()->count() }} Prize{{ $project->contestPrizes()->count() > 1 ? 's' : '' }}
                                    @endif
                                @elseif($project->prize_amount && $project->prize_amount > 0)
                                    ${{ number_format($project->prize_amount) }}
                                @else
                                    No Prizes
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Standard Budget -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Budget</span>
                            </div>
                            <div class="text-sm font-bold text-green-900">
                                @if (is_numeric($project->budget) && $project->budget > 0)
                                    ${{ number_format((float) $project->budget, 0) }}
                                @elseif(is_numeric($project->budget) && $project->budget == 0)
                                    Free
                                @else
                                    TBD
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Deadline -->
                    @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200/50 col-span-2 lg:col-span-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calendar text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">{{ $project->isContest() ? 'Submission Deadline' : 'Deadline' }}</span>
                            </div>
                            @if($project->isContest())
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                            @else
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                @if($showActions)
                    <div class="mt-auto">
                        @if($context === 'manage' || ($showEditButton && auth()->check() && $project->isOwnedByUser(auth()->user())))
                            <div class="flex flex-col sm:flex-row gap-3">
                                @if($context !== 'manage')
                                    <a href="{{ route('projects.manage', $project) }}" 
                                       class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-cog mr-2"></i>Manage Project
                                    </a>
                                @endif
                                @if($context === 'manage')
                                    <a href="{{ route('projects.show', $project) }}" 
                                       class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-eye mr-2"></i>View Project
                                    </a>
                                @endif
                                @if($showEditButton)
                                    <a href="{{ route('projects.edit', $project) }}" 
                                       class="flex-1 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-edit mr-2"></i>Edit Details
                                    </a>
                                @endif
                            </div>
                            
                            <!-- Contest Judging Navigation -->
                            @if($project->isContest() && auth()->check() && auth()->user()->can('judgeContest', $project))
                                <div class="mt-3 flex flex-col sm:flex-row gap-3">
                                    @if($project->isJudgingFinalized())
                                        <a href="{{ route('projects.contest.results', $project) }}" 
                                           class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                            <i class="fas fa-trophy mr-2"></i>View Results
                                        </a>
                                        <a href="{{ route('projects.contest.judging', $project) }}" 
                                           class="flex-1 bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-700 hover:to-orange-700 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                            <i class="fas fa-gavel mr-2"></i>Judging Dashboard
                                        </a>
                                    @else
                                        <a href="{{ route('projects.contest.judging', $project) }}" 
                                           class="flex-1 bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-700 hover:to-orange-700 text-white py-3 px-6 rounded-xl text-center font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                            <i class="fas fa-gavel mr-2"></i>Judge Contest
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="space-y-3">
                                @if($userPitch)
                                    <!-- Full Width Pitch Status Badge -->
                                    <div class="w-full">
                                        <span class="w-full flex items-center justify-center px-6 py-4 rounded-xl text-lg font-bold {{ $userPitch->getStatusColorClass() }} border border-white/30 shadow-lg backdrop-blur-sm">
                                            {{ $userPitch->readable_status }}
                                        </span>
                                    </div>
                                @elseif ($canPitch)
                                    <button onclick="openPitchTermsModal()" 
                                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-6 rounded-xl font-semibold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                        <i class="fas fa-paper-plane mr-2"></i>Start Your Pitch
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div> 