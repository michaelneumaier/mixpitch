@php
    // Unified Color System - Contest (orange) colors
    $workflowColors = [
        'bg' => '!bg-orange-50 dark:!bg-orange-950',
        'border' => 'border-orange-200 dark:border-orange-800',
        'text_primary' => 'text-orange-900 dark:text-orange-100',
        'text_secondary' => 'text-orange-700 dark:text-orange-300',
        'text_muted' => 'text-orange-600 dark:text-orange-400',
        'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
        'accent_border' => 'border-orange-200 dark:border-orange-800',
        'icon' => 'text-orange-600 dark:text-orange-400',
        'accent' => 'rgb(234 88 12)', // orange-600
    ];

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

<x-draggable-upload-page :model="$project" title="Manage Contest: {{ $project->title }}">
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="mx-auto p-2">
                <div class="mx-auto">
                    <div class="flex justify-center">
                        <div class="w-full">
                            <!-- Enhanced Project Header -->
                            @livewire('project.project-header', [
                                'project' => $project,
                                'context' => 'manage',
                                'showActions' => true,
                                'showEditButton' => true,
                                'showWorkflowStatus' => true,
                                'autoAllowAccess' => $autoAllowAccess,
                            ], key('project-header-' . $project->id))

                            {{-- Tabbed Navigation Interface --}}
                            <div class="">
                                {{-- Contest orange tab styling --}}
                                <style>
                                    [data-flux-tab][data-selected] {
                                        border-bottom-color: {{ $workflowColors['accent'] }} !important;
                                        color: {{ $workflowColors['accent'] }} !important;
                                    }
                                    [data-flux-tab][data-selected] [data-flux-icon] {
                                        color: {{ $workflowColors['accent'] }} !important;
                                    }
                                    [data-flux-tab]:not([data-selected]):hover {
                                        color: {{ $workflowColors['accent'] }};
                                    }
                                </style>

                                <flux:tab.group>
                                    {{-- Main Tab Navigation --}}
                                    <flux:tabs scrollable scrollable:fade scrollable:scrollbar="hide" wire:model="activeMainTab">
                                        <flux:tab name="overview" icon="home">
                                            Overview
                                        </flux:tab>
                                        <flux:tab name="entries" icon="users">
                                            Entries
                                            <flux:badge color="orange" size="sm" class="ml-1">{{ $this->entryCount }}</flux:badge>
                                        </flux:tab>
                                        <flux:tab name="judging" icon="scale">
                                            Judging
                                            @if($this->canJudge)
                                                <flux:badge color="green" size="sm" class="ml-1">Ready</flux:badge>
                                            @endif
                                        </flux:tab>
                                        <flux:tab name="prizes" icon="trophy">
                                            Prizes
                                        </flux:tab>
                                        <flux:tab name="files" icon="folder-open">
                                            Files
                                            @if($project->files->count() > 0)
                                                <flux:badge color="zinc" size="sm" class="ml-1">{{ $project->files->count() }}</flux:badge>
                                            @endif
                                        </flux:tab>
                                        <flux:tab name="settings" icon="cog-6-tooth">
                                            Settings
                                        </flux:tab>
                                    </flux:tabs>

                                    {{-- ==================== OVERVIEW TAB ==================== --}}
                                    <flux:tab.panel name="overview" class="!pt-4 md:px-2">
                                        @livewire('project.component.contest-overview-card', [
                                            'project' => $project,
                                            'workflowColors' => $workflowColors,
                                        ], key('contest-overview-card-' . $project->id))
                                    </flux:tab.panel>

                                    {{-- ==================== ENTRIES TAB ==================== --}}
                                    <flux:tab.panel name="entries" class="!pt-4 md:px-2">
                                        @livewire('project.component.contest-judging', [
                                            'project' => $project,
                                            'workflowColors' => $workflowColors,
                                            'semanticColors' => $semanticColors,
                                        ], key('contest-judging-' . $project->id))
                                    </flux:tab.panel>

                                    {{-- ==================== JUDGING TAB ==================== --}}
                                    <flux:tab.panel name="judging" class="!pt-4 md:px-2">
                                        @if($this->canJudge)
                                            @livewire('project.component.contest-snapshot-judging', [
                                                'project' => $project,
                                                'workflowColors' => $workflowColors,
                                                'semanticColors' => $semanticColors,
                                            ], key('contest-snapshot-judging-' . $project->id))
                                        @elseif($project->isJudgingFinalized())
                                            <flux:card class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-center gap-4">
                                                    <flux:icon.trophy variant="solid" class="w-12 h-12 {{ $semanticColors['success']['icon'] }}" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $semanticColors['success']['text'] }}">
                                                            Contest Judging Complete
                                                        </flux:heading>
                                                        <flux:subheading class="{{ $semanticColors['success']['text'] }} opacity-80">
                                                            Winner has been selected. View the Entries tab to see results.
                                                        </flux:subheading>
                                                    </div>
                                                </div>
                                            </flux:card>
                                        @else
                                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} border">
                                                <div class="flex items-center gap-4">
                                                    <flux:icon.clock variant="solid" class="w-12 h-12 {{ $workflowColors['icon'] }}" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                            Judging Not Available Yet
                                                        </flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                                                            Judging will be available after the submission deadline passes.
                                                            @if($project->submission_deadline)
                                                                <br>
                                                                <span class="font-medium">Submission deadline: {{ $project->submission_deadline->format('M j, Y g:i A') }}</span>
                                                            @endif
                                                        </flux:subheading>
                                                    </div>
                                                </div>
                                            </flux:card>
                                        @endif
                                    </flux:tab.panel>

                                    {{-- ==================== PRIZES TAB ==================== --}}
                                    <flux:tab.panel name="prizes" class="!pt-4 md:px-2">
                                        @livewire('project.component.contest-prizes', [
                                            'project' => $project,
                                            'workflowColors' => $workflowColors,
                                            'semanticColors' => $semanticColors,
                                        ], key('contest-prizes-' . $project->id))
                                    </flux:tab.panel>

                                    {{-- ==================== FILES TAB ==================== --}}
                                    <flux:tab.panel name="files" class="!pt-4 md:px-2">
                                        <flux:card>
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="flex items-center gap-3">
                                                    <flux:icon.cloud-arrow-up variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                            Contest Files
                                                        </flux:heading>
                                                        <flux:subheading class="hidden md:block {{ $workflowColors['text_muted'] }}">
                                                            Upload and manage contest resources for participants
                                                        </flux:subheading>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- File Uploader Component -->
                                            <div x-data="{ showUploader: true }" class="mb-4">
                                                <div class="flex justify-end mb-2">
                                                    <flux:button @click="showUploader = !showUploader" variant="ghost" size="sm">
                                                        <span class="hidden lg:inline" x-text="showUploader ? 'Hide Uploader' : 'Show Uploader'"></span>
                                                        <flux:icon name="chevron-up" x-show="showUploader" class="lg:ml-2" />
                                                        <flux:icon name="chevron-down" x-show="!showUploader" class="lg:ml-2" />
                                                    </flux:button>
                                                </div>

                                                <div x-show="showUploader" x-transition>
                                                    @if($this->canUploadFiles)
                                                        <x-file-management.upload-section
                                                            :model="$project"
                                                            title="Upload Contest Files"
                                                            description="Upload audio, PDFs, or images to share with contest participants"
                                                        />
                                                    @else
                                                        <div class="text-center py-8">
                                                            <flux:icon.folder-open class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" />
                                                            <p class="text-gray-500 dark:text-gray-400 mb-2">File uploads are not available for this contest.</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Files List Section -->
                                            @livewire('components.file-list', [
                                                'files' => $project->files,
                                                'colorScheme' => $workflowColors,
                                                'modelType' => 'project',
                                                'modelId' => $project->id,
                                                'playMethod' => 'playProjectFile',
                                                'downloadMethod' => 'getDownloadUrl',
                                                'deleteMethod' => 'confirmDeleteFile',
                                                'enableBulkActions' => true,
                                                'bulkActions' => ['delete', 'download'],
                                                'emptyStateSubMessage' => 'Upload files to share with contest participants',
                                                'newlyUploadedFileIds' => $newlyUploadedFileIds ?? []
                                            ], key('file-list-' . $project->id))
                                        </flux:card>
                                    </flux:tab.panel>

                                    {{-- ==================== SETTINGS TAB ==================== --}}
                                    <flux:tab.panel name="settings" class="!pt-4 md:px-2">
                                        <div class="space-y-4">
                                            {{-- Project Details Card --}}
                                            @livewire('project.component.project-details-card', [
                                                'project' => $project,
                                                'workflowColors' => $workflowColors
                                            ], key('project-details-card-' . $project->id))

                                            {{-- Contest Settings Card --}}
                                            @livewire('project.component.contest-settings-card', [
                                                'project' => $project,
                                                'workflowColors' => $workflowColors,
                                                'submissionDeadline' => $submission_deadline,
                                                'judgingDeadline' => $judging_deadline,
                                            ], key('contest-settings-card-' . $project->id))

                                            {{-- Early Closure Controls --}}
                                            <div>
                                                @include('livewire.project.component.contest-early-closure', [
                                                    'project' => $project,
                                                    'workflowColors' => $workflowColors,
                                                    'semanticColors' => $semanticColors
                                                ])
                                            </div>
                                        </div>
                                    </flux:tab.panel>
                                </flux:tab.group>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- File Delete Confirmation Modal --}}
        <flux:modal name="delete-file" class="max-w-md">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    <flux:heading size="lg">Confirm File Deletion</flux:heading>
                </div>

                <flux:subheading class="text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete this file? This action cannot be undone.
                </flux:subheading>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="cancelDeleteFile">
                            Cancel
                        </flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="deleteFile" variant="danger" icon="trash" wire:loading.attr="disabled" wire:target="deleteFile">
                        <span wire:loading.remove wire:target="deleteFile">Delete File</span>
                        <span wire:loading wire:target="deleteFile">Deleting...</span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        {{-- Bulk File Delete Confirmation Modal --}}
        <flux:modal name="bulk-delete-files" class="max-w-md">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    <flux:heading size="lg">Confirm Bulk File Deletion</flux:heading>
                </div>

                <div class="space-y-4">
                    <flux:subheading class="text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete {{ count($filesToDelete ?? []) }} selected file{{ count($filesToDelete ?? []) !== 1 ? 's' : '' }}? This action cannot be undone.
                    </flux:subheading>

                    @if(isset($filesToDelete) && count($filesToDelete) > 0)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                            <div class="text-sm text-red-800 dark:text-red-200 font-medium mb-2">Files to be deleted:</div>
                            <div class="max-h-32 overflow-y-auto space-y-1">
                                @foreach($filesToDelete as $fileId)
                                    @php
                                        $file = $project->files->firstWhere('id', $fileId);
                                    @endphp
                                    @if($file)
                                        <div class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                                            <flux:icon.document class="w-4 h-4 flex-shrink-0" />
                                            <span class="truncate">{{ $file->file_name }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="cancelBulkDeleteFiles">
                            Cancel
                        </flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="bulkDeleteFiles" variant="danger" icon="trash" wire:loading.attr="disabled" wire:target="bulkDeleteFiles">
                        <span wire:loading.remove wire:target="bulkDeleteFiles">Delete {{ count($filesToDelete ?? []) }} File{{ count($filesToDelete ?? []) !== 1 ? 's' : '' }}</span>
                        <span wire:loading wire:target="bulkDeleteFiles">Deleting...</span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        {{-- Contest Delete Confirmation Modal --}}
        <flux:modal name="delete-project" class="max-w-md">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    <flux:heading size="lg" class="text-red-800 dark:text-red-200">Delete Contest</flux:heading>
                </div>

                <div class="space-y-4">
                    <flux:subheading class="text-gray-600 dark:text-gray-400">
                        Are you sure you want to permanently delete this contest? This will also delete:
                    </flux:subheading>

                    <ul class="list-inside list-disc text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>All contest files</li>
                        <li>All contest entries and submissions</li>
                        <li>All prize configurations</li>
                        <li>All contest history and events</li>
                    </ul>

                    <p class="font-medium text-red-600 dark:text-red-400">This action cannot be undone.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="cancelDeleteProject">
                            Cancel
                        </flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="deleteProject" variant="danger" icon="trash">
                        Delete Contest
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Project Image Upload Modal -->
        <x-project.image-upload-modal :project="$project" :imagePreviewUrl="$imagePreviewUrl" />

        <!-- Google Drive Backup Modal -->
        @livewire('google-drive-backup-modal', ['model' => $project], key('google-drive-backup-' . $project->id))

        <!-- Google Drive Backup History Modal -->
        @livewire('google-drive-backup-history-modal', ['model' => $project, 'viewType' => 'project'], key('google-drive-backup-history-' . $project->id))
    </div>
</x-draggable-upload-page>
