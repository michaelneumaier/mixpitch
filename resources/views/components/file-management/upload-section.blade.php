@props(['model', 'title' => 'Upload New Files', 'description' => 'Upload audio, PDFs, or images'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-base-200 bg-base-100/50">
        <div class="flex items-center justify-between">
            <div>
                <h5 class="font-medium text-base">{{ $title }}</h5>
                <p class="text-xs text-gray-500 mt-1">{{ $description }}</p>
            </div>
            <flux:button 
                variant="outline" 
                size="sm"
                x-on:click="$flux.modal('google-drive-modal').show()"
                class="flex items-center gap-2 text-sm"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6.28 3l5.24 9.07L15.08 3z"/>
                    <path d="M19.54 10.25l-3.36 5.83-6.62-11.46z"/>
                    <path d="M9.1 13.75l3.36 5.83 6.62-11.46z"/>
                    <path d="M3.44 12.59l8.56 0 L9.46 20.5z"/>
                </svg>
                Upload from Google Drive
            </flux:button>
        </div>
    </div>
    <div class="p-4">
        <livewire:uppy-file-uploader :model="$model" wire:key="'uppy-uploader-' . $model->id" />
    </div>

    <!-- Google Drive Upload Modal -->
    <livewire:google-drive-upload-modal 
        :model="$model" 
        wire:key="'gdrive-modal-' . $model->id"
    />
</div> 