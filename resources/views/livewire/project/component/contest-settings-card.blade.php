<div>
    <flux:card class="{{ $workflowColors['bg'] ?? 'bg-orange-50 dark:bg-orange-950' }} {{ $workflowColors['border'] ?? 'border-orange-200 dark:border-orange-800' }} border">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <flux:icon.calendar variant="solid" class="w-8 h-8 {{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }}" />
                <div>
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                        Contest Deadlines
                    </flux:heading>
                    <flux:subheading class="{{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">
                        Manage submission and judging deadlines
                    </flux:subheading>
                </div>
            </div>

            @if(!$isEditing)
                <flux:button
                    wire:click="startEditing"
                    variant="ghost"
                    size="sm"
                    icon="pencil"
                    class="{{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}"
                >
                    Edit
                </flux:button>
            @endif
        </div>

        @if($isEditing)
            {{-- Editing Mode --}}
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Submission Deadline</flux:label>
                    <flux:input
                        type="datetime-local"
                        wire:model="editSubmissionDeadline"
                    />
                    <flux:description>
                        Entries can be submitted until this date and time
                    </flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Judging Deadline</flux:label>
                    <flux:input
                        type="datetime-local"
                        wire:model="editJudgingDeadline"
                    />
                    <flux:description>
                        Judging must be completed by this date
                    </flux:description>
                </flux:field>

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:button
                        wire:click="cancelEditing"
                        variant="ghost"
                        size="sm"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="saveDeadlines"
                        variant="primary"
                        size="sm"
                        icon="check"
                        wire:loading.attr="disabled"
                        wire:target="saveDeadlines"
                    >
                        <span wire:loading.remove wire:target="saveDeadlines">Save Deadlines</span>
                        <span wire:loading wire:target="saveDeadlines">Saving...</span>
                    </flux:button>
                </div>
            </div>
        @else
            {{-- Display Mode --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Submission Deadline --}}
                <div class="p-4 rounded-lg bg-white/50 dark:bg-gray-800/50 border {{ $workflowColors['accent_border'] ?? 'border-orange-200 dark:border-orange-700' }}">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon.clock class="w-5 h-5 {{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }}" />
                        <span class="text-sm font-medium {{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">
                            Submission Deadline
                        </span>
                    </div>
                    @if($project->submission_deadline)
                        <div class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                            {{ $project->submission_deadline->format('M j, Y') }}
                        </div>
                        <div class="text-sm {{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">
                            {{ $project->submission_deadline->format('g:i A') }}
                        </div>
                        <div class="mt-2 text-xs {{ $project->isSubmissionPeriodClosed() ? 'text-gray-500 dark:text-gray-400' : ($workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300') }}">
                            @if($project->isSubmissionPeriodClosed())
                                <flux:badge color="zinc" size="sm">Closed</flux:badge>
                            @else
                                {{ $project->submission_deadline->diffForHumans() }}
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            No deadline set
                        </div>
                    @endif
                </div>

                {{-- Judging Deadline --}}
                <div class="p-4 rounded-lg bg-white/50 dark:bg-gray-800/50 border {{ $workflowColors['accent_border'] ?? 'border-orange-200 dark:border-orange-700' }}">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon.scale class="w-5 h-5 {{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }}" />
                        <span class="text-sm font-medium {{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">
                            Judging Deadline
                        </span>
                    </div>
                    @if($project->judging_deadline)
                        <div class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                            {{ $project->judging_deadline->format('M j, Y') }}
                        </div>
                        <div class="text-sm {{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">
                            {{ $project->judging_deadline->format('g:i A') }}
                        </div>
                        <div class="mt-2 text-xs {{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">
                            {{ $project->judging_deadline->diffForHumans() }}
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            No deadline set
                        </div>
                    @endif
                </div>
            </div>

            {{-- Current Contest Status --}}
            @php
                $contestStatus = match(true) {
                    $project->isJudgingFinalized() => ['label' => 'Completed', 'color' => 'green', 'icon' => 'trophy'],
                    $project->isSubmissionPeriodClosed() => ['label' => 'Judging Phase', 'color' => 'purple', 'icon' => 'scale'],
                    $project->is_published => ['label' => 'Accepting Entries', 'color' => 'green', 'icon' => 'check-circle'],
                    default => ['label' => 'Draft', 'color' => 'zinc', 'icon' => 'document-text'],
                };
            @endphp

            <div class="mt-4 pt-4 border-t {{ $workflowColors['accent_border'] ?? 'border-orange-200 dark:border-orange-700' }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">
                        Contest Status
                    </span>
                    <flux:badge color="{{ $contestStatus['color'] }}" size="sm" icon="{{ $contestStatus['icon'] }}">
                        {{ $contestStatus['label'] }}
                    </flux:badge>
                </div>
            </div>
        @endif
    </flux:card>
</div>
