<div>
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Total Projects</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $this->stats['total_projects'] }}</p>
                </div>
                <div class="bg-blue-500 rounded-lg p-2">
                    <i class="fas fa-folder text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Active Projects</p>
                    <p class="text-2xl font-bold text-green-900">{{ $this->stats['active_projects'] }}</p>
                </div>
                <div class="bg-green-500 rounded-lg p-2">
                    <i class="fas fa-play text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-violet-50 border border-purple-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-600 font-medium">Completed</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $this->stats['completed_projects'] }}</p>
                </div>
                <div class="bg-purple-500 rounded-lg p-2">
                    <i class="fas fa-check text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-600 font-medium">Unique Clients</p>
                    <p class="text-2xl font-bold text-orange-900">{{ $this->stats['unique_clients'] }}</p>
                </div>
                <div class="bg-orange-500 rounded-lg p-2">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-50 to-teal-50 border border-cyan-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-cyan-600 font-medium">Total Revenue</p>
                    <p class="text-2xl font-bold text-cyan-900">${{ number_format($this->stats['total_revenue'], 0) }}</p>
                </div>
                <div class="bg-cyan-500 rounded-lg p-2">
                    <i class="fas fa-dollar-sign text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-rose-50 border border-pink-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-pink-600 font-medium">Avg. Value</p>
                    <p class="text-2xl font-bold text-pink-900">${{ number_format($this->stats['avg_project_value'], 0) }}</p>
                </div>
                <div class="bg-pink-500 rounded-lg p-2">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search projects by name, client name, or email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="all">All Statuses</option>
                    <option value="unpublished">Unpublished</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <select wire:model.live="sortDirection" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Client Management Projects -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3">
                    <i class="fas fa-briefcase text-white text-sm"></i>
                </div>
                Client Management Projects
            </h3>
        </div>

        @if($this->clientProjects->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($this->clientProjects as $project)
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h4 class="text-lg font-semibold text-gray-900 truncate">
                                        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600 transition-colors">
                                            {{ $project->name }}
                                        </a>
                                    </h4>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $project->getStatusColorClass() }}">
                                        {{ $project->readable_status }}
                                    </span>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-sm text-gray-600 mb-3">
                                    @if($project->client_name)
                                        <div class="flex items-center">
                                            <i class="fas fa-user mr-2 text-gray-400"></i>
                                            {{ $project->client_name }}
                                        </div>
                                    @endif
                                    @if($project->client_email)
                                        <div class="flex items-center">
                                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                            {{ $project->client_email }}
                                        </div>
                                    @endif
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                        {{ $project->created_at->format('M j, Y') }}
                                    </div>
                                </div>

                                @if($project->description)
                                    <p class="text-gray-700 mb-3 line-clamp-2">{{ Str::limit($project->description, 150) }}</p>
                                @endif

                                <!-- Project Metrics -->
                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                    @if($project->pitches->count() > 0)
                                        <div class="flex items-center text-blue-600">
                                            <i class="fas fa-paper-plane mr-1"></i>
                                            {{ $project->pitches->count() }} {{ Str::plural('pitch', $project->pitches->count()) }}
                                        </div>
                                    @endif
                                    @if($project->payment_amount > 0)
                                        <div class="flex items-center text-green-600">
                                            <i class="fas fa-dollar-sign mr-1"></i>
                                            ${{ number_format($project->payment_amount, 2) }}
                                        </div>
                                    @endif
                                    @php
                                        $paidPitches = $project->pitches->where('payment_status', 'paid');
                                        $totalPaid = $paidPitches->sum('payment_amount');
                                    @endphp
                                    @if($totalPaid > 0)
                                        <div class="flex items-center text-emerald-600">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            ${{ number_format($totalPaid, 2) }} earned
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 ml-4">
                                <a href="{{ route('projects.show', $project) }}" 
                                   class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    View
                                </a>
                                @if($project->pitches->count() > 0 && $project->isClientManagement())
                                    <a href="{{ route('projects.manage-client', $project) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 text-sm font-medium rounded-lg transition-colors">
                                        <i class="fas fa-cog mr-1"></i>
                                        Manage
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $this->clientProjects->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-briefcase text-3xl text-blue-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Client Management Projects</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">
                    You haven't created any client management projects yet. Start building your client relationships!
                </p>
                <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                    <i class="fas fa-plus mr-2"></i>Create Client Project
                </a>
            </div>
        @endif
    </div>
</div>
