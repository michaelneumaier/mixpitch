<div>
    <!-- Section Header -->
    <div class="mb-4 lg:mb-6">
        <h2 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-1">
            Overview
        </h2>
        <p class="text-gray-600">Key client project metrics</p>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Total Projects</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $this->stats['total_projects'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-folder text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Active Projects</p>
                    <p class="text-2xl font-bold text-green-900">{{ $this->stats['active_projects'] }}</p>
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
                    <p class="text-2xl font-bold text-purple-900">{{ $this->stats['completed_projects'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-check text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-600 font-medium">Unique Clients</p>
                    <p class="text-2xl font-bold text-orange-900">{{ $this->stats['unique_clients'] }}</p>
                </div>
                <div class="bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-50 to-teal-50 border border-cyan-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-cyan-600 font-medium">Total Revenue</p>
                    <p class="text-2xl font-bold text-cyan-900">${{ number_format($this->stats['total_revenue'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-r from-cyan-500 to-teal-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-dollar-sign text-white text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-50 to-rose-50 border border-pink-200 rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-pink-600 font-medium">Avg. Value</p>
                    <p class="text-2xl font-bold text-pink-900">${{ number_format($this->stats['avg_project_value'], 0) }}</p>
                </div>
                <div class="bg-gradient-to-r from-pink-500 to-rose-600 rounded-lg p-2 shadow-md">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- LTV & Funnel Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Client LTV</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($this->ltvStats['total_ltv'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-emerald-500 rounded-lg p-2">
                    <i class="fas fa-piggy-bank text-white text-lg"></i>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">Avg per client: ${{ number_format($this->ltvStats['avg_client_ltv'] ?? 0, 0) }}</div>
        </div>
        <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl p-4 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-600 font-medium">Funnel</p>
                <i class="fas fa-filter text-gray-400"></i>
            </div>
            <div class="grid grid-cols-4 gap-2 text-center">
                <div>
                    <div class="text-lg font-bold text-gray-900">{{ $this->funnelStats['created'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">Created</div>
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-900">{{ $this->funnelStats['submitted'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">Submitted</div>
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-900">{{ $this->funnelStats['review'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">Review</div>
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-900">{{ $this->funnelStats['approved_completed'] ?? 0 }}</div>
                    <div class="text-xs text-gray-600">Approved</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search, Filters, and Saved Views -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl p-4 mb-6 shadow-lg">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search projects by name, client name, or email..."
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
                <div class="flex items-center gap-3">
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

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <select wire:model.live="activeViewId" class="px-3 pr-10 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Saved Views…</option>
                        @foreach($this->availableViews as $view)
                            <option value="{{ $view['id'] }}">{{ $view['name'] }}{{ $view['is_default'] ? ' (Default)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <input type="text" wire:model.live="newViewName" placeholder="Save current filters as…" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="newViewDefault" class="rounded border-gray-300"> Default
                    </label>
                    <button wire:click="saveCurrentView" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg shadow">Save View</button>
                    @if($this->selectedClient)
                        <button wire:click="createReminderForSelectedClient" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-lg shadow">Create Reminder for Client</button>
                    @endif
                </div>
            </div>

            <!-- Quick Add Reminder -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <select wire:model.live="newReminderClientId" class="px-3 pr-10 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="">Add reminder for client…</option>
                        @foreach($this->clientsForSelect as $c)
                            <option value="{{ $c['id'] }}">{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                    <input type="datetime-local" wire:model.live="newReminderDueAt" class="px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                    <input type="text" wire:model.live="newReminderNote" placeholder="Note (optional)" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <button wire:click="addReminder" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-lg shadow">Add Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Management Projects + Upcoming Reminders -->
    <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/20">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent flex items-center">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                            <i class="fas fa-briefcase text-white text-sm"></i>
                        </div>
                        Client Management Projects
                    </h3>
                    <p class="text-gray-600 text-sm mt-1">Browse and manage your client projects</p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-bell text-amber-500"></i> Upcoming Reminders
                        </div>
                        <div class="space-y-2">
                            @forelse($this->upcomingReminders as $reminder)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="truncate">
                                        <span class="text-gray-800 font-medium">{{ $reminder->client->name ?: $reminder->client->email }}</span>
                                        <span class="text-gray-500">— {{ $reminder->note ?: 'Follow up' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500">{{ $reminder->due_at->diffForHumans() }}</span>
                                        <button wire:click="completeReminder({{ $reminder->id }})" class="px-2 py-1 bg-emerald-600/10 text-emerald-700 rounded">Done</button>
                                        <button wire:click="snoozeReminder({{ $reminder->id }}, '1d')" class="px-2 py-1 bg-amber-500/10 text-amber-700 rounded">+1d</button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-gray-500">No upcoming reminders</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
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
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs sm:text-sm font-medium {{ $project->getStatusColorClass() }}">
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
                                    <p class="text-gray-700 mb-3 line-clamp-2 text-sm">{{ Str::limit($project->description, 150) }}</p>
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

                            <div class="flex items-stretch gap-2 ml-4 flex-col sm:flex-row w-full sm:w-auto">
                                <a href="{{ route('projects.show', $project) }}" 
                                   class="inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg transition w-full sm:w-auto">
                                    <i class="fas fa-eye mr-1"></i>
                                    View
                                </a>
                                @if($project->pitches->count() > 0 && $project->isClientManagement())
                                    <a href="{{ route('projects.manage-client', $project) }}" 
                                       class="inline-flex items-center justify-center px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-800 text-sm font-medium rounded-lg transition-colors w-full sm:w-auto">
                                        <i class="fas fa-cog mr-1"></i>
                                        Manage
                                    </a>
                                    <button wire:click="quickReminderForProject({{ $project->id }})" class="inline-flex items-center justify-center px-3 py-2 bg-amber-100 hover:bg-amber-200 text-amber-800 text-sm font-medium rounded-lg transition-colors w-full sm:w-auto">
                                        <i class="fas fa-bell mr-1"></i>
                                        Remind
                                    </button>
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
