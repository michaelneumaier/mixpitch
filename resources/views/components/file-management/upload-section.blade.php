@props(['model', 'title' => 'Upload New Files', 'description' => 'Upload audio, PDFs, or images'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
    <div class="p-4">
        <div class="flex-row md:flex items-center justify-between">
            <div>
                <h5 class="font-medium text-base text-gray-900 dark:text-gray-100">{{ $title }}</h5>
                <p class="hidden md:block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
            </div>
            <flux:button 
                variant="outline" 
                size="sm"
                x-on:click="$flux.modal('google-drive-modal').show()"
                class="inline-flex items-center gap-2 text-sm"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Upload from Google Drive
            </flux:button>
        </div>
    </div>
    <div class="p-4 bg-gray-50 dark:bg-gray-700">
        <div 
            x-data="{
                meta: {
                    modelType: '{{ addslashes(get_class($model)) }}',
                    modelId: {{ $model->id }},
                    context: '{{ $model instanceof \App\Models\Project ? 'projects' : ($model instanceof \App\Models\Pitch ? 'pitches' : 'global') }}',
                    modelLabel: '{{ $model instanceof \App\Models\Project ? 'Project' : ($model instanceof \App\Models\Pitch ? 'Pitch' : 'Global') }}',
                }
            }"
            x-on:dragover.prevent
            x-on:dragenter.prevent="window.GlobalUploader?.setActiveTarget(meta)"
            x-on:drop.prevent="(e) => { const files = Array.from(e.dataTransfer.files || []); if (files.length) { window.GlobalUploader?.addFiles(files, meta); } }"
            class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 p-6 text-center hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
            @click="window.GlobalUploader?.openFileDialog(meta)"
        >
            <div class="flex flex-col items-center gap-2">
                <flux:icon.arrow-up-tray class="text-gray-400 dark:text-gray-500" />
                <div class="text-sm text-gray-700 dark:text-gray-300">Drag & drop files here or click to select</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Files will upload immediately and appear in the global uploader</div>
            </div>
        </div>
    </div>

    <!-- Google Drive Upload Modal -->
    <livewire:google-drive-upload-modal 
        :model="$model" 
        wire:key="'gdrive-modal-' . $model->id"
    />
</div> 