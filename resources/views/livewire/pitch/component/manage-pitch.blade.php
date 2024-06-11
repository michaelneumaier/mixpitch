<!-- resources/views/livewire/pitch/component/manage-pitch.blade.php -->
<div>
    <h3 class="text-xl font-semibold mb-4">Manage Your Pitch</h3>

    @if (session()->has('message'))
    <div class="alert alert-success">
        {{ session('message') }}
    </div>
    @endif

    <form wire:submit.prevent="uploadFiles" class="mb-4">
        <div class="mb-4">
            <input type="file" wire:model="files" multiple class="mt-1 block w-full">
            @error('files.*') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
            Upload Pitch Files
        </button>
    </form>

    @if($pitch->files->isNotEmpty())
    <div class="mt-4">
        <h4 class="font-semibold">Uploaded Files</h4>
        <ul>
            @foreach ($pitch->files as $file)
            <li class="flex justify-between items-center mt-2">
                <span>{{ $file->file_name }}</span>
                <div class="flex items-center space-x-2">
                    <a href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                        Download
                    </a>
                    <button wire:click="deleteFile({{ $file->id }})"
                        class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-2 rounded text-sm">
                        Delete
                    </button>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>