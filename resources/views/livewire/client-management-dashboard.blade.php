<div>
<!-- Section Header with Client Navigation -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-2">
        <div class="flex items-center gap-3">
            <flux:icon.chart-bar variant="solid" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            <div>
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Overview</flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400">Key client project metrics and performance</flux:subheading>
            </div>
        </div>
        
        <!-- Client Navigation Popover -->
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="primary" icon="users" icon:trailing="chevron-down">
                Go to Client
            </flux:button>
            
            <flux:popover class="w-80 max-h-80 overflow-y-auto">
                <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Select a Client</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($this->clientsForFilter) }} {{ Str::plural('client', count($this->clientsForFilter)) }} available</p>
                </div>
                
                <div class="p-1">
                    @forelse($this->clientsForFilter as $client)
                        <div class="flex items-center gap-3 w-full p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <flux:avatar size="sm" class="bg-blue-600 text-white">
                                <flux:icon.user />
                            </flux:avatar>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate text-gray-900 dark:text-gray-100">{{ $client['label'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @php
                                        $projectCount = $this->clientProjects->where('client_id', $client['id'])->count();
                                    @endphp
                                    {{ $projectCount }} {{ Str::plural('project', $projectCount) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:button 
                                    wire:click="createProjectForClient({{ $client['id'] }})"
                                    variant="filled" 
                                    size="xs" 
                                    icon="plus"
                                    title="Create project for {{ $client['label'] }}">
                                </flux:button>
                                <flux:button 
                                    href="{{ route('producer.client-detail', $client['id']) }}"
                                    variant="ghost" 
                                    size="xs" 
                                    icon="arrow-top-right-on-square"
                                    title="View client details">
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <flux:icon.users class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">No clients found</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Create your first client management project to get started</p>
                        </div>
                    @endforelse
                </div>
            </flux:popover>
        </flux:dropdown>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <flux:card class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-blue-600 dark:text-blue-400">Total Projects</flux:subheading>
                    <flux:heading size="xl" class="text-blue-900 dark:text-blue-100">{{ $this->stats['total_projects'] }}</flux:heading>
                </div>
                <flux:icon.folder variant="solid" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
        </flux:card>

        <flux:card class="bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-green-600 dark:text-green-400">Active Projects</flux:subheading>
                    <flux:heading size="xl" class="text-green-900 dark:text-green-100">{{ $this->stats['active_projects'] }}</flux:heading>
                </div>
                <flux:icon.play variant="solid" class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
        </flux:card>

        <flux:card class="bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-purple-600 dark:text-purple-400">Completed</flux:subheading>
                    <flux:heading size="xl" class="text-purple-900 dark:text-purple-100">{{ $this->stats['completed_projects'] }}</flux:heading>
                </div>
                <flux:icon.check variant="solid" class="w-8 h-8 text-purple-600 dark:text-purple-400" />
            </div>
        </flux:card>

        <flux:card class="bg-orange-50 dark:bg-orange-950 border-orange-200 dark:border-orange-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-orange-600 dark:text-orange-400">Unique Clients</flux:subheading>
                    <flux:heading size="xl" class="text-orange-900 dark:text-orange-100">{{ $this->stats['unique_clients'] }}</flux:heading>
                </div>
                <flux:icon.users variant="solid" class="w-8 h-8 text-orange-600 dark:text-orange-400" />
            </div>
        </flux:card>

        <flux:card class="bg-cyan-50 dark:bg-cyan-950 border-cyan-200 dark:border-cyan-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-cyan-600 dark:text-cyan-400">Total Revenue</flux:subheading>
                    <flux:heading size="xl" class="text-cyan-900 dark:text-cyan-100">${{ number_format($this->stats['total_revenue'], 0) }}</flux:heading>
                </div>
                <flux:icon.currency-dollar variant="solid" class="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
            </div>
        </flux:card>

        <flux:card class="bg-pink-50 dark:bg-pink-950 border-pink-200 dark:border-pink-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-pink-600 dark:text-pink-400">Avg. Value</flux:subheading>
                    <flux:heading size="xl" class="text-pink-900 dark:text-pink-100">${{ number_format($this->stats['avg_project_value'], 0) }}</flux:heading>
                </div>
                <flux:icon.chart-bar variant="solid" class="w-8 h-8 text-pink-600 dark:text-pink-400" />
            </div>
        </flux:card>
    </div>

    <!-- LTV & Funnel Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-gray-600 dark:text-gray-400">Total Client LTV</flux:subheading>
                    <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">${{ number_format($this->ltvStats['total_ltv'] ?? 0, 0) }}</flux:heading>
                </div>
                <flux:icon.banknotes variant="solid" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="mt-2">
                <flux:subheading class="text-gray-600 dark:text-gray-400">Avg per client: ${{ number_format($this->ltvStats['avg_client_ltv'] ?? 0, 0) }}</flux:subheading>
            </div>
        </flux:card>
        
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:subheading class="text-gray-600 dark:text-gray-400">Funnel</flux:subheading>
                <flux:icon.funnel class="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $this->funnelStats['created'] ?? 0 }}</flux:heading>
                    <flux:subheading class="text-gray-600 dark:text-gray-400">Created</flux:subheading>
                </div>
                <div>
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $this->funnelStats['submitted'] ?? 0 }}</flux:heading>
                    <flux:subheading class="text-gray-600 dark:text-gray-400">Submitted</flux:subheading>
                </div>
                <div>
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $this->funnelStats['review'] ?? 0 }}</flux:heading>
                    <flux:subheading class="text-gray-600 dark:text-gray-400">Review</flux:subheading>
                </div>
                <div>
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $this->funnelStats['approved_completed'] ?? 0 }}</flux:heading>
                    <flux:subheading class="text-gray-600 dark:text-gray-400">Approved</flux:subheading>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Search, Filters, and Saved Views -->
    <flux:card class="mb-6">
        <div class="space-y-6">
            <!-- Main Search and Filters -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search projects by name, client name, or email..."
                        icon="magnifying-glass" />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select wire:model.live="statusFilter" class="px-3 pr-10 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="all">All Statuses</option>
                        <option value="unpublished">Unpublished</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                    
                    <select wire:model.live="clientFilter" class="px-3 pr-10 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="all">All Clients</option>
                        @foreach($this->clientsForFilter as $client)
                            <option value="{{ $client['id'] }}">{{ $client['label'] }}</option>
                        @endforeach
                    </select>
                    
                    @if($this->clientFilter !== 'all')
                        <flux:button 
                            href="{{ route('producer.client-detail', $this->clientFilter) }}" 
                            variant="primary" 
                            size="sm"
                            icon="arrow-top-right-on-square">
                            View Client
                        </flux:button>
                    @endif
                    
                    <select wire:model.live="sortDirection" class="px-3 pr-10 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>
            </div>

            <!-- Saved Views Section -->
            <flux:separator />
            
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <select wire:model.live="activeViewId" class="px-3 pr-10 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="">Saved Views…</option>
                        @foreach($this->availableViews as $view)
                            <option value="{{ $view['id'] }}">{{ $view['name'] }}{{ $view['is_default'] ? ' (Default)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:input 
                        wire:model.live="newViewName" 
                        placeholder="Save current filters as…" 
                        size="sm" />
                    <flux:checkbox wire:model.live="newViewDefault" label="Default" />
                    <flux:button wire:click="saveCurrentView" variant="primary" size="sm">Save View</flux:button>
                    @if($this->selectedClient)
                        <flux:button wire:click="createReminderForSelectedClient" variant="filled" size="sm" icon="bell">Create Reminder</flux:button>
                    @endif
                </div>
            </div>

            <!-- Quick Add Reminder Button -->
            <flux:separator />
            
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pt-6">
                <flux:subheading class="text-gray-600 dark:text-gray-400">Manage your client relationships</flux:subheading>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button wire:click="openAddClientModal()" variant="filled" size="sm" icon="user-plus">
                        Add Client
                    </flux:button>
                    <flux:button wire:click="$dispatch('openClientSelectionModal')" variant="primary" size="sm" icon="plus">
                        Create Project
                    </flux:button>
                    <flux:button wire:click="openReminderModal()" variant="ghost" size="sm" icon="bell">
                        Add Reminder
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Client Management Projects + Upcoming Reminders -->
    <flux:card>
        <!-- Header Section -->
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <flux:icon.briefcase variant="solid" class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Client Management Projects</flux:heading>
            </div>
            
            <!-- Upcoming Reminders Panel -->
            <div class="hidden md:block">
                <flux:card class="bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon.bell class="w-4 h-4 text-amber-500 dark:text-amber-400" />
                        <flux:subheading class="text-gray-700 dark:text-gray-300">Upcoming Reminders</flux:subheading>
                    </div>
                    <div class="space-y-2">
                        @forelse($this->upcomingReminders as $reminder)
                            <div class="flex items-center justify-between text-xs">
                                <div class="truncate">
                                    <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $reminder->client->name ?: $reminder->client->email }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">— {{ $reminder->note ?: 'Follow up' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $reminder->due_at->diffForHumans() }}</span>
                                    <flux:button wire:click="completeReminder({{ $reminder->id }})" variant="subtle" size="xs">Done</flux:button>
                                    <flux:button wire:click="snoozeReminder({{ $reminder->id }}, '1d')" variant="subtle" size="xs">+1d</flux:button>
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500 dark:text-gray-400">No upcoming reminders</div>
                        @endforelse
                    </div>
                </flux:card>
            </div>
        </div>

        @if($this->clientProjects->count() > 0)
            <div class="space-y-4">
                @foreach($this->clientProjects as $project)
                    <flux:card class="hover:shadow-md transition-shadow">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <!-- Project Header -->
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <flux:heading size="base">
                                        <a href="{{ route('projects.show', $project) }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            {{ $project->name }}
                                        </a>
                                    </flux:heading>
                                    <flux:badge variant="{{ match($project->status) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'open' => 'info',
                                        'unpublished' => 'neutral',
                                        default => 'neutral'
                                    } }}">
                                        {{ $project->readable_status }}
                                    </flux:badge>
                                </div>

                                <!-- Client Information -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    @if($project->client_name)
                                        <div class="flex items-center gap-2">
                                            <flux:icon.user class="w-4 h-4" />
                                            @if($project->client_id)
                                                <a href="{{ route('producer.client-detail', $project->client_id) }}" 
                                                   class="text-blue-600 dark:text-blue-400 hover:underline">
                                                    {{ $project->client_name }}
                                                </a>
                                            @else
                                                {{ $project->client_name }}
                                            @endif
                                        </div>
                                    @endif
                                    @if($project->client_email)
                                        <div class="flex items-center gap-2">
                                            <flux:icon.envelope class="w-4 h-4" />
                                            {{ $project->client_email }}
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-2">
                                        <flux:icon.calendar class="w-4 h-4" />
                                        {{ $project->created_at->format('M j, Y') }}
                                    </div>
                                </div>

                                @if($project->description)
                                    <flux:subheading class="text-gray-700 dark:text-gray-300 mb-3 line-clamp-2">
                                        {{ Str::limit($project->description, 150) }}
                                    </flux:subheading>
                                @endif

                                <!-- Project Metrics -->
                                <div class="flex flex-wrap items-center gap-4">
                                    @if($project->pitches->count() > 0)
                                        <div class="flex items-center gap-1 text-blue-600 dark:text-blue-400">
                                            <flux:icon.paper-airplane class="w-4 h-4" />
                                            <span class="text-sm">{{ $project->pitches->count() }} {{ Str::plural('pitch', $project->pitches->count()) }}</span>
                                        </div>
                                    @endif
                                    @if($project->payment_amount > 0)
                                        <div class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                            <flux:icon.currency-dollar class="w-4 h-4" />
                                            <span class="text-sm">${{ number_format($project->payment_amount, 2) }}</span>
                                        </div>
                                    @endif
                                    @php
                                        $paidPitches = $project->pitches->where('payment_status', 'paid');
                                        $totalPaid = $paidPitches->sum('payment_amount');
                                    @endphp
                                    @if($totalPaid > 0)
                                        <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <flux:icon.check-circle class="w-4 h-4" />
                                            <span class="text-sm">${{ number_format($totalPaid, 2) }} earned</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-2 sm:w-auto w-full">
                                <flux:button href="{{ route('projects.show', $project) }}" variant="primary" size="sm" icon="eye">
                                    View
                                </flux:button>
                                @if($project->pitches->count() > 0 && $project->isClientManagement())
                                    <flux:button href="{{ route('projects.manage-client', $project) }}" variant="ghost" size="sm" icon="cog-6-tooth">
                                        Manage
                                    </flux:button>
                                    <flux:button wire:click="openReminderModal({{ $project->id }})" variant="filled" size="sm" icon="bell">
                                        Remind
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <!-- Pagination -->
            <flux:separator class="my-6" />
            <div class="flex justify-center">
                {{ $this->clientProjects->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <flux:icon.briefcase class="w-24 h-24 text-blue-500 dark:text-blue-400 mx-auto mb-6" />
                <flux:heading size="xl" class="text-gray-800 dark:text-gray-200 mb-4">Ready to Manage Clients?</flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                    You haven't created any client management projects yet. Start building your client relationships and grow your business.
                </flux:subheading>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <flux:button 
                        href="{{ route('projects.create') }}?workflow_type=client_management" 
                        variant="primary" 
                        size="base"
                        icon="plus">
                        Create Client Project
                    </flux:button>
                    <flux:button 
                        href="{{ route('clients.import.index') }}" 
                        variant="outline" 
                        size="base"
                        icon="arrow-up-tray">
                        Import Clients
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>

    <!-- Add Reminder Modal -->
    <flux:modal name="add-reminder" class="max-w-lg">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:icon.bell class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg">Add Client Reminder</flux:heading>
            </div>
            
            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Set a reminder to follow up with a client about their project or general business.
            </flux:subheading>

            <div class="space-y-4">
                <!-- Client Selection -->
                <div>
                    <flux:field>
                        <flux:label>Client</flux:label>
                        <flux:select wire:model="modalReminderClientId" placeholder="Select a client">
                            @foreach($this->clientsForSelect as $client)
                                <option value="{{ $client['id'] }}">{{ $client['label'] }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="modalReminderClientId" />
                    </flux:field>
                </div>

                <!-- Due Date -->
                <div>
                    <flux:field>
                        <flux:label>Due Date & Time</flux:label>
                        <flux:date-picker wire:model="modalReminderDueAt" />
                        <flux:error name="modalReminderDueAt" />
                    </flux:field>
                </div>

                <!-- Note -->
                <div>
                    <flux:field>
                        <flux:label>Note</flux:label>
                        <flux:textarea 
                            wire:model="modalReminderNote" 
                            placeholder="What should you follow up about? (optional)"
                            rows="3" />
                        <flux:error name="modalReminderNote" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="saveModalReminder" variant="primary" icon="plus">
                    Add Reminder
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Add New Client Modal -->
    <flux:modal name="add-client" class="max-w-lg">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <flux:icon.user-plus class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg">Add New Client</flux:heading>
            </div>
            
            <flux:subheading class="text-gray-600 dark:text-gray-400">
                Create a new client record for your client management projects.
            </flux:subheading>

            <div class="space-y-4">
                <!-- Client Email -->
                <div>
                    <flux:field>
                        <flux:label>Email *</flux:label>
                        <flux:input 
                            wire:model="newClientEmail" 
                            type="email"
                            placeholder="client@example.com" />
                        <flux:error name="newClientEmail" />
                    </flux:field>
                </div>

                <!-- Client Name -->
                <div>
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input 
                            wire:model="newClientName" 
                            placeholder="Client Name (optional)" />
                        <flux:error name="newClientName" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="saveNewClient" variant="primary" icon="plus">
                    Add Client
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>