@props(['project'])

<div class="bg-gray-50 rounded-lg p-3">
    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
        <i class="fas fa-chart-line text-blue-500 mr-2 text-xs"></i>
        Quick Stats
    </h4>
    
    <div class="space-y-2">
        <!-- Status -->
        <div class="flex items-center justify-between bg-blue-50 rounded-md px-2 py-1 border border-blue-100">
            <span class="text-xs text-blue-700 font-medium">Status</span>
            <div class="flex items-center space-x-1">
                <div class="w-3 h-3 rounded-full {{ $project->status === 'completed' ? 'bg-green-500' : ($project->is_published ? 'bg-blue-500' : 'bg-gray-400') }}"></div>
                <span class="text-xs font-semibold text-blue-800 capitalize">{{ $project->status }}</span>
            </div>
        </div>
        
        <!-- Pitches -->
        <div class="flex items-center justify-between bg-green-50 rounded-md px-2 py-1 border border-green-100">
            <span class="text-xs text-green-700 font-medium">Pitches</span>
            <span class="text-xs font-semibold text-green-800">{{ $project->pitches->count() }}</span>
        </div>
        
        <!-- Files -->
        <div class="flex items-center justify-between bg-purple-50 rounded-md px-2 py-1 border border-purple-100">
            <span class="text-xs text-purple-700 font-medium">Files</span>
            <span class="text-xs font-semibold text-purple-800">{{ $project->files->count() }}</span>
        </div>
        
        <!-- Days Active -->
        <div class="flex items-center justify-between bg-indigo-50 rounded-md px-2 py-1 border border-indigo-100">
            <span class="text-xs text-indigo-700 font-medium">Days Active</span>
            <span class="text-xs font-semibold text-indigo-800">{{ $project->created_at->diffInDays(now()) }}</span>
        </div>
    </div>
</div> 