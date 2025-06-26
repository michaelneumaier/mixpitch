@props(['project' => [], 'workflowConfig' => []])

<div class="space-y-6">
    <!-- Enhanced Summary Header -->
    <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-green-50/50 to-emerald-50/50"></div>
        <div class="absolute top-4 right-4 w-16 h-16 bg-green-400/10 rounded-full blur-lg"></div>
        
        <div class="relative">
            <h4 class="text-xl font-bold bg-gradient-to-r from-green-700 to-emerald-700 bg-clip-text text-transparent mb-4 flex items-center">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                Project Summary
            </h4>
            
            <!-- Enhanced Quick Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <!-- Workflow (Now First) -->
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-4 text-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <div class="flex items-center justify-center mb-2">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-md">
                            <i class="{{ $workflowConfig['icon'] ?? 'fas fa-workflow' }} text-white text-sm"></i>
                        </div>
                    </div>
                    <span class="text-xs font-bold bg-gradient-to-r from-purple-700 to-purple-800 bg-clip-text text-transparent block mb-1">Workflow</span>
                    <span class="text-sm font-bold text-purple-900">{{ $workflowConfig['name'] ?? 'N/A' }}</span>
                </div>
                
                <!-- Project Type (Now Second) -->
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-4 text-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <div class="flex items-center justify-center mb-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-md">
                            <i class="fas fa-project-diagram text-white text-sm"></i>
                        </div>
                    </div>
                    <span class="text-xs font-bold bg-gradient-to-r from-blue-700 to-blue-800 bg-clip-text text-transparent block mb-1">Type</span>
                    <span class="text-sm font-bold text-blue-900">{{ ucfirst($project['project_type'] ?? 'N/A') }}</span>
                </div>
                
                <!-- Budget (Now Third) -->
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-4 text-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <div class="flex items-center justify-center mb-2">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-md">
                            <i class="fas fa-dollar-sign text-white text-sm"></i>
                        </div>
                    </div>
                    <span class="text-xs font-bold bg-gradient-to-r from-green-700 to-green-800 bg-clip-text text-transparent block mb-1">Budget</span>
                    <span class="text-sm font-bold text-green-900">
                        @if(isset($project['budget']) && is_numeric($project['budget']) && $project['budget'] > 0)
                            ${{ number_format($project['budget']) }}
                        @else
                            Free
                        @endif
                    </span>
                </div>
                
                <!-- Deadline (Now Fourth) -->
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-xl p-4 text-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <div class="flex items-center justify-center mb-2">
                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-md">
                            <i class="fas fa-calendar text-white text-sm"></i>
                        </div>
                    </div>
                    <span class="text-xs font-bold bg-gradient-to-r from-indigo-700 to-indigo-800 bg-clip-text text-transparent block mb-1">Deadline</span>
                    <div class="text-sm font-bold text-indigo-900">
                        @if(isset($project['deadline']) && $project['deadline'])
                            <div><x-datetime :date="\Carbon\Carbon::parse($project['deadline'])" :convertToViewer="true" format="M d, Y" /></div>
                            <div class="text-xs text-indigo-700 mt-1"><x-datetime :date="\Carbon\Carbon::parse($project['deadline'])" :convertToViewer="true" format="g:i A T" /></div>
                        @else
                            Not set
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Project Details -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 to-indigo-50/30 rounded-xl"></div>
        
        <div class="relative">
            <h5 class="text-lg font-bold bg-gradient-to-r from-blue-700 to-indigo-700 bg-clip-text text-transparent mb-4 flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                    <i class="fas fa-info-circle text-white text-sm"></i>
                </div>
                Project Details
            </h5>
            
            <!-- Project Name -->
            @if(isset($project['name']) && $project['name'])
            <div class="mb-4">
                <label class="block text-sm font-bold text-blue-700 mb-2">Project Name</label>
                <p class="text-gray-900 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-3 shadow-sm font-medium">{{ $project['name'] }}</p>
            </div>
            @endif
            
            <!-- Artist Name -->
            @if(isset($project['artist_name']) && $project['artist_name'])
            <div class="mb-4">
                <label class="block text-sm font-bold text-blue-700 mb-2">Artist Name</label>
                <p class="text-gray-900 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-3 shadow-sm font-medium">{{ $project['artist_name'] }}</p>
            </div>
            @endif

            <!-- Description -->
            @if(isset($project['description']) && $project['description'])
            <div class="mb-4">
                <label class="block text-sm font-bold text-blue-700 mb-2">Description</label>
                <p class="text-gray-900 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-3 shadow-sm font-medium leading-relaxed">{{ $project['description'] }}</p>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(isset($project['genre']) && $project['genre'])
                <div>
                    <label class="block text-sm font-bold text-blue-700 mb-2">Genre</label>
                    <p class="text-gray-900 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl px-4 py-3 shadow-sm font-medium">{{ $project['genre'] }}</p>
                </div>
                @endif
                
                @if(isset($project['collaboration_types']) && is_array($project['collaboration_types']) && count($project['collaboration_types']) > 0)
                <div>
                    <label class="block text-sm font-bold text-blue-700 mb-2">Collaboration Types</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($project['collaboration_types'] as $type)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg">
                            {{ ucfirst($type) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Enhanced Workflow-Specific Details -->
    @if(isset($workflowConfig['fields']) && count($workflowConfig['fields']) > 0)
    <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl p-6 shadow-xl overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-{{ $workflowConfig['color'] ?? 'purple' }}-50/50 to-white/50"></div>
        <div class="absolute top-4 right-4 w-12 h-12 bg-{{ $workflowConfig['color'] ?? 'purple' }}-400/10 rounded-full blur-lg"></div>
        
        <div class="relative">
            <h4 class="text-lg font-bold bg-gradient-to-r from-{{ $workflowConfig['color'] ?? 'purple' }}-700 to-{{ $workflowConfig['color'] ?? 'purple' }}-800 bg-clip-text text-transparent mb-4 flex items-center">
                <div class="bg-gradient-to-r from-{{ $workflowConfig['color'] ?? 'purple' }}-500 to-{{ $workflowConfig['color'] ?? 'purple' }}-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                    <i class="{{ $workflowConfig['icon'] ?? 'fas fa-cog' }} text-white"></i>
                </div>
                {{ $workflowConfig['name'] }} Details
            </h4>
            
            <div class="space-y-4">
                @foreach($workflowConfig['fields'] as $field)
                    @if(isset($project[$field['key']]) && $project[$field['key']])
                    <div>
                        <label class="block text-sm font-bold text-{{ $workflowConfig['color'] ?? 'purple' }}-700 mb-2">{{ $field['label'] }}</label>
                        <p class="text-{{ $workflowConfig['color'] ?? 'purple' }}-900 bg-white/80 backdrop-blur-sm border border-{{ $workflowConfig['color'] ?? 'purple' }}-200/50 rounded-xl px-4 py-3 shadow-sm font-medium">
                            @if($field['type'] === 'currency')
                                ${{ number_format($project[$field['key']], 2) }}
                            @elseif($field['type'] === 'date')
                                {{ \Carbon\Carbon::parse($project[$field['key']])->format('M d, Y g:i A') }}
                            @else
                                {{ $project[$field['key']] }}
                            @endif
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Enhanced Contest Prize Summary -->
    @if(isset($project['workflow_type']) && $project['workflow_type'] === 'contest')
    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-amber-800 mb-4 flex items-center">
            <i class="fas fa-trophy text-amber-600 mr-2"></i>
            Contest Prizes
        </h4>
        
        @if(isset($project['totalPrizeBudget']) && $project['totalPrizeBudget'] > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Total Prize Value -->
            <div class="bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-amber-700">Total Cash Prizes</span>
                    <span class="text-lg font-bold text-amber-900">${{ number_format($project['totalPrizeBudget']) }}</span>
                </div>
            </div>
            
            <!-- Prize Count -->
            <div class="bg-white/80 backdrop-blur-sm border border-amber-200/50 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-amber-700">Total Prizes</span>
                    <span class="text-lg font-bold text-amber-900">{{ $project['prizeCount'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        
        <!-- Prize Breakdown -->
        @if(isset($project['prizeSummary']) && count($project['prizeSummary']) > 0)
        <div class="space-y-2">
            <h5 class="text-sm font-semibold text-amber-800 mb-2">Prize Breakdown:</h5>
            @foreach($project['prizeSummary'] as $prize)
            <div class="bg-white/60 backdrop-blur-sm border border-amber-200/30 rounded-lg p-3 flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-lg mr-2">{{ $prize['emoji'] ?? '🏆' }}</span>
                    <div>
                        <span class="font-medium text-amber-900">{{ $prize['placement'] ?? 'Prize' }}</span>
                        @if(isset($prize['title']) && $prize['title'])
                        <div class="text-xs text-amber-700">{{ $prize['title'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-amber-900">{{ $prize['display_value'] ?? 'N/A' }}</div>
                    @if(isset($prize['description']) && $prize['description'])
                    <div class="text-xs text-amber-600">{{ Str::limit($prize['description'], 30) }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
        @else
        <div class="bg-white/60 backdrop-blur-sm border border-amber-200/30 rounded-lg p-4 text-center">
            <i class="fas fa-info-circle text-amber-600 mb-2"></i>
            <p class="text-sm text-amber-700 font-medium">No prizes configured yet</p>
            <p class="text-xs text-amber-600">Prizes can be added during project creation or editing</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Additional Notes -->
    @if(isset($project['additional_notes']) && $project['additional_notes'])
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-sticky-note text-gray-500 mr-2"></i>
            Additional Notes
        </h4>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $project['additional_notes'] }}</p>
    </div>
    @endif
</div> 