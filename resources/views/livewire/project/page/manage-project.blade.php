@php
    // Unified Color System - Workflow-aware colors
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

<x-draggable-upload-page :model="$project" title="Manage Project: {{ $project->title }}">
<div>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="mx-auto p-2">
        <div class="mx-auto">
            <style>
                /* Custom breakpoint for extra small screens */
                @media (min-width: 475px) {
                    .xs\:inline {
                        display: inline;
                    }

                    .xs\:block {
                        display: block;
                    }

                    .xs\:hidden {
                        display: none;
                    }
                }

                /* Ensure content doesn't overflow on small screens */
                .overflow-x-auto {
                    overflow-x: auto;
                }

                /* Ensure text doesn't overflow */
                .break-words {
                    word-break: break-word;
                }

                /* Fix for file input on small screens */
                .file-input {
                    max-width: 100%;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* Fix for track filenames */
                .track-filename {
                    max-width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    word-break: break-all;
                }

                /* Ensure track container to respect parent width */
                .track-item {
                    width: 100%;
                    min-width: 0;
                }

                /* Force track container to respect parent width */
                .tracks-container {
                    width: 100%;
                    max-width: 100%;
                    min-width: 0;
                }

                /* Responsive track items on very small screens */
                @media (max-width: 380px) {
                    .track-item {
                        flex-direction: row;
                        align-items: center;
                        gap: 0.5rem;
                    }

                    .track-item .track-info {
                        flex: 1;
                        min-width: 0;
                    }

                    .track-item .track-actions {
                        flex-shrink: 0;
                    }
                }

                /* Ensure consistent button sizing in pitch actions */
                .pitch-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    align-items: center;
                }

                .pitch-actions>* {
                    flex: 0 0 auto;
                }

                /* Fix layout issues at medium screen sizes */
                @media (min-width: 640px) and (max-width: 800px) {
                    .pitch-status-wrapper {
                        flex-direction: column;
                        align-items: flex-end;
                    }

                    .pitch-status-wrapper>div {
                        margin-bottom: 0.5rem;
                    }
                }

                @media (max-width: 640px) {
                    .pitch-actions {
                        width: 100%;
                        flex-direction: column;
                        align-items: stretch;
                    }

                    .pitch-actions>* {
                        width: 100%;
                    }
                }

                /* Mobile optimizations */
                @media (max-width: 640px) {
                    .project-header-image {
                        width: 100%;
                        height: auto;
                        max-height: 200px;
                        margin: 0 auto;
                        display: flex;
                    }

                    .project-header-details {
                        padding: 1rem 0.75rem;
                    }

                    .project-metadata-row {
                        min-width: 100%;
                        overflow-x: auto;
                    }
                }
            </style>

            <div class="flex justify-center">
                <div class="w-full">
                    <!-- Enhanced Project Header -->
                    <x-project.header 
                        :project="$project" 
                        context="manage" 
                        :showActions="true"
                        :showEditButton="true"
                        :autoAllowAccess="$autoAllowAccess"
                    />
                    <div class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                        <!-- Main Content Area (2/3 width on large screens) -->
                        <div class="space-y-2 lg:col-span-2">

                            {{-- Workflow Status Component --}}
                            @if ($project->isStandard() || $project->isContest() || $project->isDirectHire())
                                <div>
                                    <x-project.workflow-status :project="$project" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                                </div>
                            @endif

                            {{-- Pitches Section / Contest Entries --}}
                            @if ($project->isContest())
                                {{-- Contest Judging Component (includes Contest Entries) --}}
                                @livewire('project.component.contest-judging', ['project' => $project, 'workflowColors' => $workflowColors, 'semanticColors' => $semanticColors], key('contest-judging-' . $project->id))
                            @else
                                {{-- Regular Pitches Section --}}
                                <x-project.pitch-list :project="$project" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                            @endif

                            @if ($project->isStandard() || $project->isContest())
                                <!-- Upload Files Section -->
                                <flux:card x-data="{ showUploader: true }">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-3">
                                            <flux:icon.cloud-arrow-up variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                            <div>
                                                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                    {{ $project->isContest() ? 'Contest Files' : 'Project Files' }}
                                                </flux:heading>
                                                <flux:subheading class="hidden md:block {{ $workflowColors['text_muted'] }}">
                                                    Upload and manage {{ $project->isContest() ? 'contest' : 'project' }} resources
                                                </flux:subheading>
                                            </div>
                                        </div>
                                        <flux:button @click="showUploader = !showUploader" variant="ghost" size="sm">
                                            <span class="hidden lg:inline" x-text="showUploader ? 'Hide Uploader' : 'Show Uploader'"></span>
                                            <flux:icon name="chevron-up" x-show="showUploader" class="lg:ml-2" />
                                            <flux:icon name="chevron-down" x-show="!showUploader" class="lg:ml-2" />
                                        </flux:button>
                                    </div>


                                    <!-- File Uploader Component -->
                                    <div x-show="showUploader" x-transition class="mb-2">
                                        @if($this->canUploadFiles)
                                            <x-file-management.upload-section 
                                                :model="$project"
                                                title="Upload New Files"
                                                :description="'Upload audio, PDFs, or images to share with ' . ($project->isContest() ? 'contest participants' : 'collaborators')"
                                            />
                                        @else
                                            <flux:card>
                                                <div class="text-center py-8">
                                                    <flux:icon.folder-open class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" />
                                                    <p class="text-gray-500 dark:text-gray-400 mb-2">File uploads are not available for this project.</p>
                                                    @if($project->status === App\Models\Project::STATUS_COMPLETED)
                                                        <p class="text-sm text-gray-400 dark:text-gray-500">Project is completed - no additional files can be uploaded.</p>
                                                    @endif
                                                </div>
                                            </flux:card>
                                        @endif
                                    </div>

                                    <!-- Files List Section -->
                                        <div class="flex items-center gap-3 mb-2">
                                            <flux:icon.folder variant="solid" class="w-6 h-6 {{ $workflowColors['icon'] }}" />
                                            <div class="flex items-center justify-between w-full">
                                                <flux:heading size="base" class="!mb-0 {{ $workflowColors['text_primary'] }}">
                                                    Files ({{ $project->files->count() }})
                                                </flux:heading>
                                                @if ($project->files->count() > 0)
                                                    <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                                                        Total: {{ $this->formatFileSize($project->files->sum('size')) }}
                                                    </flux:subheading>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @forelse($project->files as $file)
                                                <div class="track-item @if (isset($newlyUploadedFileIds) && in_array($file->id, $newlyUploadedFileIds)) animate-fade-in @endif group flex items-center justify-between py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                                    <div class="track-info flex flex-1 items-center overflow-hidden pr-4">
                                                        @if ($file->isAudioFile())
                                                            <button wire:click="playProjectFile({{ $file->id }})" class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg hover:bg-opacity-80 transition-colors cursor-pointer">
                                                                <flux:icon.play class=" w-5 h-5" />
                                                            </button>
                                                        @else
                                                            <div class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['icon'] }} mx-2 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg">
                                                                <flux:icon.musical-note class="w-5 h-5" />
                                                            </div>
                                                        @endif
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <span class="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                                                                    {{ $file->file_name }}
                                                                </span>
                                                            </div>
                                                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                                <div class="flex items-center gap-1">
                                                                    <flux:icon.calendar class="w-3 h-3" />
                                                                    <span>{{ $file->created_at->format('M d, Y') }}</span>
                                                                </div>
                                                                <div class="flex items-center gap-1">
                                                                    <flux:icon.scale class="w-3 h-3" />
                                                                    <span>{{ $file->formatted_size }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="track-actions flex items-center">
                                                        <flux:dropdown>
                                                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical">
                                                            </flux:button>
                                                            <flux:menu>
                                                                <flux:menu.item wire:click="getDownloadUrl({{ $file->id }})" icon="arrow-down-tray">
                                                                    Download
                                                                </flux:menu.item>
                                                                <flux:menu.item wire:click="confirmDeleteFile({{ $file->id }})" variant="danger" icon="trash">
                                                                    Delete
                                                                </flux:menu.item>
                                                            </flux:menu>
                                                        </flux:dropdown>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="p-8 text-center">
                                                    <flux:icon.folder-open class="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                                                    <flux:heading size="lg" class="text-gray-800 dark:text-gray-200 mb-2">No files uploaded yet</flux:heading>
                                                    <flux:subheading class="text-gray-600 dark:text-gray-400">
                                                        Upload files to share with {{ $project->isContest() ? 'contest participants' : 'collaborators' }}
                                                    </flux:subheading>
                                                </div>
                                            @endforelse
                                        </div>
                                </flux:card>
                            @endif

                            {{-- License Management Component --}}
                            <div class="mb-2">
                                <x-project.license-management :project="$project" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                            </div>

                            
                        </div>

                        <!-- Sidebar (1/3 width on large screens) -->
                        <div class="space-y-2 lg:col-span-1">
                            {{-- Workflow Type Specific Information --}}
                            @if ($project->isStandard())
                                <flux:card class="mb-2 hidden lg:block {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                                    <div class="flex items-center gap-3 mb-6">
                                        <flux:icon.users variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] }}" />
                                        <div>
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Standard Project</flux:heading>
                                            <flux:subheading class="{{ $workflowColors['text_muted'] }}">Open collaboration workflow</flux:subheading>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="p-4 {{ $workflowColors['accent_bg'] }} rounded-xl border {{ $workflowColors['accent_border'] }}">
                                            <div class="flex items-start gap-3">
                                                <flux:icon.users class="w-6 h-6 {{ $workflowColors['icon'] }} flex-shrink-0 mt-0.5" />
                                                <div>
                                                    <flux:subheading class="{{ $workflowColors['text_primary'] }} font-semibold mb-1">Open Collaboration</flux:subheading>
                                                    <p class="text-sm {{ $workflowColors['text_secondary'] }}">Any producer can submit a pitch for your project. Review and approve the best fit.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-4 {{ $workflowColors['accent_bg'] }} rounded-xl border {{ $workflowColors['accent_border'] }}">
                                            <div class="flex items-start gap-3">
                                                <flux:icon.chat-bubble-left-right class="w-6 h-6 {{ $workflowColors['icon'] }} flex-shrink-0 mt-0.5" />
                                                <div>
                                                    <flux:subheading class="{{ $workflowColors['text_primary'] }} font-semibold mb-1">Direct Communication</flux:subheading>
                                                    <p class="text-sm {{ $workflowColors['text_secondary'] }}">Work directly with your chosen producer throughout the project lifecycle.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </flux:card>

                                <!-- Project Insights -->
                                <div class="mb-2 hidden lg:block">
                                    <x-project.quick-stats :project="$project" />
                                </div>


                                <!-- Tips & Best Practices -->
                                <flux:card class="mb-2 hidden lg:block {{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }}">
                                    <div class="flex items-center gap-3 mb-6">
                                        <flux:icon.light-bulb variant="solid" class="w-8 h-8 {{ $semanticColors['success']['icon'] }}" />
                                        <div>
                                            <flux:heading size="lg" class="{{ $semanticColors['success']['text'] }}">Tips for Success</flux:heading>
                                            <flux:subheading class="{{ $semanticColors['success']['icon'] }}">Maximize your project potential</flux:subheading>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        @if ($project->pitches->count() === 0)
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Share your project on social media to attract more producers</span>
                                                </div>
                                            </div>
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Add reference tracks to help producers understand your vision</span>
                                                </div>
                                            </div>
                                        @elseif($project->pitches->where('status', 'approved')->count() === 0)
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Review pitches carefully and communicate with producers</span>
                                                </div>
                                            </div>
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Ask questions to ensure the producer understands your needs</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Provide clear feedback to help your producer deliver the best results</span>
                                                </div>
                                            </div>
                                            <div class="p-3 {{ $semanticColors['success']['bg'] }} rounded-xl {{ $semanticColors['success']['border'] }} border">
                                                <div class="flex items-start gap-3">
                                                    <flux:icon.check class="w-5 h-5 {{ $semanticColors['success']['icon'] }} flex-shrink-0 mt-0.5" />
                                                    <span class="text-sm font-medium {{ $semanticColors['success']['text'] }}">Upload reference files to guide the production process</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </flux:card>

                                
                            @elseif($project->isContest())
                                <!-- Contest Prizes Component -->
                                @livewire('project.component.contest-prizes', ['project' => $project, 'workflowColors' => $workflowColors, 'semanticColors' => $semanticColors], key('contest-prizes-' . $project->id))

                                <!-- Contest Early Closure Component -->
                                <div class="mb-2">
                                    @include('livewire.project.component.contest-early-closure', ['project' => $project, 'workflowColors' => $workflowColors, 'semanticColors' => $semanticColors])
                                </div>


                                
                            @elseif($project->isDirectHire())
                                <div class="mb-2 rounded-lg {{ $workflowColors['border'] }} {{ $workflowColors['bg'] }} p-4 border">
                                    <h3 class="mb-2 text-lg font-semibold {{ $workflowColors['text_primary'] }}"><i
                                            class="fas fa-user-check mr-2"></i>Direct Hire Details</h3>
                                    @if ($project->targetProducer)
                                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">Assigned Producer:
                                            @if ($project->targetProducer->username)
                                                <a href="{{ route('profile.username', $project->targetProducer->username) }}"
                                                   wire:navigate
                                                    class="text-primary font-semibold hover:underline">
                                                    {{ $project->targetProducer->name }}
                                                </a>
                                            @else
                                                <span
                                                    class="text-primary font-semibold">{{ $project->targetProducer->name }}</span>
                                            @endif
                                        </p>
                                    @else
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No producer assigned yet.</p>
                                    @endif
                                </div>
                            @elseif($project->isClientManagement())
                                <div class="mb-2 rounded-lg {{ $workflowColors['border'] }} {{ $workflowColors['bg'] }} p-4 border">
                                    <h3 class="mb-2 text-lg font-semibold {{ $workflowColors['text_primary'] }}"><i
                                            class="fas fa-briefcase mr-2"></i>Client Management Details</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Client Name:</strong>
                                        {{ $project->client_name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Client Email:</strong>
                                        {{ $project->client_email ?? 'N/A' }}</p>
                                    {{-- Add Resend Invite Button --}}
                                    @can('update', $project)
                                        {{-- Only project owner (producer) can resend --}}
                                        <button wire:click="resendClientInvite"
                                            class="btn btn-sm btn-outline btn-primary mt-2">
                                            <i class="fas fa-paper-plane mr-1"></i> Resend Client Invite
                                        </button>
                                    @endcan
                                </div>
                            @endif
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

        {{-- Project Delete Confirmation Modal --}}
        <flux:modal name="delete-project" class="max-w-md">
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    <flux:heading size="lg" class="text-red-800 dark:text-red-200">Delete Project</flux:heading>
                </div>
                
                <div class="space-y-4">
                    <flux:subheading class="text-gray-600 dark:text-gray-400">
                        Are you sure you want to permanently delete this project? This will also delete:
                    </flux:subheading>
                    
                    <ul class="list-inside list-disc text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>All project files</li>
                        <li>All pitch files and data</li>
                        <li>All project history and events</li>
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
                        Delete Project
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        {{-- Project Content Tabs (Consider hiding these if project is a contest) --}}
        <div class="mt-6">
            <div class="border-b border-gray-200">

            </div>

        </div>
        
        <!-- Project Image Upload Modal -->
        <x-project.image-upload-modal :project="$project" :imagePreviewUrl="$imagePreviewUrl" />
        
        <!-- Complete Pitch Modal -->
        @livewire('project.complete-pitch-modal',[] , key('complete-pitch-modal'))
        
        <!-- Google Drive Backup Modal -->
        @livewire('google-drive-backup-modal', ['model' => $project], key('google-drive-backup-' . $project->id))
        
        <!-- Google Drive Backup History Modal -->
        @livewire('google-drive-backup-history-modal', ['model' => $project, 'viewType' => 'project'], key('google-drive-backup-history-' . $project->id))
        </div>
    </div>


{{-- Reddit Posting Polling Script --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let redditPollingInterval = null;
        
        // Listen for start polling event
        document.addEventListener('livewire:init', () => {
            Livewire.on('start-reddit-polling', () => {
                console.log('Starting Reddit polling...');
                
                // Clear any existing interval
                if (redditPollingInterval) {
                    clearInterval(redditPollingInterval);
                }
                
                // Start polling every 3 seconds
                redditPollingInterval = setInterval(() => {
                    Livewire.dispatch('checkRedditStatus');
                }, 3000);
            });
            
            // Listen for stop polling event
            Livewire.on('stop-reddit-polling', () => {
                console.log('Stopping Reddit polling...');
                if (redditPollingInterval) {
                    clearInterval(redditPollingInterval);
                    redditPollingInterval = null;
                }
            });
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (redditPollingInterval) {
                clearInterval(redditPollingInterval);
            }
        });
    });
</script>


</div>
</x-draggable-upload-page>
