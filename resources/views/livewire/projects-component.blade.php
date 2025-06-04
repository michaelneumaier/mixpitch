<div class="min-h-screen flex flex-col items-center relative">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
    
    <!-- Decorative Elements -->
    <div class="absolute top-20 left-10 w-20 h-20 bg-blue-200/30 rounded-full blur-xl"></div>
    <div class="absolute bottom-20 right-10 w-32 h-32 bg-purple-200/30 rounded-full blur-xl"></div>
    <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-200/30 rounded-full blur-xl"></div>

    <div class="container mx-auto px-2 sm:px-4 py-6 relative z-10">
        <link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">

        <!-- Modern Hero Section -->
        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6">
            <!-- Hero Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-purple-50/20 to-pink-50/30"></div>
            <div class="absolute top-4 left-4 w-24 h-24 bg-blue-400/10 rounded-full blur-xl"></div>
            <div class="absolute top-4 right-4 w-16 h-16 bg-purple-400/10 rounded-full blur-lg"></div>
            
            <div class="relative bg-gradient-to-r from-blue-50/50 to-indigo-50/50 border-b border-white/30 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent mb-2 flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-2.5 w-12 h-12 flex items-center justify-center mr-4 shadow-lg">
                                <i class="fas fa-project-diagram text-white text-xl"></i>
                            </div>
                            Discover Musical Projects
                        </h1>
                        <p class="text-lg text-gray-600 font-medium">
                            Find your next creative opportunity and collaborate with talented artists
                        </p>
                    </div>
                    <div class="flex gap-3">
                        @auth
                            <a href="{{ route('projects.create') }}" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                <!-- Button Background Effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                
                                <!-- Button Content -->
                                <div class="relative flex items-center">
                                    <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                    Create Project
                                </div>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                <!-- Button Background Effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-400/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                
                                <!-- Button Content -->
                                <div class="relative flex items-center">
                                    <i class="fas fa-sign-in-alt mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                    Log In to Create
                                </div>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="relative p-6 bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div class="flex flex-col items-center space-y-2 bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">{{ $projects->total() }}</div>
                        <div class="text-xs text-gray-600 font-medium">Total Projects</div>
                    </div>
                    <div class="flex flex-col items-center space-y-2 bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <div class="text-2xl font-bold bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent">{{ $projects->where('status', 'open')->count() }}</div>
                        <div class="text-xs text-gray-600 font-medium">Open Projects</div>
                    </div>
                    <div class="flex flex-col items-center space-y-2 bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <div class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent">{{ $projects->whereIn('workflow_type', ['standard', 'contest'])->count() }}</div>
                        <div class="text-xs text-gray-600 font-medium">Public Projects</div>
                    </div>
                    <div class="flex flex-col items-center space-y-2 bg-white/60 backdrop-blur-sm border border-white/30 rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <div class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">{{ $projects->where('created_at', '>=', now()->subDays(7))->count() }}</div>
                        <div class="text-xs text-gray-600 font-medium">This Week</div>
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
                            class="group w-full bg-white/90 backdrop-blur-sm border border-white/30 rounded-xl p-4 flex items-center justify-between shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                        <span class="font-semibold text-gray-700 flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-1.5 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                                <i class="fas fa-filter text-white text-sm"></i>
                            </div>
                            Filters
                        </span>
                        <i class="fas fa-chevron-down transition-transform duration-200 group-hover:scale-110" 
                           :class="$store.filterMenu.open ? 'rotate-180' : ''"></i>
                    </button>
                </div>

                <!-- Filter Panel -->
                <div x-data="{ isLargeScreen: window.innerWidth >= 1024 }"
                     x-init="window.addEventListener('resize', () => { isLargeScreen = window.innerWidth >= 1024 })"
                     x-show="$store.filterMenu.open || isLargeScreen" 
                     x-transition
                     class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden filter-panel">
                    <!-- Filter Header -->
                    <div class="relative bg-gradient-to-r from-gray-50/80 to-blue-50/80 border-b border-white/30 p-4">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/30 to-purple-50/30"></div>
                        <h3 class="relative text-xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent flex items-center">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-sliders-h text-white text-sm"></i>
                            </div>
                            Filter Projects
                        </h3>
                    </div>
                    <div class="p-6">
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
                <div class="mt-4 bg-amber-50/90 backdrop-blur-sm border border-amber-200/60 rounded-xl p-4 shadow-lg fade-in">
                    <h4 class="text-sm font-bold text-amber-800 mb-3 flex items-center">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center mr-2 shadow-md">
                            <i class="fas fa-filter text-white text-xs"></i>
                        </div>
                        Active Filters
                    </h4>
                    <div class="space-y-2">
                        @if($search)
                        <div class="flex items-center justify-between bg-white/80 backdrop-blur-sm rounded-lg px-3 py-2 border border-amber-200/50 shadow-sm">
                            <span class="text-xs text-amber-700 font-medium">Search: "{{ $search }}"</span>
                            <button wire:click="$set('search', '')" class="text-amber-600 hover:text-amber-800 hover:scale-110 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500/25 rounded-md p-1">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        @endif
                        <!-- Add other active filter displays here -->
                    </div>
                    <button wire:click="clearFilters" class="group mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-amber-500/25">
                        <i class="fas fa-times mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                        Clear All Filters
                    </button>
                </div>
                @endif
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Search and View Controls -->
                <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6">
                    <!-- Background Effects -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-50/20 to-purple-50/20 rounded-2xl"></div>
                    
                    <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <!-- Search Bar -->
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <input wire:model.live.debounce.300ms="search" 
                                       type="text" 
                                       placeholder="Search projects..." 
                                       class="w-full pl-12 pr-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all duration-200 placeholder-gray-400">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-1.5 w-6 h-6 flex items-center justify-center shadow-md">
                                        <i class="fas fa-search text-white text-xs"></i>
                                    </div>
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>
                        </div>

                        <!-- Sort and View Controls -->
                        <div class="flex items-center gap-4">
                            <!-- Sort Dropdown -->
                            <div class="relative">
                                <select wire:model.live="sortBy" 
                                        class="px-4 py-3 bg-white/90 backdrop-blur-sm border border-gray-200/50 rounded-xl shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white text-sm font-medium transition-all duration-200 appearance-none pr-10">
                                    <option value="latest">Latest</option>
                                    <option value="oldest">Oldest</option>
                                    <option value="budget_high_low">Budget: High to Low</option>
                                    <option value="budget_low_high">Budget: Low to High</option>
                                    <option value="deadline">Deadline</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-purple-500/5 pointer-events-none opacity-0 focus-within:opacity-100 transition-opacity duration-200"></div>
                            </div>

                            <!-- View Toggle -->
                            <div class="flex rounded-xl border border-gray-200/50 overflow-hidden shadow-sm bg-white/90 backdrop-blur-sm">
                                <button wire:click="$set('viewMode', 'card')"
                                        class="px-4 py-3 text-sm font-medium transition-all duration-200 {{ $viewMode === 'card' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-white/90 text-gray-700 hover:bg-gray-50' }}">
                                    <i class="fas fa-th mr-2"></i>Cards
                                </button>
                                <button wire:click="$set('viewMode', 'list')"
                                        class="px-4 py-3 text-sm font-medium transition-all duration-200 border-l border-gray-200/50 {{ $viewMode === 'list' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg' : 'bg-white/90 text-gray-700 hover:bg-gray-50' }}">
                                    <i class="fas fa-list mr-2"></i>List
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent">
                            @if($search)
                                Search Results for "{{ $search }}"
                            @else
                                Available Projects
                            @endif
                        </h2>
                        <p class="text-sm text-gray-600 mt-1 font-medium">
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
                            <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-12 text-center fade-in">
                                <!-- Background Effects -->
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 to-purple-50/30 rounded-2xl"></div>
                                <div class="absolute top-4 right-4 w-16 h-16 bg-blue-400/10 rounded-full blur-lg"></div>
                                
                                <div class="relative">
                                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-blue-100/80 to-purple-100/80 rounded-full flex items-center justify-center mb-6 shadow-lg">
                                        <i class="fas fa-search text-3xl bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"></i>
                                    </div>
                                    <h3 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent mb-2">No projects found</h3>
                                    <p class="text-gray-600 mb-8 font-medium">
                                        @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                            Try adjusting your search criteria or filters to find more projects.
                                        @else
                                            Be the first to create a project and start collaborating!
                                        @endif
                                    </p>
                                    @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                        <button wire:click="clearFilters" 
                                                class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                            <i class="fas fa-times mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                            Clear All Filters
                                        </button>
                                    @else
                                        @auth
                                        <a href="{{ route('projects.create') }}" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                            <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                            Create First Project
                                        </a>
                                        @else
                                        <a href="{{ route('login') }}" class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                            <i class="fas fa-sign-in-alt mr-2 group-hover:scale-110 transition-transform duration-200"></i>
                                            Log In to Create Project
                                        </a>
                                        @endauth
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($projects->hasPages())
                <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-6">
                    {{ $projects->links() }}
                </div>
                @endif

                <!-- Load More for Infinite Scroll (if implemented) -->
                <div wire:loading.delay class="text-center py-8">
                    <div class="inline-flex items-center px-6 py-3 bg-blue-50/90 backdrop-blur-sm border border-blue-200/60 rounded-xl shadow-lg">
                        <i class="fas fa-spinner loading-spinner bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mr-3"></i>
                        <span class="text-blue-700 font-medium">Loading more projects...</span>
                    </div>
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