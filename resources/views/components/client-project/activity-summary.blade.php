@props(['pitch', 'project', 'component'])

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
                <div class="w-3 h-3 rounded-full {{ $component->getStatusColor($pitch->status) }}"></div>
                <span class="text-xs font-semibold text-blue-800">{{ $pitch->readable_status }}</span>
            </div>
        </div>
        
        <!-- Files -->
        <div class="flex items-center justify-between bg-green-50 rounded-md px-2 py-1 border border-green-100">
            <span class="text-xs text-green-700 font-medium">Files</span>
            <span class="text-xs font-semibold text-green-800">{{ $pitch->files->count() }}</span>
        </div>
        
        <!-- Messages -->
        <div class="flex items-center justify-between bg-purple-50 rounded-md px-2 py-1 border border-purple-100">
            <span class="text-xs text-purple-700 font-medium">Messages</span>
            <span class="text-xs font-semibold text-purple-800">{{ $pitch->events->whereIn('event_type', ['client_comment', 'producer_comment'])->count() }}</span>
        </div>
        
        <!-- Last Activity -->
        <div class="flex items-center justify-between bg-indigo-50 rounded-md px-2 py-1 border border-indigo-100">
            <span class="text-xs text-indigo-700 font-medium">Last Activity</span>
            <span class="text-xs font-semibold text-indigo-800">{{ $pitch->events->first()?->created_at?->diffForHumans() ?? 'None' }}</span>
        </div>
    </div>
</div> 