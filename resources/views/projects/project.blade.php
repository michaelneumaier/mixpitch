<x-layouts.app-sidebar>

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Project Header Component (Livewire) -->
            @livewire('project.project-header', [
                'project' => $project,
                'hasPreviewTrack' => $project->hasPreviewTrack(),
                'context' => 'view',
                'showEditButton' => false,
                'userPitch' => $userPitch ?? null,
                'canPitch' => $canPitch ?? false,
            ], key('project-header-' . $project->id))

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-2">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Budget/Prize Section -->
                    @if($project->isContest())
                        <!-- Contest Prizes Display -->
                        <x-contest.prize-display :project="$project" />
                    @endif

                    <!-- Description Section -->
                    <flux:card class="mb-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-sm">
                                <flux:icon name="document-text" class="text-white" size="lg" />
                            </div>
                            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Project Description</flux:heading>
                        </div>

                        <div class="prose prose-lg prose-gray dark:prose-invert max-w-none">
                            <flux:text class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
                                {{ $project->description }}
                            </flux:text>
                        </div>
                    </flux:card>

                    <!-- Collaboration Types -->
                    @if($project->collaboration_type && count(array_filter($project->collaboration_type)) > 0)
                        <flux:card class="mb-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                                    <flux:icon name="hand-raised" class="text-white" size="lg" />
                                </div>
                                <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Looking For Collaboration In</flux:heading>
                            </div>

                            <div class="flex flex-wrap gap-3">
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
                                        
                                        // Map collaboration types to Flux icons
                                        $iconName = match(strtolower($collaborationType)) {
                                            'mixing' => 'adjustments-horizontal',
                                            'mastering' => 'circle-stack', 
                                            'production' => 'musical-note',
                                            'vocals', 'vocal tuning' => 'microphone',
                                            'audio editing' => 'scissors',
                                            'instruments' => 'musical-note',
                                            'songwriting' => 'pencil',
                                            default => 'cog-6-tooth'
                                        };
                                    @endphp
                                    
                                    @if($collaborationType)
                                        <flux:badge color="indigo" size="sm" :icon="$iconName">
                                            {{ $collaborationType }}
                                        </flux:badge>
                                    @endif
                                @endforeach
                            </div>
                        </flux:card>
                    @endif

                    <!-- Additional Notes -->
                    @if ($project->notes)
                        <flux:card class="mb-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-sm">
                                    <flux:icon name="clipboard-document-list" class="text-white" size="lg" />
                                </div>
                                <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Additional Notes</flux:heading>
                            </div>

                            <flux:callout color="amber">
                                <flux:text class="whitespace-pre-wrap leading-relaxed">
                                    {{ $project->notes }}
                                </flux:text>
                            </flux:callout>
                        </flux:card>
                    @endif

                    <!-- Project Files -->
                    <flux:card class="mb-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                        <livewire:components.file-list 
                            :files="$project->files"
                            modelType="project"
                            :modelId="$project->id"
                            colorScheme="project"
                            :canPlay="false"
                            :canDownload="false"
                            :canDelete="false"
                            :enableBulkActions="false"
                            :showComments="false"
                            emptyStateMessage="No Files Yet"
                            emptyStateSubMessage="No files have been uploaded for this project. Files will appear here once the project owner uploads reference tracks or materials."
                            headerIcon="musical-note"
                        />
                    </flux:card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Payout Status (if applicable) -->
                    <x-project.payout-status :project="$project" />

                    <!-- Project Stats -->
                    <flux:card class="mb-4 bg-white/80 dark:bg-gray-800/80 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-lg shadow-sm">
                                <flux:icon name="chart-bar" class="text-white" size="sm" />
                            </div>
                            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Project Stats</flux:heading>
                        </div>

                        <div class="space-y-3">
                            <!-- Status -->
                            <div class="flex items-center justify-between">
                                <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">Status</flux:text>
                                <flux:badge 
                                    :color="$project->status === 'open' ? 'green' : 'gray'" 
                                    size="sm"
                                >
                                    {{ Str::title($project->status) }}
                                </flux:badge>
                            </div>

                            <!-- Budget -->
                            <div class="flex items-center justify-between">
                                <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">Budget</flux:text>
                                @if($project->budget == 0)
                                    <flux:badge color="blue" size="sm" icon="heart">
                                        Free Project
                                    </flux:badge>
                                @else
                                    <flux:text size="sm" class="font-semibold text-emerald-600 dark:text-emerald-400">
                                        ${{ number_format($project->budget, 0) }}
                                    </flux:text>
                                @endif
                            </div>

                            <!-- Created Date -->
                            <div class="flex items-center justify-between">
                                <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">Created</flux:text>
                                <flux:text size="sm" class="font-semibold text-slate-900 dark:text-slate-100">{{ toUserTimezone($project->created_at)->format('M d, Y') }}</flux:text>
                            </div>

                            <!-- Deadline -->
                            @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                                <div class="flex items-center justify-between">
                                    <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">{{ $project->isContest() ? 'Submission Deadline' : 'Deadline' }}</flux:text>
                                    <div class="text-right">
                                        @if($project->isContest())
                                            <flux:text size="sm" class="font-semibold text-slate-900 dark:text-slate-100">
                                                <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                            </flux:text>
                                            <flux:text size="xs" class="text-slate-600 dark:text-slate-400">
                                                <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                            </flux:text>
                                        @else
                                            <flux:text size="sm" class="font-semibold text-slate-900 dark:text-slate-100">
                                                <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                            </flux:text>
                                            <flux:text size="xs" class="text-slate-600 dark:text-slate-400">
                                                <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Files Count -->
                            <div class="flex items-center justify-between">
                                <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">Files</flux:text>
                                <flux:text size="sm" class="font-semibold text-slate-900 dark:text-slate-100">{{ $project->files->count() }}</flux:text>
                            </div>

                            <!-- Genre -->
                            @if($project->genre)
                                <div class="flex items-center justify-between">
                                    <flux:text size="sm" class="font-medium text-slate-700 dark:text-slate-300">Genre</flux:text>
                                    <flux:badge color="purple" size="sm" icon="musical-note">
                                        {{ $project->genre }}
                                    </flux:badge>
                                </div>
                            @endif
                        </div>
                    </flux:card>

                                        <!-- License & Terms -->
                    <x-project.license-info :project="$project" />
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the pitch terms modal component -->
<x-pitch-terms-modal :project="$project" />

</x-layouts.app-sidebar>