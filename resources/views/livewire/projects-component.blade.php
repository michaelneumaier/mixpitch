<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Compact Dashboard Header -->
            <flux:card class="mb-2 bg-white/50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 dark:from-gray-100 dark:via-blue-300 dark:to-purple-300 bg-clip-text text-transparent">
                        Discover Projects
                    </flux:heading>
                    
                    <div class="flex items-center gap-2">
                        @auth
                            <flux:button href="{{ route('projects.create') }}" icon="plus" variant="primary" size="xs">
                                Create
                            </flux:button>
                        @else
                            <flux:button href="{{ route('login') }}" icon="arrow-right-end-on-rectangle" variant="primary" size="xs">
                                Login
                            </flux:button>
                        @endauth
                    </div>
                </div>
                
                <flux:subheading class="text-slate-600 dark:text-slate-400">
                    Find your next creative opportunity and collaborate with talented artists
                </flux:subheading>
            </flux:card>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">


                <!-- Main Content Area -->
                <div class="lg:col-span-3 space-y-4">
                    <!-- Search and View Controls -->
                    <flux:card>
                    
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <!-- Search Bar -->
                            <div class="flex-1 sm:max-w-md">
                                <flux:input wire:model.live.debounce.300ms="search" 
                                           type="text" 
                                           placeholder="Search projects..." 
                                           icon="magnifying-glass" />
                            </div>

                            <!-- Sort and View Controls -->
                            <div class="flex items-center gap-3">
                                <!-- Sort Dropdown -->
                                <flux:select wire:model.live="sortBy" class="w-full sm:w-auto">
                                    <option value="latest">Latest</option>
                                    <option value="oldest">Oldest</option>
                                    <option value="budget_high_low">Budget: High to Low</option>
                                    <option value="budget_low_high">Budget: Low to High</option>
                                    <option value="deadline">Deadline</option>
                                </flux:select>

                                <!-- View Toggle -->
                                <div class="flex border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                                    <flux:button wire:click="$set('viewMode', 'card')"
                                               :variant="$viewMode === 'card' ? 'primary' : 'ghost'"
                                               icon="squares-2x2"
                                               size="sm"
                                               class="rounded-none">
                                        <span class="hidden sm:inline ml-1 text-gray-900 dark:text-gray-100">Cards</span>
                                    </flux:button>
                                    <flux:button wire:click="$set('viewMode', 'list')"
                                               :variant="$viewMode === 'list' ? 'primary' : 'ghost'"
                                               icon="list-bullet"
                                               size="sm"
                                               class="rounded-none border-l border-slate-200 dark:border-slate-700">
                                        <span class="hidden sm:inline ml-1 text-gray-900 dark:text-gray-100">List</span>
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </flux:card>

                    <!-- Results Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <flux:heading size="xl" class="mb-1 text-gray-900 dark:text-gray-100">
                                @if($search)
                                    Search Results for "{{ $search }}"
                                @endif
                            </flux:heading>
                            <flux:text size="sm" class="text-slate-600 dark:text-slate-400 font-medium">
                                {{ $projects->total() }} {{ Str::plural('project', $projects->total()) }} found
                            </flux:text>
                        </div>
                    </div>

                    <!-- Projects Grid/List -->
                    <div class="{{ $viewMode === 'card' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4' : 'space-y-4' }}">
                        @forelse($projects as $project)
                        @if($viewMode === 'card')
                                <div>
                        @livewire('project-card', ['project' => $project], key('card-'.$project->id))
                                </div>
                        @else
                                <div>
                        @livewire('project-list-item', ['project' => $project], key('list-'.$project->id))
                                </div>
                        @endif
                        @empty
                            <div class="col-span-full">
                                <flux:card class="text-center py-12 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                                    <div class="mb-4">
                                        <flux:icon name="magnifying-glass" class="mx-auto text-slate-400 dark:text-slate-500" size="2xl" />
                                    </div>
                                    <flux:heading size="lg" class="mb-2 text-gray-900 dark:text-gray-100">No projects found</flux:heading>
                                    <flux:text class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">
                                        @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                            Try adjusting your search criteria or filters to find more projects.
                                        @else
                                            Be the first to create a project and start collaborating!
                                        @endif
                                    </flux:text>
                                    @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes))
                                        <flux:button wire:click="clearFilters" icon="x-mark" variant="primary">
                                            Clear All Filters
                                        </flux:button>
                                    @else
                                        @auth
                                        <flux:button href="{{ route('projects.create') }}" icon="plus" variant="primary">
                                            Create First Project
                                        </flux:button>
                                        @else
                                        <flux:button href="{{ route('login') }}" icon="arrow-right-end-on-rectangle" variant="primary">
                                            Log In to Create Project
                                        </flux:button>
                                        @endauth
                                    @endif
                                </flux:card>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($projects->hasPages())
                    <flux:card class="bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                        <div class="text-gray-900 dark:text-gray-100">
                            {{ $projects->links() }}
                        </div>
                    </flux:card>
                    @endif

                    <!-- Load More for Infinite Scroll (if implemented) -->
                    <div wire:loading.delay class="text-center py-8">
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <flux:icon name="arrow-path" class="animate-spin text-blue-600 dark:text-blue-400" />
                            <flux:text class="text-blue-700 dark:text-blue-300 font-medium">Loading more projects...</flux:text>
                        </div>
                    </div>
                </div>

                                <!-- Filters Sidebar -->
                <div class="lg:col-span-1" x-data="{ mobileFiltersOpen: false }">
                    <!-- Mobile Filter Toggle -->
                    <div class="lg:hidden mb-4">
                        <button @click="mobileFiltersOpen = !mobileFiltersOpen" 
                                class="w-full flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                            <div class="flex items-center gap-3">
                                <flux:icon name="funnel" size="sm" class="text-gray-600 dark:text-gray-300" />
                                <span class="font-medium text-gray-900 dark:text-gray-100">Filters</span>
                            </div>
                            <flux:icon name="chevron-down" size="sm" :class="mobileFiltersOpen ? 'rotate-180' : ''" class="transition-transform text-gray-600 dark:text-gray-300" />
                        </button>
                    </div>

                    <!-- Mobile Filter Panel -->
                    <div class="lg:hidden" x-show="mobileFiltersOpen" x-transition.opacity>
                        <flux:card>
                                <!-- Filter Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-sm">
                                            <flux:icon name="adjustments-horizontal" class="text-white" size="sm" />
                                        </div>
                                        <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Filter Projects</flux:heading>
                                    </div>
                                    <!-- Mobile Close Button -->
                                    <button @click="mobileFiltersOpen = false" class="lg:hidden p-2 hover:bg-slate-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-600 dark:text-gray-300">
                                        <flux:icon name="x-mark" size="sm" />
                                    </button>
                                </div>
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
                            </flux:card>
                        </div>
                    
                    <!-- Desktop Filter Panel -->
                    <div class="hidden lg:block">
                        <flux:card>
                            <!-- Filter Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-sm">
                                        <flux:icon name="adjustments-horizontal" class="text-white" size="sm" />
                                    </div>
                                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Filter Projects</flux:heading>
                                </div>
                            </div>
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
                        </flux:card>
                    </div>

                    <!-- Active Filters Summary -->
                    @if($search || !empty($genres) || !empty($statuses) || !empty($projectTypes) || $min_budget || $max_budget || $deadline_start || $deadline_end || !empty($selected_collaboration_types))
                    <flux:card class="mt-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-1.5 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-sm">
                                <flux:icon name="funnel" class="text-white" size="xs" />
                            </div>
                            <flux:heading size="sm" class="text-slate-800 dark:text-slate-200">Active Filters</flux:heading>
                        </div>
                        <div class="space-y-2 mb-4">
                            @if($search)
                            <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-600">
                                <flux:text size="xs" class="font-medium text-gray-800 dark:text-gray-200">Search: "{{ $search }}"</flux:text>
                                <flux:button wire:click="$set('search', '')" icon="x-mark" variant="ghost" size="xs" />
                            </div>
                            @endif
                        </div>
                        <flux:button wire:click="clearFilters" icon="x-mark" variant="danger" size="sm" class="w-full">
                            Clear All Filters
                        </flux:button>
                    </flux:card>
                    @endif
                </div>
            </div>
        </div>
    </div>

