<div>
    <!-- Background Effects -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div
            class="absolute -right-40 -top-40 h-80 w-80 rounded-full bg-gradient-to-br from-blue-400/20 to-purple-600/20 blur-3xl">
        </div>
        <div
            class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-gradient-to-tr from-purple-400/20 to-pink-600/20 blur-3xl">
        </div>
        <div
            class="absolute left-1/4 top-1/3 h-64 w-64 rounded-full bg-gradient-to-r from-blue-300/10 to-purple-300/10 blur-2xl">
        </div>
        <div
            class="absolute bottom-1/3 right-1/4 h-48 w-48 rounded-full bg-gradient-to-l from-purple-300/15 to-pink-300/15 blur-xl">
        </div>
    </div>

    <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30">
        <div class="container mx-auto px-2 sm:px-4">
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
                        flex-direction: column;
                        align-items: flex-start;
                    }

                    .track-item .track-actions {
                        margin-top: 0.5rem;
                        margin-left: 2.5rem;
                        width: 100%;
                        display: flex;
                        justify-content: flex-start;
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
                    <!-- Project Header -->
                    <x-project.header :project="$project" :hasPreviewTrack="$this->hasPreviewTrack" context="manage" :showEditButton="true" />
                    <div class="grid grid-cols-1 gap-3 p-0 sm:gap-4 sm:p-2 md:p-4 lg:grid-cols-3">
                        <!-- Main Content Area (2/3 width on large screens) -->
                        <div class="space-y-6 lg:col-span-2">
                            {{-- Quick Actions - Show first on mobile for immediate access --}}
                            @if ($project->isStandard() || $project->isContest())
                                <div
                                    class="rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 p-4 shadow-xl backdrop-blur-md lg:hidden">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                            <i class="fas fa-bolt text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-blue-800">Quick Actions</h3>
                                            <p class="text-sm text-blue-600">Manage your project efficiently</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('projects.show', $project) }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2.5 text-sm font-medium text-white transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-lg">
                                            <i class="fas fa-eye mr-1.5"></i>View Public
                                        </a>
                                        <a href="{{ route('projects.edit', $project) }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-gray-600 to-gray-700 px-3 py-2.5 text-sm font-medium text-white transition-all duration-200 hover:scale-105 hover:from-gray-700 hover:to-gray-800 hover:shadow-lg">
                                            <i class="fas fa-edit mr-1.5"></i>Edit Project
                                        </a>
                                        
                                        {{-- Reddit Post Button --}}
                                        @if ($project->is_published)
                                            @if ($project->hasBeenPostedToReddit())
                                                <a href="{{ $project->getRedditUrl() }}" target="_blank" rel="noopener"
                                                    class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                                                    <i class="fab fa-reddit mr-2"></i>View on Reddit
                                                </a>
                                            @else
                                                <button wire:click="postToReddit"
                                                    @if($isPostingToReddit) disabled @endif
                                                    class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg {{ $isPostingToReddit ? 'opacity-75 cursor-not-allowed' : '' }}">
                                                    @if($isPostingToReddit)
                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Posting...
                                                    @else
                                                        <i class="fab fa-reddit mr-2"></i>Post to r/MixPitch
                                                    @endif
                                                </button>
                                            @endif
                                        @endif
                                        
                                        @if (!$project->isClientManagement())
                                            @if ($project->is_published)
                                                <button wire:click="unpublish"
                                                    class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-amber-600 hover:to-orange-600 hover:shadow-lg">
                                                    <i class="fas fa-eye-slash mr-2"></i>Unpublish Project
                                                </button>
                                            @else
                                                <button wire:click="publish"
                                                    class="col-span-2 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
                                                    <i class="fas fa-globe mr-2"></i>Publish Project
                                                </button>
                                            @endif
                                        @else
                                            {{-- Client Management projects remain private by design --}}
                                            <div class="col-span-2 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 text-center">
                                                <div class="flex items-center justify-center mb-2">
                                                    <i class="fas fa-user-shield text-blue-600 mr-2"></i>
                                                    <span class="font-semibold text-blue-800">Private Project</span>
                                                </div>
                                                <p class="text-sm text-blue-700">
                                                    Client Management projects remain private and are only accessible through secure client portals.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Workflow Status Component --}}
                            @if ($project->isStandard() || $project->isContest() || $project->isDirectHire())
                                <div>
                                    <x-project.workflow-status :project="$project" />
                                </div>
                            @endif

                            {{-- Project Insights - Show early on mobile for key metrics --}}
                            @if ($project->isStandard())
                                <div class="lg:hidden">
                                    <x-project.quick-stats-mobile :project="$project" />
                                </div>
                            @endif

                            {{-- Pitches Section / Contest Entries --}}
                            @if ($project->isContest())
                                {{-- Contest Judging Component (includes Contest Entries) --}}
                                @livewire('project.component.contest-judging', ['project' => $project], key('contest-judging-' . $project->id))
                            @else
                                {{-- Regular Pitches Section --}}
                                <x-project.pitch-list :project="$project" />
                            @endif

                            {{-- License Management Component --}}
                            <div>
                                <x-project.license-management :project="$project" />
                            </div>

                            @if ($project->isStandard() || $project->isContest())
                                <!-- Upload Files Section -->
                                <div x-data="{ showUploader: true }"
                                    class="overflow-hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-purple-50/90 shadow-xl backdrop-blur-md">
                                    <div
                                        class="border-b border-white/20 bg-gradient-to-r from-purple-500/10 via-indigo-500/10 to-purple-500/10 p-4 lg:p-6 backdrop-blur-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div
                                                    class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600">
                                                    <i class="fas fa-upload text-lg text-white"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-purple-800">{{ $project->isContest() ? 'Contest Files' : 'Project Files' }}</h3>
                                                    <p class="text-sm text-purple-600">Upload and manage {{ $project->isContest() ? 'contest' : 'project' }}
                                                        resources</p>
                                                </div>
                                            </div>
                                                                        <button @click="showUploader = !showUploader"
                                class="inline-flex items-center rounded-xl bg-gradient-to-r from-purple-100 to-indigo-100 px-3 lg:px-4 py-2 font-medium text-purple-700 transition-all duration-200 hover:scale-105 hover:from-purple-200 hover:to-indigo-200">
                                <span class="hidden lg:inline" x-text="showUploader ? 'Hide Uploader' : 'Show Uploader'"></span>
                                <i class="fas lg:ml-2"
                                    :class="showUploader ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                                        </div>
                                    </div>

                                    <!-- Storage Usage Indicator -->
                                    <div class="border-b border-white/20 p-4 lg:p-6">
                                        <div
                                            class="rounded-xl border border-purple-200/50 bg-gradient-to-br from-white/80 to-purple-50/80 p-4 backdrop-blur-sm">
                                            <div class="mb-3 flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div
                                                        class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500">
                                                        <i class="fas fa-hdd text-sm text-white"></i>
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-bold text-purple-800">Storage Used:
                                                            {{ $storageLimitMessage }}</span>
                                                        <div class="text-xs text-purple-600">
                                                            {{ $this->formatFileSize($storageRemaining) }} remaining
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="h-3 w-full rounded-full bg-purple-100/60 shadow-inner">
                                                <div class="{{ $storageUsedPercentage > 90 ? 'bg-gradient-to-r from-red-500 to-pink-500' : ($storageUsedPercentage > 70 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-purple-500 to-indigo-500') }} h-3 rounded-full transition-all duration-500"
                                                    style="width: {{ $storageUsedPercentage }}%"></div>
                                            </div>
                                            <div class="mt-2 flex items-center text-xs text-purple-600">
                                                <i class="fas fa-info-circle mr-2 text-purple-500"></i>
                                                Maximum file size: 200MB. Storage based on your subscription plan.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File Uploader Component -->
                                    <div x-show="showUploader" x-transition class="border-b border-white/20 p-4 lg:p-6">
                                        <div
                                            class="overflow-hidden rounded-2xl border border-purple-200/50 bg-gradient-to-br from-white/90 to-purple-50/90 shadow-lg backdrop-blur-sm">
                                            <div
                                                class="border-b border-purple-200/50 bg-gradient-to-r from-purple-100/80 to-indigo-100/80 p-4 backdrop-blur-sm">
                                                <div class="flex items-center">
                                                    <div
                                                        class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600">
                                                        <i class="fas fa-cloud-upload-alt text-sm text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="font-bold text-purple-800">Upload New Files</h5>
                                                        <p class="text-xs text-purple-600">Upload audio, PDFs, or images
                                                            to share with {{ $project->isContest() ? 'contest participants' : 'collaborators' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                @if($this->canUploadFiles)
                                                    <livewire:uppy-file-uploader :model="$project"
                                                        wire:key="enhanced-project-uploader-{{ $project->id }}" />
                                                @else
                                                    <div class="text-center py-8">
                                                        <p class="text-gray-500 mb-2">File uploads are not available for this project.</p>
                                                        @if($project->status === App\Models\Project::STATUS_COMPLETED)
                                                            <p class="text-sm text-gray-400">Project is completed - no additional files can be uploaded.</p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Files List Section -->
                                    <div
                                        class="mx-4 lg:mx-6 mt-0 mb-4 lg:mb-6 overflow-hidden rounded-2xl border border-purple-200/50 bg-gradient-to-br from-white/90 to-purple-50/90 shadow-lg backdrop-blur-sm">
                                        <div
                                            class="border-b border-purple-200/50 bg-gradient-to-r from-purple-100/80 to-indigo-100/80 p-4 backdrop-blur-sm">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div
                                                        class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600">
                                                        <i class="fas fa-folder text-sm text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="font-bold text-purple-800">Files
                                                            ({{ $project->files->count() }})</h5>
                                                        @if ($project->files->count() > 0)
                                                            <div class="text-xs text-purple-600">Total:
                                                                {{ $this->formatFileSize($project->files->sum('size')) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="divide-y divide-purple-100/50">
                                            @forelse($project->files as $file)
                                                <div
                                                    class="track-item @if (isset($newlyUploadedFileIds) && in_array($file->id, $newlyUploadedFileIds)) animate-fade-in @endif group flex items-center justify-between px-4 py-4 transition-all duration-300 hover:bg-gradient-to-r hover:from-purple-50/50 hover:to-indigo-50/50">
                                                    <div class="flex flex-1 items-center overflow-hidden pr-2">
                                                        <div
                                                            class="{{ $file->id == $project->preview_track ? 'bg-gradient-to-br from-purple-500 to-indigo-600 text-white' : 'bg-gradient-to-br from-purple-100 to-indigo-100 text-purple-600' }} mr-3 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl shadow-md transition-transform duration-200 group-hover:scale-105">
                                                            <i class="fas fa-music"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="truncate font-semibold text-purple-900">
                                                                {{ $file->file_name }}
                                                                @if ($file->id == $project->preview_track)
                                                                    <span
                                                                        class="ml-2 inline-flex items-center rounded-lg bg-gradient-to-r from-purple-500 to-indigo-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">
                                                                        <i class="fas fa-star mr-1"></i>Preview
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center text-xs text-purple-600">
                                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                                <span>{{ $file->created_at->format('M d, Y') }}</span>
                                                                <span class="mx-2">•</span>
                                                                <i class="fas fa-weight-hanging mr-1"></i>
                                                                <span>{{ $file->formatted_size }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        @if ($file->id != $project->preview_track)
                                                            <button
                                                                wire:click="togglePreviewTrack({{ $file->id }})"
                                                                class="rounded-lg bg-gradient-to-r from-purple-100 to-indigo-100 p-2 text-purple-600 transition-all duration-200 hover:scale-105 hover:from-purple-200 hover:to-indigo-200 hover:text-purple-700">
                                                                <i class="fas fa-star text-sm"></i>
                                                            </button>
                                                        @else
                                                            <button wire:click="clearPreviewTrack"
                                                                class="rounded-lg bg-gradient-to-r from-purple-500 to-indigo-600 p-2 text-white transition-all duration-200 hover:scale-105 hover:from-purple-600 hover:to-indigo-700">
                                                                <i class="fas fa-star-half-alt text-sm"></i>
                                                            </button>
                                                        @endif

                                                        <button wire:click="getDownloadUrl({{ $file->id }})"
                                                            class="rounded-lg bg-gradient-to-r from-blue-100 to-blue-200 p-2 text-blue-600 transition-all duration-200 hover:scale-105 hover:from-blue-200 hover:to-blue-300 hover:text-blue-700">
                                                            <i class="fas fa-download text-sm"></i>
                                                        </button>

                                                        <button wire:click="confirmDeleteFile({{ $file->id }})"
                                                            class="rounded-lg bg-gradient-to-r from-red-100 to-pink-100 p-2 text-red-600 transition-all duration-200 hover:scale-105 hover:from-red-200 hover:to-pink-200 hover:text-red-700">
                                                            <i class="fas fa-trash text-sm"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="p-8 text-center">
                                                    <div
                                                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-100 to-indigo-100">
                                                        <i class="fas fa-folder-open text-2xl text-purple-400"></i>
                                                    </div>
                                                    <h4 class="mb-2 text-lg font-bold text-purple-800">No files
                                                        uploaded yet</h4>
                                                    <p class="text-sm text-purple-600">Upload files to share with
                                                        {{ $project->isContest() ? 'contest participants' : 'collaborators' }}</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Tips for Success - Show on mobile after main content --}}
                            @if ($project->isStandard())
                                <div
                                    class="rounded-2xl border border-green-200/50 bg-gradient-to-br from-green-50/90 to-emerald-50/90 p-4 shadow-lg backdrop-blur-sm lg:hidden">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                                            <i class="fas fa-lightbulb text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-green-800">Tips for Success</h3>
                                            <p class="text-sm text-green-600">Maximize your project potential</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        @if ($project->pitches->count() === 0)
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Share your project on
                                                    social media to attract more producers</span>
                                            </div>
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Add reference tracks
                                                    to help producers understand your vision</span>
                                            </div>
                                        @elseif($project->pitches->where('status', 'approved')->count() === 0)
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Review pitches
                                                    carefully and communicate with producers</span>
                                            </div>
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Ask questions to
                                                    ensure the producer understands your needs</span>
                                            </div>
                                        @else
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Provide clear feedback
                                                    to help your producer deliver the best results</span>
                                            </div>
                                            <div
                                                class="flex items-start rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                    <i class="fas fa-check text-xs text-white"></i>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Upload reference files
                                                    to guide the production process</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Standard Project Info - Show on mobile after tips --}}
                            @if ($project->isStandard())
                                <div
                                    class="rounded-2xl border border-blue-200/50 bg-gradient-to-br from-blue-50/90 to-indigo-50/90 p-4 shadow-lg backdrop-blur-sm lg:hidden">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                            <i class="fas fa-users text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-blue-800">Standard Project</h3>
                                            <p class="text-sm text-blue-600">Open collaboration workflow</p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            class="rounded-xl border border-blue-200/50 bg-white/60 p-4 backdrop-blur-sm">
                                            <div class="flex items-start">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
                                                    <i class="fas fa-users text-sm text-white"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1 text-sm font-bold text-blue-800">Open Collaboration
                                                    </h4>
                                                    <p class="text-xs text-blue-700">Any producer can submit a pitch
                                                        for your project. Review and approve the best fit.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl border border-blue-200/50 bg-white/60 p-4 backdrop-blur-sm">
                                            <div class="flex items-start">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
                                                    <i class="fas fa-handshake text-sm text-white"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1 text-sm font-bold text-blue-800">Direct
                                                        Communication</h4>
                                                    <p class="text-xs text-blue-700">Work directly with your chosen
                                                        producer throughout the project lifecycle.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Danger Zone - Mobile --}}
                            @if ($project->isStandard() || $project->isContest())
                                <div class="relative lg:hidden">
                                    <!-- Background Effects -->
                                    <div
                                        class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-50/30 via-pink-50/20 to-red-50/30">
                                    </div>
                                    <div class="absolute left-2 top-2 h-16 w-16 rounded-full bg-red-400/10 blur-xl"></div>
                                    <div class="absolute bottom-2 right-2 h-12 w-12 rounded-full bg-pink-400/10 blur-lg">
                                    </div>

                                    <!-- Content -->
                                    <div
                                        class="relative rounded-2xl border border-white/20 bg-white/95 p-4 lg:p-6 shadow-xl backdrop-blur-md">
                                        <div class="mb-4 flex items-center">
                                            <div
                                                class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-pink-600">
                                                <i class="fas fa-exclamation-triangle text-lg text-white"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-red-800">Danger Zone</h3>
                                                <p class="text-sm text-red-600">Irreversible actions</p>
                                            </div>
                                        </div>
                                        <div
                                            class="mb-4 rounded-xl border border-red-200/50 bg-gradient-to-r from-red-50/80 to-pink-50/80 p-4 backdrop-blur-sm">
                                            <p class="text-sm font-medium text-red-700">
                                                Permanently delete this {{ $project->isContest() ? 'contest' : 'project' }} and all associated files. This action cannot
                                                be undone.
                                            </p>
                                        </div>
                                        <button wire:click="confirmDeleteProject"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-pink-600 px-4 py-3 font-semibold text-white transition-all duration-200 hover:scale-105 hover:from-red-700 hover:to-pink-700 hover:shadow-lg">
                                            <i class="fas fa-trash-alt mr-2"></i>Delete {{ $project->isContest() ? 'Contest' : 'Project' }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Sidebar (1/3 width on large screens) -->
                        <div class="space-y-6 lg:col-span-1">
                            {{-- Workflow Type Specific Information --}}
                            @if ($project->isStandard())
                                <div
                                    class="mb-6 hidden rounded-2xl border border-blue-200/50 bg-gradient-to-br from-blue-50/90 to-indigo-50/90 p-4 lg:p-6 shadow-lg backdrop-blur-sm lg:block">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                            <i class="fas fa-users text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-blue-800">Standard Project</h3>
                                            <p class="text-sm text-blue-600">Open collaboration workflow</p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            class="rounded-xl border border-blue-200/50 bg-white/60 p-4 backdrop-blur-sm">
                                            <div class="flex items-start">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
                                                    <i class="fas fa-users text-sm text-white"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1 text-sm font-bold text-blue-800">Open Collaboration
                                                    </h4>
                                                    <p class="text-xs text-blue-700">Any producer can submit a pitch
                                                        for your project. Review and approve the best fit.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl border border-blue-200/50 bg-white/60 p-4 backdrop-blur-sm">
                                            <div class="flex items-start">
                                                <div
                                                    class="mr-3 mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
                                                    <i class="fas fa-handshake text-sm text-white"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1 text-sm font-bold text-blue-800">Direct
                                                        Communication</h4>
                                                    <p class="text-xs text-blue-700">Work directly with your chosen
                                                        producer throughout the project lifecycle.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Insights -->
                                <div class="mb-6 hidden lg:block">
                                    <x-project.quick-stats :project="$project" />
                                </div>

                                <!-- Quick Actions -->
                                <div
                                    class="mb-6 hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 p-4 lg:p-6 shadow-xl backdrop-blur-md lg:block">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                            <i class="fas fa-bolt text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-blue-800">Quick Actions</h3>
                                            <p class="text-sm text-blue-600">Manage your project efficiently</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <a href="{{ route('projects.show', $project) }}"
                                            class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-lg">
                                            <i class="fas fa-eye mr-2"></i>View Public Page
                                        </a>
                                        <a href="{{ route('projects.edit', $project) }}"
                                            class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-gray-600 to-gray-700 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-gray-700 hover:to-gray-800 hover:shadow-lg">
                                            <i class="fas fa-edit mr-2"></i>Edit Project
                                        </a>
                                        
                                        {{-- Reddit Post Button --}}
                                        @if ($project->is_published)
                                            @if ($project->hasBeenPostedToReddit())
                                                <a href="{{ $project->getRedditUrl() }}" target="_blank" rel="noopener"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                                                    <i class="fab fa-reddit mr-2"></i>View on Reddit
                                                </a>
                                            @else
                                                <button wire:click="postToReddit"
                                                    @if($isPostingToReddit) disabled @endif
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg {{ $isPostingToReddit ? 'opacity-75 cursor-not-allowed' : '' }}">
                                                    @if($isPostingToReddit)
                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Posting...
                                                    @else
                                                        <i class="fab fa-reddit mr-2"></i>Post to r/MixPitch
                                                    @endif
                                                </button>
                                            @endif
                                        @endif
                                        
                                        @if (!$project->isClientManagement())
                                            @if ($project->is_published)
                                                <button wire:click="unpublish"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-amber-600 hover:to-orange-600 hover:shadow-lg">
                                                    <i class="fas fa-eye-slash mr-2"></i>Unpublish
                                                </button>
                                            @else
                                                <button wire:click="publish"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
                                                    <i class="fas fa-globe mr-2"></i>Publish Project
                                                </button>
                                            @endif
                                        @else
                                            {{-- Client Management projects remain private by design --}}
                                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-3 text-center">
                                                <div class="flex items-center justify-center mb-1">
                                                    <i class="fas fa-user-shield text-blue-600 mr-2"></i>
                                                    <span class="font-semibold text-blue-800 text-sm">Private Project</span>
                                                </div>
                                                <p class="text-xs text-blue-700">
                                                    Client projects remain private and accessible only through secure portals.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Tips & Best Practices -->
                                <div
                                    class="mb-6 hidden rounded-2xl border border-green-200/50 bg-gradient-to-br from-green-50/90 to-emerald-50/90 p-4 lg:p-6 shadow-lg backdrop-blur-sm lg:block">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600">
                                            <i class="fas fa-lightbulb text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-green-800">Tips for Success</h3>
                                            <p class="text-sm text-green-600">Maximize your project potential</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        @if ($project->pitches->count() === 0)
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Share your project
                                                        on social media to attract more producers</span>
                                                </div>
                                            </div>
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Add reference
                                                        tracks to help producers understand your vision</span>
                                                </div>
                                            </div>
                                        @elseif($project->pitches->where('status', 'approved')->count() === 0)
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Review pitches
                                                        carefully and communicate with producers</span>
                                                </div>
                                            </div>
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Ask questions to
                                                        ensure the producer understands your needs</span>
                                                </div>
                                            </div>
                                        @else
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Provide clear
                                                        feedback to help your producer deliver the best results</span>
                                                </div>
                                            </div>
                                            <div
                                                class="rounded-xl border border-green-200/50 bg-white/60 p-3 backdrop-blur-sm">
                                                <div class="flex items-start">
                                                    <div
                                                        class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                                        <i class="fas fa-check text-xs text-white"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-green-800">Upload reference
                                                        files to guide the production process</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Danger Zone - Desktop --}}
                                <div class="relative hidden lg:block">
                                    <!-- Background Effects -->
                                    <div
                                        class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-50/30 via-pink-50/20 to-red-50/30">
                                    </div>
                                    <div class="absolute left-2 top-2 h-16 w-16 rounded-full bg-red-400/10 blur-xl">
                                    </div>
                                    <div
                                        class="absolute bottom-2 right-2 h-12 w-12 rounded-full bg-pink-400/10 blur-lg">
                                    </div>

                                    <!-- Content -->
                                    <div
                                        class="relative rounded-2xl border border-white/20 bg-white/95 p-4 lg:p-6 shadow-xl backdrop-blur-md">
                                        <div class="mb-4 flex items-center">
                                            <div
                                                class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-pink-600">
                                                <i class="fas fa-exclamation-triangle text-lg text-white"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-red-800">Danger Zone</h3>
                                                <p class="text-sm text-red-600">Irreversible actions</p>
                                            </div>
                                        </div>
                                        <div
                                            class="mb-4 rounded-xl border border-red-200/50 bg-gradient-to-r from-red-50/80 to-pink-50/80 p-4 backdrop-blur-sm">
                                            <p class="text-sm font-medium text-red-700">
                                                Permanently delete this project and all associated files. This action
                                                cannot be undone.
                                            </p>
                                        </div>
                                        <button wire:click="confirmDeleteProject"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-pink-600 px-4 py-3 font-semibold text-white transition-all duration-200 hover:scale-105 hover:from-red-700 hover:to-pink-700 hover:shadow-lg">
                                            <i class="fas fa-trash-alt mr-2"></i>Delete Project
                                        </button>
                                    </div>
                                </div>
                            @elseif($project->isContest())
                                <!-- Contest Prizes Component -->
                                @livewire('project.component.contest-prizes', ['project' => $project], key('contest-prizes-' . $project->id))

                                <!-- Contest Early Closure Component -->
                                <div class="mb-6">
                                    @include('livewire.project.component.contest-early-closure', ['project' => $project])
                                </div>

                                {{-- Quick Actions for Contest Projects --}}
                                <div
                                    class="mb-6 hidden rounded-2xl border border-white/30 bg-gradient-to-br from-white/95 to-blue-50/90 p-4 lg:p-6 shadow-xl backdrop-blur-md lg:block">
                                    <div class="mb-4 flex items-center">
                                        <div
                                            class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600">
                                            <i class="fas fa-bolt text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-blue-800">Quick Actions</h3>
                                            <p class="text-sm text-blue-600">Manage your contest efficiently</p>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <a href="{{ route('projects.show', $project) }}"
                                            class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-lg">
                                            <i class="fas fa-eye mr-2"></i>View Public Page
                                        </a>
                                        <a href="{{ route('projects.edit', $project) }}"
                                            class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-gray-600 to-gray-700 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-gray-700 hover:to-gray-800 hover:shadow-lg">
                                            <i class="fas fa-edit mr-2"></i>Edit Contest
                                        </a>
                                        
                                        {{-- Contest Judging Button --}}
                                        @if($project->isSubmissionPeriodClosed() && !$project->isJudgingFinalized())
                                            <a href="{{ route('projects.contest.judging', $project) }}"
                                                class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-purple-700 hover:to-indigo-700 hover:shadow-lg">
                                                <i class="fas fa-gavel mr-2"></i>Judge Contest
                                            </a>
                                        @elseif($project->isJudgingFinalized())
                                            <a href="{{ route('projects.contest.results', $project) }}"
                                                class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
                                                <i class="fas fa-trophy mr-2"></i>View Results
                                            </a>
                                        @endif
                                        
                                        {{-- Reddit Post Button --}}
                                        @if ($project->is_published)
                                            @if ($project->hasBeenPostedToReddit())
                                                <a href="{{ $project->getRedditUrl() }}" target="_blank" rel="noopener"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg">
                                                    <i class="fab fa-reddit mr-2"></i>View on Reddit
                                                </a>
                                            @else
                                                <button wire:click="postToReddit"
                                                    @if($isPostingToReddit) disabled @endif
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-orange-600 hover:to-red-600 hover:shadow-lg {{ $isPostingToReddit ? 'opacity-75 cursor-not-allowed' : '' }}">
                                                    @if($isPostingToReddit)
                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Posting...
                                                    @else
                                                        <i class="fab fa-reddit mr-2"></i>Post to r/MixPitch
                                                    @endif
                                                </button>
                                            @endif
                                        @endif
                                        
                                        @if (!$project->isClientManagement())
                                            @if ($project->is_published)
                                                <button wire:click="unpublish"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-amber-600 hover:to-orange-600 hover:shadow-lg">
                                                    <i class="fas fa-eye-slash mr-2"></i>Unpublish Contest
                                                </button>
                                            @else
                                                <button wire:click="publish"
                                                    class="block inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 font-medium text-white transition-all duration-200 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-lg">
                                                    <i class="fas fa-globe mr-2"></i>Publish Contest
                                                </button>
                                            @endif
                                        @else
                                            {{-- Client Management projects should not appear in contest sections --}}
                                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-3 text-center">
                                                <div class="flex items-center justify-center mb-1">
                                                    <i class="fas fa-user-shield text-blue-600 mr-2"></i>
                                                    <span class="font-semibold text-blue-800 text-sm">Private Project</span>
                                                </div>
                                                <p class="text-xs text-blue-700">
                                                    Client projects remain private and accessible only through secure portals.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Danger Zone for Contest Projects --}}
                                <div class="relative hidden lg:block">
                                    <!-- Background Effects -->
                                    <div
                                        class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-50/30 via-pink-50/20 to-red-50/30">
                                    </div>
                                    <div class="absolute left-2 top-2 h-16 w-16 rounded-full bg-red-400/10 blur-xl">
                                    </div>
                                    <div
                                        class="absolute bottom-2 right-2 h-12 w-12 rounded-full bg-pink-400/10 blur-lg">
                                    </div>

                                    <!-- Content -->
                                    <div
                                        class="relative rounded-2xl border border-white/20 bg-white/95 p-4 lg:p-6 shadow-xl backdrop-blur-md">
                                        <div class="mb-4 flex items-center">
                                            <div
                                                class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-pink-600">
                                                <i class="fas fa-exclamation-triangle text-lg text-white"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-red-800">Danger Zone</h3>
                                                <p class="text-sm text-red-600">Irreversible actions</p>
                                            </div>
                                        </div>
                                        <div
                                            class="mb-4 rounded-xl border border-red-200/50 bg-gradient-to-r from-red-50/80 to-pink-50/80 p-4 backdrop-blur-sm">
                                            <p class="text-sm font-medium text-red-700">
                                                Permanently delete this contest and all associated files, entries, and judging data. This action cannot be undone.
                                            </p>
                                        </div>
                                        <button wire:click="confirmDeleteProject"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-pink-600 px-4 py-3 font-semibold text-white transition-all duration-200 hover:scale-105 hover:from-red-700 hover:to-pink-700 hover:shadow-lg">
                                            <i class="fas fa-trash-alt mr-2"></i>Delete Contest
                                        </button>
                                    </div>
                                </div>
                            @elseif($project->isDirectHire())
                                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                                    <h3 class="mb-2 text-lg font-semibold text-green-800"><i
                                            class="fas fa-user-check mr-2"></i>Direct Hire Details</h3>
                                    @if ($project->targetProducer)
                                        <p class="mt-1 text-sm text-gray-700">Assigned Producer:
                                            @if ($project->targetProducer->username)
                                                <a href="{{ route('profile.username', $project->targetProducer->username) }}"
                                                    class="text-primary font-semibold hover:underline">
                                                    {{ $project->targetProducer->name }}
                                                </a>
                                            @else
                                                <span
                                                    class="text-primary font-semibold">{{ $project->targetProducer->name }}</span>
                                            @endif
                                        </p>
                                    @else
                                        <p class="mt-1 text-sm text-gray-500">No producer assigned yet.</p>
                                    @endif
                                </div>
                            @elseif($project->isClientManagement())
                                <div class="mb-6 rounded-lg border border-purple-200 bg-purple-50 p-4">
                                    <h3 class="mb-2 text-lg font-semibold text-purple-800"><i
                                            class="fas fa-briefcase mr-2"></i>Client Management Details</h3>
                                    <p class="text-sm"><strong>Client Name:</strong>
                                        {{ $project->client_name ?? 'N/A' }}</p>
                                    <p class="text-sm"><strong>Client Email:</strong>
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
        @if ($showDeleteModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                 x-data="{ 
                     handleKeydown(event) { 
                         if (event.key === 'Enter' && !this.$wire.isDeleting) { 
                             this.$wire.deleteFile(); 
                         } 
                     } 
                 }"
                 x-on:keydown.window="handleKeydown($event)">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Confirm File Deletion</h3>
                    <p class="mb-6 text-gray-600">Are you sure you want to delete this file? This action cannot be
                        undone.</p>
                    <div class="flex justify-end space-x-3">
                        <button wire:click="cancelDeleteFile" 
                                class="btn btn-outline"
                                wire:loading.attr="disabled"
                                wire:target="deleteFile">
                            Cancel
                        </button>
                        <button wire:click="deleteFile" 
                                class="btn btn-error flex items-center justify-center"
                                wire:loading.attr="disabled"
                                wire:target="deleteFile">
                            <span wire:loading.remove wire:target="deleteFile">Delete File</span>
                            <svg wire:loading wire:target="deleteFile" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Project Delete Confirmation Modal --}}
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

        {{-- Project Content Tabs (Consider hiding these if project is a contest) --}}
        <div class="mt-6">
            <div class="border-b border-gray-200">

            </div>

        </div>
        
        <!-- Project Image Upload Modal -->
        <x-project.image-upload-modal :project="$project" :imagePreviewUrl="$imagePreviewUrl" />
        
        <!-- Complete Pitch Modal -->
        @livewire('project.complete-pitch-modal',[] , key('complete-pitch-modal'))
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
