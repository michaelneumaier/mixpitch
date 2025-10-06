@php
    // Unified Color System - Workflow-aware colors (matching manage-project.blade.php)
    $workflowColors = match ($project->workflow_type) {
        'standard' => [
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
            'accent_border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400',
        ],
        'contest' => [
            'bg' => 'bg-orange-50 dark:bg-orange-950',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text_primary' => 'text-orange-900 dark:text-orange-100',
            'text_secondary' => 'text-orange-700 dark:text-orange-300',
            'text_muted' => 'text-orange-600 dark:text-orange-400',
            'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
            'accent_border' => 'border-orange-200 dark:border-orange-800',
            'icon' => 'text-orange-600 dark:text-orange-400',
        ],
        'direct_hire' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300',
            'text_muted' => 'text-green-600 dark:text-green-400',
            'accent_bg' => 'bg-green-100 dark:bg-green-900',
            'accent_border' => 'border-green-200 dark:border-green-800',
            'icon' => 'text-green-600 dark:text-green-400',
        ],
        'client_management' => [
            'bg' => 'bg-purple-50 dark:bg-purple-950',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
            'accent_border' => 'border-purple-200 dark:border-purple-800',
            'icon' => 'text-purple-600 dark:text-purple-400',
        ],
        default => [
            'bg' => 'bg-gray-50 dark:bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
            'accent_border' => 'border-gray-200 dark:border-gray-800',
            'icon' => 'text-gray-600 dark:text-gray-400',
        ],
    };

    // Semantic colors (always consistent regardless of workflow)
    $semanticColors = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-600 dark:bg-green-500',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'accent' => 'bg-amber-500',
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500',
        ],
    ];
@endphp

<x-draggable-upload-page :model="$pitch" title="Manage Client Project: {{ $project->title }}">
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="mx-auto p-2">
                <div class="mx-auto">
                    <div class="flex justify-center">
                        <div class="w-full">
                            <!-- Enhanced Project Header -->
                            <x-project.header :project="$project" context="manage" :showActions="true" :showEditButton="true"
                                :showWorkflowStatus="true" />

                            <div class="grid grid-cols-1 lg:gap-2 lg:grid-cols-3">
                                <!-- Main Content Area (70% width on large screens) -->
                                <div class="space-y-2 lg:col-span-2">
                                    <!-- File Management Tabs -->
                                    <div x-data="{ activeTab: 'deliverables' }" class="mb-2">
                                        <!-- Tab Navigation -->
                                        <div class="mb-2 border-b border-gray-200 dark:border-gray-700">
                                            <nav class="-mb-px flex space-x-1 md:space-x-8">
                                                <button @click="activeTab = 'deliverables'"
                                                    :class="activeTab === 'deliverables' ? 'border-purple-500 text-purple-600' :
                                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                                                    <flux:icon.musical-note class="mr-1 inline h-4 w-4" />
                                                    <span class="hidden md:inline">Your</span> Deliverables
                                                    <flux:badge variant="outline" size="sm" class="ml-1">
                                                        {{ $this->producerFiles->count() }} files
                                                    </flux:badge>
                                                </button>
                                                <button @click="activeTab = 'client-files'"
                                                    :class="activeTab === 'client-files' ? 'border-purple-500 text-purple-600' :
                                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                                                    <flux:icon.folder-open class="mr-1 inline h-4 w-4" />
                                                    Client <span class="hidden md:inline">Reference</span> Files
                                                </button>
                                            </nav>
                                        </div>

                                        <!-- Tab Content -->
                                        <!-- Client Reference Files Tab -->
                                        <div x-show="activeTab === 'client-files'" x-transition>
                                            <flux:card
                                                class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }}">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.folder-open variant="solid"
                                                        class="{{ $semanticColors['success']['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="base"
                                                            class="{{ $semanticColors['success']['text'] }}">
                                                            Client Reference Files
                                                        </flux:heading>
                                                        <flux:subheading
                                                            class="{{ $semanticColors['success']['icon'] }}">
                                                            Files uploaded by your client to provide project
                                                            requirements,
                                                            references, or examples</flux:subheading>
                                                    </div>
                                                </div>

                                                @livewire(
                                                    'components.file-list',
                                                    [
                                                        'files' => $this->clientFiles,
                                                        'modelType' => 'project',
                                                        'modelId' => $project->id,
                                                        'colorScheme' => [
                                                            'bg' => $semanticColors['success']['bg'],
                                                            'border' => $semanticColors['success']['border'],
                                                            'text_primary' => $semanticColors['success']['text'],
                                                            'text_secondary' => $semanticColors['success']['text'],
                                                            'text_muted' => $semanticColors['success']['icon'],
                                                            'accent_bg' => 'bg-green-100 dark:bg-green-900',
                                                            'accent_border' => $semanticColors['success']['border'],
                                                            'icon' => $semanticColors['success']['icon'],
                                                        ],
                                                        'canPlay' => true,
                                                        'canDownload' => true,
                                                        'canDelete' => true,
                                                        'downloadMethod' => 'downloadClientFile',
                                                        'deleteMethod' => 'confirmDeleteClientFile',
                                                        'emptyStateMessage' => 'No client files yet',
                                                        'emptyStateSubMessage' => 'Your client can upload reference files through their portal',
                                                        'headerIcon' => 'folder-open',
                                                        'emptyIcon' => 'inbox',
                                                    ],
                                                    key('client-files-list-' . $project->id . '-' . $refreshKey)
                                                )

                                                <!-- Link Import Section -->
                                                <div class="mt-6 border-t border-green-200 pt-6 dark:border-green-800">
                                                    <div class="mb-4 flex items-center justify-between">
                                                        <div>
                                                            <flux:heading size="sm"
                                                                class="{{ $semanticColors['success']['text'] }}">
                                                                Import from Link
                                                            </flux:heading>
                                                            <flux:text size="xs"
                                                                class="{{ $semanticColors['success']['icon'] }}">
                                                                Import files from WeTransfer, Google Drive, Dropbox, or
                                                                OneDrive
                                                            </flux:text>
                                                        </div>
                                                    </div>

                                                    @livewire('link-importer', ['project' => $project], key('link-importer-' . $project->id))
                                                </div>
                                            </flux:card>
                                        </div>

                                        <!-- Your Deliverables Tab -->
                                        <div x-show="activeTab === 'deliverables'" x-transition>
                                            <!-- Producer Deliverables Section -->
                                            <flux:card data-section="producer-deliverables"
                                                class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.musical-note variant="solid"
                                                        class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="base"
                                                            class="{{ $workflowColors['text_primary'] }}">
                                                            Your Deliverables
                                                            <flux:badge variant="outline" size="sm" class="ml-2">
                                                                {{ $this->producerFiles->count() }} files
                                                            </flux:badge>
                                                        </flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                                                            Upload
                                                            your
                                                            work files here. These will be visible to your client for
                                                            review
                                                        </flux:subheading>
                                                    </div>
                                                </div>

                                                <!-- Upload Section for Producer Deliverables -->
                                                <div class="mb-6">
                                                    <x-file-management.upload-section :model="$pitch"
                                                        title="Upload Deliverables"
                                                        description="Upload your work files here. These will be visible to your client for review" />
                                                </div>

                                                <!-- Producer Files List -->
                                                @livewire(
                                                    'components.file-list',
                                                    [
                                                        'files' => $this->producerFiles,
                                                        'modelType' => 'pitch',
                                                        'modelId' => $pitch->id,
                                                        'colorScheme' => $workflowColors,
                                                        'canPlay' => true,
                                                        'canDownload' => true,
                                                        'canDelete' => in_array($pitch->status, [\App\Models\Pitch::STATUS_IN_PROGRESS, \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_DENIED, \App\Models\Pitch::STATUS_READY_FOR_REVIEW]),
                                                        'playMethod' => 'playPitchFile',
                                                        'downloadMethod' => 'downloadFile',
                                                        'deleteMethod' => 'confirmDeleteFile',
                                                        'showComments' => true,
                                                        'commentsData' => $this->fileCommentsData,
                                                        'enableCommentCreation' => true,
                                                        'headerIcon' => 'musical-note',
                                                        'emptyStateMessage' => 'No deliverables uploaded yet',
                                                        'emptyStateSubMessage' => 'Use the upload area above to add files',
                                                        'showFileCount' => false,
                                                    ],
                                                    key('producer-files-list-' . $pitch->id . '-' . $refreshKey)
                                                )
                                            </flux:card>
                                        </div>
                                    </div>

                                    <!-- Response to Feedback Section (if applicable) -->
                                    @if (in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                            \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                                        ]))
                                        @livewire(
                                            'project.component.response-to-feedback',
                                            [
                                                'pitch' => $pitch,
                                                'project' => $project,
                                                'workflowColors' => $workflowColors,
                                            ],
                                            key('response-feedback-' . $pitch->id . '-' . $pitch->updated_at->timestamp)
                                        )
                                    @endif

                                    <!-- Submit/Recall Section -->
                                    @livewire(
                                        'project.component.client-submit-section',
                                        [
                                            'project' => $project,
                                            'pitch' => $pitch,
                                            'workflowColors' => $workflowColors,
                                        ],
                                        key('client-submit-section-' . $pitch->id . '-' . $pitch->updated_at->timestamp)
                                    )
                                </div>
                                <!-- Sidebar (30% width on large screens) -->
                                <div class="space-y-4 lg:col-span-1">
                                    <!-- Client Communication Hub -->

                                        <x-client-project.client-communication-hub :component="$this" :project="$project"
                                            :conversationItems="$this->conversationItems" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />

                                    <!-- Milestones Section -->
                                    <flux:card class="mb-4">
                                        <!-- Header -->
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <flux:icon.flag variant="solid"
                                                    class="{{ $workflowColors['icon'] }} h-6 w-6" />
                                                <div>
                                                    <flux:heading size="base"
                                                        class="{{ $workflowColors['text_primary'] }}">Milestone Payments
                                                    </flux:heading>
                                                    <flux:text size="xs"
                                                        class="{{ $workflowColors['text_muted'] }}">
                                                        Split payments & budget
                                                    </flux:text>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Budget Header Section -->
                                        <div class="mb-4 rounded-lg border-2 border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <flux:text size="sm" class="mb-1 font-medium text-purple-700 dark:text-purple-300">
                                                        Total Budget
                                                    </flux:text>
                                                    @if (!$showBudgetEditForm)
                                                        <div class="flex items-baseline gap-2">
                                                            <flux:heading size="2xl" class="font-bold text-purple-900 dark:text-purple-100">
                                                                ${{ number_format($this->getBaseClientBudget(), 2) }}
                                                            </flux:heading>
                                                        </div>
                                                    @else
                                                        <!-- Budget Edit Form -->
                                                        <div class="mt-2 flex items-center gap-2">
                                                            <div class="flex-1">
                                                                <flux:input
                                                                    type="number"
                                                                    step="0.01"
                                                                    wire:model.defer="editableBudget"
                                                                    placeholder="0.00"
                                                                    size="sm"
                                                                />
                                                            </div>
                                                            <div class="flex gap-1">
                                                                <flux:button wire:click="saveBudget" variant="primary" color="green" size="xs">
                                                                    <flux:icon.check class="h-3 w-3" />
                                                                </flux:button>
                                                                <flux:button wire:click="cancelBudgetEdit" variant="ghost" size="xs">
                                                                    <flux:icon.x-mark class="h-3 w-3" />
                                                                </flux:button>
                                                            </div>
                                                        </div>
                                                        @error('editableBudget')
                                                            <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                                                                {{ $message }}
                                                            </flux:text>
                                                        @enderror
                                                    @endif
                                                </div>
                                                @if (!$showBudgetEditForm)
                                                    <flux:button wire:click="toggleBudgetEdit" variant="ghost" size="xs">
                                                        <flux:icon.pencil class="h-4 w-4" />
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Allocation Tracker -->
                                        @php
                                            $allocationStatus = $this->allocationStatus;
                                        @endphp
                                        <div class="mb-4 rounded-lg border-2 p-3 {{ $allocationStatus['color']['border'] }} {{ $allocationStatus['color']['bg'] }}">
                                            <div class="mb-2 flex items-center justify-between">
                                                <flux:text size="sm" class="font-medium {{ $allocationStatus['color']['text'] }}">
                                                    Allocation Status
                                                </flux:text>
                                                <flux:text size="sm" class="font-semibold {{ $allocationStatus['color']['text'] }}">
                                                    ${{ number_format($allocationStatus['allocated'], 2) }} / ${{ number_format($allocationStatus['budget'], 2) }}
                                                </flux:text>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="mb-2 h-2 w-full overflow-hidden rounded-full bg-white/50 dark:bg-gray-800/50">
                                                <div class="h-2 rounded-full transition-all duration-300 {{ $allocationStatus['color']['bar'] }}"
                                                    style="width: {{ min($allocationStatus['percentage'], 100) }}%"></div>
                                            </div>

                                            <div class="flex items-center justify-between">
                                                <flux:text size="xs" class="{{ $allocationStatus['color']['text'] }}">
                                                    {{ $allocationStatus['message'] }}
                                                </flux:text>
                                                @if ($allocationStatus['status'] === 'perfect')
                                                    <flux:icon.check-circle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
                                                @elseif ($allocationStatus['status'] === 'under')
                                                    <flux:icon.exclamation-triangle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
                                                @elseif ($allocationStatus['status'] === 'over')
                                                    <flux:icon.exclamation-circle class="h-4 w-4 {{ $allocationStatus['color']['icon'] }}" />
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="mb-4 flex flex-wrap gap-2">
                                            <flux:button wire:click="beginAddMilestone" variant="primary" size="sm" icon="plus">
                                                Add Milestone
                                            </flux:button>
                                            <flux:button wire:click="toggleSplitForm" variant="outline" size="sm" icon="scissors">
                                                Split Budget
                                            </flux:button>
                                        </div>

                                        <!-- Milestones List -->
                                        @if ($this->milestones->count())
                                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                                @foreach ($this->milestones as $m)
                                                    <div
                                                        class="{{ $workflowColors['accent_bg'] }} rounded-lg p-3 text-sm">
                                                        <div class="flex items-start justify-between">
                                                            <div class="min-w-0 flex-1">
                                                                <div
                                                                    class="{{ $workflowColors['text_primary'] }} truncate font-medium">
                                                                    {{ $m->name }}
                                                                </div>
                                                                <div class="mt-1 flex items-center gap-2">
                                                                    <span
                                                                        class="{{ $workflowColors['text_secondary'] }} font-medium">
                                                                        ${{ number_format($m->amount, 2) }}
                                                                    </span>
                                                                    @if ($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                                        <flux:badge variant="success" size="xs">
                                                                            Paid
                                                                        </flux:badge>
                                                                    @elseif ($m->status === 'approved')
                                                                        <flux:badge variant="primary" size="xs">
                                                                            Approved</flux:badge>
                                                                    @else
                                                                        <flux:badge variant="outline" size="xs">
                                                                            Pending</flux:badge>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <flux:button
                                                                wire:click="beginEditMilestone({{ $m->id }})"
                                                                variant="ghost" size="xs">
                                                                <flux:icon.pencil class="h-3 w-3" />
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="py-4 text-center">
                                                <flux:text size="sm"
                                                    class="{{ $workflowColors['text_muted'] }}">
                                                    No milestones yet
                                                </flux:text>
                                            </div>
                                        @endif

                                        <!-- Milestone Forms (when editing) -->
                                        @if ($showMilestoneForm)
                                            <div
                                                class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mt-4 rounded-lg border p-4">
                                                <flux:heading size="sm"
                                                    class="{{ $workflowColors['text_primary'] }} mb-3">
                                                    {{ $editingMilestoneId ? 'Edit Milestone' : 'Add Milestone' }}
                                                </flux:heading>

                                                <!-- Remaining Budget Helper -->
                                                <div class="mb-3 rounded-lg border p-2 text-center
                                                    {{ $this->remainingBudgetForForm > 0 ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/20' }}">
                                                    <flux:text size="xs" class="{{ $this->remainingBudgetForForm > 0 ? 'text-blue-800 dark:text-blue-200' : 'text-gray-700 dark:text-gray-300' }}">
                                                        <span class="font-medium">Budget Available:</span>
                                                        ${{ number_format($this->remainingBudgetForForm, 2) }}
                                                    </flux:text>
                                                </div>

                                                <div class="space-y-3">
                                                    <flux:field>
                                                        <flux:label>Name</flux:label>
                                                        <flux:input type="text" wire:model.defer="milestoneName"
                                                            size="sm" placeholder="e.g., Initial Deposit" />
                                                        <flux:error name="milestoneName" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <div class="flex items-center justify-between">
                                                            <flux:label>Amount</flux:label>
                                                            @if (!$editingMilestoneId && $this->remainingBudgetForForm > 0)
                                                                <flux:button wire:click="$set('milestoneAmount', {{ $this->remainingBudgetForForm }})"
                                                                    variant="ghost" size="xs">
                                                                    <flux:icon.calculator class="h-3 w-3 mr-1" />
                                                                    Use Remaining
                                                                </flux:button>
                                                            @endif
                                                        </div>
                                                        <flux:input type="number" step="0.01" size="sm"
                                                            wire:model.defer="milestoneAmount" placeholder="0.00" />
                                                        @if ($milestoneAmount && $this->allocationStatus['budget'] > 0)
                                                            <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} mt-1">
                                                                {{ number_format(($milestoneAmount / $this->allocationStatus['budget']) * 100, 1) }}% of total budget
                                                            </flux:text>
                                                        @endif
                                                        <flux:error name="milestoneAmount" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label>Description (Optional)</flux:label>
                                                        <flux:textarea rows="2" size="sm"
                                                            wire:model.defer="milestoneDescription"
                                                            placeholder="Optional details..." />
                                                        <flux:error name="milestoneDescription" />
                                                    </flux:field>
                                                </div>
                                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                                    <flux:button wire:click="saveMilestone" variant="primary"
                                                        size="sm" icon="check">Save</flux:button>
                                                    <flux:button wire:click="cancelMilestoneForm" variant="outline"
                                                        size="sm">Cancel</flux:button>
                                                    @if ($editingMilestoneId)
                                                        @php
                                                            $editingMilestone = $pitch->milestones()->find($editingMilestoneId);
                                                        @endphp
                                                        @if ($editingMilestone && $editingMilestone->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                            <flux:button wire:click="deleteMilestone({{ $editingMilestoneId }})"
                                                                variant="danger" size="sm" class="ml-auto">
                                                                <flux:icon.trash class="h-3 w-3 mr-1" />
                                                                Delete
                                                            </flux:button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        @if ($showSplitForm)
                                            <div
                                                class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mt-4 rounded-lg border p-4">
                                                <flux:heading size="sm"
                                                    class="{{ $workflowColors['text_primary'] }} mb-3">
                                                    Split Budget into Milestones
                                                </flux:heading>
                                                <div class="space-y-4">
                                                    <!-- Template Selection -->
                                                    <flux:field>
                                                        <flux:label>Split Template</flux:label>
                                                        <div class="space-y-2">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="radio" wire:model.live="splitTemplate" value="equal"
                                                                    class="text-purple-600 focus:ring-purple-500">
                                                                <div>
                                                                    <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                                                                        Equal Split
                                                                    </flux:text>
                                                                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                                                                        Divide budget evenly across milestones
                                                                    </flux:text>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="radio" wire:model.live="splitTemplate" value="deposit_structure"
                                                                    class="text-purple-600 focus:ring-purple-500">
                                                                <div>
                                                                    <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                                                                        Deposit Structure
                                                                    </flux:text>
                                                                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                                                                        30% deposit / 40% progress / 30% final
                                                                    </flux:text>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="radio" wire:model.live="splitTemplate" value="percentage"
                                                                    class="text-purple-600 focus:ring-purple-500">
                                                                <div>
                                                                    <flux:text size="sm" class="font-medium {{ $workflowColors['text_primary'] }}">
                                                                        Custom Percentages
                                                                    </flux:text>
                                                                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                                                                        Define your own percentage split
                                                                    </flux:text>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </flux:field>

                                                    <!-- Equal Split Options -->
                                                    @if ($splitTemplate === 'equal')
                                                        <flux:field>
                                                            <flux:label>Number of milestones</flux:label>
                                                            <flux:input type="number" min="2" max="20"
                                                                size="sm" wire:model.defer="splitCount" />
                                                            <flux:error name="splitCount" />
                                                        </flux:field>
                                                    @endif

                                                    <!-- Percentage Split Options -->
                                                    @if ($splitTemplate === 'percentage')
                                                        <div class="space-y-2">
                                                            <div class="flex items-center justify-between">
                                                                <flux:label>Percentages (must sum to 100%)</flux:label>
                                                                <flux:button wire:click="addPercentageInput" variant="ghost" size="xs">
                                                                    <flux:icon.plus class="h-3 w-3" />
                                                                </flux:button>
                                                            </div>
                                                            @foreach ($percentageSplit as $index => $percentage)
                                                                <div class="flex items-center gap-2">
                                                                    <flux:text size="sm" class="w-24 {{ $workflowColors['text_secondary'] }}">
                                                                        Milestone {{ $index + 1 }}:
                                                                    </flux:text>
                                                                    <flux:input type="number" step="0.1" min="0" max="100"
                                                                        size="sm" wire:model.defer="percentageSplit.{{ $index }}"
                                                                        placeholder="0.0" class="flex-1" />
                                                                    <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">%</flux:text>
                                                                    @if (count($percentageSplit) > 2)
                                                                        <flux:button wire:click="removePercentageInput({{ $index }})"
                                                                            variant="ghost" size="xs">
                                                                            <flux:icon.trash class="h-3 w-3 text-red-500" />
                                                                        </flux:button>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                            @if (count($percentageSplit) === 0)
                                                                <div class="text-center py-2">
                                                                    <flux:button wire:click="addPercentageInput" variant="outline" size="sm">
                                                                        Add First Percentage
                                                                    </flux:button>
                                                                </div>
                                                            @endif
                                                            @if (count($percentageSplit) > 0)
                                                                <div class="mt-2 rounded-lg border p-2 text-center
                                                                    {{ abs($this->percentageTotal - 100) < 0.01 ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' }}">
                                                                    <flux:text size="sm" class="font-medium
                                                                        {{ abs($this->percentageTotal - 100) < 0.01 ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                                                                        Total: {{ number_format($this->percentageTotal, 1) }}%
                                                                        @if (abs($this->percentageTotal - 100) < 0.01)
                                                                            <flux:icon.check-circle class="ml-1 inline h-4 w-4" />
                                                                        @endif
                                                                    </flux:text>
                                                                </div>
                                                            @endif
                                                            <flux:error name="percentageSplit" />
                                                        </div>
                                                    @endif

                                                    <!-- Deposit Structure Preview -->
                                                    @if ($splitTemplate === 'deposit_structure')
                                                        <div class="rounded-lg border bg-white/50 p-3 dark:bg-gray-800/50 {{ $workflowColors['accent_border'] }}">
                                                            <flux:text size="sm" class="mb-2 font-medium {{ $workflowColors['text_primary'] }}">
                                                                Preview:
                                                            </flux:text>
                                                            <div class="space-y-1 text-xs {{ $workflowColors['text_secondary'] }}">
                                                                <div>• Initial Deposit: ${{ number_format($this->getBaseClientBudget() * 0.30, 2) }} (30%)</div>
                                                                <div>• Progress Payment: ${{ number_format($this->getBaseClientBudget() * 0.40, 2) }} (40%)</div>
                                                                <div>• Final Payment: ${{ number_format($this->getBaseClientBudget() * 0.30, 2) }} (30%)</div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <!-- Action Buttons -->
                                                    <div class="flex gap-2">
                                                        <flux:button wire:click="splitBudgetIntoMilestones"
                                                            variant="primary" size="sm" icon="check">
                                                            Create Milestones
                                                        </flux:button>
                                                        <flux:button wire:click="toggleSplitForm" variant="outline"
                                                            size="sm">Cancel</flux:button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </flux:card>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Delete Confirmation Modal -->
            @if ($showDeleteModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Confirm File Deletion</h3>
                        <p class="mb-6 text-gray-600">Are you sure you want to delete this file? This action cannot be
                            undone.</p>
                        <div class="flex justify-end space-x-3">
                            <button wire:click="cancelDeleteFile" class="btn btn-outline">Cancel</button>
                            <button wire:click="deleteFile" class="btn btn-error">Delete File</button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Client File Delete Confirmation Modal -->
            @if ($showDeleteClientFileModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>Confirm Client File Deletion
                        </h3>
                        <p class="mb-4 text-gray-600">
                            Are you sure you want to delete the client reference file:
                            <strong class="text-gray-900">{{ $clientFileNameToDelete }}</strong>?
                        </p>
                        <p class="mb-6 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-600">
                            <i class="fas fa-info-circle mr-2"></i>
                            This file was uploaded by your client. Once deleted, they will need to re-upload it if
                            needed.
                        </p>
                        <p class="mb-6 font-medium text-red-600">This action cannot be undone.</p>
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
            @if ($showProjectDeleteModal)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6">
                        <h3 class="mb-4 text-lg font-semibold text-red-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Delete Project
                        </h3>
                        <p class="mb-4 text-gray-600">
                            Are you sure you want to permanently delete this project? This will also delete:
                        </p>
                        <ul class="mb-6 list-inside list-disc text-sm text-gray-600">
                            <li>All project files</li>
                            <li>All pitch files and data</li>
                            <li>All project history and events</li>
                        </ul>
                        <p class="mb-6 font-medium text-red-600">This action cannot be undone.</p>
                        <div class="flex justify-end space-x-3">
                            <button wire:click="cancelDeleteProject" class="btn btn-outline">Cancel</button>
                            <button wire:click="deleteProject" class="btn btn-error">
                                <i class="fas fa-trash-alt mr-2"></i>Delete Project
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Google Drive Backup Modal -->
            @livewire('google-drive-backup-modal', ['model' => $project], key('google-drive-backup-' . $project->id))

            <!-- Google Drive Backup History Modal -->
            @livewire('google-drive-backup-history-modal', ['model' => $project, 'viewType' => 'project'], key('google-drive-backup-history-' . $project->id))

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
            @if ($pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED)
                {{-- Revision Response FAB --}}
                <div class="fixed bottom-6 right-6 z-50 lg:hidden" x-data="{ showTooltip: false }">
                    <button @click="handleFabAction('scrollToRevisionResponse')" @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-xl transition-all duration-300 hover:shadow-2xl">
                        <i class="fas fa-edit text-lg"></i>
                    </button>

                    <div x-show="showTooltip" x-transition
                        class="absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">
                        Respond to Feedback
                        <div
                            class="absolute right-3 top-full h-0 w-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900">
                        </div>
                    </div>
                </div>
            @elseif($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                {{-- Communication FAB --}}
                <div class="fixed bottom-6 right-6 z-50 lg:hidden" x-data="{ showTooltip: false }">
                    <button @click="handleFabAction('scrollToCommunication')" @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-xl transition-all duration-300 hover:shadow-2xl">
                        <i class="fas fa-comment text-lg"></i>
                    </button>

                    <div x-show="showTooltip" x-transition
                        class="absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">
                        Send Message
                        <div
                            class="absolute right-3 top-full h-0 w-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900">
                        </div>
                    </div>
                </div>
            @else
                {{-- In Progress / Default FAB --}}
                @if ($this->producerFiles->count() === 0)
                    {{-- Upload Files FAB --}}
                    <div class="fixed bottom-6 right-6 z-50 lg:hidden" x-data="{ showTooltip: false }">
                        <button @click="handleFabAction('scrollToUpload')" @mouseenter="showTooltip = true"
                            @mouseleave="showTooltip = false"
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-xl transition-all duration-300 hover:shadow-2xl">
                            <i class="fas fa-upload text-lg"></i>
                        </button>

                        <div x-show="showTooltip" x-transition
                            class="absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">
                            Upload Files
                            <div
                                class="absolute right-3 top-full h-0 w-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900">
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Submit for Review FAB --}}
                    <div class="fixed bottom-6 right-6 z-50 lg:hidden" x-data="{ showTooltip: false }">
                        <button @click="handleFabAction('scrollToSubmit')" @mouseenter="showTooltip = true"
                            @mouseleave="showTooltip = false"
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-xl duration-300 hover:shadow-2xl">
                            <i class="fas fa-paper-plane text-lg"></i>
                        </button>

                        <div x-show="showTooltip" x-transition
                            class="absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">
                            Submit for Review
                            <div
                                class="absolute right-3 top-full h-0 w-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900">
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- JavaScript for FAB Actions --}}
            <script>
                function handleFabAction(action) {
                    switch (action) {
                        case 'scrollToRevisionResponse':
                            // Scroll to revision response area
                            const responseArea = document.querySelector('textarea[wire\\:model\\.lazy="responseToFeedback"]');
                            if (responseArea) {
                                responseArea.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                                setTimeout(() => responseArea.focus(), 800);
                            }
                            break;

                        case 'scrollToCommunication':
                            // Scroll to communication hub in sidebar
                            const communicationHub = document.querySelector(
                                '.client-communication-hub, [wire\\:id*="client-communication-hub"]');
                            if (communicationHub) {
                                communicationHub.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                                // Try to find and focus the comment textarea
                                setTimeout(() => {
                                    const commentArea = document.getElementById('newComment') ||
                                        document.querySelector('textarea[wire\\:model*="comment"]') ||
                                        document.querySelector('textarea[placeholder*="message"]');
                                    if (commentArea) {
                                        commentArea.focus();
                                    }
                                }, 800);
                            }
                            break;

                        case 'scrollToUpload':
                            // Switch to deliverables tab and scroll to upload section
                            const deliverablesTab = document.querySelector('button[\\@click="activeTab = \'deliverables\'"]');
                            if (deliverablesTab) {
                                deliverablesTab.click();
                                // Wait for tab transition then scroll to upload
                                setTimeout(() => {
                                    const uploadSection = document.querySelector(
                                        '[data-section="producer-deliverables"], .uppy-Dashboard');
                                    if (uploadSection) {
                                        uploadSection.scrollIntoView({
                                            behavior: 'smooth',
                                            block: 'start'
                                        });
                                    }
                                }, 200);
                            }
                            break;

                        case 'scrollToSubmit':
                            // Scroll to submit section
                            const submitButton = document.querySelector('button[wire\\:click="submitForReview"]');
                            if (submitButton) {
                                submitButton.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                                // Add a subtle highlight effect
                                submitButton.classList.add('animate-pulse');
                                setTimeout(() => submitButton.classList.remove('animate-pulse'), 2000);
                            }
                            break;
                    }
                }
            </script>
        </div>
</x-draggable-upload-page>
