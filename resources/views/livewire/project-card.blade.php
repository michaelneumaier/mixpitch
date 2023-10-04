<div class="w-full lg:w-1/4 md:w-1/2 mb-4 px-1">
    <a href="{{ route('projects.show', $project) }}" class="no-underline">
        <div class="bg-white border rounded shadow-sm">
            <div class="relative aspect-w-1 aspect-h-1 rounded-t">
                <div class="absolute inset-0 bg-center bg-cover bg-no-repeat rounded-t"
                     style="background-image: url('{{ $project->image_path ? asset('storage' . $project->image_path) : 'https://via.placeholder.com/150' }}');">
                    <img
                        src="{{ $project->image_path ? asset('storage' . $project->image_path) : 'https://via.placeholder.com/150' }}"
                        alt="Project Image" class="opacity-0 w-full h-full object-cover">
                </div>

                <div class="absolute top-0 right-0">
                    <livewire:status-button :status="$project->status" type="top-right"/>
                </div>
            </div>
            <div class="p-3">
                <h5 class="font-bold mb-1">{{ $project->name }}</h5>
                <h6 class="text-sm text-gray-600">{{ $project->genre }}</h6>
                <p class="text-sm mt-2">Uploaded by: {{ $project->user->name }}</p>
            </div>
        </div>
    </a>
</div>
