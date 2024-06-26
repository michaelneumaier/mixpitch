<!-- resources/views/livewire/pitch/component/manage-pitch.blade.php -->
<div class="bg-base-200/50 rounded-lg p-3 my-2">
    <h3 class="text-xl font-semibold mb-4">Manage Your Pitch</h3>

    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
        @if ($message = session('message'))
        <div x-show="show" class="alert alert-success" x-transition>
            {{ $message }}
        </div>
        @endif
    </div>

    <form wire:submit.prevent="uploadFiles" x-data="{ files: [], showUpload: false }" @file-upload-success.window="
        console.log('File upload success');
        files = [];
        showUpload = false;
    " class="mb-4">
        <div class="mb-4 relative">
            <input type="file" wire:model.defer="files" multiple
                class="opacity-0 absolute inset-0 w-full h-full cursor-pointer"
                @change="files = Array.from($event.target.files); showUpload = files.length > 0">
            <div
                class="border border-dashed border-gray-400 p-4 rounded-lg flex flex-col items-center justify-center cursor-pointer">
                <div class="fa fa-upload fa-3x text-gray-400"></div>
                <p class="text-gray-400 mt-2"
                    x-html="files.length > 0 ? files.map(file => `<span class='truncate max-w-xs inline-block'>${file.name}</span>`).join('<br>') : 'Drag and drop files here or click to upload'">
                </p>
            </div>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded"
            :disabled="files.length === 0" x-show="showUpload" x-transition>
            Upload Pitch Files
        </button>
        <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded ml-2"
            :disabled="files.length === 0" x-show="showUpload" x-transition @click="files = []; showUpload = false;">
            Clear Selection
        </button>
    </form>

    @if($pitch->files->isEmpty())
    <div class="mt-4">
        <p class="text-gray-500">No files uploaded yet.</p>
    </div>
    @else
    <div class="m-4">
        <h4 class="font-semibold">Uploaded Files</h4>
        <div class="space-y-2">
            @foreach ($pitch->files as $file)
            <div class="flex justify-between items-center p-2 bg-gray-100 rounded-lg shadow">
                <span class="flex-1 truncate">{{ $file->file_name }}</span>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('pitch-files.show', $file) }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                        View
                    </a>
                    <a href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                        Download
                    </a>
                    <button wire:click="deleteFile({{ $file->id }})"
                        wire:confirm="Are you sure you want to delete this file?"
                        class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-2 rounded text-sm">
                        Delete
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>