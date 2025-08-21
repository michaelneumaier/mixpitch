@props(['project'])

<flux:card class="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2 mb-4">
        <flux:icon.chart-bar class="w-4 h-4 text-blue-500 dark:text-blue-400" />
        <flux:subheading class="text-gray-700 dark:text-gray-300">Project Overview</flux:subheading>
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        <!-- Status -->
        <div class="flex flex-col items-center space-y-1 bg-blue-50 dark:bg-blue-900 rounded-lg p-2 border border-blue-100 dark:border-blue-800">
            <div class="flex items-center space-x-1">
                <div class="w-3 h-3 rounded-full {{ $project->status === 'completed' ? 'bg-green-500' : ($project->is_published ? 'bg-blue-500' : 'bg-gray-400') }}"></div>
                <span class="text-xs text-blue-700 dark:text-blue-300 font-medium">Status</span>
            </div>
            <span class="text-xs font-semibold text-blue-800 dark:text-blue-200 capitalize">{{ $project->status }}</span>
        </div>
        
        <!-- Pitches -->
        <div class="flex flex-col items-center space-y-1 bg-green-50 dark:bg-green-900 rounded-lg p-2 border border-green-100 dark:border-green-800">
            <div class="flex items-center space-x-1">
                <flux:icon.paper-airplane class="w-3 h-3 text-green-600 dark:text-green-400" />
                <span class="text-xs text-green-700 dark:text-green-300 font-medium">Pitches</span>
            </div>
            <span class="text-xs font-semibold text-green-800 dark:text-green-200">{{ $project->pitches->count() }}</span>
        </div>
        
        <!-- Files -->
        <div class="flex flex-col items-center space-y-1 bg-purple-50 dark:bg-purple-900 rounded-lg p-2 border border-purple-100 dark:border-purple-800">
            <div class="flex items-center space-x-1">
                <flux:icon.document class="w-3 h-3 text-purple-600 dark:text-purple-400" />
                <span class="text-xs text-purple-700 dark:text-purple-300 font-medium">Files</span>
            </div>
            <span class="text-xs font-semibold text-purple-800 dark:text-purple-200">{{ $project->files->count() }}</span>
        </div>
        
        <!-- Days Active -->
        <div class="flex flex-col items-center space-y-1 bg-indigo-50 dark:bg-indigo-900 rounded-lg p-2 border border-indigo-100 dark:border-indigo-800">
            <div class="flex items-center space-x-1">
                <flux:icon.clock class="w-3 h-3 text-indigo-600 dark:text-indigo-400" />
                <span class="text-xs text-indigo-700 dark:text-indigo-300 font-medium">Active</span>
            </div>
            <span class="text-xs font-semibold text-indigo-800 dark:text-indigo-200">{{ $project->created_at->diffInDays(now()) }}d</span>
        </div>
    </div>
</flux:card> 