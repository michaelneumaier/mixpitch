<x-layouts.app-sidebar>

@php
    // Unified Color System - Workflow-aware colors
    $workflowColors = match($pitch->project->workflow_type) {
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

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto p-2">
        <!-- Pitch Header -->
        <x-pitch.header 
            :pitch="$pitch"
            context="view"
        />

        <div class="grid gap-2">
            <!-- Main Content Area (2/3 width on large screens) -->
            <div class="space-y-2">
                <!-- Pitch Workflow Status -->
                @if($pitch->project->isContest() && in_array($pitch->status, [
                    \App\Models\Pitch::STATUS_CONTEST_ENTRY,
                    \App\Models\Pitch::STATUS_CONTEST_WINNER,
                    \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP,
                    \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED
                ]))
                    <x-contest.workflow-status :pitch="$pitch" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
                @else
                    <x-pitch.workflow-status :pitch="$pitch" :workflowColors="$workflowColors" :semanticColors="$semanticColors" />
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
                <flux:card>
                    <livewire:pitch.component.feedback-conversation :pitch="$pitch" />
                </flux:card>

                <!-- Pitch History Timeline -->
                @if(false) {{-- Hidden for now --}}
                    <flux:card>
                        <livewire:pitch.component.pitch-history :pitch="$pitch" />
                    </flux:card>
                @endif

                <!-- Project Files Section -->
                <flux:card>
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <flux:icon.musical-note variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                            <div>
                                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Project Files</flux:heading>
                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Audio files and resources</flux:subheading>
                            </div>
                        </div>
                        
                        @if ($pitch->status !== \App\Models\Pitch::STATUS_PENDING && !$pitch->project->files->isEmpty())
                            <flux:button href="{{ route('projects.download', $pitch->project) }}" variant="primary" icon="arrow-down-tray">
                                Download All
                            </flux:button>
                        @endif
                    </div>

                    @if ($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                        <div class="{{ $semanticColors['warning']['bg'] }} {{ $semanticColors['warning']['border'] }} border rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <flux:icon.lock-closed class="{{ $semanticColors['warning']['icon'] }} h-6 w-6" />
                                <div>
                                    <flux:heading size="base" class="{{ $semanticColors['warning']['text'] }}">Access Restricted</flux:heading>
                                    <flux:text size="sm" class="{{ $semanticColors['warning']['text'] }}">You don't have access to the project files yet.</flux:text>
                                </div>
                            </div>
                        </div>
                    @elseif($pitch->project->files->isEmpty())
                        <div class="text-center py-8">
                            <flux:icon.folder-open class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                            <flux:heading size="base" class="text-gray-600 dark:text-gray-400 mb-1">No Files Available</flux:heading>
                            <flux:text class="text-gray-500 dark:text-gray-400">No files have been uploaded for this project</flux:text>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($pitch->project->files as $file)
                                <div class="flex items-center justify-between p-4 {{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} border rounded-xl hover:bg-white dark:hover:bg-gray-800 transition-colors">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <flux:icon.document class="{{ $workflowColors['icon'] }} h-6 w-6" />
                                        <div class="flex-1 min-w-0">
                                            <flux:text class="{{ $workflowColors['text_primary'] }} font-medium truncate">
                                                {{ $file->file_name }}
                                            </flux:text>
                                            <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                                                {{ $file->formatted_size }}
                                            </flux:text>
                                        </div>
                                    </div>
                                    <flux:button href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}" variant="ghost" size="sm" icon="arrow-down-tray">
                                        Download
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>

                <!-- Project Description Section -->
                <flux:card>
                    <div class="mb-6 flex items-center gap-3">
                        <flux:icon.bars-3 variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                        <div>
                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Project Description</flux:heading>
                            <flux:subheading class="{{ $workflowColors['text_muted'] }}">Detailed project information</flux:subheading>
                        </div>
                    </div>
                    <div class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} border rounded-xl p-4">
                        <flux:text class="{{ $workflowColors['text_primary'] }} whitespace-pre-wrap leading-relaxed">
                            {{ $pitch->project->description }}
                        </flux:text>
                    </div>
                </flux:card>

                <!-- Additional Notes Section -->
                @if ($pitch->project->notes)
                    <flux:card>
                        <div class="mb-6 flex items-center gap-3">
                            <flux:icon.document-text variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                            <div>
                                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Additional Notes</flux:heading>
                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Extra project details</flux:subheading>
                            </div>
                        </div>
                        <div class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} border rounded-xl p-4">
                            <flux:text class="{{ $workflowColors['text_primary'] }} whitespace-pre-wrap leading-relaxed">
                                {{ $pitch->project->notes }}
                            </flux:text>
                        </div>
                    </flux:card>
                @endif

                <!-- Admin Actions Section -->
                @if(Auth::check() && Auth::user()->is_admin)
                    <flux:card class="bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                        <div class="mb-6 flex items-center gap-3">
                            <flux:icon.shield-check variant="solid" class="h-8 w-8 text-gray-600 dark:text-gray-400" />
                            <div>
                                <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Admin Actions</flux:heading>
                                <flux:subheading class="text-gray-600 dark:text-gray-400">Administrative controls</flux:subheading>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <flux:button href="#" variant="outline" icon="camera">
                                View All Snapshots
                            </flux:button>
                            <flux:button href="#" variant="primary" icon="pencil">
                                Edit Pitch Details
                            </flux:button>
                        </div>
                    </flux:card>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-2">
                <!-- Payout Status (if applicable) -->
                <x-pitch.payout-status :pitch="$pitch" />

                <!-- Pitch Rating (if completed) -->
                @if($pitch->status === 'completed' && $pitch->getCompletionRating())
                    <flux:card class="{{ $semanticColors['warning']['bg'] }} {{ $semanticColors['warning']['border'] }}">
                        <div class="mb-4 flex items-center gap-3">
                            <flux:icon.star variant="solid" class="{{ $semanticColors['warning']['icon'] }} h-8 w-8" />
                            <div>
                                <flux:heading size="lg" class="{{ $semanticColors['warning']['text'] }}">Pitch Rating</flux:heading>
                                <flux:subheading class="{{ $semanticColors['warning']['icon'] }}">Completion feedback</flux:subheading>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <span class="text-3xl font-bold {{ $semanticColors['warning']['text'] }} flex items-center">
                                {{ number_format($pitch->getCompletionRating(), 1) }}
                                <flux:icon.star class="{{ $semanticColors['warning']['icon'] }} h-8 w-8 ml-2" />
                            </span>
                            <span class="ml-2 {{ $semanticColors['warning']['text'] }}">/ 5</span>
                        </div>
                        <flux:text class="{{ $semanticColors['warning']['text'] }}">
                            This pitch received a rating of {{ number_format($pitch->getCompletionRating(), 1) }} out of 5 stars upon completion.
                        </flux:text>
                    </flux:card>
                @endif

            </div>
        </div>
    </div>
</div>

<!-- Delete Pitch Component for Modal Functionality -->
@if(auth()->check() && auth()->id() === $pitch->user_id)
    <livewire:pitch.component.delete-pitch :pitch="$pitch" />
@endif

</x-layouts.app-sidebar>

