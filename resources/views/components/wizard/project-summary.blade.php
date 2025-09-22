@props(['project' => [], 'workflowConfig' => []])

<div class="space-y-2">
    <!-- Project Summary Header -->
    <flux:card class="bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 mb-2">
        <flux:heading class="flex items-center gap-3 mb-4 text-green-900 dark:text-green-100">
            <flux:icon name="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400" />
            Project Summary
        </flux:heading>
            
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <!-- Workflow -->
            <div class="bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 p-4 text-center hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-center mb-2">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                        <flux:icon name="{{ $workflowConfig['icon'] ?? 'cog' }}" class="w-4 h-4 text-white" />
                    </div>
                </div>
                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Workflow</div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $workflowConfig['name'] ?? 'N/A' }}</div>
            </div>
            
            <!-- Project Type -->
            <div class="bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 p-4 text-center hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-center mb-2">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                        <flux:icon name="squares-2x2" class="w-4 h-4 text-white" />
                    </div>
                </div>
                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Type</div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ ucfirst($project['project_type'] ?? 'N/A') }}</div>
            </div>
            
            <!-- Budget -->
            <div class="bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 p-4 text-center hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-center mb-2">
                    <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                        <flux:icon name="currency-dollar" class="w-4 h-4 text-white" />
                    </div>
                </div>
                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Budget</div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">
                    @if(isset($project['budget']) && is_numeric($project['budget']) && $project['budget'] > 0)
                        ${{ number_format($project['budget']) }}
                    @else
                        Free
                    @endif
                </div>
            </div>
            
            <!-- Deadline -->
            <div class="bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 p-4 text-center hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-center mb-2">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                        <flux:icon name="calendar" class="w-4 h-4 text-white" />
                    </div>
                </div>
                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Deadline</div>
                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">
                    @if(isset($project['deadline']) && $project['deadline'])
                        <div><x-datetime :date="\Carbon\Carbon::parse($project['deadline'])" :convertToViewer="true" format="M d, Y" /></div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1"><x-datetime :date="\Carbon\Carbon::parse($project['deadline'])" :convertToViewer="true" format="g:i A T" /></div>
                    @else
                        Not set
                    @endif
                </div>
        </div>
    </flux:card>

    <!-- Project Details -->
    <flux:card class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800 mb-2">
        <flux:heading class="flex items-center gap-3 mb-4 text-blue-900 dark:text-blue-100">
            <flux:icon name="information-circle" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            Project Details
        </flux:heading>
            
        <!-- Project Name -->
        @if(isset($project['name']) && $project['name'])
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Project Name</label>
            <p class="text-slate-900 dark:text-slate-100 bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-medium">{{ $project['name'] }}</p>
        </div>
        @endif
        
        <!-- Artist Name -->
        @if(isset($project['artist_name']) && $project['artist_name'])
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Artist Name</label>
            <p class="text-slate-900 dark:text-slate-100 bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-medium">{{ $project['artist_name'] }}</p>
        </div>
        @endif

        <!-- Description -->
        @if(isset($project['description']) && $project['description'])
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
            <p class="text-slate-900 dark:text-slate-100 bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-medium leading-relaxed">{{ $project['description'] }}</p>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(isset($project['genre']) && $project['genre'])
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Genre</label>
                <p class="text-slate-900 dark:text-slate-100 bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-medium">{{ $project['genre'] }}</p>
            </div>
            @endif
            
            @if(isset($project['collaboration_types']) && is_array($project['collaboration_types']) && count($project['collaboration_types']) > 0)
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Collaboration Types</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($project['collaboration_types'] as $type)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm">
                        {{ ucfirst($type) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </flux:card>

    <!-- Workflow-Specific Details -->
    @if(isset($workflowConfig['fields']) && count($workflowConfig['fields']) > 0)
    <flux:card class="bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800 mb-2">
        <flux:heading class="flex items-center gap-3 mb-4 text-purple-900 dark:text-purple-100">
            <flux:icon name="{{ $workflowConfig['icon'] ?? 'cog' }}" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            {{ $workflowConfig['name'] }} Details
        </flux:heading>
        
        <div class="space-y-4">
            @foreach($workflowConfig['fields'] as $field)
                @if(isset($project[$field['key']]) && $project[$field['key']])
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ $field['label'] }}</label>
                    <p class="text-slate-900 dark:text-slate-100 bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3 font-medium">
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
    </flux:card>
    @endif

    <!-- Contest Prize Summary -->
    @if(isset($project['workflow_type']) && $project['workflow_type'] === 'contest')
    <flux:card class="bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800 mb-2">
        <flux:heading class="flex items-center gap-3 mb-4 text-amber-900 dark:text-amber-100">
            <flux:icon name="trophy" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            Contest Prizes
        </flux:heading>
        
        @if(isset($project['totalPrizeBudget']) && $project['totalPrizeBudget'] > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Total Prize Value -->
            <div class="bg-white/70 dark:bg-white/10 border border-amber-200 dark:border-amber-700 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Total Cash Prizes</span>
                    <span class="text-lg font-bold text-amber-900 dark:text-amber-100">${{ number_format($project['totalPrizeBudget']) }}</span>
                </div>
            </div>
            
            <!-- Prize Count -->
            <div class="bg-white/70 dark:bg-white/10 border border-amber-200 dark:border-amber-700 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Total Prizes</span>
                    <span class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ $project['prizeCount'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        
        <!-- Prize Breakdown -->
        @if(isset($project['prizeSummary']) && count($project['prizeSummary']) > 0)
        <div class="space-y-2">
            <h5 class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">Prize Breakdown:</h5>
            @foreach($project['prizeSummary'] as $prize)
            <div class="bg-white/60 dark:bg-white/5 border border-amber-200 dark:border-amber-700 rounded-lg p-3 flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-lg mr-2">{{ $prize['emoji'] ?? '🏆' }}</span>
                    <div>
                        <span class="font-medium text-amber-900 dark:text-amber-100">{{ $prize['placement'] ?? 'Prize' }}</span>
                        @if(isset($prize['title']) && $prize['title'])
                        <div class="text-xs text-amber-700 dark:text-amber-300">{{ $prize['title'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-amber-900 dark:text-amber-100">{{ $prize['display_value'] ?? 'N/A' }}</div>
                    @if(isset($prize['description']) && $prize['description'])
                    <div class="text-xs text-amber-600 dark:text-amber-400">{{ Str::limit($prize['description'], 30) }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
        @else
        <div class="bg-white/60 dark:bg-white/5 border border-amber-200 dark:border-amber-700 rounded-lg p-4 text-center">
            <flux:icon name="information-circle" class="w-5 h-5 text-amber-600 dark:text-amber-400 mb-2 mx-auto" />
            <p class="text-sm text-amber-700 dark:text-amber-300 font-medium">No prizes configured yet</p>
            <p class="text-xs text-amber-600 dark:text-amber-400">Prizes can be added during project creation or editing</p>
        </div>
        @endif
    </flux:card>
    @endif

    <!-- Additional Notes -->
    @if(isset($project['additional_notes']) && $project['additional_notes'])
    <flux:card class="bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 mb-2">
        <flux:heading class="flex items-center gap-3 mb-4 text-slate-900 dark:text-slate-100">
            <flux:icon name="document-text" class="w-6 h-6 text-slate-600 dark:text-slate-400" />
            Additional Notes
        </flux:heading>
        
        <div class="bg-white/70 dark:bg-white/10 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3">
            <p class="text-slate-900 dark:text-slate-100 whitespace-pre-wrap leading-relaxed">{{ $project['additional_notes'] }}</p>
        </div>
    </flux:card>
    @endif
</div> 