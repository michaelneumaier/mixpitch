<div class="relative">
    <!-- Section Header with Glass Morphism -->
    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-green-800 bg-clip-text text-transparent mb-6 flex items-center">
        <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
            <i class="fas fa-chart-line text-white text-sm"></i>
        </div>
        Client Activity
    </h2>

    <!-- Enhanced Stats Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                    <i class="fas fa-project-diagram text-white text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Projects Posted</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalProjects }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                    <i class="fas fa-handshake text-white text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Projects Hired</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $hiredProjectsCount }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(($totalProjects > 0 || $hiredProjectsCount > 0) && ($recentProjects->isNotEmpty() || $completedProjects->isNotEmpty()))
    <div class="border-t border-white/30 my-6"></div>
    @endif

    @if ($recentProjects->isNotEmpty())
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                    <i class="fas fa-clock text-white text-xs"></i>
                </div>
                Recent Projects
            </h3>
            <div class="space-y-3">
                @foreach ($recentProjects as $project)
                    <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 hover:bg-white/80 hover:shadow-lg transition-all duration-300 hover:scale-[1.01] group">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('projects.show', $project->slug) }}" 
                                   class="text-lg font-semibold text-gray-900 hover:text-blue-600 transition-colors duration-200 group-hover:text-blue-600 block truncate">
                                    {{ $project->name }}
                                </a>
                                <div class="flex items-center mt-2 text-sm text-gray-600 space-x-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-gray-400 mr-1"></i>
                                        <span class="font-medium">{{ Str::title(str_replace('_', ' ', $project->status)) }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                        <span>{{ $project->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm border shadow-sm ml-3 flex-shrink-0
                                @if($project->status === 'completed') bg-green-100/80 border-green-200/50 text-green-800 
                                @elseif($project->status === 'open') bg-blue-100/80 border-blue-200/50 text-blue-800 
                                @elseif($project->status === 'in_progress') bg-yellow-100/80 border-yellow-200/50 text-yellow-800 
                                @else bg-gray-100/80 border-gray-200/50 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($completedProjects->isNotEmpty())
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                    <i class="fas fa-check-circle text-white text-xs"></i>
                </div>
                Project History (Completed)
            </h3>
            <div class="space-y-3">
                @foreach ($completedProjects as $project)
                    <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 hover:bg-white/80 hover:shadow-lg transition-all duration-300 hover:scale-[1.01] group">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('projects.show', $project->slug) }}" 
                                   class="text-lg font-semibold text-gray-900 hover:text-emerald-600 transition-colors duration-200 group-hover:text-emerald-600 block truncate">
                                    {{ $project->name }}
                                </a>
                                <div class="flex items-center mt-2 text-sm text-gray-600 space-x-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-emerald-500 mr-1"></i>
                                        <span class="font-medium">{{ Str::title(str_replace('_', ' ', $project->status)) }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-check text-gray-400 mr-1"></i>
                                        <span>{{ $project->completed_at ? $project->completed_at->diffForHumans() : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100/80 backdrop-blur-sm border border-green-200/50 text-green-800 shadow-sm ml-3 flex-shrink-0">
                                Completed
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Enhanced empty state --}}
    @if ($totalProjects == 0 && $hiredProjectsCount == 0 && $recentProjects->isEmpty() && $completedProjects->isEmpty())
        <div class="text-center py-8">
            <div class="bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-6">
                <i class="fas fa-chart-line text-gray-400 text-2xl mb-3"></i>
                <p class="text-gray-500 italic">No project activity to display yet.</p>
            </div>
        </div>
    @endif
</div>
