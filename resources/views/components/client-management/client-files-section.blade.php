@props(['project', 'component', 'files'])

<div class="overflow-hidden rounded-2xl border border-blue-200/50 dark:border-blue-700/50 bg-gradient-to-br from-white/90 to-blue-50/90 dark:from-gray-900/90 dark:to-blue-950/90 shadow-lg backdrop-blur-sm">
    <div class="border-b border-blue-200/50 dark:border-blue-700/50 bg-gradient-to-r from-blue-100/80 to-indigo-100/80 dark:from-blue-900/80 dark:to-indigo-900/80 p-4 backdrop-blur-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                    <i class="fas fa-folder-open text-white"></i>
                </div>
                <div>
                    <h5 class="font-bold text-blue-800 dark:text-blue-200">Client Reference Files
                        <span class="ml-2 bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-xs px-2 py-1 rounded-full">
                            {{ $files->count() }} files
                        </span>
                    </h5>
                    <p class="text-xs text-blue-600 dark:text-blue-400">Download these first to understand project requirements</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="p-4">
        @if($files->count() > 0)
            <div class="divide-y divide-blue-100/50 dark:divide-blue-800/50">
                @foreach($files as $file)
                    <div class="group py-4 transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50 dark:hover:from-blue-950/50 dark:hover:to-indigo-950/50">
                        <div class="flex items-center mb-3">
                            <div class="bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 text-blue-600 dark:text-blue-400 mr-3 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl shadow-md transition-transform duration-200 group-hover:scale-105">
                                <i class="fas fa-file"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-blue-900 dark:text-blue-100">{{ $file->file_name }}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400">
                                    {{ $component->formatFileSize($file->size) }} • 
                                    Uploaded {{ $file->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <button wire:click="downloadClientFile({{ $file->id }})" 
                                    class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 hover:from-blue-200 hover:to-indigo-200 dark:hover:from-blue-800 dark:hover:to-indigo-800 text-blue-700 dark:text-blue-300 rounded-lg font-medium transition-all duration-200 hover:scale-105 text-sm">
                                <i class="fas fa-download mr-2"></i>Download
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-full mx-auto mb-4">
                    <i class="fas fa-inbox text-blue-500 dark:text-blue-400 text-xl"></i>
                </div>
                <p class="text-blue-600 dark:text-blue-400 text-sm">No client files yet. Your client can upload reference files through their portal.</p>
            </div>
        @endif
    </div>
</div>