@props(['project' => [], 'workflowConfig' => []])

<div class="space-y-6">
    <!-- Summary Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-2 text-xs"></i>
            Project Summary
        </h4>
        
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center mb-4">
            <!-- Project Type -->
            <div class="flex flex-col items-center space-y-1 bg-blue-50 rounded-lg p-2 border border-blue-100">
                <div class="flex items-center space-x-1">
                    <i class="fas fa-project-diagram text-blue-600 text-xs"></i>
                    <span class="text-xs text-blue-700 font-medium">Type</span>
                </div>
                <span class="text-xs font-semibold text-blue-800">{{ $project['project_type'] ?? 'N/A' }}</span>
            </div>
            
            <!-- Workflow -->
            <div class="flex flex-col items-center space-y-1 bg-purple-50 rounded-lg p-2 border border-purple-100">
                <div class="flex items-center space-x-1">
                    <i class="fas fa-workflow text-purple-600 text-xs"></i>
                    <span class="text-xs text-purple-700 font-medium">Workflow</span>
                </div>
                <span class="text-xs font-semibold text-purple-800">{{ $workflowConfig['name'] ?? 'N/A' }}</span>
            </div>
            
            <!-- Budget -->
            <div class="flex flex-col items-center space-y-1 bg-green-50 rounded-lg p-2 border border-green-100">
                <div class="flex items-center space-x-1">
                    <i class="fas fa-dollar-sign text-green-600 text-xs"></i>
                    <span class="text-xs text-green-700 font-medium">Budget</span>
                </div>
                <span class="text-xs font-semibold text-green-800">
                    @if(isset($project['budget']) && is_numeric($project['budget']) && $project['budget'] > 0)
                        ${{ number_format($project['budget']) }}
                    @else
                        Free
                    @endif
                </span>
            </div>
            
            <!-- Deadline -->
            <div class="flex flex-col items-center space-y-1 bg-indigo-50 rounded-lg p-2 border border-indigo-100">
                <div class="flex items-center space-x-1">
                    <i class="fas fa-calendar text-indigo-600 text-xs"></i>
                    <span class="text-xs text-indigo-700 font-medium">Deadline</span>
                </div>
                <span class="text-xs font-semibold text-indigo-800">
                    @if(isset($project['deadline']) && $project['deadline'])
                        {{ \Carbon\Carbon::parse($project['deadline'])->format('M d, Y') }}
                    @else
                        Not set
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Project Details -->
    <div class="bg-white rounded-lg border border-base-300 shadow-sm p-4">
        <h4 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
            Project Details
        </h4>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                    <p class="text-sm text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $project['name'] ?? 'N/A' }}</p>
                </div>
                
                @if(isset($project['artist_name']) && $project['artist_name'])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Artist Name</label>
                    <p class="text-sm text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $project['artist_name'] }}</p>
                </div>
                @endif
            </div>
            
            @if(isset($project['description']) && $project['description'])
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <p class="text-sm text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $project['description'] }}</p>
            </div>
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(isset($project['genre']) && $project['genre'])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
                    <p class="text-sm text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $project['genre'] }}</p>
                </div>
                @endif
                
                @if(isset($project['collaboration_types']) && is_array($project['collaboration_types']) && count($project['collaboration_types']) > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Collaboration Types</label>
                    <div class="flex flex-wrap gap-1">
                        @foreach($project['collaboration_types'] as $type)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($type) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Workflow-Specific Details -->
    @if(isset($workflowConfig['fields']) && count($workflowConfig['fields']) > 0)
    <div class="bg-{{ $workflowConfig['color'] ?? 'purple' }}-50 border border-{{ $workflowConfig['color'] ?? 'purple' }}-200 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-{{ $workflowConfig['color'] ?? 'purple' }}-800 mb-4 flex items-center">
            <i class="{{ $workflowConfig['icon'] ?? 'fas fa-cog' }} text-{{ $workflowConfig['color'] ?? 'purple' }}-600 mr-2"></i>
            {{ $workflowConfig['name'] }} Details
        </h4>
        
        <div class="space-y-3">
            @foreach($workflowConfig['fields'] as $field)
                @if(isset($project[$field['key']]) && $project[$field['key']])
                <div>
                    <label class="block text-sm font-medium text-{{ $workflowConfig['color'] ?? 'purple' }}-700 mb-1">{{ $field['label'] }}</label>
                    <p class="text-sm text-{{ $workflowConfig['color'] ?? 'purple' }}-900 bg-white rounded-md px-3 py-2 border border-{{ $workflowConfig['color'] ?? 'purple' }}-200">
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