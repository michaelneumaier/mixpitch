<div class="container mx-auto my-6 px-4">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">

    <!-- Minimal Hero Section -->
    <div class="bg-gradient-to-r from-base-200 to-base-300 py-5 px-6 rounded-lg shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div class="mb-3 md:mb-0">
                <h1 class="text-2xl font-bold text-primary">Discover Musical Projects</h1>
                <p class="text-sm text-base-content opacity-75 mt-1">
                    Find your next creative opportunity and collaborate with artists
                </p>
            </div>
            <div class="flex gap-2">
                @auth
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Create Project
                </a>
                @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                    Log In to Create
                </a>
                @endauth
            </div>
        </div>
    </div>

    <div class="flex flex-wrap mb-8">
        <!-- Enhanced Search and Sort Bar - Full Width Above Filters and Projects -->
        <div class="w-full mb-6">
            <div class="bg-white rounded-xl shadow-md p-4">
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0">
                    <div class="relative flex-grow md:mr-4">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Search projects by name, description..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            wire:model.live.debounce.300ms="search">
                    </div>
                    <div class="flex items-center">
                        <label for="sort" class="text-sm font-medium text-gray-700 mr-2 whitespace-nowrap">Sort
                            by:</label>
                        <select id="sort"
                            class="border border-gray-300 rounded-lg py-2 pl-3 pr-10 text-base focus:outline-none focus:ring-primary focus:border-primary"
                            wire:model.live="sortBy">
                            <option value="latest">Latest</option>
                            <option value="oldest">Oldest</option>
                            <option value="budget_high_low">Budget: High to Low</option>
                            <option value="budget_low_high">Budget: Low to High</option>
                            <option value="deadline">Deadline</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap mx-1">
        <!-- Enhanced Filters Column -->
        <div class="w-full lg:w-1/4 pr-4 mb-6 lg:mb-0">
            <div class="lg:sticky lg:top-6">
                <div class="lg:hidden mb-4">
                    <button x-on:click="$store.filterMenu.toggle()"
                        class="w-full py-3 px-4 bg-base-300 text-base-content font-medium rounded-lg shadow-sm hover:bg-base-200 transition flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span x-text="$store.filterMenu.open ? 'Hide Filters' : 'Show Filters'"></span>
                    </button>
                </div>
                <div x-data="{ open: true, isLargeScreen: window.innerWidth >= 1024 }"
                    x-init="window.addEventListener('resize', () => { isLargeScreen = window.innerWidth >= 1024 })"
                    x-show="$store.filterMenu.open || isLargeScreen" x-transition
                    class="lg:block bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-base-200 p-4">
                        <h2 class="text-lg font-semibold">Filters</h2>
                    </div>
                    <div class="p-4">
                        @livewire('filters-projects-component')
                    </div>
                </div>

                <!-- Active Filters Summary -->
                <div x-data="{ isLargeScreen: window.innerWidth >= 1024 }"
                    x-init="window.addEventListener('resize', () => { isLargeScreen = window.innerWidth >= 1024 })"
                    x-show="$store.filterMenu.open || isLargeScreen" x-transition
                    class="mt-4 bg-white rounded-xl shadow-md p-4">
                    <h3 class="font-medium text-sm text-gray-500 uppercase mb-2">Active Filters</h3>
                    <div class="flex flex-wrap gap-2">
                        @if(count($genres) > 0)
                        @foreach($genres as $genre)
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                            {{ $genre }}
                            <button type="button" wire:click="removeGenre('{{ $genre }}')"
                                class="ml-1 inline-flex items-center justify-center">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        @endforeach
                        @endif

                        @if(count($projectTypes) > 0)
                        @foreach($projectTypes as $projectType)
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            {{ ucfirst($projectType) }}
                            <button type="button" wire:click="removeProjectType('{{ $projectType }}')"
                                class="ml-1 inline-flex items-center justify-center">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        @endforeach
                        @endif

                        @if(count($statuses) > 0)
                        @foreach($statuses as $status)
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($status) }}
                            <button type="button" wire:click="removeStatus('{{ $status }}')"
                                class="ml-1 inline-flex items-center justify-center">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        @endforeach
                        @endif

                        @if(count($genres) == 0 && count($statuses) == 0 && count($projectTypes) == 0)
                        <span class="text-sm text-gray-500 italic">No filters applied</span>
                        @endif

                        @if(count($genres) > 0 || count($statuses) > 0 || count($projectTypes) > 0)
                        <button type="button" wire:click="clearFilters"
                            class="text-sm text-red-600 hover:text-red-800 font-medium ml-auto">
                            Clear All
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Column -->
        <div class="w-full lg:w-3/4">
            <!-- Project Count and Search Results Info -->
            <div class="mb-6 flex flex-wrap items-center justify-between">
                <h2 class="text-xl font-semibold">
                    @if($search)
                    Search results for "{{ $search }}"
                    @else
                    Available Projects
                    @endif
                    <span class="ml-2 text-sm font-normal text-gray-500">{{ $projects->total() }} {{
                        Str::plural('project', $projects->total()) }}</span>
                </h2>
            </div>

            <div class="flex flex-wrap">
                @forelse($projects as $project)
                @livewire('project-card', ['project' => $project], key($project->id))
                @empty
                <div class="w-full p-12 bg-white rounded-xl shadow-md text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No projects found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter criteria</p>
                    <button type="button" wire:click="clearFilters"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-focus focus:outline-none">
                        Clear All Filters
                    </button>
                </div>
                @endforelse
            </div>

            <!-- Improved Infinite Scroll Loader -->
            <div class="mt-8" x-data="{}" x-intersect="$wire.loadMore()">
                @if($projects->hasMorePages())
                <div class="flex justify-center items-center space-x-2 text-center p-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent"></div>
                    <span class="text-sm text-gray-500">Loading more projects...</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Enhanced Call to Action -->
    <div class="bg-gradient-to-r from-accent to-accent-focus p-8 rounded-xl shadow-md mt-12 text-center">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold mb-3 text-black">Ready to Share Your Project?</h2>
            <p class="text-xl mb-6 text-black/80">
                Create your project and connect with talented musicians, producers, and creators from around the world.
            </p>
            @auth
            <a href="{{ route('projects.create') }}"
                class="transition-all transform hover:scale-105 inline-block bg-white text-accent font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Create a Project
                </span>
            </a>
            @else
            <a href="{{ route('login') }}"
                class="transition-all transform hover:scale-105 inline-block bg-white text-accent font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Log In to Create a Project
                </span>
            </a>
            @endauth
        </div>
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