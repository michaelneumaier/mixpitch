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

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                                <!-- Main Content Area (70% width on large screens) -->
                                <div class="space-y-2 lg:col-span-2">
                                    <!-- File Management Tabs -->
                                    <div x-data="{ activeTab: 'client-files' }" class="mb-6">
                                        <!-- Tab Navigation -->
                                        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                                            <nav class="-mb-px flex space-x-8">
                                                <button @click="activeTab = 'client-files'"
                                                    :class="activeTab === 'client-files' ? 'border-purple-500 text-purple-600' :
                                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                                                    <flux:icon.folder-open class="mr-2 inline h-4 w-4" />
                                                    Client Reference Files
                                                </button>
                                                <button @click="activeTab = 'deliverables'"
                                                    :class="activeTab === 'deliverables' ? 'border-purple-500 text-purple-600' :
                                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                                                    <flux:icon.musical-note class="mr-2 inline h-4 w-4" />
                                                    Your Deliverables
                                                    <flux:badge variant="outline" size="sm" class="ml-2">
                                                        {{ $this->producerFiles->count() }} files
                                                    </flux:badge>
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
                                                    key('producer-files-list-' . $pitch->id)
                                                )
                                            </flux:card>
                                        </div>
                                    </div>

                                    <!-- Response to Feedback Section (if applicable) -->
                                    @if (in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                            \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                                        ]))
                                        <flux:card class="mb-2 border-amber-200 bg-amber-50">
                                            <div>
                                                <flux:heading size="lg">
                                                    <flux:icon name="arrow-uturn-left" class="mr-2" />
                                                    Respond to Feedback
                                                </flux:heading>
                                            </div>
                                            <div>
                                                <!-- Client Feedback Display -->
                                                @if ($statusFeedbackMessage)
                                                    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                                        <div class="flex items-start gap-3">
                                                            <div
                                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                                                                <flux:icon name="chat-bubble-left-ellipsis"
                                                                    class="h-4 w-4 text-white" />
                                                            </div>
                                                            <div class="flex-1">
                                                                <div class="mb-2 flex items-center gap-2">
                                                                    <flux:heading size="sm" class="text-blue-900">
                                                                        Client Feedback
                                                                    </flux:heading>
                                                                    @if ($this->getLatestFeedbackEvent())
                                                                        <flux:text size="xs" class="text-blue-600">
                                                                            {{ $this->getLatestFeedbackEvent()->created_at->diffForHumans() }}
                                                                        </flux:text>
                                                                    @endif
                                                                </div>
                                                                <div
                                                                    class="rounded bg-white p-3 text-sm text-gray-800 shadow-sm">
                                                                    {{ $statusFeedbackMessage }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- File Comments Summary -->
                                                @if ($this->fileCommentsSummary->count() > 0)
                                                    <div
                                                        class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                                        <div class="flex items-start gap-3">
                                                            <div
                                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                                                                <flux:icon name="document-text"
                                                                    class="h-4 w-4 text-white" />
                                                            </div>
                                                            <div class="flex-1">
                                                                <div class="mb-3 flex items-center gap-2">
                                                                    <flux:heading size="sm" class="text-amber-900">
                                                                        File Comments Overview
                                                                    </flux:heading>
                                                                    <flux:badge variant="warning" size="xs">
                                                                        {{ $this->fileCommentsTotals['unresolved'] }}
                                                                        unresolved of
                                                                        {{ $this->fileCommentsTotals['total'] }} total
                                                                    </flux:badge>
                                                                </div>

                                                                <div class="space-y-2">
                                                                    @foreach ($this->fileCommentsSummary as $summary)
                                                                        <div class="rounded bg-white p-3 shadow-sm">
                                                                            <div
                                                                                class="flex items-start justify-between">
                                                                                <div class="flex-1">
                                                                                    <div
                                                                                        class="mb-1 flex items-center gap-2">
                                                                                        <flux:text weight="medium"
                                                                                            size="sm"
                                                                                            class="text-gray-900">
                                                                                            {{ $summary['file']->file_name }}
                                                                                        </flux:text>
                                                                                        @if ($summary['needs_attention'])
                                                                                            <flux:badge
                                                                                                variant="warning"
                                                                                                size="xs">
                                                                                                {{ $summary['unresolved_count'] }}
                                                                                                need attention
                                                                                            </flux:badge>
                                                                                        @else
                                                                                            <flux:badge
                                                                                                variant="success"
                                                                                                size="xs">
                                                                                                All resolved
                                                                                            </flux:badge>
                                                                                        @endif
                                                                                    </div>

                                                                                    @if ($summary['latest_unresolved'])
                                                                                        <flux:text size="xs"
                                                                                            class="line-clamp-2 text-gray-600">
                                                                                            Latest:
                                                                                            "{{ Str::limit($summary['latest_unresolved']->comment, 80) }}"
                                                                                        </flux:text>
                                                                                        <flux:text size="xs"
                                                                                            class="text-gray-500">
                                                                                            —
                                                                                            {{ $summary['latest_unresolved']->is_client_comment ? ($this->project->client_name ?: 'Client') : 'Producer' }},
                                                                                            {{ $summary['latest_unresolved']->created_at->diffForHumans() }}
                                                                                        </flux:text>
                                                                                    @endif
                                                                                </div>

                                                                                <div class="text-right">
                                                                                    <flux:text size="xs"
                                                                                        class="text-gray-500">
                                                                                        {{ $summary['total_comments'] }}
                                                                                        total
                                                                                    </flux:text>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>

                                                                <div class="mt-3 text-center">
                                                                    <flux:text size="xs" class="text-amber-700">
                                                                        💡 Navigate to individual files below to respond
                                                                        to
                                                                        specific comments
                                                                    </flux:text>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <flux:field>
                                                    <flux:label>Your Response to Client Feedback</flux:label>
                                                    <flux:textarea wire:model.lazy="responseToFeedback" rows="4"
                                                        placeholder="Explain what changes you've made in response to the feedback..." />
                                                    <flux:error name="responseToFeedback" />

                                                    <!-- Send Response Button -->
                                                    @if ($statusFeedbackMessage || $this->fileCommentsSummary->count() > 0)
                                                        <div class="mt-3">
                                                            <flux:button wire:click="sendFeedbackResponse"
                                                                variant="primary" size="sm"
                                                                icon="paper-airplane" wire:loading.attr="disabled">
                                                                <span wire:loading.remove>Send Response</span>
                                                                <span wire:loading>Sending...</span>
                                                            </flux:button>
                                                            <flux:text size="xs" class="ml-2 text-gray-600">
                                                                This will notify the client without changing project
                                                                status
                                                            </flux:text>
                                                        </div>
                                                    @endif
                                                </flux:field>
                                            </div>
                                        </flux:card>
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
                                    <div class="mb-4">
                                        <x-client-project.client-communication-hub :component="$this" :project="$project"
                                            :conversationItems="$this->conversationItems" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                                    </div>

                                    <!-- Milestones Section -->
                                    <flux:card class="mb-4">
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <flux:icon.flag variant="solid"
                                                    class="{{ $workflowColors['icon'] }} h-6 w-6" />
                                                <div>
                                                    <flux:heading size="base"
                                                        class="{{ $workflowColors['text_primary'] }}">Milestones
                                                    </flux:heading>
                                                    <flux:text size="xs"
                                                        class="{{ $workflowColors['text_muted'] }}">
                                                        Payments & approvals
                                                    </flux:text>
                                                </div>
                                            </div>
                                            <flux:button wire:click="beginAddMilestone" variant="primary"
                                                size="sm" icon="plus">
                                                Add
                                            </flux:button>
                                        </div>

                                        @php($milestones = $pitch->milestones()->get())
                                        @php($milestoneTotal = $milestones->sum('amount'))
                                        @php($milestonePaid = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount'))

                                        <!-- Quick Stats -->
                                        <div class="mb-4 space-y-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="{{ $workflowColors['text_secondary'] }}">Progress</span>
                                                <span class="{{ $workflowColors['text_primary'] }} font-medium">
                                                    ${{ number_format($milestonePaid, 2) }} /
                                                    ${{ number_format($milestoneTotal, 2) }}
                                                </span>
                                            </div>
                                            @if ($milestoneTotal > 0)
                                                @php($percentPaid = round(($milestonePaid / max($milestoneTotal, 0.01)) * 100))
                                                <div
                                                    class="{{ $workflowColors['accent_bg'] }} h-1.5 w-full overflow-hidden rounded-full">
                                                    <div class="h-1.5 bg-gradient-to-r from-purple-500 to-indigo-600 transition-all duration-300"
                                                        style="width: {{ $percentPaid }}%"></div>
                                                </div>
                                                <div class="{{ $workflowColors['text_muted'] }} text-xs">
                                                    {{ $percentPaid }}% completed
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Milestones List -->
                                        @if ($milestones->count())
                                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                                @foreach ($milestones as $m)
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
                                                                    @elseif($m->status === 'approved')
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
                                                <div class="space-y-3">
                                                    <flux:field>
                                                        <flux:label>Name</flux:label>
                                                        <flux:input type="text" wire:model.defer="milestoneName"
                                                            size="sm" placeholder="e.g., Initial Deposit" />
                                                        <flux:error name="milestoneName" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label>Amount</flux:label>
                                                        <flux:input type="number" step="0.01" size="sm"
                                                            wire:model.defer="milestoneAmount" placeholder="0.00" />
                                                        <flux:error name="milestoneAmount" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label>Description</flux:label>
                                                        <flux:textarea rows="2" size="sm"
                                                            wire:model.defer="milestoneDescription"
                                                            placeholder="Optional details..." />
                                                        <flux:error name="milestoneDescription" />
                                                    </flux:field>
                                                </div>
                                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                                    <flux:button wire:click="saveMilestone" variant="primary"
                                                        size="sm">Save</flux:button>
                                                    <flux:button wire:click="cancelMilestoneForm" variant="outline"
                                                        size="sm">Cancel</flux:button>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($showSplitForm)
                                            <div
                                                class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mt-4 rounded-lg border p-4">
                                                <flux:heading size="sm"
                                                    class="{{ $workflowColors['text_primary'] }} mb-3">
                                                    Split Budget
                                                </flux:heading>
                                                <div class="space-y-3">
                                                    <flux:field>
                                                        <flux:label>Number of milestones</flux:label>
                                                        <flux:input type="number" min="2" max="20"
                                                            size="sm" wire:model.defer="splitCount" />
                                                    </flux:field>
                                                    <div class="flex gap-2">
                                                        <flux:button wire:click="splitBudgetIntoMilestones"
                                                            variant="primary" size="sm">Create</flux:button>
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
