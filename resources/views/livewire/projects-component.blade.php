<div class="container mx-auto my-4">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">
    <div class="text-4xl text-center text-primary mb-6">Projects</div>

    <div class="flex flex-wrap mx-1">
        <!-- Filters Column -->
        <div class="w-full lg:w-1/6 px-2 pb-2">
            <div class="block -mt-6 lg:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="py-1 px-4 bg-base-300 text-sm border border-base-200 rounded-none hover:bg-base-200">
                    Filters
                </button>
                <div x-show="open" x-cloak
                    class="absolute  left-0 block lg:hidden z-50 bg-base-100 p-4 rounded-b-lg shadow-lg">
                    @livewire('filters-projects-component')
                </div>
            </div>
            <div class="hidden lg:block">
                @livewire('filters-projects-component')
            </div>
        </div>

        <!-- Projects Column -->
        <div class="w-full px-1 flex-1">
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