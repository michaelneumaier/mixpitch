<div class="container mx-auto my-4 px-4">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">
    
    <!-- Combined Hero and Quick Guide Section -->
    <div class="bg-base-200 p-6 rounded-lg mb-8">
        <h1 class="text-3xl font-bold text-primary mb-4">Discover Projects</h1>
        <p class="text-lg text-base-content mb-6">
            Explore musical opportunities, showcase your skills, and collaborate on groundbreaking tracks.
        </p>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Browse diverse projects</span>
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Apply filters to find matches</span>
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Submit your pitch to collaborate</span>
            </div>
        </div>
    </div>

    

    <div class="flex flex-wrap mx-1">
        <!-- Filters Column -->
        <div class="w-full lg:w-1/4 px-2 pb-2">
            <div class="lg:hidden mb-4">
                <button @click="$store.filterMenu.toggle()"
                    class="w-full py-2 px-4 bg-base-300 text-sm border border-base-200 rounded-md hover:bg-base-200">
                    Toggle Filters
                </button>
            </div>
            <div x-data="{ open: true }" x-show="$store.filterMenu.open || window.innerWidth >= 1024" x-transition
                class="lg:block bg-base-100 p-4 rounded-lg shadow-lg">
                @livewire('filters-projects-component')
            </div>
        </div>

        <!-- Projects Column -->
         
        <div class="w-full lg:w-3/4 px-1">
            <!-- Search and Sort Bar -->
    <div class="flex flex-wrap items-center justify-between mb-6 p-1">
        <div class="flex-grow mb-4 md:mb-0 mr-2">
            <input type="text" placeholder="Search projects..." class="w-full p-2 border rounded-md" wire:model.live.debounce.300ms="search">
        </div>
        <div class="w-full md:w-auto flex items-center">
            <label for="sort" class="mr-2">Sort by:</label>
            <select id="sort" class="p-2 border rounded-md" wire:model.live="sortBy">
                <option value="latest">Latest</option>
                <option value="oldest">Oldest</option>
                <option value="budget_high_low">Budget: High to Low</option>
                <option value="budget_low_high">Budget: Low to High</option>
                <option value="deadline">Deadline</option>
            </select>
        </div>
    </div>
            <div class="flex flex-wrap">
                @foreach($projects as $project)
                    @livewire('project-card', ['project' => $project], key($project->id))
                @endforeach
            </div>
            <div class="mt-8" x-data="{}" x-intersect="$wire.loadMore()">
                @if($projects->hasMorePages())
                    <div class="flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-base-200 p-6 rounded-lg mt-12 text-center">
        <h2 class="text-2xl font-bold mb-2">Ready to Showcase Your Skills?</h2>
        <p class="text-lg mb-4">
            Create your own project and let talented collaborators come to you!
        </p>
        @auth
        <a href="{{ route('projects.create') }}"
            class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-lg text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
            Create a Project
        </a>
        @else
        <a href="{{ route('login') }}"
            class="transition-all hover:scale-[1.02] inline-block bg-accent hover:bg-accent-focus text-lg text-center font-bold py-2 px-6 border-b-4 border-accent hover:border-accent-focus shadow-glow shadow-accent hover:shadow-accent-focus rounded whitespace-nowrap">
            Log In to Create a Project
        </a>
        @endauth
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('filterMenu', {
            open: window.innerWidth >= 1024,
            toggle() {
                this.open = !this.open;
            }
        });
    });
</script>