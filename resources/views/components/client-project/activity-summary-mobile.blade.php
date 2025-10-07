@props(['pitch', 'project', 'component'])

<div class="bg-white rounded-lg border border-gray-200 p-3">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-chart-line text-blue-500 mr-2 text-xs"></i>
            Project Overview
        </h4>
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        <!-- Status -->
        <div class="flex flex-col items-center space-y-1 bg-blue-50 rounded-lg p-2 border border-blue-100">
            <div class="flex items-center space-x-1">
                <div class="w-3 h-3 rounded-full {{ $component->getStatusColor($pitch->status) }}"></div>
                <span class="text-xs text-blue-700 font-medium">Status</span>
            </div>
            <span class="text-xs font-semibold text-blue-800">{{ $pitch->readable_status }}</span>
        </div>
        
        <!-- Files -->
        <div class="flex flex-col items-center space-y-1 bg-green-50 rounded-lg p-2 border border-green-100">
            <div class="flex items-center space-x-1">
                <i class="fas fa-file text-green-600 text-xs"></i>
                <span class="text-xs text-green-700 font-medium">Files</span>
            </div>
            <span class="text-xs font-semibold text-green-800">{{ $pitch->files->count() }}</span>
        </div>
        
        <!-- Messages -->
        <div class="flex flex-col items-center space-y-1 bg-purple-50 rounded-lg p-2 border border-purple-100">
            <div class="flex items-center space-x-1">
                <i class="fas fa-comments text-purple-600 text-xs"></i>
                <span class="text-xs text-purple-700 font-medium">Messages</span>
            </div>
            <span class="text-xs font-semibold text-purple-800">{{ $pitch->events->whereIn('event_type', ['client_comment', 'producer_comment'])->count() }}</span>
        </div>
        
        <!-- Last Activity -->
        <div class="flex flex-col items-center space-y-1 bg-indigo-50 rounded-lg p-2 border border-indigo-100">
            <div class="flex items-center space-x-1">
                <i class="fas fa-clock text-indigo-600 text-xs"></i>
                <span class="text-xs text-indigo-700 font-medium">Activity</span>
            </div>
            <span class="text-xs font-semibold text-indigo-800">{{ $pitch->events->first()?->created_at_for_user?->diffForHumans() ?? 'None' }}</span>
        </div>
    </div>
</div> 