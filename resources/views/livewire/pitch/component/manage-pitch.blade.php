@php
    use Illuminate\Support\Str;

    // Unified Color System - Workflow-aware colors
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

<x-draggable-upload-page :model="$pitch" title="Manage Pitch: {{ $project->title }}">
    <div class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto">
            <div class="mx-auto">
                <div class="flex justify-center">
                    <div class="w-full">

                        {{-- Contest View --}}
                        @if ($project->isContest())
                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2">
                                <div class="mb-6 flex items-center gap-3">
                                    <flux:icon.trophy variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                    <div>
                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                            Contest Entry Status</flux:heading>
                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Your participation
                                            in this contest</flux:subheading>
                                    </div>
                                </div>
                                <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div
                                        class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} rounded-xl border p-4">
                                        <flux:subheading class="{{ $workflowColors['text_secondary'] }} mb-2">Current
                                            Status</flux:subheading>
                                        <flux:badge
                                            variant="{{ $pitch->status === \App\Models\Pitch::STATUS_CONTEST_WINNER ? 'success' : ($pitch->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP ? 'warning' : 'primary') }}">
                                            {{ $pitch->readable_status }}
                                        </flux:badge>
                                    </div>

                                    @if ($pitch->rank && $pitch->rank > 0)
                                        <div
                                            class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} rounded-xl border p-4">
                                            <flux:subheading class="{{ $workflowColors['text_secondary'] }} mb-2">Rank
                                            </flux:subheading>
                                            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                                {{ $pitch->rank }}</flux:heading>
                                        </div>
                                    @endif
                                </div>

                                @if ($pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                                    <!-- Contest Entry Instructions -->
                                    <div
                                        class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} mb-6 rounded-xl border p-4">
                                        <div class="flex items-start gap-3">
                                            <flux:icon.information-circle
                                                class="{{ $semanticColors['success']['icon'] }} mt-0.5 h-6 w-6 flex-shrink-0" />
                                            <div class="flex-1">
                                                <flux:heading size="base"
                                                    class="{{ $semanticColors['success']['text'] }} mb-2">Contest Entry
                                                    Instructions</flux:heading>
                                                <ul class="{{ $semanticColors['success']['text'] }} space-y-2 text-sm">
                                                    <li class="flex items-start gap-2">
                                                        <flux:icon.check
                                                            class="{{ $semanticColors['success']['icon'] }} mt-0.5 h-4 w-4 flex-shrink-0" />
                                                        <span>You have immediate access to download project files and
                                                            upload your contest entry</span>
                                                    </li>
                                                    <li class="flex items-start gap-2">
                                                        <flux:icon.check
                                                            class="{{ $semanticColors['success']['icon'] }} mt-0.5 h-4 w-4 flex-shrink-0" />
                                                        <span>Upload your best work - you can update files anytime
                                                            before the deadline</span>
                                                    </li>
                                                    @if ($project->submission_deadline)
                                                        <li class="flex items-start gap-2">
                                                            <flux:icon.clock
                                                                class="{{ $semanticColors['warning']['icon'] }} mt-0.5 h-4 w-4 flex-shrink-0" />
                                                            <span>Contest deadline: <strong><x-datetime
                                                                        :date="$project->submission_deadline" :user="$project->user"
                                                                        :convertToViewer="true"
                                                                        format="M d, Y \a\t H:i T" /></strong></span>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Files for Contest Entry -->
                                    @if ($project->files->count() > 0)
                                        <div
                                            class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} mb-6 rounded-xl border p-4">
                                            <div class="mb-4 flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <flux:icon.arrow-down-tray variant="solid"
                                                        class="{{ $semanticColors['success']['icon'] }} h-6 w-6" />
                                                    <div>
                                                        <flux:heading size="base"
                                                            class="{{ $semanticColors['success']['text'] }}">Project
                                                            Files</flux:heading>
                                                        <flux:subheading
                                                            class="{{ $semanticColors['success']['icon'] }}">Download
                                                            these files to create your contest entry</flux:subheading>
                                                    </div>
                                                </div>
                                                <flux:button href="{{ route('projects.download', $project) }}"
                                                    variant="primary" icon="arrow-down-tray">
                                                    Download All
                                                </flux:button>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach ($project->files as $file)
                                                    <div
                                                        class="{{ $semanticColors['success']['accent_bg'] ?? 'bg-green-100' }} {{ $semanticColors['success']['border'] }} flex items-center justify-between rounded-lg border p-3 dark:bg-green-900">
                                                        <div class="flex items-center gap-3">
                                                            <flux:icon.document
                                                                class="{{ $semanticColors['success']['icon'] }} h-4 w-4" />
                                                            <flux:text size="sm"
                                                                class="{{ $semanticColors['success']['text'] }} font-medium">
                                                                {{ $file->file_name }}</flux:text>
                                                        </div>
                                                        <flux:text size="xs"
                                                            class="{{ $semanticColors['success']['icon'] }}">
                                                            {{ \App\Models\Pitch::formatBytes($file->size) }}
                                                        </flux:text>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <!-- Final Contest Results -->
                                    <div
                                        class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} mb-6 rounded-xl border p-4">
                                        <flux:subheading class="{{ $workflowColors['text_secondary'] }} mb-3">Final
                                            Entry Files</flux:subheading>
                                        @if ($pitch->files->count() > 0)
                                            <div class="space-y-2">
                                                @foreach ($pitch->files as $file)
                                                    <div
                                                        class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} flex items-center justify-between rounded-lg border p-3">
                                                        <div class="flex items-center gap-3">
                                                            <flux:icon.document
                                                                class="{{ $workflowColors['icon'] }} h-4 w-4" />
                                                            <flux:text
                                                                class="{{ $workflowColors['text_primary'] }} font-medium">
                                                                {{ $file->file_name }}</flux:text>
                                                        </div>
                                                        <flux:button wire:click="downloadFile({{ $file->id }})"
                                                            variant="ghost" size="sm" icon="arrow-down-tray" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="py-6 text-center">
                                                <flux:icon.folder
                                                    class="{{ $workflowColors['icon'] }} mx-auto mb-2 h-12 w-12" />
                                                <flux:text class="{{ $workflowColors['text_secondary'] }}">No files
                                                    were submitted with this entry.</flux:text>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </flux:card>

                            {{-- Direct Hire View --}}
                        @elseif($project->isDirectHire())
                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2">
                                <div class="flex items-center gap-3">
                                    <flux:icon.check-circle variant="solid"
                                        class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                    <div>
                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                            Direct Hire Project</flux:heading>
                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Status:
                                            {{ $pitch->readable_status }}</flux:subheading>
                                    </div>
                                </div>
                            </flux:card>
                        @elseif($project->isClientManagement())
                            <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2">
                                <div class="mb-6 flex items-center gap-3">
                                    <flux:icon.briefcase variant="solid"
                                        class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                    <div>
                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                                            Client Management Project</flux:heading>
                                        <div class="mt-2 space-y-1">
                                            @if ($project->pitches->first())
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Status:
                                                    {{ $project->pitches->first()->readable_status }}</flux:subheading>
                                            @else
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Status: No
                                                    pitch initiated</flux:subheading>
                                            @endif
                                            <flux:subheading class="{{ $workflowColors['text_muted'] }}">Client:
                                                {{ $project->client_name ?: 'N/A' }} ({{ $project->client_email }})
                                            </flux:subheading>

                                            {{-- Payment Details for Producer --}}
                                            @if ($pitch->payment_amount > 0)
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">
                                                    Payment Amount: ${{ number_format($pitch->payment_amount, 2) }}
                                                    {{ $pitch->currency ?? 'USD' }}
                                                    <flux:badge
                                                        variant="{{ $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID ? 'success' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ? 'warning' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING ? 'primary' : 'danger')) }}"
                                                        size="sm" class="ml-2">
                                                        {{ Str::title(str_replace('_', ' ', $pitch->payment_status)) }}
                                                    </flux:badge>
                                                </flux:subheading>
                                            @else
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Payment:
                                                    Not applicable (Amount is $0)</flux:subheading>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </flux:card>

                            {{-- Payout Status for Client Management (Producer View) --}}
                            @if (auth()->check() && auth()->id() === $pitch->user_id)
                                <x-pitch.payout-status :pitch="$pitch" />
                            @endif

                            {{-- Client Management Pitch Details --}}
                            <flux:card class="mb-2">
                                <div class="mb-6 flex items-center justify-between">
                                    <div>
                                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">Pitch
                                            Status: {{ $pitch->readable_status }}</flux:heading>
                                    </div>
                                    <flux:button
                                        href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $pitch]) }}"
                                        variant="primary" icon="cog-6-tooth">
                                        Manage Pitch Details & Files
                                    </flux:button>
                                </div>

                                {{-- Display Client Comments/Events --}}
                                <div
                                    class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                    <flux:heading size="base"
                                        class="mb-3 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                        <flux:icon.clock class="h-5 w-5 text-blue-500" />
                                        Recent Client Activity
                                    </flux:heading>
                                    <div class="space-y-2">
                                        @forelse($pitch->events->whereIn('event_type', ['client_comment', 'client_revisions_requested', 'client_approved'])->sortByDesc('created_at')->take(5) as $event)
                                            <div
                                                class="flex items-start rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700">
                                                <div
                                                    class="mr-3 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                                    @if ($event->event_type === 'client_comment')
                                                        <flux:icon.chat-bubble-left-ellipsis
                                                            class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                    @elseif($event->event_type === 'client_revisions_requested')
                                                        <flux:icon.pencil
                                                            class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                    @elseif($event->event_type === 'client_approved')
                                                        <flux:icon.check
                                                            class="h-4 w-4 text-green-600 dark:text-green-400" />
                                                    @endif
                                                </div>
                                                <div class="flex-1">
                                                    <flux:text size="sm"
                                                        class="font-medium text-gray-800 dark:text-gray-200">
                                                        @if ($event->event_type === 'client_comment')
                                                            Client Comment: "{{ Str::limit($event->comment, 50) }}"
                                                        @elseif($event->event_type === 'client_revisions_requested')
                                                            Client Requested Revisions:
                                                            "{{ Str::limit($event->comment, 50) }}"
                                                        @elseif($event->event_type === 'client_approved')
                                                            Client Approved Submission
                                                        @endif
                                                    </flux:text>
                                                    <flux:text size="xs"
                                                        class="mt-1 text-gray-500 dark:text-gray-400">
                                                        {{ $event->created_at_for_user->diffForHumans() }}</flux:text>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="py-6 text-center">
                                                <flux:icon.inbox class="mx-auto mb-2 h-12 w-12 text-gray-400" />
                                                <flux:text class="text-gray-500 dark:text-gray-400">No recent client
                                                    activity.</flux:text>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </flux:card>
                        @else
                            {{-- Standard Project View --}}
                            <div class="grid">
                                <!-- Main Content Area (2/3 width on large screens) -->
                                <div class="space-y-2">

                                    <!-- Main Pitch Management Card -->
                                    <flux:card class="">
                                        <div class="mb-6 flex items-center gap-3">
                                            <flux:icon.document variant="solid"
                                                class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                            <div>
                                                <flux:heading size="lg"
                                                    class="{{ $workflowColors['text_primary'] }}">Pitch Management
                                                </flux:heading>
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Manage
                                                    your pitch submission and files</flux:subheading>
                                            </div>
                                        </div>

                                        <!-- Success Message -->
                                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
                                            @if ($message = session('message'))
                                                <div x-show="show"
                                                    class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} {{ $semanticColors['success']['text'] }} mb-6 rounded-xl border p-4"
                                                    x-transition>
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.check-circle
                                                            class="{{ $semanticColors['success']['icon'] }} h-5 w-5" />
                                                        {{ $message }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Status Messages -->
                                        @if ($pitch->is_inactive || $pitch->status == 'closed')
                                            <div
                                                class="mb-6 rounded-xl border-l-4 border-gray-500 bg-gray-50 p-4 dark:bg-gray-800">
                                                <div class="flex items-center gap-3">
                                                    <flux:icon.lock-closed
                                                        class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                                    <flux:text class="font-medium text-gray-800 dark:text-gray-200">
                                                        {{ $pitch->is_inactive ? 'This pitch is now inactive' : 'This pitch has been closed' }}
                                                    </flux:text>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Denied Pitch Alert Section -->
                                        @if ($pitch->status == 'denied')
                                            <flux:card
                                                class="{{ $semanticColors['danger']['bg'] }} {{ $semanticColors['danger']['border'] }} mb-6">
                                                <div class="mb-4 flex items-start gap-4">
                                                    <flux:icon.x-mark variant="solid"
                                                        class="{{ $semanticColors['danger']['icon'] }} h-8 w-8 flex-shrink-0" />
                                                    <div class="flex-1">
                                                        <flux:heading size="lg"
                                                            class="{{ $semanticColors['danger']['text'] }} mb-2">Your
                                                            Pitch Has Been Denied</flux:heading>
                                                        <flux:text size="sm"
                                                            class="{{ $semanticColors['danger']['text'] }} mb-4">
                                                            The project owner has reviewed your pitch and has decided
                                                            not to proceed with it at this time. You
                                                            can view their feedback below, make changes to your files,
                                                            and resubmit if appropriate.
                                                        </flux:text>
                                                    </div>
                                                </div>

                                                <!-- For now, let's just add basic functionality back -->
                                                @if ($snapshots->isNotEmpty() && !empty($statusFeedbackMessage))
                                                    <div
                                                        class="{{ $semanticColors['danger']['bg'] }} {{ $semanticColors['danger']['border'] }} rounded-xl border p-4">
                                                        <flux:heading size="base"
                                                            class="{{ $semanticColors['danger']['text'] }} mb-2">
                                                            Feedback from Project Owner</flux:heading>
                                                        <flux:text size="sm"
                                                            class="{{ $semanticColors['danger']['text'] }}">
                                                            {!! nl2br(e($statusFeedbackMessage)) !!}</flux:text>
                                                    </div>
                                                @endif
                                            </flux:card>
                                        @endif

                                        <!-- Enhanced Revisions Section -->
                                        @if ($pitch->status == 'revisions_requested')
                                            <flux:card class="{{ $semanticColors['warning']['bg'] }} {{ $semanticColors['warning']['border'] }} mb-6">
                                                <div class="mb-4 flex items-start gap-4">
                                                    <flux:icon.arrow-path variant="solid" class="{{ $semanticColors['warning']['icon'] }} h-8 w-8 flex-shrink-0" />
                                                    <div class="flex-1">
                                                        <flux:heading size="lg" class="{{ $semanticColors['warning']['text'] }} mb-2">
                                                            Revisions Requested
                                                        </flux:heading>
                                                        <flux:text size="sm" class="{{ $semanticColors['warning']['text'] }} mb-4">
                                                            The project owner has reviewed your submission and requested changes. 
                                                            Please review their feedback below and update your files accordingly.
                                                        </flux:text>
                                                    </div>
                                                </div>
                                                
                                                <!-- Feedback from Project Owner -->
                                                @if($statusFeedbackMessage)
                                                    <div class="{{ $semanticColors['warning']['bg'] }} border {{ $semanticColors['warning']['border'] }} rounded-xl p-4 mb-4">
                                                        <flux:heading size="base" class="{{ $semanticColors['warning']['text'] }} mb-2 flex items-center gap-2">
                                                            <flux:icon.chat-bubble-left-ellipsis class="h-5 w-5" />
                                                            Feedback from Project Owner
                                                        </flux:heading>
                                                        <flux:text size="sm" class="{{ $semanticColors['warning']['text'] }}">
                                                            {!! nl2br(e($statusFeedbackMessage)) !!}
                                                        </flux:text>
                                                    </div>
                                                @endif

                                                <!-- Response to Feedback Section -->
                                                <div class="space-y-4">
                                                    <flux:field>
                                                        <flux:label for="response-feedback">Your Response (Optional)</flux:label>
                                                        <flux:textarea 
                                                            id="response-feedback"
                                                            wire:model.defer="responseToFeedback" 
                                                            placeholder="Describe the changes you've made or ask questions about the feedback..."
                                                            rows="3"
                                                            class="resize-y"
                                                        />
                                                        <flux:description>Let the project owner know how you've addressed their feedback.</flux:description>
                                                    </flux:field>
                                                    
                                                    <div class="flex items-center gap-3">
                                                        <flux:button wire:click="resubmitPitch" variant="primary" icon="paper-airplane">
                                                            Resubmit Pitch
                                                        </flux:button>
                                                        <flux:text size="sm" class="{{ $semanticColors['warning']['text'] }}">
                                                            Upload any new files above before resubmitting.
                                                        </flux:text>
                                                    </div>
                                                </div>
                                            </flux:card>
                                        @endif

                                        <!-- File Upload Section -->
                                        @if($this->canUploadFiles)
                                            <flux:card class="mb-2">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.arrow-up-tray variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Upload Files</flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Add audio files and documents to your pitch submission</flux:subheading>
                                                    </div>
                                                </div>
                                                
                                                <!-- File Upload Component -->
                                                <x-file-management.upload-section 
                                                    :model="$pitch"
                                                    title="Upload New Files"
                                                    description="Upload audio files, PDFs, or images for your pitch submission"
                                                />
                                            </flux:card>
                                        @endif

                                        <!-- Files List Section -->
                                        @if($pitch->files->count() > 0 || $this->canUploadFiles)
                                            <flux:card class="mb-2">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.folder variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Pitch Files</flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Manage your uploaded files and submissions</flux:subheading>
                                                    </div>
                                                </div>
                                                
                                                <!-- Files List Component -->
                                                @livewire('components.file-list', [
                                                    'files' => $pitch->files,
                                                    'colorScheme' => $workflowColors,
                                                    'modelType' => 'pitch',
                                                    'modelId' => $pitch->id,
                                                    'playMethod' => 'playPitchFile',
                                                    'downloadMethod' => 'downloadFile',
                                                    'deleteMethod' => 'confirmDeleteFile',
                                                    'canDelete' => $this->canUploadFiles,
                                                    'bulkActions' => ['download'],
                                                    'emptyStateMessage' => 'No files uploaded yet',
                                                    'emptyStateSubMessage' => 'Upload files to complete your pitch submission',
                                                    'newlyUploadedFileIds' => $newlyUploadedFileIds ?? []
                                                ], key('pitch-file-list-' . $pitch->id))
                                            </flux:card>
                                        @endif

                                        <!-- Submission Controls -->
                                        @if($pitch->status === 'pending' && $this->canUploadFiles)
                                            <flux:card class="mb-2">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.paper-airplane variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Submit for Review</flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Ready to submit your pitch for project owner review</flux:subheading>
                                                    </div>
                                                </div>
                                                
                                                <!-- Submission Requirements Check -->
                                                @if($pitch->files->count() > 0)
                                                    <div class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }} border rounded-xl p-4 mb-4">
                                                        <div class="flex items-center gap-3">
                                                            <flux:icon.check-circle class="{{ $semanticColors['success']['icon'] }} h-6 w-6" />
                                                            <div>
                                                                <flux:text class="{{ $semanticColors['success']['text'] }} font-medium">Ready to Submit</flux:text>
                                                                <flux:text size="sm" class="{{ $semanticColors['success']['text'] }}">You have uploaded {{ $pitch->files->count() }} file(s) and can now submit for review.</flux:text>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex items-center gap-3">
                                                        <flux:button wire:click="submitForReview" variant="primary" icon="paper-airplane">
                                                            Submit for Review
                                                        </flux:button>
                                                        <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">
                                                            Once submitted, you can still upload additional files before the project owner reviews.
                                                        </flux:text>
                                                    </div>
                                                @else
                                                    <div class="{{ $semanticColors['warning']['bg'] }} {{ $semanticColors['warning']['border'] }} border rounded-xl p-4">
                                                        <div class="flex items-center gap-3">
                                                            <flux:icon.exclamation-triangle class="{{ $semanticColors['warning']['icon'] }} h-6 w-6" />
                                                            <div>
                                                                <flux:text class="{{ $semanticColors['warning']['text'] }} font-medium">Upload Required</flux:text>
                                                                <flux:text size="sm" class="{{ $semanticColors['warning']['text'] }}">Please upload at least one file before submitting for review.</flux:text>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </flux:card>
                                        @endif

                                        @if(auth()->check() && auth()->id() === $pitch->user_id)
                                            {{-- Internal Notes Section (Only for Pitch Owner) --}}
                                            <flux:card class="mb-2">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.pencil-square variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                                    <div>
                                                        <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Internal Notes</flux:heading>
                                                        <flux:subheading class="{{ $workflowColors['text_muted'] }}">Personal notes and reminders (only visible to you)</flux:subheading>
                                                    </div>
                                                </div>
                                                
                                                <div class="space-y-4">
                                                    <flux:field>
                                                        <flux:label for="internal-notes">Your Notes</flux:label>
                                                        <flux:textarea 
                                                            id="internal-notes"
                                                            wire:model.defer="internalNotes" 
                                                            placeholder="Add personal notes, reminders, or thoughts about this pitch..."
                                                            rows="4"
                                                            class="resize-y"
                                                        />
                                                    </flux:field>
                                                    
                                                    <div class="flex items-center gap-3">
                                                        <flux:button wire:click="saveInternalNotes" variant="outline" icon="bookmark">
                                                            Save Notes
                                                        </flux:button>
                                                        @if(!empty($pitch->internal_notes))
                                                            <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">
                                                                Last updated: {{ $pitch->updated_at->format('M d, Y \a\t H:i') }}
                                                            </flux:text>
                                                        @endif
                                                    </div>
                                                </div>
                                            </flux:card>
                                        @endif
                                    </flux:card>
                                </div>
                            </div>

                            <!-- Sidebar (1/3 width on large screens) -->
                            <div class="space-y-2 lg:col-span-1">
                                <!-- Workflow Type Information -->
                                <flux:card
                                    class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }} mb-2 hidden">
                                    <div class="mb-6 flex items-center gap-3">
                                        <flux:icon.users variant="solid"
                                            class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                        <div>
                                            <flux:heading size="lg"
                                                class="{{ $workflowColors['text_primary'] }}">Standard Project
                                            </flux:heading>
                                            <flux:subheading class="{{ $workflowColors['text_muted'] }}">Open
                                                collaboration workflow</flux:subheading>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} rounded-xl border p-4">
                                            <div class="flex items-start gap-3">
                                                <flux:icon.users
                                                    class="{{ $workflowColors['icon'] }} mt-0.5 h-6 w-6 flex-shrink-0" />
                                                <div>
                                                    <flux:subheading
                                                        class="{{ $workflowColors['text_primary'] }} mb-1 font-semibold">
                                                        Open Collaboration</flux:subheading>
                                                    <flux:text size="sm"
                                                        class="{{ $workflowColors['text_secondary'] }}">Submit your
                                                        pitch for project owner review and approval.</flux:text>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} rounded-xl border p-4">
                                            <div class="flex items-start gap-3">
                                                <flux:icon.chat-bubble-left-ellipsis
                                                    class="{{ $workflowColors['icon'] }} mt-0.5 h-6 w-6 flex-shrink-0" />
                                                <div>
                                                    <flux:subheading
                                                        class="{{ $workflowColors['text_primary'] }} mb-1 font-semibold">
                                                        Direct Communication</flux:subheading>
                                                    <flux:text size="sm"
                                                        class="{{ $workflowColors['text_secondary'] }}">Work directly
                                                        with the project owner throughout the process.</flux:text>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </flux:card>
                                
                                <!-- Snapshot History -->
                                @if($snapshots && $snapshots->count() > 0)
                                    <flux:card class="mb-2">
                                        <div class="mb-6 flex items-center gap-3">
                                            <flux:icon.clock variant="solid" class="{{ $workflowColors['icon'] }} h-8 w-8" />
                                            <div>
                                                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">Submission History</flux:heading>
                                                <flux:subheading class="{{ $workflowColors['text_muted'] }}">Track your pitch submissions and status changes</flux:subheading>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-3">
                                            @foreach($snapshots->take(5) as $snapshot)
                                                <div class="{{ $workflowColors['accent_bg'] }} {{ $workflowColors['accent_border'] }} border rounded-xl p-4">
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0">
                                                            @if($snapshot->status === 'pending')
                                                                <flux:icon.clock class="h-6 w-6 text-amber-500" />
                                                            @elseif($snapshot->status === 'in_progress')
                                                                <flux:icon.cog-6-tooth class="h-6 w-6 text-blue-500" />
                                                            @elseif($snapshot->status === 'ready_for_review')
                                                                <flux:icon.eye class="h-6 w-6 text-purple-500" />
                                                            @elseif($snapshot->status === 'approved')
                                                                <flux:icon.check-circle class="h-6 w-6 text-green-500" />
                                                            @elseif($snapshot->status === 'denied')
                                                                <flux:icon.x-circle class="h-6 w-6 text-red-500" />
                                                            @elseif($snapshot->status === 'revisions_requested')
                                                                <flux:icon.arrow-path class="h-6 w-6 text-orange-500" />
                                                            @else
                                                                <flux:icon.document class="h-6 w-6 {{ $workflowColors['icon'] }}" />
                                                            @endif
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center justify-between">
                                                                <flux:text class="{{ $workflowColors['text_primary'] }} font-medium">{{ ucfirst(str_replace('_', ' ', $snapshot->status)) }}</flux:text>
                                                                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">{{ $snapshot->created_at->format('M d, H:i') }}</flux:text>
                                                            </div>
                                                            @if($snapshot->message)
                                                                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] }} mt-1">{{ Str::limit($snapshot->message, 100) }}</flux:text>
                                                            @endif
                                                            @if($snapshot->file_count > 0)
                                                                <flux:text size="xs" class="{{ $workflowColors['text_muted'] }} mt-1">{{ $snapshot->file_count }} file(s) included</flux:text>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            @if($snapshots->count() > 5)
                                                <div class="text-center pt-2">
                                                    <flux:button variant="ghost" size="sm" wire:click="showAllSnapshots">
                                                        View All {{ $snapshots->count() }} Submissions
                                                    </flux:button>
                                                </div>
                                            @endif
                                        </div>
                                    </flux:card>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- File Delete Confirmation Modal --}}
                    @if ($showDeleteModal)
                        <flux:modal name="delete-file" class="max-w-md">
                            <div class="space-y-6">
                                <div class="flex items-center gap-3">
                                    <flux:icon.exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                                    <flux:heading size="lg">Delete File</flux:heading>
                                </div>

                                <flux:subheading class="text-gray-600 dark:text-gray-400">
                                    Are you sure you want to delete this file? This action cannot be undone.
                                </flux:subheading>

                                <div class="flex items-center justify-end gap-3 pt-4">
                                    <flux:button wire:click="cancelDeleteFile" variant="ghost">
                                        Cancel
                                    </flux:button>
                                    <flux:button wire:click="deleteSelectedFile" variant="danger" icon="trash">
                                        Delete
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-draggable-upload-page>
