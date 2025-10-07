@props(['files', 'canDelete' => true, 'formatFileSize'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-base-200 bg-base-100/50 flex justify-between items-center">
        <h5 class="font-medium text-base">Files ({{ $files->count() }})</h5>
        @if($files->count() > 0)
        <span class="text-xs text-gray-500">Total: {{ $formatFileSize($files->sum('size')) }}</span>
        @endif
    </div>
    
    <div class="divide-y divide-base-200">
        @forelse($files as $file)
        <div class="flex items-center justify-between py-3 px-4 hover:bg-base-100/50 transition-all duration-300">
            <div class="flex items-center overflow-hidden flex-1 pr-2">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 flex items-center justify-center bg-base-200 text-gray-500 mr-3">
                    @if (Str::startsWith($file->mime_type, 'audio/'))
                        <i class="fas fa-music text-sm sm:text-base"></i>
                    @elseif (Str::startsWith($file->mime_type, 'video/'))
                        <i class="fas fa-video text-sm sm:text-base text-purple-500"></i>
                    @elseif ($file->mime_type == 'application/pdf')
                        <i class="fas fa-file-pdf text-sm sm:text-base text-red-500"></i>
                    @elseif (Str::startsWith($file->mime_type, 'image/'))
                        <i class="fas fa-file-image text-sm sm:text-base text-blue-500"></i>
                    @elseif ($file->mime_type == 'application/zip')
                        <i class="fas fa-file-archive text-sm sm:text-base text-orange-500"></i>
                    @else
                        <i class="fas fa-file-alt text-sm sm:text-base"></i>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium truncate text-sm sm:text-base">{{ $file->file_name }}</div>
                    <div class="flex items-center text-xs text-gray-500">
                        <span>{{ toUserTimezone($file->created_at)->format('M d, Y') }}</span>
                        <span class="mx-1.5">•</span>
                        <span>{{ $formatFileSize($file->size) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-1 sm:space-x-2">
                <button wire:click="downloadFile({{ $file->id }})"
                    class="btn btn-sm btn-ghost text-gray-600 hover:text-blue-600">
                    <i class="fas fa-download"></i>
                </button>
                @if($canDelete)
                <button wire:click="confirmDeleteFile({{ $file->id }})" 
                        class="btn btn-sm btn-ghost text-gray-600 hover:text-red-600">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 sm:p-10 text-center text-gray-500 italic">
            <i class="fas fa-folder-open text-4xl sm:text-5xl text-gray-300 mb-3"></i>
            <p class="text-base sm:text-lg">No files uploaded yet</p>
            <p class="text-xs sm:text-sm mt-2">Upload files to share with your client</p>
        </div>
        @endforelse
    </div>
</div> 