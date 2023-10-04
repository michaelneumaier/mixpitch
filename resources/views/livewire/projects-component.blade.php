<div class="container mx-auto my-4">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">
    <div class="text-4xl text-center text-white mb-6">Projects</div>

    <div class="flex flex-wrap -mx-4">
        <!-- Filters Column -->
        <div class="w-full md:w-1/4 lg:w-1/6 px-2">
            <div class="block md:hidden" x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full mb-3 text-white bg-transparent border border-gray-300 rounded-none hover:bg-gray-100">
                    Filters
                </button>
                <div x-show="open" class="block md:hidden">
                    @livewire('filters-projects-component')
                </div>
            </div>
            <div class="hidden md:block">
                @livewire('filters-projects-component')
            </div>
        </div>

        <!-- Projects Column -->
        <div class="w-full px-4 flex-1">
            <div class="flex flex-wrap">
                @foreach($projects as $project)
                    @livewire('project-card', ['project' => $project], key($project->id))
                @endforeach
            </div>
            <div class="flex justify-center mt-4">
                {{ $projects->links() }}
            </div>
        </div>
    </div>
</div>

