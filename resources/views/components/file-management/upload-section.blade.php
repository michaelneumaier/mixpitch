@props(['model', 'title' => 'Upload New Files', 'description' => 'Upload audio, PDFs, or images'])

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-base-200 bg-base-100/50">
        <h5 class="font-medium text-base">{{ $title }}</h5>
        <p class="text-xs text-gray-500 mt-1">{{ $description }}</p>
    </div>
    <div class="p-4">
        <livewire:file-uploader :model="$model" wire:key="'uploader-' . $model->id" />
    </div>
</div> 