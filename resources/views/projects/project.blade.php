@extends('components.layouts.app')

@section('content')
<div class="relative min-h-screen bg-gray-50">
    <div class="container mx-auto px-2 sm:px-4 py-6">
        <div class="max-w-6xl mx-auto">
            <!-- Project Header -->
            <x-project.header 
                :project="$project" 
                :hasPreviewTrack="$project->hasPreviewTrack()" 
                context="view"
                :showEditButton="auth()->check() && $project->isOwnedByUser(auth()->user())"
                :userPitch="$userPitch ?? null"
                :canPitch="$canPitch ?? false"
            />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Budget/Prize Section -->
                    @if($project->isContest())
                        <!-- Contest Prizes Display -->
                        <x-contest.prize-display :project="$project" />
                    @else
                        <!-- Standard Budget Section -->
                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <!-- Gradient Border Effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-green-500/20 via-emerald-500/20 to-teal-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                                <!-- Header with Icon -->
                                <div class="flex items-center mb-6">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i class="fas fa-money-bill-wave text-white text-lg"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                        Project Budget
                                    </h3>
                                </div>

                                <!-- Budget Display -->
                                <div class="mb-6">
                                    <div class="flex items-baseline mb-3">
                                        <span class="text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                                            {{ $project->budget == 0 ? 'Free Project' : '$'.number_format($project->budget, 0) }}
                                        </span>
                                        @if($project->budget > 0)
                                            <span class="ml-3 text-lg text-gray-500 font-medium">USD</span>
                                        @endif
                                    </div>

                                    <!-- Budget Type Badge -->
                                    <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium {{ $project->budget == 0 ? 'bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800' : 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800' }}">
                                        <i class="fas {{ $project->budget == 0 ? 'fa-heart' : 'fa-dollar-sign' }} mr-2"></i>
                                        {{ $project->budget == 0 ? 'Collaboration Project' : 'Paid Project' }}
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 rounded-xl p-6 border border-gray-200/50">
                                    <p class="text-gray-700 leading-relaxed">
                                        @if($project->budget == 0)
                                            This is a free collaboration project where artists work together to create something amazing. No payment is expected - just pure creative collaboration and mutual benefit.
                                        @else
                                            This is the allocated budget for this project. The final payment may vary based on project requirements, scope changes, and agreement with the selected collaborator.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Description Section -->
                    <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <!-- Gradient Border Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 via-indigo-500/20 to-purple-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                            <!-- Header with Icon -->
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                    <i class="fas fa-align-left text-white text-lg"></i>
                                </div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                    Project Description
                                </h3>
                            </div>

                            <!-- Description Content -->
                            <div class="prose prose-lg prose-gray max-w-none">
                                <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-200/30">
                                    <p class="text-gray-800 whitespace-pre-wrap leading-relaxed text-lg font-medium">{{ $project->description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collaboration Types -->
                    @if($project->collaboration_type && count(array_filter($project->collaboration_type)) > 0)
                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <!-- Gradient Border Effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 via-purple-500/20 to-pink-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                                <!-- Header with Icon -->
                                <div class="flex items-center mb-6">
                                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i class="fas fa-handshake text-white text-lg"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                        Looking For Collaboration In
                                    </h3>
                                </div>

                                <!-- Collaboration Tags -->
                                <div class="flex flex-wrap gap-4">
                                    @foreach($project->collaboration_type as $key => $value)
                                        @php
                                            // Handle both formats: simple array ['mixing', 'mastering'] and associative array ['mixing' => true]
                                            $collaborationType = '';
                                            if (is_string($key) && $value && $value !== false) {
                                                // Associative array format: ['mixing' => true, 'mastering' => false]
                                                $collaborationType = $key;
                                            } elseif (is_string($value) && !empty($value)) {
                                                // Simple array format: ['mixing', 'mastering']
                                                $collaborationType = $value;
                                            } elseif (is_numeric($key) && is_string($value) && !empty($value)) {
                                                // Indexed array format: [0 => 'mixing', 1 => 'mastering']
                                                $collaborationType = $value;
                                            }
                                            
                                            // Format the collaboration type for display
                                            if ($collaborationType) {
                                                $collaborationType = str_replace('_', ' ', $collaborationType);
                                                $collaborationType = ucwords(strtolower($collaborationType));
                                            }
                                        @endphp
                                        
                                        @if($collaborationType)
                                            <div class="group/tag relative">
                                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-25 group-hover/tag:opacity-40 transition-opacity"></div>
                                                <span class="relative inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold bg-white/90 backdrop-blur-sm border border-indigo-200/50 text-indigo-800 hover:bg-white transition-all duration-200 shadow-lg">
                                                    <i class="fas {{ 
                                                        strtolower($collaborationType) == 'mixing' ? 'fa-sliders-h' : 
                                                        (strtolower($collaborationType) == 'mastering' ? 'fa-compact-disc' : 
                                                        (strtolower($collaborationType) == 'production' ? 'fa-music' : 
                                                        (strtolower($collaborationType) == 'vocals' || strtolower($collaborationType) == 'vocal tuning' ? 'fa-microphone' : 
                                                        (strtolower($collaborationType) == 'audio editing' ? 'fa-cut' :
                                                        (strtolower($collaborationType) == 'instruments' ? 'fa-guitar' : 
                                                        (strtolower($collaborationType) == 'songwriting' ? 'fa-pen-fancy' : 'fa-tasks')))))) 
                                                    }} mr-3 text-indigo-600"></i>
                                                    {{ $collaborationType }}
                                                </span>
                                            </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Additional Notes -->
                    @if ($project->notes)
                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <!-- Gradient Border Effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/20 via-orange-500/20 to-red-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                                <!-- Header with Icon -->
                                <div class="flex items-center mb-6">
                                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i class="fas fa-sticky-note text-white text-lg"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                        Additional Notes
                                    </h3>
                                </div>

                                <!-- Notes Content -->
                                <div class="prose prose-lg prose-gray max-w-none">
                                    <div class="bg-gradient-to-r from-amber-50/50 to-orange-50/50 rounded-xl p-6 border border-amber-200/30">
                                        <p class="text-gray-800 whitespace-pre-wrap leading-relaxed text-lg font-medium">{{ $project->notes }}</p>
                                </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- License & Terms -->
                    <x-project.license-info :project="$project" />

                    <!-- Project Files -->
                    <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <!-- Gradient Border Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-rose-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                            <!-- Header with Icon -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i class="fas fa-music text-white text-lg"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                        Project Files
                                    </h3>
                                </div>
                                <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 border border-purple-200/50">
                                    <i class="fas fa-file-audio mr-2"></i>
                                    {{ $project->files->count() }} {{ Str::plural('file', $project->files->count()) }}
                            </div>
                        </div>

                            @if($project->files->isEmpty())
                                <!-- Empty State -->
                                <div class="text-center py-16">
                                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                                        <i class="fas fa-folder-open text-3xl text-purple-400"></i>
                                    </div>
                                    <h4 class="text-xl font-semibold text-gray-700 mb-2">No Files Yet</h4>
                                    <p class="text-gray-500 max-w-md mx-auto">No files have been uploaded for this project. Files will appear here once the project owner uploads reference tracks or materials.</p>
                                </div>
                            @else
                                <!-- Files Grid -->
                                <div class="grid gap-4">
                                    @foreach($project->files as $file)
                                        <div class="group/file relative bg-gradient-to-r from-purple-50/50 to-pink-50/50 rounded-xl p-6 border border-purple-200/30 hover:border-purple-300/50 transition-all duration-200 hover:shadow-lg">
                                            <div class="flex items-center">
                                                <!-- File Icon -->
                                                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover/file:scale-105 transition-transform duration-200">
                                                    <i class="fas fa-file-audio text-white text-xl"></i>
                    </div>

                                                <!-- File Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-lg font-semibold text-gray-900 truncate mb-1" title="{{ $file->file_name }}">
                                                        {{ $file->file_name }}
                                                    </h4>
                                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                        <span class="inline-flex items-center">
                                                            <i class="fas fa-hdd mr-1"></i>
                                                            {{ $file->formatted_size }}
                                                        </span>
                                                        <span class="inline-flex items-center">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            {{ $file->created_at->format('M d, Y') }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- File Actions -->
                                                <div class="flex items-center space-x-2 opacity-0 group-hover/file:opacity-100 transition-opacity duration-200">
                                                    <button class="w-10 h-10 bg-white/80 hover:bg-white rounded-lg flex items-center justify-center text-gray-600 hover:text-purple-600 transition-colors shadow-md">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button class="w-10 h-10 bg-white/80 hover:bg-white rounded-lg flex items-center justify-center text-gray-600 hover:text-purple-600 transition-colors shadow-md">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                    <!-- Payout Status (if applicable) -->
                    <x-project.payout-status :project="$project" />

                    <!-- Project Stats -->
                    <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <!-- Gradient Border Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 via-cyan-500/20 to-teal-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-6">
                            <!-- Header -->
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                    <i class="fas fa-chart-line text-white"></i>
                                </div>
                                <h3 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                    Project Stats
                            </h3>
                            </div>

                            <!-- Stats Grid -->
                            <div class="space-y-4">
                                <!-- Status -->
                                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50/50 to-blue-50/50 rounded-lg border border-gray-200/30">
                                    <span class="text-sm font-medium text-gray-700">Status</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $project->status === 'open' ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800' : 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800' }} shadow-sm">
                                        <div class="w-2 h-2 rounded-full {{ $project->status === 'open' ? 'bg-green-500' : 'bg-gray-400' }} mr-2"></div>
                                        {{ Str::title($project->status) }}
                                    </span>
                                </div>

                                <!-- Created Date -->
                                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50/50 to-blue-50/50 rounded-lg border border-gray-200/30">
                                    <span class="text-sm font-medium text-gray-700">Created</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $project->created_at->format('M d, Y') }}</span>
                                </div>

                                <!-- Deadline -->
                                @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50/50 to-indigo-50/50 rounded-lg border border-purple-200/30">
                                        <span class="text-sm font-medium text-gray-700">{{ $project->isContest() ? 'Submission Deadline' : 'Deadline' }}</span>
                                        <div class="text-right">
                                            @if($project->isContest())
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                                </div>
                                                <div class="text-xs text-gray-600">
                                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                                </div>
                                            @else
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                                </div>
                                                <div class="text-xs text-gray-600">
                                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Files Count -->
                                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50/50 to-blue-50/50 rounded-lg border border-gray-200/30">
                                    <span class="text-sm font-medium text-gray-700">Files</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $project->files->count() }}</span>
                                </div>

                                <!-- Genre -->
                                @if($project->genre)
                                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50/50 to-blue-50/50 rounded-lg border border-gray-200/30">
                                        <span class="text-sm font-medium text-gray-700">Genre</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 shadow-sm">
                                            <i class="fas fa-music mr-1"></i>
                                            {{ $project->genre }}
                                </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    @if(!auth()->check() || !$project->isOwnedByUser(auth()->user()))
                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <!-- Gradient Border Effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 via-indigo-500/20 to-purple-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-6">
                                <!-- Header -->
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-rocket text-white"></i>
                        </div>
                                    <h3 class="text-lg font-bold bg-gradient-to-r from-blue-800 to-indigo-800 bg-clip-text text-transparent">
                                        Get Started
                                    </h3>
                    </div>

                                @if(!auth()->check())
                                    <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-4 mb-6 border border-blue-200/30">
                                        <p class="text-sm text-blue-800 font-medium leading-relaxed">
                                            Join MixPitch to collaborate on this project and connect with talented artists worldwide.
                                        </p>
                                    </div>
                                    <div class="space-y-3">
                                        <a href="{{ route('login') }}" 
                                           class="group/btn w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 text-center block shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                            <i class="fas fa-sign-in-alt mr-2 group-hover/btn:scale-110 transition-transform"></i>
                                            Sign In
                                        </a>
                                        <a href="{{ route('register') }}" 
                                           class="group/btn w-full bg-white hover:bg-gray-50 text-blue-600 py-3 px-4 rounded-xl text-sm font-semibold border-2 border-blue-200 hover:border-blue-300 transition-all duration-200 text-center block shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                            <i class="fas fa-user-plus mr-2 group-hover/btn:scale-110 transition-transform"></i>
                                            Create Account
                                        </a>
                                    </div>
                                @elseif($canPitch)
                                    <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-4 mb-6 border border-blue-200/30">
                                        <p class="text-sm text-blue-800 font-medium leading-relaxed">
                                            Ready to collaborate? Submit your pitch and showcase your skills to the project owner.
                                        </p>
                                    </div>
                                    <button onclick="openPitchTermsModal()" 
                                            class="group/btn w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="fas fa-paper-plane mr-2 group-hover/btn:scale-110 transition-transform"></i>
                                        Submit Pitch
                                    </button>
                                @endif
                        </div>
                    </div>
                    @endif

                    <!-- Project Owner Actions -->
                    @if(auth()->check() && $project->isOwnedByUser(auth()->user()))
                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                            <!-- Gradient Border Effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-gray-500/20 via-slate-500/20 to-zinc-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-6">
                                <!-- Header -->
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-gradient-to-br from-gray-600 to-slate-700 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-cog text-white"></i>
                                    </div>
                                    <h3 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                        Project Actions
                                    </h3>
                                    </div>

                                <div class="space-y-3">
                                    <a href="{{ route('projects.manage', $project) }}" 
                                       class="group/btn w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 text-center block shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="fas fa-cog mr-2 group-hover/btn:scale-110 transition-transform"></i>
                                        Manage Project
                                    </a>
                                    <a href="{{ route('projects.edit', $project) }}" 
                                       class="group/btn w-full bg-gradient-to-r from-gray-600 to-slate-700 hover:from-gray-700 hover:to-slate-800 text-white py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 text-center block shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="fas fa-edit mr-2 group-hover/btn:scale-110 transition-transform"></i>
                                        Edit Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the pitch terms modal component -->
<x-pitch-terms-modal :project="$project" />

@endsection