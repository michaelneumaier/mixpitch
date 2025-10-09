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

                            <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-2">
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
                                                    <flux:badge variant="outline" size="sm" class="ml-1">
                                                        {{ $this->clientFiles->count() }} files
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
                                                <div class="relative mb-6">
                                                    {{-- Main Content (Icon + Text) --}}
                                                    <div class="mb-3 flex items-center gap-3 md:mb-0">
                                                        <flux:icon.musical-note variant="solid"
                                                            class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                        <div class="flex-1">
                                                            <flux:heading size="base"
                                                                class="{{ $workflowColors['text_primary'] }}">
                                                                Your Deliverables
                                                                <flux:badge variant="outline" size="sm"
                                                                    class="ml-2">
                                                                    {{ $this->displayFiles->count() }} files
                                                                </flux:badge>
                                                            </flux:heading>
                                                            <flux:subheading
                                                                class="{{ $workflowColors['text_muted'] }}">
                                                                @if ($viewingHistory)
                                                                    📜 Viewing {{ $this->currentVersionLabel }}
                                                                @else
                                                                    <span class="hidden md:inline">Upload your work
                                                                        files here. These will be visible to your client
                                                                        for review</span>
                                                                @endif
                                                            </flux:subheading>
                                                        </div>
                                                    </div>

                                                    {{-- Version Switcher - Full Width on Mobile, Top Right on Desktop --}}
                                                    @if ($this->snapshotHistory->count() > 0 || $this->producerFiles->count() > 0)
                                                        <div class="md:absolute md:right-0 md:top-0">
                                                            <flux:dropdown position="bottom end">
                                                                <flux:button variant="outline" size="sm"
                                                                    class="w-full md:w-auto"
                                                                    icon-trailing="chevron-down">
                                                                    @if ($selectedSnapshotId === null)
                                                                        <flux:icon.pencil-square
                                                                            class="mr-1 inline h-4 w-4" />
                                                                        Working V{{ $this->getNextVersionNumber() }}
                                                                    @else
                                                                        <flux:icon.document-duplicate
                                                                            class="mr-1 inline h-4 w-4" />
                                                                        V{{ $this->snapshotHistory->firstWhere('id', $selectedSnapshotId)['version'] ?? '?' }}
                                                                    @endif
                                                                </flux:button>

                                                                <flux:menu
                                                                    class="max-h-[70vh] w-full overflow-y-auto md:w-72">
                                                                    {{-- Current Working Version (Unsaved) --}}
                                                                    @if ($this->producerFiles->count() > 0)
                                                                        <flux:menu.item
                                                                            wire:click="switchToVersion(null)">
                                                                            <div
                                                                                class="flex w-full items-center justify-between">
                                                                                <div class="flex items-center gap-2">
                                                                                    <flux:icon.pencil-square
                                                                                        class="h-4 w-4 shrink-0 text-purple-500" />
                                                                                    <div>
                                                                                        <div
                                                                                            class="text-sm font-semibold">
                                                                                            Working Version
                                                                                            (V{{ $this->getNextVersionNumber() }})
                                                                                        </div>
                                                                                        <div
                                                                                            class="text-xs text-gray-500">
                                                                                            {{ $this->producerFiles->count() }}
                                                                                            files · Not submitted yet
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                @if ($selectedSnapshotId === null)
                                                                                    <flux:badge variant="primary"
                                                                                        size="sm">Current
                                                                                    </flux:badge>
                                                                                @endif
                                                                            </div>
                                                                        </flux:menu.item>

                                                                        @if ($this->snapshotHistory->count() > 0)
                                                                            <flux:menu.separator />
                                                                            <div
                                                                                class="px-3 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                                                Submitted Versions
                                                                            </div>
                                                                        @endif
                                                                    @endif

                                                                    {{-- Historical Snapshots --}}
                                                                    @foreach ($this->snapshotHistory as $snapshot)
                                                                        <flux:menu.item
                                                                            wire:click="switchToVersion({{ $snapshot['id'] }})">
                                                                            <div
                                                                                class="flex w-full items-center justify-between gap-2">
                                                                                <div
                                                                                    class="flex min-w-0 items-center gap-2">
                                                                                    <flux:icon.document-duplicate
                                                                                        class="{{ $workflowColors['icon'] }} h-4 w-4 shrink-0" />
                                                                                    <div class="min-w-0">
                                                                                        <div
                                                                                            class="text-sm font-semibold">
                                                                                            Version
                                                                                            {{ $snapshot['version'] }}
                                                                                        </div>
                                                                                        <div
                                                                                            class="truncate text-xs text-gray-500">
                                                                                            {{ $snapshot['file_count'] }}
                                                                                            files ·
                                                                                            {{ $snapshot['submitted_at']->format('M j, g:i A') }}
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div
                                                                                    class="flex shrink-0 flex-col items-end gap-1">
                                                                                    @php
                                                                                        $statusColors = match (
                                                                                            $snapshot['status']
                                                                                        ) {
                                                                                            'accepted'
                                                                                                => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                                                            'pending'
                                                                                                => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                                                            'revisions_requested'
                                                                                                => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                                                                            'cancelled'
                                                                                                => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                                                                            'revision_addressed'
                                                                                                => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                                                            'denied'
                                                                                                => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                                                            default
                                                                                                => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                                                                        };
                                                                                    @endphp
                                                                                    <span
                                                                                        class="{{ $statusColors }} rounded px-1.5 py-0.5 text-xs font-medium">
                                                                                        {{ $snapshot['status_label'] }}
                                                                                    </span>
                                                                                    @if ($selectedSnapshotId === $snapshot['id'])
                                                                                        <flux:badge variant="primary"
                                                                                            size="xs">Viewing
                                                                                        </flux:badge>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </flux:menu.item>
                                                                    @endforeach

                                                                    {{-- No versions state --}}
                                                                    @if ($this->snapshotHistory->count() === 0 && $this->producerFiles->count() === 0)
                                                                        <div
                                                                            class="px-4 py-3 text-center text-sm text-gray-500">
                                                                            No versions yet. Upload files to get
                                                                            started.
                                                                        </div>
                                                                    @endif
                                                                </flux:menu>
                                                            </flux:dropdown>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Context Banner for History Viewing --}}
                                                @if ($viewingHistory)
                                                    <div
                                                        class="{{ $workflowColors['border'] }} {{ $workflowColors['accent_bg'] }} mb-4 rounded-lg border p-4">
                                                        <div
                                                            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                                            {{-- Info Section --}}
                                                            <div class="flex items-start gap-3">
                                                                <flux:icon.information-circle
                                                                    class="{{ $workflowColors['icon'] }} h-5 w-5 shrink-0" />
                                                                <div class="flex-1">
                                                                    <flux:text size="sm"
                                                                        class="{{ $workflowColors['text_primary'] }} font-medium">
                                                                        📜 Viewing {{ $this->currentVersionLabel }}
                                                                    </flux:text>
                                                                    <flux:text size="xs"
                                                                        class="{{ $workflowColors['text_muted'] }}">
                                                                        This is a snapshot from
                                                                        {{ $this->snapshotHistory->firstWhere('id', $selectedSnapshotId)['submitted_at']->diffForHumans() }}.
                                                                        Uploads will still go to your current working
                                                                        version.
                                                                    </flux:text>
                                                                </div>
                                                            </div>

                                                            {{-- Button Section - Full Width on Mobile --}}
                                                            <flux:button size="sm" variant="primary"
                                                                class="w-full shrink-0 md:w-auto"
                                                                wire:click="switchToVersion(null)">
                                                                Back to Working Version
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endif

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
                                                        'files' => $this->displayFiles,
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

                                    <!-- Billing & Payment Tracker -->
                                    @livewire(
                                        'project.component.project-billing-tracker',
                                        [
                                            'pitch' => $pitch,
                                            'project' => $project,
                                            'workflowColors' => $workflowColors,
                                        ],
                                        key('billing-tracker-' . $pitch->id)
                                    )
                                </div>
                                <!-- Sidebar (30% width on large screens) -->
                                <div class="space-y-4 lg:col-span-1">
                                    <!-- Client Communication Hub -->

                                    <x-client-project.client-communication-hub :component="$this" :project="$project"
                                        :conversationItems="$this->conversationItems" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />

                                    <!-- Milestones Section -->
                                    @livewire(
                                        'project.component.milestone-manager',
                                        [
                                            'pitch' => $pitch,
                                            'project' => $project,
                                            'workflowColors' => $workflowColors,
                                        ],
                                        key('milestone-manager-' . $pitch->id)
                                    )

                                    <!-- Email Notification Preferences -->
                                    <flux:card class="mt-4">
                                        <flux:heading size="lg">Email Notifications</flux:heading>
                                        <flux:subheading>Control which email notifications you receive for this project</flux:subheading>

                                        <div class="mt-4 space-y-3">
                                            <label class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium text-sm">Client requests revisions</div>
                                                    <div class="text-xs text-gray-600 dark:text-gray-400">Get notified when the client asks for changes</div>
                                                </div>
                                                <flux:switch
                                                    wire:model.live="producerEmailPreferences.producer_revisions_requested"
                                                    wire:change="updateProducerEmailPreference('producer_revisions_requested', $event.target.checked)"
                                                />
                                            </label>

                                            <label class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium text-sm">Client adds comments</div>
                                                    <div class="text-xs text-gray-600 dark:text-gray-400">Get notified when the client leaves a message</div>
                                                </div>
                                                <flux:switch
                                                    wire:model.live="producerEmailPreferences.producer_client_commented"
                                                    wire:change="updateProducerEmailPreference('producer_client_commented', $event.target.checked)"
                                                />
                                            </label>

                                            <label class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium text-sm">Payment confirmation</div>
                                                    <div class="text-xs text-gray-600 dark:text-gray-400">Get notified when you receive payment with payout details</div>
                                                </div>
                                                <flux:switch
                                                    wire:model.live="producerEmailPreferences.payment_received"
                                                    wire:change="updateProducerEmailPreference('payment_received', $event.target.checked)"
                                                />
                                            </label>
                                        </div>
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

            {{-- Version History FAB (Mobile Only) - Shows when viewing historical version --}}
            @if ($viewingHistory)
                <div class="fixed bottom-6 left-6 z-50 lg:hidden" x-data="{ showTooltip: false }">
                    <button wire:click="switchToVersion(null)" @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-xl transition-all duration-300 hover:shadow-2xl">
                        <flux:icon.arrow-uturn-left class="h-6 w-6" />
                    </button>

                    {{-- Tooltip --}}
                    <div x-show="showTooltip" x-transition
                        class="absolute bottom-full left-0 mb-2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">
                        Back to Working Version
                        <div
                            class="absolute left-3 top-full h-0 w-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900">
                        </div>
                    </div>
                </div>
            @endif

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
