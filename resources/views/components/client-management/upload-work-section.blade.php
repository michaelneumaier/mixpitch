@props(['pitch', 'component', 'fileCount'])

<div class="overflow-hidden rounded-2xl border border-green-200/50 dark:border-green-700/50 bg-gradient-to-br from-white/90 to-green-50/90 dark:from-gray-900/90 dark:to-green-950/90 shadow-lg backdrop-blur-sm">
    <div class="border-b border-green-200/50 dark:border-green-700/50 bg-gradient-to-r from-green-100/80 to-emerald-100/80 dark:from-green-900/80 dark:to-emerald-900/80 p-4 backdrop-blur-sm">
        <div class="flex items-center">
            <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                <i class="fas fa-music text-white"></i>
            </div>
            <div>
                <h5 class="font-bold text-green-800 dark:text-green-200">Upload Your Work
                    <span class="ml-2 bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 text-xs px-2 py-1 rounded-full">
                        {{ $fileCount }} files
                    </span>
                </h5>
                <p class="text-xs text-green-600 dark:text-green-400">Upload your deliverables as you create them</p>
            </div>
        </div>
    </div>
    
    <div class="p-4">
        <!-- Upload Section -->
        <x-file-management.upload-section 
            :model="$pitch"
            title="Upload Deliverables"
            description="Upload audio, PDFs, or images for your client to review" />
    </div>
</div>