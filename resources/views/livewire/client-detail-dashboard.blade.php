<div>
    <!-- Client Header Card -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl mb-6 overflow-hidden">
        <!-- Background gradient -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/50 via-indigo-50/50 to-purple-50/50"></div>
        
        <div class="relative p-6 lg:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                <!-- Client Information -->
                <div class="flex-1">
                    <div class="flex items-start gap-4">
                        <!-- Client Avatar/Icon -->
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-3 shadow-lg flex-shrink-0">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                        
                        <!-- Client Details -->
                        <div class="flex-1">
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                                {{ $client->name ?: 'Client' }}
                            </h1>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-envelope mr-2 text-blue-500"></i>
                                    <span>{{ $client->email }}</span>
                                </div>
                                
                                @if($client->company)
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-building mr-2 text-indigo-500"></i>
                                    <span>{{ $client->company }}</span>
                                </div>
                                @endif
                                
                                @if($client->phone)
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-phone mr-2 text-green-500"></i>
                                    <span>{{ $client->phone }}</span>
                                </div>
                                @endif
                                
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-calendar mr-2 text-purple-500"></i>
                                    <span>Client since {{ $client->created_at->format('M Y') }}</span>
                                </div>
                            </div>

                            <!-- Client Status Badge -->
                            <div class="mt-3">
                                @php
                                    $statusConfig = [
                                        'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
                                        'inactive' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-pause-circle'],
                                        'blocked' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-ban'],
                                    ];
                                    $status = $statusConfig[$client->status] ?? $statusConfig['active'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                    <i class="fas {{ $status['icon'] }} mr-1"></i>
                                    {{ ucfirst($client->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex flex-col sm:flex-row lg:flex-col gap-3 lg:w-64">
                    <a href="mailto:{{ $client->email }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <i class="fas fa-envelope mr-2"></i>
                        Contact Client
                    </a>
                    
                    <a href="{{ route('projects.create') }}?workflow_type=client_management&client_id={{ $client->id }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                        <i class="fas fa-plus mr-2"></i>
                        New Project
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Total Projects</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $this->clientStats['total_projects'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-folder text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Active</p>
                    <p class="text-2xl font-bold text-green-900">{{ $this->clientStats['active_projects'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-play text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-violet-50 border border-purple-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-600 font-medium">Completed</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $this->clientStats['completed_projects'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-check text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-600 font-medium">Total Revenue</p>
                    <p class="text-2xl font-bold text-yellow-900">${{ number_format($this->clientStats['total_revenue'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-r from-yellow-500 to-amber-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-dollar-sign text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-rose-50 border border-pink-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-pink-600 font-medium">Avg. Value</p>
                    <p class="text-2xl font-bold text-pink-900">${{ number_format($this->clientStats['average_project_value'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-r from-pink-500 to-rose-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-teal-50 to-cyan-50 border border-teal-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-teal-600 font-medium">Success Rate</p>
                    <p class="text-2xl font-bold text-teal-900">{{ $this->clientStats['completion_rate'] }}%</p>
                </div>
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-trophy text-white text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- View Toggle and Filters -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl mb-6 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
                <button wire:click="switchView('kanban')" 
                        class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 {{ $view === 'kanban' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fas fa-columns mr-2"></i>Kanban
                </button>
                <button wire:click="switchView('list')" 
                        class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 {{ $view === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    <i class="fas fa-list mr-2"></i>List
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-1 lg:max-w-2xl gap-3">
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search client projects..."
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
                
                <select wire:model.live="statusFilter" class="px-3 pr-10 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <option value="all">All Statuses</option>
                    <option value="unpublished">Unpublished</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                
                <select wire:model.live="sortDirection" class="px-3 pr-10 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        @if($view === 'kanban')
            <!-- Client-Filtered Kanban Board -->
            <div class="p-6">
                @livewire('delivery-pipeline-board', ['clientId' => $client->id])
            </div>
        @else
            <!-- Project List -->
            <div class="p-6">
                @if($this->clientProjects->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->clientProjects as $project)
                            <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-shadow duration-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $project->name }}</h3>
                                        
                                        @if($project->description)
                                            <p class="text-gray-600 text-sm mb-3">{{ Str::limit($project->description, 120) }}</p>
                                        @endif
                                        
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>Created {{ $project->created_at->diffForHumans() }}</span>
                                            @if($project->pitches->first())
                                                <span>Producer: {{ $project->pitches->first()->user->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 ml-4">
                                        <!-- Status Badge -->
                                        @php
                                            $statusConfig = [
                                                'open' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
                                                'in_progress' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
                                                'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800'],
                                                'unpublished' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
                                            ];
                                            $status = $statusConfig[$project->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800'];
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </span>
                                        
                                        <!-- Actions -->
                                        <a href="{{ route('projects.manage', $project) }}" 
                                           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                            Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $this->clientProjects->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <div class="bg-gray-50 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Projects Yet</h3>
                        <p class="text-gray-600 mb-6">This client doesn't have any projects yet.</p>
                        <a href="{{ route('projects.create') }}?workflow_type=client_management&client_id={{ $client->id }}" 
                           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                            <i class="fas fa-plus mr-2"></i>
                            Create First Project
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>