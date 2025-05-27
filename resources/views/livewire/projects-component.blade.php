<div class="container mx-auto px-2 sm:px-4 py-6">
    <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">
    
    <!-- Modern Hero Section -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-project-diagram text-blue-600 mr-3"></i>
                        Discover Musical Projects
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Find your next creative opportunity and collaborate with talented artists
                    </p>
                </div>
                <div class="flex gap-3">
                    @auth
                    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-hover-lift">
                        <i class="fas fa-plus mr-2"></i>
                        Create Project
                    </a>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-hover-lift">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Log In to Create
                    </a>
                    @endauth
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="p-4 bg-gray-50">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                <div class="flex flex-col items-center space-y-1">
                    <div class="text-2xl font-bold text-blue-600">{{ $projects->total() }}</div>
                    <div class="text-xs text-gray-600">Total Projects</div>
                </div>
                <div class="flex flex-col items-center space-y-1">
                    <div class="text-2xl font-bold text-green-600">{{ $projects->where('status', 'open')->count() }}</div>
                    <div class="text-xs text-gray-600">Open Projects</div>
                </div>
                <div class="flex flex-col items-center space-y-1">
                    <div class="text-2xl font-bold text-purple-600">{{ $projects->whereIn('workflow_type', ['standard', 'contest'])->count() }}</div>
                    <div class="text-xs text-gray-600">Public Projects</div>
                </div>
                <div class="flex flex-col items-center space-y-1">
                    <div class="text-2xl font-bold text-indigo-600">{{ $projects->where('created_at', '>=', now()->subDays(7))->count() }}</div>
                    <div class="text-xs text-gray-600">This Week</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Filters Sidebar -->
        <div class="lg:col-span-1">
            <!-- Mobile Filter Toggle -->
            <div class="lg:hidden mb-4">
                <button @click="$store.filterMenu.toggle()" 
                        class="w-full bg-white border border-gray-200 rounded-lg p-3 flex items-center justify-between shadow-sm focus-ring">
                    <span class="font-medium text-gray-700">
                        <i class="fas fa-filter mr-2 text-blue-500"></i>Filters
                    </span>
                    <i class="fas fa-chevron-down transition-transform" 
                       :class="$store.filterMenu.open ? 'rotate-180' : ''"></i>
                </button>
            </div>

            <!-- Filter Panel -->
            <div x-data="{ isLargeScreen: window.innerWidth >= 1024 }"
                 x-init="window.addEventListener('resize', () => { isLargeScreen = window.innerWidth >= 1024 })"
                 x-show="$store.filterMenu.open || isLargeScreen" 
                 x-transition
                 class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden filter-panel">
                <div class="bg-gray-50 border-b border-gray-200 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-sliders-h text-blue-500 mr-2"></i>
                        Filter Projects
                    </h3>
                </div>
                <div class="p-4">
                    @livewire('filters-projects-component', [
                        'genres' => $genres,
                        'statuses' => $statuses,
                        'projectTypes' => $projectTypes,
                        'selected_collaboration_types' => $selected_collaboration_types,
                        'min_budget' => $min_budget,
                        'max_budget' => $max_budget,
                        'deadline_start' => $deadline_start,
                        'deadline_end' => $deadline_end,
                    ])
                </div>
            </div>

            <!-- Active Filters Summary -->
            @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes) || $min_budget || $max_budget || $deadline_start || $deadline_end || !empty($selected_collaboration_types))
            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-4 fade-in">
                <h4 class="text-sm font-semibold text-amber-800 mb-3 flex items-center">
                    <i class="fas fa-filter text-amber-600 mr-2"></i>
                    Active Filters
                </h4>
                <div class="space-y-2">
                    @if($search)
                    <div class="flex items-center justify-between bg-white rounded-md px-2 py-1 border border-amber-200">
                        <span class="text-xs text-amber-700">Search: "{{ $search }}"</span>
                        <button wire:click="$set('search', '')" class="text-amber-600 hover:text-amber-800 focus-ring">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    @endif
                    <!-- Add other active filter displays here -->
                </div>
                <button wire:click="clearFilters" class="mt-3 btn btn-sm btn-outline btn-warning w-full btn-hover-lift">
                    <i class="fas fa-times mr-2"></i>Clear All Filters
                </button>
            </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Search and View Controls -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="search" 
                                   type="text" 
                                   placeholder="Search projects..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent focus-ring">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Sort and View Controls -->
                    <div class="flex items-center gap-3">
                        <!-- Sort Dropdown -->
                        <select wire:model.live="sortBy" 
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm focus-ring">
                            <option value="latest">Latest</option>
                            <option value="oldest">Oldest</option>
                            <option value="budget_high_low">Budget: High to Low</option>
                            <option value="budget_low_high">Budget: Low to High</option>
                            <option value="deadline">Deadline</option>
                        </select>

                        <!-- View Toggle -->
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                            <button wire:click="$set('viewMode', 'card')"
                                    class="px-3 py-2 text-sm font-medium {{ $viewMode === 'card' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition-colors focus-ring">
                                <i class="fas fa-th mr-1"></i>Cards
                            </button>
                            <button wire:click="$set('viewMode', 'list')"
                                    class="px-3 py-2 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition-colors border-l border-gray-300 focus-ring">
                                <i class="fas fa-list mr-1"></i>List
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        @if($search)
                        Search Results for "{{ $search }}"
                        @else
                        Available Projects
                        @endif
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $projects->total() }} {{ Str::plural('project', $projects->total()) }} found
                    </p>
                </div>
            </div>

            <!-- Projects Grid/List -->
            <div class="{{ $viewMode === 'card' ? 'project-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6' : 'project-list-view space-y-4' }}">
                @forelse($projects as $project)
                    @if($viewMode === 'card')
                        <div class="project-card fade-in">
                            @livewire('project-card', ['project' => $project], key('card-'.$project->id))
                        </div>
                    @else
                        <div class="fade-in">
                            @livewire('project-list-item', ['project' => $project], key('list-'.$project->id))
                        </div>
                    @endif
                @empty
                    <div class="col-span-full">
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-12 text-center fade-in">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-search text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No projects found</h3>
                            <p class="text-gray-600 mb-6">
                                @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                    Try adjusting your search criteria or filters to find more projects.
                                @else
                                    Be the first to create a project and start collaborating!
                                @endif
                            </p>
                            @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                <button wire:click="clearFilters" 
                                        class="btn btn-primary btn-hover-lift">
                                    <i class="fas fa-times mr-2"></i>Clear All Filters
                                </button>
                            @else
                                @auth
                                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-hover-lift">
                                    <i class="fas fa-plus mr-2"></i>Create First Project
                                </a>
                                @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-hover-lift">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Log In to Create Project
                                </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($projects->hasPages())
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                {{ $projects->links() }}
            </div>
            @endif

            <!-- Load More for Infinite Scroll (if implemented) -->
            <div wire:loading.delay class="text-center py-8">
                <div class="inline-flex items-center px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg">
                    <i class="fas fa-spinner loading-spinner text-blue-600 mr-2"></i>
                    <span class="text-blue-700">Loading more projects...</span>
                </div>
            </div>
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