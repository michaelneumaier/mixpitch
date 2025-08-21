@php
    // Unified Color System - Workflow-aware colors (matching manage-project.blade.php)
    $workflowColors = match($project->workflow_type) {
        'standard' => [
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800', 
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
            'accent_border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400'
        ],
        'contest' => [
            'bg' => 'bg-orange-50 dark:bg-orange-950',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text_primary' => 'text-orange-900 dark:text-orange-100', 
            'text_secondary' => 'text-orange-700 dark:text-orange-300',
            'text_muted' => 'text-orange-600 dark:text-orange-400',
            'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
            'accent_border' => 'border-orange-200 dark:border-orange-800',
            'icon' => 'text-orange-600 dark:text-orange-400'
        ],
        'direct_hire' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300', 
            'text_muted' => 'text-green-600 dark:text-green-400',
            'accent_bg' => 'bg-green-100 dark:bg-green-900',
            'accent_border' => 'border-green-200 dark:border-green-800',
            'icon' => 'text-green-600 dark:text-green-400'
        ],
        'client_management' => [
            'bg' => 'bg-purple-50 dark:bg-purple-950',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400', 
            'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
            'accent_border' => 'border-purple-200 dark:border-purple-800',
            'icon' => 'text-purple-600 dark:text-purple-400'
        ],
        default => [
            'bg' => 'bg-gray-50 dark:bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100 dark:bg-gray-900', 
            'accent_border' => 'border-gray-200 dark:border-gray-800',
            'icon' => 'text-gray-600 dark:text-gray-400'
        ]
    };

    // Semantic colors (always consistent regardless of workflow)
    $semanticColors = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-600 dark:bg-green-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400', 
            'accent' => 'bg-amber-500'
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500'
        ]
    ];
@endphp

<div>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-2 py-2">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-center">
                <div class="w-full">
                    <!-- Enhanced Project Header -->
                    <x-project.header 
                        :project="$project" 
                        context="manage" 
                        :showActions="true"
                        :showEditButton="true"
                    />
                    
                    <div class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                        <!-- Main Content Area (2/3 width on large screens) -->
                        <div class="space-y-2 lg:col-span-2">
                            <!-- Client Management Workflow Status -->
                            <div class="mb-2">
                                <x-client-management.workflow-status :pitch="$pitch" :project="$project" :component="$this" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                            </div>

                            <!-- Status-Specific Workflow Actions -->
                            <div class="mb-2">
                                <x-client-management.workflow-actions 
                                    :pitch="$pitch" 
                                    :project="$project" 
                                    :component="$this"
                                    :workflowColors="$workflowColors"
                                    :semanticColors="$semanticColors" />
                            </div>

                            <!-- COMMUNICATION SECTION - Always accessible but positioned based on priority -->
                            <flux:card class="mb-2 {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                <div class="border-b {{ $workflowColors['border'] }}">
                                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                        <flux:icon name="chat-bubble-left-ellipsis" class="mr-2 {{ $workflowColors['icon'] }}" />
                                        Client Communication
                                    </flux:heading>
                                    <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">Send messages and view conversation history</flux:text>
                                </div>
                                
                                <div class="p-6">
                                    <!-- Send Message Form -->
                                    <form wire:submit.prevent="addProducerComment">
                                        <flux:field>
                                            <flux:label for="newComment">Send Message to Client</flux:label>
                                            <flux:textarea wire:model.defer="newComment" 
                                                          id="newComment"
                                                          rows="4"
                                                          placeholder="Share updates, ask questions, or provide additional context..." />
                                            <flux:error name="newComment" />
                                        </flux:field>
                                        
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">
                                            <div class="flex-1 {{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} border rounded-lg p-3">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.information-circle class="w-4 h-4 {{ $semanticColors['success']['icon'] }}" />
                                                    <span class="text-sm {{ $semanticColors['success']['text'] }}">This message will be visible to your client and they'll receive an email notification</span>
                                                </div>
                                            </div>
                                            
                                            <flux:button type="submit" 
                                                    variant="primary"
                                                    icon="paper-airplane"
                                                    wire:loading.attr="disabled">
                                                <span wire:loading.remove>Send Message</span>
                                                <span wire:loading>Sending...</span>
                                            </flux:button>
                                        </div>
                                    </form>
                                </div>
                            </flux:card>

                            <!-- Communication Timeline -->
                            <div class="mb-2">
                                <x-client-project.communication-timeline 
                                    :component="$this" 
                                    :conversationItems="$this->conversationItems"
                                    :workflowColors="$workflowColors"
                                    :semanticColors="$semanticColors" />
                            </div>

                            <!-- Milestones Section -->
                            <flux:card class="mb-2 {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                <div class="border-b {{ $workflowColors['border'] }}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                <flux:icon name="flag" class="mr-2 {{ $workflowColors['icon'] }}" />
                                                Milestones
                                            </flux:heading>
                                            <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">Define partial payments and approvals</flux:text>
                                        </div>
                                        <flux:button wire:click="beginAddMilestone" variant="primary" icon="plus">
                                            Add Milestone
                                        </flux:button>
                                    </div>
                                </div>
                                <div>
                    @php($milestones = $pitch->milestones()->get())
                    @php($milestoneTotal = $milestones->sum('amount'))
                    @php($milestonePaid = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount'))
                    @php($approvedFiles = $pitch->files->where('client_approval_status', 'approved')->count())
                    @php($totalFiles = $pitch->files->count())

                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            <span class="mr-2">Totals:</span>
                            <span class="font-semibold text-gray-900">${{ number_format($milestonePaid, 2) }}</span>
                            <span class="text-gray-500">of</span>
                                <span class="font-semibold text-gray-900">${{ number_format($milestoneTotal, 2) }}</span>
                                @php($baseBudget = $pitch->payment_amount > 0 ? $pitch->payment_amount : ($project->budget ?? 0))
                                @if($baseBudget && $baseBudget > 0)
                                    <span class="text-gray-500">(base budget: ${{ number_format($baseBudget, 2) }})</span>
                                @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-dollar-sign mr-1"></i> Paid: ${{ number_format($milestonePaid, 2) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">
                                <i class="fas fa-flag mr-1"></i> Milestones: {{ $milestones->count() }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-800">
                                <i class="fas fa-file-check mr-1"></i> File approvals: {{ $approvedFiles }} / {{ $totalFiles }}
                            </span>
                                <button wire:click="toggleSplitForm" type="button" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-purple-200 text-purple-700 hover:bg-purple-50">
                                    <i class="fas fa-divide mr-1"></i> Split budget
                                </button>
                        </div>
                    </div>
                    @if($milestones->count())
                        <div class="mb-4">
                            @php($percentPaid = $milestoneTotal > 0 ? round(($milestonePaid / max($milestoneTotal, 0.01)) * 100) : 0)
                            <div class="w-full h-2 rounded-full {{ $workflowColors['accent_bg'] }} overflow-hidden">
                                <div class="h-2 bg-gradient-to-r from-purple-500 to-indigo-600 transition-all duration-300" style="width: {{ $percentPaid }}%"></div>
                            </div>
                            <div class="mt-1 text-xs {{ $workflowColors['text_muted'] }}">{{ $percentPaid }}% paid</div>
                        </div>

                        <div
                            x-data="{
                                init() {
                                    if (window.Sortable) {
                                        const el = this.$refs.milestoneList;
                                        window.Sortable.create(el, {
                                            animation: 150,
                                            handle: '.drag-handle',
                                            onEnd: (evt) => {
                                                const orderedIds = Array.from(el.querySelectorAll('[data-id]')).map(e => e.getAttribute('data-id'));
                                                this.$wire.reorderMilestones(orderedIds);
                                            }
                                        });
                                    }
                                }
                            }"
                            x-init="init()"
                        >
                            <div class="divide-y {{ $workflowColors['accent_border'] }}" x-ref="milestoneList">
                            @foreach($milestones as $m)
                                <div class="py-3 flex items-center justify-between" data-id="{{ $m->id }}">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 flex items-center gap-2">
                                            {{ $m->name }}
                                            @if($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px]"><i class="fas fa-dollar-sign mr-1"></i> Paid</span>
                                            @elseif($m->status === 'approved')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-purple-100 text-purple-800 text-[10px]"><i class="fas fa-check mr-1"></i> Approved</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[10px]"><i class="fas fa-clock mr-1"></i> Pending</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600">Status: {{ ucfirst($m->status) }} @if($m->payment_status) • Payment: {{ str_replace('_',' ', $m->payment_status) }} @endif</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-sm font-semibold text-gray-900">${{ number_format($m->amount, 2) }}</div>
                                        <span class="drag-handle cursor-move inline-flex items-center justify-center w-8 h-8 rounded-md bg-white border border-gray-200 text-gray-500 hover:text-gray-700" title="Drag to reorder">
                                            <i class="fas fa-grip-vertical"></i>
                                        </span>
                                        <button wire:click="beginEditMilestone({{ $m->id }})" class="px-3 py-1.5 text-xs rounded-md bg-white border border-gray-200 hover:bg-gray-50">Edit</button>
                                        <button wire:click="deleteMilestone({{ $m->id }})" wire:confirm="Delete this milestone?" class="px-3 py-1.5 text-xs rounded-md bg-white border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6 text-sm text-gray-600">No milestones yet.</div>
                    @endif

                    @if($showMilestoneForm)
                        <div class="mt-4 border {{ $workflowColors['border'] }} rounded-xl bg-white dark:bg-gray-800 p-4">
                            <h6 class="font-semibold {{ $workflowColors['text_primary'] }} mb-3">{{ $editingMilestoneId ? 'Edit Milestone' : 'Add Milestone' }}</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" wire:model.defer="milestoneName" class="w-full border rounded-md px-3 py-2 text-sm" placeholder="e.g., Initial Deposit" />
                                    @error('milestoneName')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
                                    <input type="number" step="0.01" wire:model.defer="milestoneAmount" class="w-full border rounded-md px-3 py-2 text-sm" placeholder="0.00" />
                                    @error('milestoneAmount')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                    <textarea rows="2" wire:model.defer="milestoneDescription" class="w-full border rounded-md px-3 py-2 text-sm" placeholder="Optional details..."></textarea>
                                    @error('milestoneDescription')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sort Order</label>
                                    <input type="number" wire:model.defer="milestoneSortOrder" class="w-full border rounded-md px-3 py-2 text-sm" placeholder="0" />
                                    @error('milestoneSortOrder')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                <flux:button wire:click="saveMilestone" variant="primary">Save</flux:button>
                                <flux:button wire:click="cancelMilestoneForm" variant="outline">Cancel</flux:button>
                                @if($pitch->project && $pitch->project->budget)
                                <flux:button type="button" wire:click="$set('milestoneAmount', {{ number_format($pitch->project->budget, 2, '.', '') }})" variant="ghost" size="sm" class="{{ $workflowColors['text_secondary'] }} hover:{{ $workflowColors['accent_bg'] }}">Use total budget</flux:button>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($showSplitForm)
                        <div class="mt-4 border {{ $workflowColors['border'] }} rounded-xl bg-white dark:bg-gray-800 p-4">
                            <h6 class="font-semibold {{ $workflowColors['text_primary'] }} mb-3">Split Total Budget</h6>
                            <div class="flex items-center gap-3">
                                <flux:label class="text-sm">Number of milestones</flux:label>
                                <flux:input type="number" min="2" max="20" wire:model.defer="splitCount" class="w-28" />
                                <flux:button wire:click="splitBudgetIntoMilestones" variant="primary">Create</flux:button>
                                <flux:button type="button" wire:click="toggleSplitForm" variant="outline">Cancel</flux:button>
                            </div>
                            <p class="mt-2 text-xs {{ $workflowColors['text_muted'] }}">Splits the project budget into equal parts. The last milestone gets the rounding remainder.</p>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- File Management Section -->
            <flux:card class="mb-2 {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                <div class="border-b {{ $workflowColors['border'] }}">
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                        <flux:icon name="folder" class="mr-2 {{ $workflowColors['icon'] }}" />
                        File Management
                    </flux:heading>
                    <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">Manage client references and your deliverables</flux:text>
                </div>

                <div>

                <!-- Storage Indicator -->
                <x-file-management.storage-indicator 
                    :storageUsedPercentage="$storageUsedPercentage"
                    :storageLimitMessage="$storageLimitMessage"
                    :storageRemaining="$this->formatFileSize($storageRemaining)" />

                <!-- Client Reference Files Section -->
                <div class="{{ $semanticColors['success']['bg'] }} border {{ $semanticColors['success']['border'] }} rounded-lg mb-6">
                    <div class="p-2 border-b {{ $semanticColors['success']['border'] }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <flux:icon.folder-open variant="solid" class="w-8 h-8 {{ $semanticColors['success']['icon'] }}" />
                                <div>
                                    <flux:heading size="lg" class="{{ $semanticColors['success']['text'] }}">
                                        Client Reference Files
                                        <flux:badge variant="outline" size="sm" class="ml-2">
                                            {{ $this->clientFiles->count() }} files
                                        </flux:badge>
                                    </flux:heading>
                                    <flux:text size="sm" class="{{ $semanticColors['success']['icon'] }}">Files uploaded by your client to provide project requirements, references, or examples</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                    
                    @if($this->clientFiles->count() > 0)
                            <div class="space-y-4">
                                @foreach($this->clientFiles as $file)
                                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex h-10 w-10 items-center justify-center rounded-lg">
                                                    <flux:icon name="document" size="sm" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <flux:text weight="semibold" class="text-gray-900 dark:text-gray-100">{{ $file->file_name }}</flux:text>
                                                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                                                        {{ $this->formatFileSize($file->size) }} • 
                                                        Uploaded {{ $file->created_at->diffForHumans() }}
                                                        @if(isset($file->metadata) && json_decode($file->metadata)?->uploaded_by_client)
                                                            • <span class="font-medium">Client Upload</span>
                                                        @endif
                                                    </flux:text>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-2">
                                                <flux:button 
                                                    wire:click="downloadClientFile({{ $file->id }})" 
                                                    variant="outline"
                                                    size="sm"
                                                    icon="arrow-down-tray"
                                                    class="sm:px-3">
                                                    <span class="hidden sm:inline">Download</span>
                                                </flux:button>
                                                <flux:button 
                                                    wire:click="confirmDeleteClientFile({{ $file->id }})" 
                                                    variant="danger"
                                                    size="sm"
                                                    icon="trash"
                                                    class="sm:px-3">
                                                    <span class="hidden sm:inline">Delete</span>
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mx-auto mb-4">
                                    <flux:icon.inbox class="w-8 h-8 text-gray-400" />
                                </div>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">No client files yet. Your client can upload reference files through their portal.</flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Producer Deliverables Section -->
                <div data-section="producer-deliverables" class="{{ $workflowColors['bg'] }} border {{ $workflowColors['border'] }} rounded-lg">
                    <div class="p-2 border-b {{ $workflowColors['border'] }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <flux:icon.musical-note variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                <div>
                                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                        Your Deliverables
                                        <flux:badge variant="outline" size="sm" class="ml-2">
                                            {{ $this->producerFiles->count() }} files
                                        </flux:badge>
                                    </flux:heading>
                                    <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">Upload your work files here. These will be visible to your client for review</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-2">

                    <!-- Upload Section is rendered by workflow-actions → upload-work-section; avoid duplicate here -->

                    <!-- Producer Files List -->
                    @if($this->producerFiles->count() > 0)
                        <div class="space-y-4 mt-4">
                            @foreach($this->producerFiles as $file)
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow" x-data="{ showComments: false }">
                                    {{-- File Header --}}
                                    <div class="p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 flex h-10 w-10 items-center justify-center rounded-lg">
                                                    <flux:icon name="musical-note" size="sm" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <flux:text weight="semibold" class="text-gray-900 dark:text-gray-100">{{ $file->file_name }}</flux:text>
                                                    
                                                        @if($file->client_approval_status === 'approved')
                                                            <flux:badge variant="success" size="xs">
                                                                <flux:icon name="check-circle" size="xs" class="mr-1" /> Approved by client
                                                            </flux:badge>
                                                        @elseif($file->client_approval_status === 'revision_requested')
                                                            <flux:badge variant="warning" size="xs">
                                                                <flux:icon name="pencil" size="xs" class="mr-1" /> Revision requested
                                                            </flux:badge>
                                                        @endif
                                                    
                                                    {{-- Client Comments Badge --}}
                                                    @php($fileComments = $pitch->events()->where('event_type', 'client_file_comment')->where('metadata->file_id', $file->id)->orderBy('created_at', 'desc')->get())
                                                    
                                                        @if($fileComments->count() > 0)
                                                            <flux:button 
                                                                @click="showComments = !showComments" 
                                                                variant="outline"
                                                                size="xs">
                                                                <flux:icon name="chat-bubble-left-ellipsis" size="xs" class="mr-1" />
                                                                {{ $fileComments->count() }} comment{{ $fileComments->count() > 1 ? 's' : '' }}
                                                            </flux:button>
                                                        @endif
                                                    </div>
                                                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400 mt-1">
                                                        {{ $this->formatFileSize($file->size) }} • 
                                                        Uploaded {{ $file->created_at->diffForHumans() }}
                                                        @if($fileComments->count() > 0)
                                                            • <span class="text-blue-600 font-medium">Client feedback available</span>
                                                        @endif
                                                    </flux:text>
                                                </div>
                                            </div>
                                            
                                            {{-- Action Buttons --}}
                                            <div class="flex gap-2">
                                                <flux:button 
                                                    wire:click="downloadFile({{ $file->id }})" 
                                                    variant="outline"
                                                    size="sm"
                                                    icon="arrow-down-tray"
                                                    class="sm:px-3">
                                                </flux:button>
                                                @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED, \App\Models\Pitch::STATUS_READY_FOR_REVIEW]))
                                                <flux:button 
                                                    wire:click="confirmDeleteFile({{ $file->id }})" 
                                                    variant="danger"
                                                    size="sm"
                                                    icon="trash">
                                                </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Client File Comments Section --}}
                                    @if($fileComments->count() > 0)
                                        <div x-show="showComments" 
                                             x-collapse
                                             class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 p-4">
                                            <div class="mb-3">
                                                <flux:text weight="semibold" size="sm" class="text-gray-900 dark:text-gray-100 flex items-center">
                                                    <flux:icon name="chat-bubble-left-ellipsis" class="mr-2 text-blue-600" />
                                                    Client Feedback for this File
                                                </flux:text>
                                            </div>
                                            
                                            <div class="space-y-3 max-h-48 overflow-y-auto">
                                                @foreach($fileComments as $comment)
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                                        <div class="flex items-start justify-between mb-2">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                                                    <flux:icon name="user" size="xs" class="text-white" />
                                                                </div>
                                                                <div>
                                                                    <flux:text weight="medium" size="sm" class="text-gray-900 dark:text-gray-100">
                                                                        {{ $project->client_name ?: 'Client' }}
                                                                    </flux:text>
                                                                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                                                                        {{ $comment->created_at->diffForHumans() }}
                                                                    </flux:text>
                                                                </div>
                                                            </div>
                                                            
                                                            @if($comment->metadata['type'] ?? null === 'revision_request')
                                                                <flux:badge variant="warning" size="xs">
                                                                    <flux:icon name="pencil" size="xs" class="mr-1" />Revision Request
                                                                </flux:badge>
                                                            @elseif($comment->metadata['type'] ?? null === 'approval')
                                                                <flux:badge variant="success" size="xs">
                                                                    <flux:icon name="check" size="xs" class="mr-1" />Approved
                                                                </flux:badge>
                                                            @endif
                                                        </div>
                                                        
                                                        <flux:text size="sm" class="text-gray-800 dark:text-gray-200 leading-relaxed">
                                                            {{ $comment->comment }}
                                                        </flux:text>
                                                        
                                                        {{-- Quick Response for Revision Requests --}}
                                                        @if(($comment->metadata['type'] ?? null) === 'revision_request' && !($comment->metadata['responded'] ?? false))
                                                            <div class="mt-3 pt-3 border-t border-blue-200">
                                                                <div class="flex gap-2">
                                                                    <button wire:click="markFileCommentResolved({{ $comment->id }})" 
                                                                            class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-800 rounded-md text-xs font-medium transition-colors">
                                                                        <i class="fas fa-check mr-1"></i>Mark as Addressed
                                                                    </button>
                                                                    <button @click="showResponse = !showResponse" 
                                                                            class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-md text-xs font-medium transition-colors">
                                                                        <i class="fas fa-reply mr-1"></i>Respond
                                                                    </button>
                                                                </div>
                                                                
                                                                <div x-data="{ showResponse: false }" 
                                                                     x-show="showResponse" 
                                                                     x-collapse 
                                                                     class="mt-3">
                                                                    <form wire:submit.prevent="respondToFileComment({{ $comment->id }})">
                                                                        <textarea wire:model.defer="fileCommentResponse.{{ $comment->id }}" 
                                                                                  rows="2"
                                                                                  class="w-full px-3 py-2 text-sm border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                                                  placeholder="Explain how you've addressed this feedback..."></textarea>
                                                                        <div class="mt-2 flex gap-2">
                                                                            <button type="submit" 
                                                                                    class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-medium">
                                                                                <i class="fas fa-paper-plane mr-1"></i>Send Response
                                                                            </button>
                                                                            <button type="button" 
                                                                                    @click="showResponse = false" 
                                                                                    class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-medium">
                                                                                Cancel
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-t border-blue-200">
                                                <p class="text-xs text-blue-600">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    This feedback is specific to the "{{ $file->file_name }}" file. 
                                                    General project communication should use the main message area below.
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Audio Player for Audio Files --}}
                                    @if(in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                        <div class="border-t border-green-200 bg-white p-3" wire:ignore>
                                            @livewire('pitch-file-player', [
                                                'file' => $file,
                                                'isInCard' => true
                                            ], key('pitch-player-'.$file->id))
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 mt-4">
                            <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-100 to-emerald-100 rounded-full mx-auto mb-4">
                                <i class="fas fa-cloud-upload-alt text-green-500 text-xl"></i>
                            </div>
                            <p class="text-green-600 text-sm">No deliverables uploaded yet. Use the upload area above to add files.</p>
                        </div>
                    @endif
                    </div>
                </div>
                </div>
            </flux:card>

            <!-- Response to Feedback Section (if applicable) -->
            @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
            <flux:card class="mb-2 bg-amber-50 border-amber-200">
                <div class="p-6 border-b border-amber-200">
                    <flux:heading size="lg">
                        <flux:icon name="arrow-uturn-left" class="mr-2" />
                        Respond to Feedback
                    </flux:heading>
                </div>
                <div class="p-6">
                    <flux:field>
                        <flux:label>Your Response to Client Feedback</flux:label>
                        <flux:textarea wire:model.lazy="responseToFeedback" 
                                      rows="4"
                                      placeholder="Explain what changes you've made in response to the feedback..." />
                        <flux:error name="responseToFeedback" />
                    </flux:field>
                </div>
            </flux:card>
            @endif

            <!-- Submit for Review Section -->
            @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED]))
            <flux:card class="mb-2">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <flux:heading size="lg">
                        <flux:icon name="paper-airplane" class="mr-2" />
                        Ready to Submit for Review?
                    </flux:heading>
                    <flux:text size="sm" class="text-gray-600">Submit your work to your client for review and approval</flux:text>
                </div>
                <div>

                @if($this->producerFiles->count() === 0)
                    <div class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 border border-amber-200/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg mr-3">
                                <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-amber-800">No deliverables uploaded</h5>
                                <p class="text-sm text-amber-700">You need to upload at least one file before submitting for review.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 border border-green-200/50 rounded-xl p-4 mb-4 backdrop-blur-sm">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-white text-sm"></i>
                            </div>
                            <div>
                                <h5 class="font-semibold text-green-800">{{ $this->producerFiles->count() }} {{ Str::plural('file', $this->producerFiles->count()) }} ready</h5>
                                <p class="text-sm text-green-700">Your deliverables are ready to be submitted to the client.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- NEW: Watermarking Toggle Section -->
                @if($this->producerFiles->count() > 0)
                <div class="bg-white/60 backdrop-blur-sm border border-purple-200/30 rounded-xl p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <h5 class="font-semibold text-purple-900 mr-2">Audio Protection</h5>
                            <button wire:click="$toggle('showWatermarkingInfo')" 
                                    class="text-purple-600 hover:text-purple-800 transition-colors">
                                <i class="fas fa-info-circle text-sm"></i>
                            </button>
                        </div>
                        
                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="watermarkingEnabled"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            <span class="ml-3 text-sm font-medium text-purple-900">
                                {{ $watermarkingEnabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>
                    </div>
                    
                    <!-- Information Panel -->
                    @if($showWatermarkingInfo)
                    <div class="bg-purple-50/50 border border-purple-200/50 rounded-lg p-3 text-sm text-purple-800 mb-3">
                        <p class="mb-2"><strong>Audio Protection adds a subtle watermark to your files during client review.</strong></p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Protects your intellectual property during the review phase</li>
                            <li>Client receives clean, unwatermarked files after approval and payment</li>
                            <li>Processing takes 30-60 seconds per audio file</li>
                            <li>Does not affect non-audio files (PDFs, images, etc.)</li>
                        </ul>
                    </div>
                    @endif
                    
                    <!-- Audio Files Preview -->
                    @if($this->audioFiles->count() > 0)
                    <div class="mt-3 p-3 bg-purple-50/30 rounded-lg">
                        <p class="text-xs text-purple-700 font-medium mb-2">
                            {{ $this->audioFiles->count() }} audio file(s) will be {{ $watermarkingEnabled ? 'processed with watermarking' : 'submitted without processing' }}:
                        </p>
                        <ul class="text-xs text-purple-600 space-y-1">
                            @foreach($this->audioFiles->take(3) as $file)
                            <li class="flex items-center">
                                <i class="fas fa-music mr-2"></i>
                                {{ $file->file_name }}
                                @if($watermarkingEnabled && $file->audio_processed)
                                    <span class="ml-2 text-green-600">(Already processed)</span>
                                @endif
                            </li>
                            @endforeach
                            @if($this->audioFiles->count() > 3)
                            <li class="text-purple-500 italic">... and {{ $this->audioFiles->count() - 3 }} more</li>
                            @endif
                        </ul>
                    </div>
                    @else
                    <div class="mt-3 p-3 bg-gray-50/30 rounded-lg">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-2"></i>
                            No audio files detected. Watermarking only affects audio files (MP3, WAV, etc.).
                        </p>
                    </div>
                    @endif
                </div>
                @endif

                @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]) && $responseToFeedback)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h5 class="font-medium text-blue-800 mb-2">Your Response to Feedback:</h5>
                        <p class="text-sm text-blue-700 italic">{{ $responseToFeedback }}</p>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3">
                    @if($this->producerFiles->count() > 0)
                        <button wire:click="submitForReview" 
                                wire:loading.attr="disabled"
                                class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg transition-all duration-200 hover:scale-105 hover:shadow-xl disabled:opacity-50 disabled:transform-none relative overflow-hidden group">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span wire:loading wire:target="submitForReview" class="inline-block w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mr-3"></span>
                            <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane mr-3 relative z-10"></i>
                            <span class="relative z-10">
                                @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
                                    Submit Revisions
                                @else
                                    Submit for Review
                                @endif
                            </span>
                        </button>
                    @else
                        <button disabled
                                class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gray-400 text-white rounded-xl font-bold text-lg opacity-50 cursor-not-allowed">
                            <i class="fas fa-paper-plane mr-3"></i>
                            Submit for Review
                        </button>
                    @endif
                    
                    <button onclick="window.scrollTo({top: document.querySelector('[data-section=producer-deliverables]').offsetTop - 100, behavior: 'smooth'})"
                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-100 to-indigo-100 hover:from-purple-200 hover:to-indigo-200 text-purple-800 border border-purple-300 rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-md">
                        <i class="fas fa-upload mr-3"></i>Upload More Files
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-sm text-purple-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Once submitted, your client will receive an email notification and can review your work through their secure portal.
                    </p>
                </div>
            </div>
            </flux:card>
            @endif
                        </div>
                        
                        <!-- Sidebar (1/3 width on large screens) -->
                        <div class="space-y-2 lg:col-span-1">
                            {{-- Client Management Workflow Information --}}
                            <flux:card class="mb-2 hidden lg:block {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                <div class="flex items-center gap-3 mb-6">
                                    <flux:icon.users variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                    <div>
                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Client Management</flux:heading>
                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Private client workflow</flux:subheading>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="p-4 {{ $workflowColors['accent_bg'] }} rounded-xl border {{ $workflowColors['accent_border'] }}">
                                        <div class="flex items-start gap-3">
                                            <flux:icon.shield-check class="w-6 h-6 {{ $workflowColors['icon'] }} flex-shrink-0 mt-0.5" />
                                            <div>
                                                <flux:subheading class="{{ $workflowColors['text_primary'] }} font-semibold mb-1">Private Workflow</flux:subheading>
                                                <p class="text-sm {{ $workflowColors['text_secondary'] }}">Work exclusively with your client through a secure, private workflow.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 {{ $workflowColors['accent_bg'] }} rounded-xl border {{ $workflowColors['accent_border'] }}">
                                        <div class="flex items-start gap-3">
                                            <flux:icon.envelope class="w-6 h-6 {{ $workflowColors['icon'] }} flex-shrink-0 mt-0.5" />
                                            <div>
                                                <flux:subheading class="{{ $workflowColors['text_primary'] }} font-semibold mb-1">Client Portal</flux:subheading>
                                                <p class="text-sm {{ $workflowColors['text_secondary'] }}">Your client reviews and approves work through their dedicated portal.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 {{ $workflowColors['accent_bg'] }} rounded-xl border {{ $workflowColors['accent_border'] }}">
                                        <div class="flex items-start gap-3">
                                            <flux:icon.currency-dollar class="w-6 h-6 {{ $workflowColors['icon'] }} flex-shrink-0 mt-0.5" />
                                            <div>
                                                <flux:subheading class="{{ $workflowColors['text_primary'] }} font-semibold mb-1">Direct Payment</flux:subheading>
                                                <p class="text-sm {{ $workflowColors['text_secondary'] }}">Get paid immediately after client approval with no waiting period.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </flux:card>

                            {{-- Client Information --}}
                            @if($project->client_name || $project->client_email)
                            <flux:card class="mb-2 {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                <div class="flex items-center gap-3 mb-4">
                                    <flux:icon.user-circle variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                    <div>
                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Client Information</flux:heading>
                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Project stakeholder details</flux:subheading>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    @if($project->client_name)
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <flux:icon.user class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                            <div>
                                                <flux:subheading class="font-medium">{{ $project->client_name }}</flux:subheading>
                                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Client Name</flux:text>
                                            </div>
                                        </div>
                                    @endif
                                    @if($project->client_email)
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <flux:icon.envelope class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                            <div>
                                                <flux:subheading class="font-medium">{{ $project->client_email }}</flux:subheading>
                                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Contact Email</flux:text>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </flux:card>
                            @endif



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm File Deletion</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this file? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteFile" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteFile" class="btn btn-error">Delete File</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Client File Delete Confirmation Modal -->
    @if($showDeleteClientFileModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Confirm Client File Deletion
            </h3>
            <p class="text-gray-600 mb-4">
                Are you sure you want to delete the client reference file: 
                <strong class="text-gray-900">{{ $clientFileNameToDelete }}</strong>?
            </p>
            <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded p-3 mb-6">
                <i class="fas fa-info-circle mr-2"></i>
                This file was uploaded by your client. Once deleted, they will need to re-upload it if needed.
            </p>
            <p class="text-red-600 font-medium mb-6">This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteClientFile" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteClientFile" class="btn btn-error">
                    <i class="fas fa-trash mr-2"></i>Delete File
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Project Delete Confirmation Modal -->
    @if($showProjectDeleteModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-red-800 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>Delete Project
            </h3>
            <p class="text-gray-600 mb-4">
                Are you sure you want to permanently delete this project? This will also delete:
            </p>
            <ul class="text-sm text-gray-600 mb-6 list-disc list-inside">
                <li>All project files</li>
                <li>All pitch files and data</li>
                <li>All project history and events</li>
            </ul>
            <p class="text-red-600 font-medium mb-6">This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDeleteProject" class="btn btn-outline">Cancel</button>
                <button wire:click="deleteProject" class="btn btn-error">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Project
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- JavaScript for File Annotations -->
    <script>
        // Global function to expand comment details
        function expandComment(fileId, commentId) {
            console.log('Expand comment', commentId, 'for file', fileId);
            
            // Show a notification with comment details
            showNotification('Comment details view - Comment ID: ' + commentId + ' (Full modal coming soon)');
        }

        // Helper function to show notifications
        function showNotification(message) {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Simple cleanup - no special handling needed
        console.log('ManageClientProject initialized');

    </script>

    {{-- Mobile Floating Action Button --}}
    @if($pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED)
        {{-- Revision Response FAB --}}
        <div class="fixed bottom-6 right-6 lg:hidden z-50" x-data="{ showTooltip: false }">
            <button @click="handleFabAction('scrollToRevisionResponse')" 
                    @mouseenter="showTooltip = true" 
                    @mouseleave="showTooltip = false"
                    class="bg-gradient-to-r from-amber-600 to-orange-600 hover:shadow-2xl text-white rounded-full w-14 h-14 shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center">
                <i class="fas fa-edit text-lg"></i>
            </button>
            
            <div x-show="showTooltip" 
                 x-transition
                 class="absolute bottom-full right-0 mb-2 bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap">
                Respond to Feedback
                <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
            </div>
        </div>
        
    @elseif($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
        {{-- Communication FAB --}}
        <div class="fixed bottom-6 right-6 lg:hidden z-50" x-data="{ showTooltip: false }">
            <button @click="handleFabAction('scrollToCommunication')" 
                    @mouseenter="showTooltip = true" 
                    @mouseleave="showTooltip = false"
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:shadow-2xl text-white rounded-full w-14 h-14 shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center">
                <i class="fas fa-comment text-lg"></i>
            </button>
            
            <div x-show="showTooltip" 
                 x-transition
                 class="absolute bottom-full right-0 mb-2 bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap">
                Send Message
                <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
            </div>
        </div>
        
    @else
        {{-- In Progress / Default FAB --}}
        @if($this->producerFiles->count() === 0)
            {{-- Upload Files FAB --}}
            <div class="fixed bottom-6 right-6 lg:hidden z-50" x-data="{ showTooltip: false }">
                <button @click="handleFabAction('scrollToUpload')" 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 hover:shadow-2xl text-white rounded-full w-14 h-14 shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center">
                    <i class="fas fa-upload text-lg"></i>
                </button>
                
                <div x-show="showTooltip" 
                     x-transition
                     class="absolute bottom-full right-0 mb-2 bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap">
                    Upload Files
                    <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
                </div>
            </div>
        @else
            {{-- Submit for Review FAB --}}
            <div class="fixed bottom-6 right-6 lg:hidden z-50" x-data="{ showTooltip: false }">
                <button @click="handleFabAction('scrollToSubmit')" 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:shadow-2xl text-white rounded-full w-14 h-14 shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center">
                    <i class="fas fa-paper-plane text-lg"></i>
                </button>
                
                <div x-show="showTooltip" 
                     x-transition
                     class="absolute bottom-full right-0 mb-2 bg-gray-900 text-white text-xs rounded-lg py-2 px-3 whitespace-nowrap">
                    Submit for Review
                    <div class="absolute top-full right-3 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
                </div>
            </div>
        @endif
    @endif

    {{-- JavaScript for FAB Actions --}}
    <script>
        function handleFabAction(action) {
            switch(action) {
                case 'scrollToRevisionResponse':
                    // Scroll to revision response area
                    const responseArea = document.querySelector('textarea[wire\\:model\\.lazy="responseToFeedback"]');
                    if (responseArea) {
                        responseArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => responseArea.focus(), 800);
                    }
                    break;
                    
                case 'scrollToCommunication':
                    // Scroll to communication form
                    const commentArea = document.getElementById('newComment');
                    if (commentArea) {
                        commentArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => commentArea.focus(), 800);
                    }
                    break;
                    
                case 'scrollToUpload':
                    // Scroll to upload section
                    const uploadSection = document.querySelector('[data-section="producer-deliverables"], .uppy-Dashboard');
                    if (uploadSection) {
                        uploadSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    break;
                    
                case 'scrollToSubmit':
                    // Scroll to submit section
                    const submitButton = document.querySelector('button[wire\\:click="submitForReview"]');
                    if (submitButton) {
                        submitButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Add a subtle highlight effect
                        submitButton.classList.add('animate-pulse');
                        setTimeout(() => submitButton.classList.remove('animate-pulse'), 2000);
                    }
                    break;
            }
        }
    </script>
</div> 