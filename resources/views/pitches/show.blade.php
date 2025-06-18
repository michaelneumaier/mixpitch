@extends('components.layouts.app')

@section('content')
<!-- Background Effects -->
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-purple-400/20 to-pink-600/20 rounded-full blur-3xl"></div>
    <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-300/10 to-purple-300/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-1/3 right-1/4 w-48 h-48 bg-gradient-to-l from-purple-300/15 to-pink-300/15 rounded-full blur-xl"></div>
</div>

<div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30">
    <div class="container mx-auto px-2 sm:px-4 py-6">
        <!-- Project Header -->
        <x-project.header 
            :project="$pitch->project" 
            :hasPreviewTrack="$pitch->project->hasPreviewTrack()" 
            context="view"
            :showEditButton="auth()->check() && $pitch->project->isOwnedByUser(auth()->user())"
            :userPitch="$pitch"
            :canPitch="false"
        />

        <!-- Mobile Pitch Stats (visible on mobile/tablet, hidden on desktop) -->
        <div class="lg:hidden mb-6">
            <x-pitch.quick-stats-mobile :pitch="$pitch" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Area (2/3 width on large screens) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Pitch Workflow Status -->
                @if($pitch->project->isContest() && in_array($pitch->status, [
                    \App\Models\Pitch::STATUS_CONTEST_ENTRY,
                    \App\Models\Pitch::STATUS_CONTEST_WINNER,
                    \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP,
                    \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED
                ]))
                    <x-contest.workflow-status :pitch="$pitch" />
                @else
                    <x-pitch.workflow-status :pitch="$pitch" />
                @endif

                <!-- Project Management Section (for pitch owner only) -->
                @if (auth()->check() && auth()->id() === $pitch->user_id)
                    @if($pitch->project->isContest() && $pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                        <livewire:pitch.component.manage-contest-pitch :pitch="$pitch" />
                    @else
                        <livewire:pitch.component.manage-pitch :pitch="$pitch" />
                    @endif
                @endif

                <!-- Feedback & Revision History -->
                <div class="bg-gradient-to-br from-white/95 to-blue-50/90 backdrop-blur-md border border-white/30 rounded-2xl shadow-xl p-6">
                    <livewire:pitch.component.feedback-conversation :pitch="$pitch" />
                </div>

                <!-- Pitch History Timeline -->
                <div class="bg-gradient-to-br hidden from-white/95 to-purple-50/90 backdrop-blur-md border border-white/30 rounded-2xl shadow-xl p-6">
                    <livewire:pitch.component.pitch-history :pitch="$pitch" />
                </div>

                <!-- Project Files Section -->
                <div class="bg-gradient-to-br from-purple-50/90 to-indigo-50/90 backdrop-blur-sm border border-purple-200/50 rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                                    <i class="fas fa-music text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-purple-800">Project Files</h3>
                                    <p class="text-sm text-purple-600">Audio files and resources</p>
                                </div>
                            </div>
                            
                            @if ($pitch->status !== \App\Models\Pitch::STATUS_PENDING && !$pitch->project->files->isEmpty())
                                <a href="{{ route('projects.download', $pitch->project) }}"
                                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-download mr-2"></i>Download All
                                </a>
                            @endif
                        </div>

                        @if ($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                            <div class="bg-gradient-to-br from-amber-50/90 to-orange-50/90 backdrop-blur-sm border border-amber-200/50 rounded-xl p-4">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg mr-3">
                                        <i class="fas fa-lock text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-amber-800">Access Restricted</h4>
                                        <p class="text-xs text-amber-700">You don't have access to the project files yet.</p>
                                    </div>
                                </div>
                            </div>
                        @elseif($pitch->project->files->isEmpty())
                            <div class="bg-gradient-to-br from-gray-50/90 to-gray-100/90 backdrop-blur-sm border border-gray-200/50 rounded-xl p-6 text-center">
                                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-gray-200 to-gray-300 rounded-xl mx-auto mb-3">
                                    <i class="fas fa-folder-open text-gray-500 text-lg"></i>
                                </div>
                                <h4 class="text-sm font-medium text-gray-600 mb-1">No Files Available</h4>
                                <p class="text-xs text-gray-500">No files have been uploaded for this project</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($pitch->project->files as $file)
                                    <div class="flex items-center justify-between p-4 bg-white/60 backdrop-blur-sm border border-purple-200/30 rounded-xl hover:bg-white/80 transition-all duration-200 group">
                                        <div class="flex items-center flex-1 min-w-0">
                                            <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg mr-3 group-hover:scale-105 transition-transform duration-200">
                                                <i class="fas fa-file-audio text-purple-600"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-medium text-purple-900 truncate" title="{{ $file->file_name }}">
                                                    {{ $file->file_name }}
                                                </h4>
                                                <p class="text-xs text-purple-600">{{ $file->formatted_size }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ asset('storage/' . $file->file_path) }}"
                                           download="{{ $file->file_name }}"
                                           class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-lg text-xs font-medium transition-all duration-200 hover:scale-105 hover:shadow-md">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Project Description Section -->
                <div class="bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4">
                            <i class="fas fa-align-left text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-blue-800">Project Description</h3>
                            <p class="text-sm text-blue-600">Detailed project information</p>
                        </div>
                    </div>
                    <div class="bg-white/60 backdrop-blur-sm border border-blue-200/30 rounded-xl p-4">
                        <p class="text-blue-900 whitespace-pre-wrap leading-relaxed">{{ $pitch->project->description }}</p>
                    </div>
                </div>

                <!-- Additional Notes Section -->
                @if ($pitch->project->notes)
                    <div class="bg-gradient-to-br from-yellow-50/90 to-amber-50/90 backdrop-blur-sm border border-yellow-200/50 rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center mb-4">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl mr-4">
                                <i class="fas fa-sticky-note text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-yellow-800">Additional Notes</h3>
                                <p class="text-sm text-yellow-600">Extra project details</p>
                            </div>
                        </div>
                        <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                            <p class="text-yellow-900 whitespace-pre-wrap leading-relaxed">{{ $pitch->project->notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Admin Actions Section -->
                @if(Auth::check() && Auth::user()->is_admin)
                    <div class="relative">
                        <!-- Background Effects -->
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-50/30 via-slate-50/20 to-gray-50/30 rounded-2xl"></div>
                        <div class="absolute top-2 left-2 w-16 h-16 bg-gray-400/10 rounded-full blur-xl"></div>
                        <div class="absolute bottom-2 right-2 w-12 h-12 bg-slate-400/10 rounded-full blur-lg"></div>
                        
                        <!-- Admin Content -->
                        <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-gray-600 to-slate-700 rounded-xl mr-3">
                                    <i class="fas fa-user-shield text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">Admin Actions</h3>
                                    <p class="text-sm text-gray-600">Administrative controls</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="#" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-slate-700 hover:from-gray-700 hover:to-slate-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-camera mr-2"></i>View All Snapshots
                                </a>
                                <a href="#" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-edit mr-2"></i>Edit Pitch Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Payout Status (if applicable) -->
                <x-pitch.payout-status :pitch="$pitch" />

                <!-- Pitch Rating (if completed) -->
                @if($pitch->status === 'completed' && $pitch->getCompletionRating())
                    <div class="bg-gradient-to-br from-orange-50/90 to-amber-50/90 backdrop-blur-sm border border-orange-200/50 rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center mb-4">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl mr-4">
                                <i class="fas fa-star text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-orange-800">Pitch Rating</h3>
                                <p class="text-sm text-orange-600">Completion feedback</p>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <span class="text-3xl font-bold text-orange-900 flex items-center">
                                {{ number_format($pitch->getCompletionRating(), 1) }}
                                <span class="text-orange-500 ml-2 text-2xl">★</span>
                            </span>
                            <span class="ml-2 text-orange-700">/ 5</span>
                        </div>
                        <p class="text-orange-700">
                            This pitch received a rating of {{ number_format($pitch->getCompletionRating(), 1) }} out of 5 stars upon completion.
                        </p>
                    </div>
                @endif

                <!-- Project Metadata -->
                <x-pitch.metadata :pitch="$pitch" />

                <!-- Pitch Delete Component (for pitch owner only) -->
                @if(Auth::check() && Auth::id() === $pitch->user_id)
                    <livewire:pitch.component.delete-pitch :pitch="$pitch" />
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

