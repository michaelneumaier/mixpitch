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
                        <x-project.header :project="$project" context="manage" :showActions="true" :showEditButton="true" :showWorkflowStatus="true" />

                        <div class="grid">
                            <!-- Main Content Area (2/3 width on large screens) -->
                            <div class="space-y-2">
                                <!-- INTEGRATED CLIENT COMMUNICATION HUB -->
                                <div class="mb-2">
                                    <x-client-project.client-communication-hub :component="$this" :project="$project" :conversationItems="$this->conversationItems"
                                        :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                                </div>

                                <!-- Milestones Section -->
                                <flux:card class="mb-2">
                                    <div class="mb-6 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <flux:icon.flag variant="solid"
                                                class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                            <div>
                                                <flux:heading size="lg"
                                                    class="{{ $workflowColors['text_primary'] }}">Milestones
                                                </flux:heading>
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Define
                                                    partial payments and approvals</flux:subheading>
                                            </div>
                                        </div>
                                        <flux:button wire:click="beginAddMilestone" variant="primary" icon="plus">
                                            Add Milestone
                                        </flux:button>
                                    </div>
                                    @php($milestones = $pitch->milestones()->get())
                                    @php($milestoneTotal = $milestones->sum('amount'))
                                    @php($milestonePaid = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount'))
                                    @php($approvedFiles = $pitch->files->where('client_approval_status', 'approved')->count())
                                    @php($totalFiles = $pitch->files->count())

                                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                                        <div class="{{ $workflowColors['text_secondary'] }} text-sm">
                                            <span class="mr-2">Totals:</span>
                                            <span
                                                class="{{ $workflowColors['text_primary'] }} font-semibold">${{ number_format($milestonePaid, 2) }}</span>
                                            <span class="{{ $workflowColors['text_muted'] }}">of</span>
                                            <span
                                                class="{{ $workflowColors['text_primary'] }} font-semibold">${{ number_format($milestoneTotal, 2) }}</span>
                                            @php($baseBudget = $pitch->payment_amount > 0 ? $pitch->payment_amount : $project->budget ?? 0)
                                            @if ($baseBudget && $baseBudget > 0)
                                                <span class="{{ $workflowColors['text_muted'] }}">(base budget:
                                                    ${{ number_format($baseBudget, 2) }})</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:badge variant="success" size="sm">
                                                <flux:icon.currency-dollar class="mr-1" />
                                                Paid: ${{ number_format($milestonePaid, 2) }}
                                            </flux:badge>
                                            <flux:badge variant="outline" size="sm">
                                                <flux:icon.flag class="mr-1" />
                                                Milestones: {{ $milestones->count() }}
                                            </flux:badge>
                                            <flux:badge variant="primary" size="sm">
                                                <flux:icon.document-check class="mr-1" />
                                                Approvals: {{ $approvedFiles }} / {{ $totalFiles }}
                                            </flux:badge>
                                            <flux:button wire:click="toggleSplitForm" variant="outline" size="sm" icon="calculator">
                                                Split budget
                                            </flux:button>
                                        </div>
                                    </div>
                                    @if ($milestones->count())
                                        <div class="mb-4">
                                            @php($percentPaid = $milestoneTotal > 0 ? round(($milestonePaid / max($milestoneTotal, 0.01)) * 100) : 0)
                                            <div
                                                class="{{ $workflowColors['accent_bg'] }} h-2 w-full overflow-hidden rounded-full">
                                                <div class="h-2 bg-gradient-to-r from-purple-500 to-indigo-600 transition-all duration-300"
                                                    style="width: {{ $percentPaid }}%"></div>
                                            </div>
                                            <div class="{{ $workflowColors['text_muted'] }} mt-1 text-xs">
                                                {{ $percentPaid }}% paid</div>
                                        </div>

                                        <div x-data="{
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
                                        }" x-init="init()">
                                            <div class="{{ $workflowColors['accent_border'] }} divide-y"
                                                x-ref="milestoneList">
                                                @foreach ($milestones as $m)
                                                    <div class="flex items-center justify-between py-3"
                                                        data-id="{{ $m->id }}">
                                                        <div class="min-w-0">
                                                            <div
                                                                class="flex items-center gap-2 font-medium text-gray-900">
                                                                {{ $m->name }}
                                                                @if ($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                                    <span
                                                                        class="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-[10px] text-green-800"><i
                                                                            class="fas fa-dollar-sign mr-1"></i>
                                                                        Paid</span>
                                                                @elseif($m->status === 'approved')
                                                                    <span
                                                                        class="inline-flex items-center rounded bg-purple-100 px-2 py-0.5 text-[10px] text-purple-800"><i
                                                                            class="fas fa-check mr-1"></i>
                                                                        Approved</span>
                                                                @else
                                                                    <span
                                                                        class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700"><i
                                                                            class="fas fa-clock mr-1"></i>
                                                                        Pending</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-xs text-gray-600">Status:
                                                                {{ ucfirst($m->status) }} @if ($m->payment_status)
                                                                    • Payment:
                                                                    {{ str_replace('_', ' ', $m->payment_status) }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <div class="text-sm font-semibold text-gray-900">
                                                                ${{ number_format($m->amount, 2) }}</div>
                                                            <span
                                                                class="drag-handle inline-flex h-8 w-8 cursor-move items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 hover:text-gray-700"
                                                                title="Drag to reorder">
                                                                <i class="fas fa-grip-vertical"></i>
                                                            </span>
                                                            <button
                                                                wire:click="beginEditMilestone({{ $m->id }})"
                                                                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs hover:bg-gray-50">Edit</button>
                                                            <button wire:click="deleteMilestone({{ $m->id }})"
                                                                wire:confirm="Delete this milestone?"
                                                                class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">Delete</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="py-6 text-center text-sm text-gray-600">No milestones yet.</div>
                                    @endif

                                    @if ($showMilestoneForm)
                                        <div
                                            class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mt-6 rounded-xl border p-6">
                                            <flux:heading size="base"
                                                class="{{ $workflowColors['text_primary'] }} mb-4">
                                                {{ $editingMilestoneId ? 'Edit Milestone' : 'Add Milestone' }}
                                            </flux:heading>
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <flux:field>
                                                    <flux:label>Name</flux:label>
                                                    <flux:input type="text" wire:model.defer="milestoneName"
                                                        placeholder="e.g., Initial Deposit" />
                                                    <flux:error name="milestoneName" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Amount</flux:label>
                                                    <flux:input type="number" step="0.01"
                                                        wire:model.defer="milestoneAmount" placeholder="0.00" />
                                                    <flux:error name="milestoneAmount" />
                                                </flux:field>
                                                <flux:field class="md:col-span-2">
                                                    <flux:label>Description</flux:label>
                                                    <flux:textarea rows="2"
                                                        wire:model.defer="milestoneDescription"
                                                        placeholder="Optional details..." />
                                                    <flux:error name="milestoneDescription" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Sort Order</flux:label>
                                                    <flux:input type="number" wire:model.defer="milestoneSortOrder"
                                                        placeholder="0" />
                                                    <flux:error name="milestoneSortOrder" />
                                                </flux:field>
                                            </div>
                                            <div class="mt-6 flex flex-wrap items-center gap-3">
                                                <flux:button wire:click="saveMilestone" variant="primary">Save
                                                </flux:button>
                                                <flux:button wire:click="cancelMilestoneForm" variant="outline">Cancel
                                                </flux:button>
                                                @if ($pitch->project && $pitch->project->budget)
                                                    <flux:button type="button"
                                                        wire:click="$set('milestoneAmount', {{ number_format($pitch->project->budget, 2, '.', '') }})"
                                                        variant="ghost" size="sm">
                                                        Use total budget
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if ($showSplitForm)
                                        <div
                                            class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mt-6 rounded-xl border p-6">
                                            <flux:heading size="base"
                                                class="{{ $workflowColors['text_primary'] }} mb-4">Split Total Budget
                                            </flux:heading>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <flux:field class="max-w-xs flex-1">
                                                    <flux:label>Number of milestones</flux:label>
                                                    <flux:input type="number" min="2" max="20"
                                                        wire:model.defer="splitCount" />
                                                </flux:field>
                                                <div class="mt-6 flex gap-2">
                                                    <flux:button wire:click="splitBudgetIntoMilestones"
                                                        variant="primary">Create</flux:button>
                                                    <flux:button type="button" wire:click="toggleSplitForm"
                                                        variant="outline">Cancel</flux:button>
                                                </div>
                                            </div>
                                            <flux:text size="sm"
                                                class="{{ $workflowColors['text_muted'] }} mt-4">Splits the project
                                                budget into equal parts. The last milestone gets the rounding remainder.
                                            </flux:text>
                                        </div>
                                    @endif

                                </flux:card>

                                <!-- File Management Section -->
                                    <!-- Client Reference Files Section -->
                                    <flux:card
                                        class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} mb-6">
                                        <div class="mb-6 flex items-center gap-3">
                                            <flux:icon.folder-open variant="solid"
                                                class="{{ $semanticColors['success']['icon'] }} h-8 w-8" />
                                            <div>
                                                <flux:heading size="base"
                                                    class="{{ $semanticColors['success']['text'] }}">
                                                    Client Reference Files
                                                </flux:heading>
                                                <flux:subheading class="{{ $semanticColors['success']['icon'] }}">
                                                    Files uploaded by your client to provide project requirements, references, or examples</flux:subheading>
                                            </div>
                                        </div>

                                        @livewire('components.file-list', [
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
                                            'emptyIcon' => 'inbox'
                                        ], key('client-files-list-' . $project->id . '-' . $refreshKey))

                                        <!-- Link Import Section -->
                                        <div class="mt-6 border-t border-green-200 pt-6 dark:border-green-800">
                                            <div class="flex items-center justify-between mb-4">
                                                <div>
                                                    <flux:heading size="sm" class="{{ $semanticColors['success']['text'] }}">
                                                        Import from Link
                                                    </flux:heading>
                                                    <flux:text size="xs" class="{{ $semanticColors['success']['icon'] }}">
                                                        Import files from WeTransfer, Google Drive, Dropbox, or OneDrive
                                                    </flux:text>
                                                </div>
                                            </div>
                                            
                                            @livewire('link-importer', ['project' => $project], key('link-importer-' . $project->id))
                                        </div>
                                    </flux:card>

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
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Upload
                                                    your
                                                    work files here. These will be visible to your client for review
                                                </flux:subheading>
                                            </div>
                                        </div>

                                        <!-- Upload Section for Producer Deliverables -->
                                        <div class="mb-6">
                                            <x-file-management.upload-section 
                                                :model="$pitch"
                                                title="Upload Deliverables"
                                                description="Upload your work files here. These will be visible to your client for review"
                                            />
                                        </div>

                                        <!-- Producer Files List -->
                                        @livewire('components.file-list', [
                                            'files' => $this->producerFiles,
                                            'modelType' => 'pitch',
                                            'modelId' => $pitch->id,
                                            'colorScheme' => $workflowColors,
                                            'canPlay' => true,
                                            'canDownload' => true,
                                            'canDelete' => in_array($pitch->status, [
                                                \App\Models\Pitch::STATUS_IN_PROGRESS,
                                                \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                                \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                                                \App\Models\Pitch::STATUS_DENIED,
                                                \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                            ]),
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
                                        ], key('producer-files-list-' . $pitch->id))
                                    </flux:card>

                                <!-- Response to Feedback Section (if applicable) -->
                                @if (in_array($pitch->status, [
                                        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                        \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                                    ]))
                                    <flux:card class="mb-2 border-amber-200 bg-amber-50">
                                        <div class="border-b border-amber-200 p-6">
                                            <flux:heading size="lg">
                                                <flux:icon name="arrow-uturn-left" class="mr-2" />
                                                Respond to Feedback
                                            </flux:heading>
                                        </div>
                                        <div class="p-6">
                                            <flux:field>
                                                <flux:label>Your Response to Client Feedback</flux:label>
                                                <flux:textarea wire:model.lazy="responseToFeedback" rows="4"
                                                    placeholder="Explain what changes you've made in response to the feedback..." />
                                                <flux:error name="responseToFeedback" />
                                            </flux:field>
                                        </div>
                                    </flux:card>
                                @endif

                                <!-- Submit/Recall Section -->
                                @livewire('project.component.client-submit-section', [
                                    'project' => $project,
                                    'pitch' => $pitch,
                                    'workflowColors' => $workflowColors
                                ], key('client-submit-section-' . $pitch->id . '-' . $pitch->updated_at->timestamp))
                            </div>
                            <!-- Sidebar (1/3 width on large screens) -->
                            <div class="space-y-2 lg:col-span-1">
                                <!-- Sidebar content can be added here if needed -->
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
                        // Scroll to communication form
                        const commentArea = document.getElementById('newComment');
                        if (commentArea) {
                            commentArea.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            setTimeout(() => commentArea.focus(), 800);
                        }
                        break;

                    case 'scrollToUpload':
                        // Scroll to upload section
                        const uploadSection = document.querySelector('[data-section="producer-deliverables"], .uppy-Dashboard');
                        if (uploadSection) {
                            uploadSection.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
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
</div>
</x-draggable-upload-page>
