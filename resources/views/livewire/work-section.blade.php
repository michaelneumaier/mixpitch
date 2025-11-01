<div class="relative mb-2">
    <flux:card>
        <!-- Section Header -->
        <div class="mb-4">
            <flux:heading size="xl" class="mb-2">My Work</flux:heading>
            <flux:subheading>Track and manage all your active projects and collaborations</flux:subheading>
        </div>

        @if ($workItems->isEmpty())
            <!-- Enhanced Empty State -->
            <div class="max-w-2xl mx-auto">
                <flux:callout icon="rocket-launch" color="indigo" class="text-center">
                    <flux:callout.heading class="text-xl lg:text-2xl mb-3">
                        Ready to Start Creating?
                    </flux:callout.heading>
                    <flux:callout.text class="text-base lg:text-lg mb-6">
                        You don't have any active work items yet. Create your first project or find exciting collaborations to get started on your musical journey.
                    </flux:callout.text>
                    
                    <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                        <flux:button href="{{ route('projects.create') }}" wire:navigate icon="plus" variant="primary">
                            Create Project
                        </flux:button>
                        <flux:button href="{{ route('projects.index') }}" wire:navigate icon="magnifying-glass" variant="outline">
                            Browse Projects
                        </flux:button>
                    </div>
                </flux:callout>
            </div>
        @else
            <!-- Work Items Table -->
            <div class="overflow-x-hidden sm:overflow-visible">
                <flux:table class="table-fixed" 
                           x-data="dashboardTable()"
                           x-init="initTable()">
                    <flux:table.columns>
                        <flux:table.column class="w-10">
                            <!-- Filter Dropdown -->
                            <div>
                                <flux:dropdown position="bottom" align="start">
                                    <flux:button variant="ghost" size="xs" icon="funnel" class="p-1">
                                    </flux:button>
                                    
                                    <flux:menu>
                                        <flux:menu.item wire:click="setFilter('all')" icon="squares-2x2" 
                                                        :class="$filter === 'all' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                                            All
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="setFilter('project')" icon="folder"
                                                        :class="$filter === 'project' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                                            Projects
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="setFilter('contest')" icon="trophy"
                                                        :class="$filter === 'contest' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                                            Contests
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="setFilter('client')" icon="briefcase"
                                                        :class="$filter === 'client' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                                            Client Projects
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="setFilter('pitch')" icon="paper-airplane"
                                                        :class="$filter === 'pitch' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                                            Pitches
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.column>
                        <flux:table.column class="w-auto">Name</flux:table.column>
                        <flux:table.column class="hidden sm:table-cell">
                            <div class="flex items-center justify-between">
                                <span>Type</span>
                            </div>
                        </flux:table.column>
                        <flux:table.column class="w-24 sm:w-auto">Status</flux:table.column>
                        <flux:table.column class="hidden sm:table-cell">Amount</flux:table.column>
                        <flux:table.column class="hidden md:table-cell">Deadline</flux:table.column>
                        <flux:table.column class="hidden lg:table-cell">Updated</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($workItems as $item)
                            @php
                                $itemType = 'unknown';
                                $itemUrl = '#';
                                $itemName = 'Unknown';
                                $itemStatus = 'unknown';
                                $itemAmount = null;
                                $itemDeadline = null;
                                $itemUpdated = null;
                                $itemIcon = 'fa-question';
                                $itemBadgeColor = 'zinc';

                                if ($item instanceof \App\Models\Project) {
                                    if ($item->isClientManagement() && ($item->client_user_id === auth()->id() || $item->client_email === auth()->user()->email)) {
                                        $itemType = 'client';
                                        // Route registered clients to the client portal (which will use app-sidebar layout)
                                        $itemUrl = route('client.portal.view', $item);
                                    } elseif ($item->isContest()) {
                                        $itemType = 'contest';
                                        $itemUrl = route('projects.manage', $item);
                                    } else {
                                        $itemType = 'project';
                                        $itemUrl = route('projects.manage', $item);
                                    }
                                    $itemName = $item->name;
                                    $itemStatus = $item->status;
                                    $itemAmount = $item->budget;
                                    $itemDeadline = $item->isContest() ? $item->submission_deadline : $item->deadline;
                                    $itemUpdated = $item->updated_at;
                                    $itemIcon = $item->isContest() ? 'fa-trophy' : ($item->isClientManagement() ? 'fa-briefcase' : 'fa-folder');
                                    $itemBadgeColor = match($item->status) {
                                        'open' => 'blue',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                        'paused' => 'amber',
                                        default => 'zinc'
                                    };
                                }
                                elseif ($item instanceof \App\Models\Pitch) { 
                                    if ($item->project && $item->project->isClientManagement()) {
                                        $itemType = 'client';
                                    } else {
                                        $itemType = 'pitch';
                                    }
                                    $itemUrl = \App\Helpers\RouteHelpers::pitchUrl($item);
                                    $itemName = $item->project ? $item->project->name : 'Pitch';
                                    $itemStatus = $item->status;
                                    $itemAmount = $item->amount;
                                    $itemDeadline = $item->project ? ($item->project->isContest() ? $item->project->submission_deadline : $item->project->deadline) : null;
                                    $itemUpdated = $item->updated_at;
                                    $itemIcon = $item->project && $item->project->isClientManagement() ? 'fa-briefcase' : 'fa-paper-plane';
                                    $itemBadgeColor = match($item->status) {
                                        'pending' => 'amber',
                                        'accepted', 'completed' => 'green',
                                        'rejected' => 'red',
                                        'ready_for_review' => 'blue',
                                        'in_progress' => 'purple',
                                        default => 'zinc'
                                    };
                                }
                                elseif ($item instanceof \App\Models\Order) { 
                                    $itemType = 'order';
                                    $itemUrl = route('orders.show', $item);
                                    $itemName = $item->servicePackage ? $item->servicePackage->name : 'Order';
                                    $itemStatus = $item->status;
                                    $itemAmount = $item->total;
                                    $itemDeadline = $item->delivery_date;
                                    $itemUpdated = $item->updated_at;
                                    $itemIcon = 'fa-shopping-cart';
                                    $itemBadgeColor = match($item->status) {
                                        'pending' => 'amber',
                                        'processing' => 'blue',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                        'refunded' => 'zinc',
                                        default => 'zinc'
                                    };
                                }
                                elseif ($item instanceof \App\Models\ServicePackage) { 
                                    $itemType = 'service';
                                    $itemUrl = route('services.show', $item);
                                    $itemName = $item->name;
                                    $itemStatus = $item->status ?? 'active';
                                    $itemAmount = $item->price;
                                    $itemDeadline = null;
                                    $itemUpdated = $item->updated_at;
                                    $itemIcon = 'fa-cube';
                                    $itemBadgeColor = 'blue';
                                }

                                // Check if item should be shown based on filter
                                $shouldShow = $filter === 'all' || $filter === $itemType;
                            @endphp
                            
                            @if($shouldShow)
                            @php
                                // Build the metadata object for GlobalDragDropManager (same as sidebar approach)
                                $itemMeta = [
                                    'modelId' => $item->id,
                                    'context' => $itemType . 's',
                                    'modelLabel' => ucfirst($itemType),
                                ];
                                
                                // Set proper model type
                                if ($itemType === 'project') {
                                    $itemMeta['modelType'] = 'App\\Models\\Project';
                                    $itemMeta['projectTitle'] = $itemName;
                                    $itemMeta['workflowType'] = $item->workflow_type ?? 'standard';
                                    $itemMeta['projectStatus'] = $itemStatus;
                                } elseif ($itemType === 'client') {
                                    // Client management projects are still Project models
                                    $itemMeta['modelType'] = 'App\\Models\\Project';
                                    $itemMeta['projectTitle'] = $itemName;
                                    $itemMeta['workflowType'] = $item->workflow_type ?? 'client_management';
                                    $itemMeta['projectStatus'] = $itemStatus;
                                    $itemMeta['isClientManagement'] = true;
                                    $itemMeta['clientName'] = $item->client_name ?? 'Client';
                                } elseif ($itemType === 'contest') {
                                    // Contest projects are still Project models
                                    $itemMeta['modelType'] = 'App\\Models\\Project';
                                    $itemMeta['projectTitle'] = $itemName;
                                    $itemMeta['workflowType'] = $item->workflow_type ?? 'contest';
                                    $itemMeta['projectStatus'] = $itemStatus;
                                } elseif ($itemType === 'pitch') {
                                    $itemMeta['modelType'] = 'App\\Models\\Pitch';
                                    $itemMeta['pitchTitle'] = $itemName;
                                    $itemMeta['pitchStatus'] = $itemStatus;
                                    $itemMeta['workflowType'] = $item->project ? $item->project->workflow_type : 'standard';
                                    $itemMeta['isClientManagement'] = $item->project ? $item->project->isClientManagement() : false;
                                    
                                    if ($item->project && $item->project->isClientManagement()) {
                                        $itemMeta['clientName'] = $item->project->client_name ?? 'Client';
                                    }
                                } elseif ($itemType === 'order') {
                                    $itemMeta['modelType'] = 'App\\Models\\Order';
                                } elseif ($itemType === 'service') {
                                    $itemMeta['modelType'] = 'App\\Models\\ServicePackage';
                                } else {
                                    // Fallback: try to determine from the actual model instance
                                    if ($item instanceof \App\Models\Project) {
                                        $itemMeta['modelType'] = 'App\\Models\\Project';
                                        $itemMeta['projectTitle'] = $itemName;
                                    } elseif ($item instanceof \App\Models\Pitch) {
                                        $itemMeta['modelType'] = 'App\\Models\\Pitch';
                                        $itemMeta['pitchTitle'] = $itemName;
                                    } elseif ($item instanceof \App\Models\Order) {
                                        $itemMeta['modelType'] = 'App\\Models\\Order';
                                    } elseif ($item instanceof \App\Models\ServicePackage) {
                                        $itemMeta['modelType'] = 'App\\Models\\ServicePackage';
                                    }
                                }
                            @endphp
                            <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors dashboard-drop-zone" 
                                           data-item-url="{{ $itemUrl }}"
                                           data-model-id="{{ $itemMeta['modelId'] }}"
                                           data-context="{{ $itemMeta['context'] }}"
                                           data-model-label="{{ $itemMeta['modelLabel'] }}"
                                           data-model-type="{{ $itemMeta['modelType'] }}"
                                           data-project-title="{{ $itemMeta['projectTitle'] ?? '' }}"
                                           data-workflow-type="{{ $itemMeta['workflowType'] ?? '' }}"
                                           data-project-status="{{ $itemMeta['projectStatus'] ?? '' }}"
                                           data-pitch-title="{{ $itemMeta['pitchTitle'] ?? '' }}"
                                           data-pitch-status="{{ $itemMeta['pitchStatus'] ?? '' }}"
                                           data-is-client-management="{{ isset($itemMeta['isClientManagement']) && $itemMeta['isClientManagement'] ? 'true' : 'false' }}"
                                           data-client-name="{{ $itemMeta['clientName'] ?? '' }}"
                                           x-init="(() => { const ds = $el.dataset; const meta = { modelId: Number(ds.modelId), context: ds.context, modelLabel: ds.modelLabel, modelType: ds.modelType }; if (ds.projectTitle) meta.projectTitle = ds.projectTitle; if (ds.workflowType) meta.workflowType = ds.workflowType; if (ds.projectStatus) meta.projectStatus = ds.projectStatus; if (ds.pitchTitle) meta.pitchTitle = ds.pitchTitle; if (ds.pitchStatus) meta.pitchStatus = ds.pitchStatus; if (ds.isClientManagement && ds.isClientManagement !== 'false') meta.isClientManagement = ds.isClientManagement === 'true'; if (ds.clientName) meta.clientName = ds.clientName; if (window.GlobalDragDrop) window.GlobalDragDrop.registerDropZone($el, meta); })()"
                                           onclick="window.location.href='{{ $itemUrl }}'">
                                <flux:table.cell class="w-10">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $itemType === 'project' ? 'bg-blue-100 dark:bg-blue-900/30' : ($itemType === 'pitch' ? 'bg-indigo-100 dark:bg-indigo-900/30' : ($itemType === 'order' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-purple-100 dark:bg-purple-900/30')) }}">
                                        <i class="fas {{ $itemIcon }} text-xs {{ $itemType === 'project' ? 'text-blue-600 dark:text-blue-400' : ($itemType === 'pitch' ? 'text-indigo-600 dark:text-indigo-400' : ($itemType === 'order' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400')) }}"></i>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-0 w-full">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $itemName }}
                                        </div>
                                        @if ($itemType === 'pitch' && $item->project)
                                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                {{ $item->project->readableWorkflowTypeAttribute }}
                                            </div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="hidden sm:table-cell">
                                    <flux:badge size="sm" color="{{ $itemBadgeColor }}" class="capitalize">
                                        {{ Str::title(str_replace('_', ' ', $itemType)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="w-24 sm:w-auto">
                                    @php
                                        $fullStatus = Str::title(str_replace('_', ' ', $itemStatus));
                                        $mobileStatus = match($itemStatus) {
                                            'ready_for_review' => 'Review',
                                            'in_progress' => 'In Prog',
                                            'revisions_requested' => 'Revisions',
                                            'client_revisions_requested' => 'Revisions',
                                            'contest_winner' => 'Winner',
                                            'contest_runner_up' => 'Runner Up',
                                            'contest_not_selected' => 'Not Sel.',
                                            'contest_entry' => 'Entry',
                                            default => strlen($fullStatus) > 8 ? Str::limit($fullStatus, 8, '') : $fullStatus
                                        };
                                    @endphp
                                    <flux:badge size="sm" color="{{ $itemBadgeColor }}" variant="outline" class="capitalize whitespace-nowrap">
                                        <span class="sm:hidden">{{ $mobileStatus }}</span>
                                        <span class="hidden sm:inline">{{ $fullStatus }}</span>
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="hidden sm:table-cell">
                                    @if ($itemAmount && $itemAmount > 0)
                                        <span class="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">
                                            ${{ number_format($itemAmount, 0) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="hidden md:table-cell">
                                    @if ($itemDeadline)
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            <x-datetime :date="$itemDeadline" :user="auth()->user()" :convertToViewer="true" format="M j, Y" />
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="hidden lg:table-cell">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <x-datetime :date="$itemUpdated" relative="true" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:card>


<!-- Dashboard table-level drag & drop -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardTable', () => ({
        currentHoveredRow: null,
        
        initTable() {
            // Mark page as having dashboard drops to prevent global overlay
            document.body.setAttribute('data-has-dashboard-drops', 'true');
        },
        
        handleTableDragEnter(event) {
            const row = event.target.closest('.dashboard-drop-zone');
            if (row && row !== this.currentHoveredRow) {
                // Clear previous row
                if (this.currentHoveredRow) {
                    this.currentHoveredRow.classList.remove('drag-drop-active');
                }
                
                // Set new row as active
                this.currentHoveredRow = row;
                row.classList.add('drag-drop-active');
            }
        },
        
        handleTableDragOver(event) {
            // Just prevent default to allow drop
            const row = event.target.closest('.dashboard-drop-zone');
            if (row && row !== this.currentHoveredRow) {
                // Clear previous row
                if (this.currentHoveredRow) {
                    this.currentHoveredRow.classList.remove('drag-drop-active');
                }
                
                // Set new row as active
                this.currentHoveredRow = row;
                row.classList.add('drag-drop-active');
            }
        },
        
        handleTableDragLeave(event) {
            // Only clear if leaving the entire table
            if (!this.$el.contains(event.relatedTarget)) {
                if (this.currentHoveredRow) {
                    this.currentHoveredRow.classList.remove('drag-drop-active');
                    this.currentHoveredRow = null;
                }
            }
        },
        
        handleTableDrop(event) {
            const row = event.target.closest('.dashboard-drop-zone');
            if (row) {
                // Clear visual state
                if (this.currentHoveredRow) {
                    this.currentHoveredRow.classList.remove('drag-drop-active');
                    this.currentHoveredRow = null;
                }
                
                // Get item metadata
                const metaData = JSON.parse(row.dataset.itemMeta);
                
                // Handle file upload
                const files = Array.from(event.dataTransfer.files || []);
                if (files.length > 0 && window.GlobalUploader) {
                    window.GlobalUploader.addValidatedFiles(files, metaData);
                    console.log('📊 Uploaded files to:', metaData.modelLabel, metaData.projectTitle || metaData.pitchTitle || 'item');
                }
            }
        }
    }));
});
</script>

<style>
/* Dashboard table row drop zone styling */
.dashboard-drop-zone {
    transition: all 0.2s ease-out;
    position: relative;
}

.dashboard-drop-zone.drag-drop-active {
    transform: scale(1.01);
    background: transparent !important; /* clear any row-level gradient */
}

/* Apply highlight to cells so the entire row appears unified */
.dashboard-drop-zone.drag-drop-active > td {
    background: rgba(59, 130, 246, 0.10) !important;
}

.dashboard-drop-zone.drag-drop-active:hover > td {
    background: rgba(59, 130, 246, 0.14) !important;
}

/* Left accent and rounded corners across the row */
.dashboard-drop-zone.drag-drop-active > td:first-child {
    border-left: 4px solid #60a5fa !important;
    border-top-left-radius: 0.5rem;
    border-bottom-left-radius: 0.5rem;
}

.dashboard-drop-zone.drag-drop-active > td:last-child {
    border-top-right-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

/* Dark mode adjustments */
.dark .dashboard-drop-zone.drag-drop-active > td {
    background: rgba(59, 130, 246, 0.22) !important;
}

.dark .dashboard-drop-zone.drag-drop-active:hover > td {
    background: rgba(59, 130, 246, 0.28) !important;
}
</style>
</div>