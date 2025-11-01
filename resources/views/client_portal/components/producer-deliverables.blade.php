<flux:card id="producer-deliverables" class="mb-2 rounded-xl !bg-green-50 !p-6 dark:!bg-green-900/20">

    {{-- Header with Version Info --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:icon.clock class="text-green-500" />
            <div>
                <flux:heading size="lg">Producer Deliverables</flux:heading>
                <flux:subheading>
                    @if ($currentSnapshot)
                        Version {{ $currentSnapshot->version ?? 1 }} of
                        {{ $snapshotHistory->count() }}
                    @else
                        No submissions yet
                    @endif
                </flux:subheading>
            </div>
        </div>

        @if ($snapshotHistory->count() > 1)
            <flux:badge variant="success" size="sm">
                {{ $snapshotHistory->count() }} versions available
            </flux:badge>
        @endif
    </div>

    {{-- Enhanced Snapshot Navigation with Version Comparison --}}
    @if ($snapshotHistory->count() > 1)
        <div class="mb-6">
            <div
                class="rounded-xl border border-blue-200/50 bg-gradient-to-r from-blue-50/80 to-green-50/80 p-4 backdrop-blur-sm dark:border-blue-800/50 dark:from-blue-950/80 dark:to-green-950/80">
                <div class="mb-3 flex items-center justify-between">
                    <h5 class="font-semibold text-gray-900 dark:text-gray-100">Submission
                        History
                    </h5>
                    @if ($snapshotHistory->count() >= 2)
                        <button
                            onclick="window.toggleVersionComparison()"
                            class="js-toggle-comparison rounded-lg bg-blue-100 px-3 py-1 text-sm text-blue-800 transition-colors duration-200 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800">
                            <i class="fas fa-columns mr-1"></i>Compare Versions
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="snapshot-grid">
                    @foreach ($snapshotHistory as $snapshot)
                        <div class="snapshot-item {{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                            ? 'bg-green-100 border-green-300 ring-2 ring-green-500 dark:bg-green-900/50 dark:border-green-700 dark:ring-green-600'
                            : 'bg-white border-gray-200 hover:border-green-300 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-green-600' }} group cursor-pointer rounded-lg border p-3 transition-all duration-200 hover:shadow-md"
                            data-snapshot-id="{{ $snapshot['id'] }}"
                            @if ($snapshot['id'] !== 'current') data-snapshot-url="{{ URL::temporarySignedRoute('client.portal.snapshot', now()->addMinutes(60), ['project' => $project->id, 'snapshot' => $snapshot['id']]) }}#producer-deliverables" @endif>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="{{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                                            ? 'bg-green-500 text-white dark:bg-green-600'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }} mr-3 flex h-8 w-8 items-center justify-center rounded-lg">
                                        <i class="fas fa-camera text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold dark:text-gray-100">
                                            V{{ $snapshot['version'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $snapshot['submitted_at']->format('M j') }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="{{ $snapshot['status'] === 'accepted'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : ($snapshot['status'] === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }} rounded-lg px-2 py-1 text-xs">
                                    {{ ucfirst($snapshot['status']) }}
                                </div>

                                {{-- Comparison Checkbox --}}
                                <input type="checkbox" class="comparison-checkbox ml-2 hidden"
                                    data-snapshot-id="{{ $snapshot['id'] }}">
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Version Comparison Interface --}}
                <div id="version-comparison"
                    class="mt-4 hidden rounded-lg border border-blue-200/30 bg-white/60 p-4 backdrop-blur-sm dark:border-blue-800/30 dark:bg-gray-800/80">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="font-semibold text-blue-800 dark:text-blue-200">Compare Versions</h6>
                        <button class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" id="js-hide-comparison">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="mb-3 text-sm text-blue-700 dark:text-blue-300">Select two versions to compare side by side.</p>
                    <div id="comparison-content">
                        <!-- Comparison content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Enhanced Current Snapshot Files Display with Audio Player --}}
    {{-- Show Producer Deliverables based on workflow state for client management projects --}}
    @php
        $shouldShowDeliverables = false;
        
        // For client management workflow, show deliverables when producer has submitted files for review
        if ($project->isClientManagement()) {
            $statusAllowed = in_array($pitch->status, [
                \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                \App\Models\Pitch::STATUS_COMPLETED
            ]);
            $hasCurrentSnapshot = $currentSnapshot ? true : false;
            $hasFiles = false;
            if ($currentSnapshot) {
                $hasFiles = method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : ($currentSnapshot->files ?? collect())->count() > 0;
            }
            
            $shouldShowDeliverables = $statusAllowed && $hasCurrentSnapshot && $hasFiles;
        } else {
            // For other workflows, use the original logic
            $shouldShowDeliverables = $currentSnapshot && (method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : ($currentSnapshot->files ?? collect())->count() > 0);
        }
    @endphp
    
    
    @if ($shouldShowDeliverables)
        <div class="mb-4">
            {{-- Response to Feedback (moved to top for better visibility) --}}
            @if ($currentSnapshot->response_to_feedback ?? false)
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h6 class="mb-2 font-semibold text-blue-800">Producer's Response to Feedback:</h6>
                    <p class="text-sm text-blue-700">
                        {{ $currentSnapshot->response_to_feedback }}
                    </p>
                </div>
            @endif

            <div class="mb-3 flex items-center justify-between">
                <h5 class="font-semibold text-green-800">
                    Files in Version {{ $currentSnapshot->version ?? 1 }}
                </h5>
                <span class="text-sm text-green-600">
                    Submitted {{ $currentSnapshot->created_at_for_user->format('M j, Y g:i A') }}
                </span>
            </div>

            {{-- Enhanced File Display with Audio Players and Annotations --}}
            @if (request('checkout_status') === 'success')
                <div class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                    <i class="fas fa-check-circle mr-1"></i> Payment completed. Thank you!
                </div>
            @elseif(request('checkout_status') === 'cancel')
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <i class="fas fa-info-circle mr-1"></i> Checkout canceled.
                </div>
            @endif
            {{-- Enhanced File List with Comments using ClientPortal FileManager --}}
            @livewire('client-portal.file-manager', [
                'project' => $project,
                'pitch' => $pitch,
                'files' => $currentSnapshot->files ?? collect(),
                'canPlay' => true,
                'canDownload' => false,
                'canDelete' => false,
                'enableBulkActions' => false,
                'showComments' => true,
                'enableCommentCreation' => true,
                'colorScheme' => [
                    'bg' => 'bg-green-50 dark:bg-green-950',
                    'border' => 'border-green-200 dark:border-green-800',
                    'text_primary' => 'text-green-900 dark:text-green-100',
                    'text_secondary' => 'text-green-700 dark:text-green-300',
                    'text_muted' => 'text-green-600 dark:text-green-400',
                    'accent_bg' => 'bg-green-100 dark:bg-green-900',
                    'accent_border' => 'border-green-200 dark:border-green-800',
                    'icon' => 'text-green-600 dark:text-green-400',
                ],
                'headerIcon' => 'document-duplicate',
                'emptyStateMessage' => 'No files in this version',
                'emptyStateSubMessage' => 'Files will appear here when uploaded',
            ])

            {{-- File Approval Section --}}
            @if (($currentSnapshot->files ?? collect())->count() > 0 && (!isset($isPreview) || !$isPreview))
                <div class="mt-6 rounded-xl bg-white p-6 dark:bg-gray-800">
                    <flux:heading size="md" class="inline-flex items-center gap-2 mb-4 text-green-700 dark:text-green-300">
                        <flux:icon.check-circle class="mr-2" />
                        File Approval
                    </flux:heading>

                    @php
                        $unapprovedFiles = ($currentSnapshot->files ?? collect())->filter(function($file) {
                            return $file->client_approval_status !== 'approved';
                        });
                        $approvedFiles = ($currentSnapshot->files ?? collect())->filter(function($file) {
                            return $file->client_approval_status === 'approved';
                        });
                    @endphp

                    @if ($unapprovedFiles->count() > 0)
                        <flux:text size="sm" class="mb-4 text-green-600 dark:text-green-400">
                            {{ $unapprovedFiles->count() }} file{{ $unapprovedFiles->count() > 1 ? 's' : '' }} pending your approval
                        </flux:text>

                        <div class="space-y-3 mb-6">
                            @foreach ($unapprovedFiles as $file)
                                <div class="flex items-center justify-between p-3 bg-white border border-green-200 rounded-lg dark:bg-gray-800 dark:border-green-700">
                                    <div class="flex items-center gap-2">
                                        @if (in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                            <flux:icon.musical-note class="text-green-500 dark:text-green-400" />
                                        @else
                                            <flux:icon.document class="text-green-500 dark:text-green-400" />
                                        @endif
                                        <flux:text size="sm" class="font-medium dark:text-gray-200">{{ $file->file_name }}</flux:text>
                                    </div>
                                    <form method="POST"
                                        x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })"
                                        @submit.prevent="submit">
                                        @csrf
                                        <flux:button type="submit" variant="primary" size="sm" icon="check-circle">
                                            <span x-show="!loading">Approve</span>
                                            <span x-show="loading">
                                                Approving...
                                            </span>
                                        </flux:button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Approve All Button --}}
                        <div class="mb-4">
                            <form x-data="approveAll({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve_all', now()->addHours(24), ['project' => $project->id]) }}' })"
                                @submit.prevent="submit" method="POST">
                                @csrf
                                <flux:button type="submit" variant="primary" icon="check-circle">
                                    <span x-show="!loading">Approve All Files</span>
                                    <span x-show="loading">
                                        Approving...
                                    </span>
                                </flux:button>
                            </form>
                        </div>
                    @endif
                    
                    @if ($approvedFiles->count() > 0)
                        <div class="pt-4 border-t border-green-200 dark:border-green-700">
                            <div class="flex items-center justify-between mb-3">
                                <flux:text size="sm" class="text-green-700 font-medium dark:text-green-300">
                                    <flux:icon.check-circle class="mr-1" />
                                    {{ $approvedFiles->count() }} file{{ $approvedFiles->count() > 1 ? 's' : '' }} approved
                                </flux:text>

                                {{-- Unapprove All Button --}}
                                <form x-data="unapproveAll({ url: '{{ URL::temporarySignedRoute('client.portal.files.unapprove_all', now()->addHours(24), ['project' => $project->id]) }}' })"
                                    @submit.prevent="submit" method="POST">
                                    @csrf
                                    <flux:button type="submit" variant="ghost" size="xs" icon="x-circle">
                                        <span x-show="!loading">Unapprove All</span>
                                        <span x-show="loading">
                                            Unapproving...
                                        </span>
                                    </flux:button>
                                </form>
                            </div>

                            <div class="mt-2 space-y-2">
                                @foreach ($approvedFiles as $file)
                                    <div class="flex items-center justify-between p-2 bg-green-50 border border-green-100 rounded-lg dark:bg-green-900/10 dark:border-green-800/30">
                                        <div class="flex items-center gap-2">
                                            @if (in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                                <flux:icon.musical-note class="text-green-500 dark:text-green-400" />
                                            @else
                                                <flux:icon.document class="text-green-500 dark:text-green-400" />
                                            @endif
                                            <div>
                                                <flux:text size="xs" class="text-green-700 font-medium dark:text-green-300">{{ $file->file_name }}</flux:text>
                                                <flux:text size="xs" class="text-green-600 dark:text-green-400">
                                                    Approved {{ optional($file->client_approved_at)->diffForHumans() }}
                                                </flux:text>
                                            </div>
                                        </div>
                                        <form method="POST"
                                            x-data="unapproveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.unapprove', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })"
                                            @submit.prevent="submit">
                                            @csrf
                                            <flux:button type="submit" variant="ghost" size="xs" icon="x-circle">
                                                <span x-show="!loading">Unapprove</span>
                                                <span x-show="loading">
                                                    <flux:icon.arrow-path class="mr-1 animate-spin" />
                                                </span>
                                            </flux:button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="py-8 text-center">
            <flux:icon.clock class="mx-auto mb-4 w-16 h-16 text-green-400" />
            
            @if ($project->isClientManagement())
                {{-- Client Management Workflow Empty States --}}
                @if ($pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS)
                    <flux:heading size="md" class="mb-2 text-green-700">Producer is working on your project</flux:heading>
                    <flux:text class="mx-auto max-w-md leading-relaxed text-green-600">
                        Files will appear here when the producer submits them for your review. 
                        You'll receive an email notification when deliverables are ready.
                    </flux:text>
                @elseif (in_array($pitch->status, [\App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, \App\Models\Pitch::STATUS_COMPLETED]))
                    <flux:heading size="md" class="mb-2 text-green-700">No files in this submission</flux:heading>
                    <flux:text class="text-green-600">The producer hasn't uploaded any files for this submission yet.</flux:text>
                @else
                    <flux:heading size="md" class="mb-2 text-green-700">Deliverables will appear here</flux:heading>
                    <flux:text class="mx-auto max-w-md leading-relaxed text-green-600">
                        Files will be uploaded here as the producer works on your project. 
                        You'll be notified when new deliverables are available for review.
                    </flux:text>
                @endif
            @else
                {{-- Other Workflow Types --}}
                @if ($currentSnapshot)
                    <flux:heading size="md" class="mb-2 text-green-700">No files in this version</flux:heading>
                    <flux:text class="text-green-600">The producer hasn't uploaded files for this submission yet.</flux:text>
                @else
                    <flux:heading size="md" class="mb-2 text-green-700">No deliverables uploaded yet</flux:heading>
                    <flux:text class="mx-auto max-w-md leading-relaxed text-green-600">
                        The producer will upload files here as they work on your project. 
                        You'll be notified when new files are available.
                    </flux:text>
                @endif
            @endif
        </div>
    @endif
</flux:card>

