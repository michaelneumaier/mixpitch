<div id="producer-deliverables" class="mb-6 rounded-xl bg-green-50 p-6 dark:bg-green-900/20">

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
                class="rounded-xl border border-blue-200/50 bg-gradient-to-r from-blue-50/80 to-green-50/80 p-4 backdrop-blur-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h5 class="font-semibold" style="color: {{ $branding['primary'] ?? '#1f2937' }};">Submission
                        History
                    </h5>
                    @if ($snapshotHistory->count() >= 2)
                        <button
                            class="js-toggle-comparison rounded-lg bg-blue-100 px-3 py-1 text-sm text-blue-800 transition-colors duration-200 hover:bg-blue-200">
                            <i class="fas fa-columns mr-1"></i>Compare Versions
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="snapshot-grid">
                    @foreach ($snapshotHistory as $snapshot)
                        <div class="snapshot-item {{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                            ? 'bg-green-100 border-green-300 ring-2 ring-green-500'
                            : 'bg-white border-gray-200 hover:border-green-300' }} group cursor-pointer rounded-lg border p-3 transition-all duration-200 hover:shadow-md"
                            data-snapshot-id="{{ $snapshot['id'] }}"
                            @if ($snapshot['id'] !== 'current') data-snapshot-url="{{ URL::temporarySignedRoute('client.portal.snapshot', now()->addMinutes(60), ['project' => $project->id, 'snapshot' => $snapshot['id']]) }}#producer-deliverables" @endif>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="{{ $currentSnapshot && $currentSnapshot->id === $snapshot['id']
                                            ? 'bg-green-500 text-white'
                                            : 'bg-gray-100 text-gray-600' }} mr-3 flex h-8 w-8 items-center justify-center rounded-lg">
                                        <i class="fas fa-camera text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold">
                                            V{{ $snapshot['version'] }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $snapshot['submitted_at']->format('M j') }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="{{ $snapshot['status'] === 'accepted'
                                        ? 'bg-green-100 text-green-800'
                                        : ($snapshot['status'] === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-gray-100 text-gray-600') }} rounded-lg px-2 py-1 text-xs">
                                    {{ ucfirst($snapshot['status']) }}
                                </div>

                                {{-- Comparison Checkbox --}}
                                <input type="checkbox" class="comparison-checkbox ml-2 hidden"
                                    data-snapshot-id="{{ $snapshot['id'] }}"
                                    onchange="updateComparison()">
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Version Comparison Interface --}}
                <div id="version-comparison"
                    class="mt-4 hidden rounded-lg border border-blue-200/30 bg-white/60 p-4 backdrop-blur-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="font-semibold text-blue-800">Compare Versions</h6>
                        <button class="text-blue-600 hover:text-blue-800" id="js-hide-comparison">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="mb-3 text-sm text-blue-700">Select two versions to compare side by side.</p>
                    <div id="comparison-content">
                        <!-- Comparison content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Enhanced Current Snapshot Files Display with Audio Player --}}
    @if (
        $currentSnapshot &&
            (method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : ($currentSnapshot->files ?? collect())->count() > 0))
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
                    Submitted {{ $currentSnapshot->created_at->format('M j, Y g:i A') }}
                </span>
            </div>

            @if (!isset($isPreview) || !$isPreview)
                <div class="mb-4">
                    <form x-data="approveAll({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve_all', now()->addHours(24), ['project' => $project->id]) }}' })"
                        @submit.prevent="submit" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-2 text-sm text-white hover:from-green-700 hover:to-emerald-700">
                            <i class="fas fa-check-double mr-2"></i>
                            <span x-show="!loading">Approve All Files</span>
                            <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i>
                                Approving...</span>
                        </button>
                    </form>
                </div>
            @endif

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

            {{-- Milestones Section --}}
            @if (isset($milestones) && $milestones->count() > 0)
                <div class="mb-6 rounded-xl bg-purple-50 p-6 dark:bg-purple-900/20">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon.flag class="text-purple-500" />
                            <flux:heading size="lg">Milestones</flux:heading>
                        </div>
                        @php($sumMilestones = $milestones->sum('amount'))
                        <flux:badge variant="info">Total:
                            ${{ number_format($sumMilestones, 2) }}
                        </flux:badge>
                    </div>
                    <div class="space-y-3">
                        @foreach ($milestones as $m)
                            <div class="flex items-center justify-between rounded-xl border bg-white p-4 dark:bg-gray-700">
                                <div class="min-w-0 flex-1">
                                    <flux:heading size="sm" class="truncate">
                                        {{ $m->name }}</flux:heading>
                                    @if ($m->description)
                                        <flux:subheading class="truncate">
                                            {{ $m->description }}
                                        </flux:subheading>
                                    @endif
                                    <div class="mt-1 flex items-center gap-2">
                                        <flux:text size="xs">Status:
                                            {{ ucfirst($m->status) }}</flux:text>
                                        @if ($m->amount > 0)
                                            @if ($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                <flux:badge variant="success" size="sm">
                                                    Paid
                                                </flux:badge>
                                            @elseif($m->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                                <flux:badge variant="warning" size="sm">
                                                    Payment pending</flux:badge>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-4 flex items-center gap-3">
                                    <flux:heading size="sm">
                                        ${{ number_format($m->amount, 2) }}</flux:heading>
                                    @if ($m->status !== 'approved' || ($m->amount > 0 && $m->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID))
                                        <form method="POST"
                                            action="{{ URL::temporarySignedRoute('client.portal.milestones.approve', now()->addHours(24), ['project' => $project->id, 'milestone' => $m->id]) }}">
                                            @csrf
                                            <flux:button type="submit" variant="primary" size="sm">
                                                @if ($m->amount > 0)
                                                    <flux:icon.credit-card class="mr-1" />Approve & Pay
                                                @else
                                                    <flux:icon.check class="mr-1" />Approve
                                                @endif
                                            </flux:button>
                                        </form>
                                    @else
                                        <flux:badge variant="success">
                                            <flux:icon.check-circle class="mr-1" /> Completed
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- Enhanced File List using Livewire Component --}}
            @livewire('file-list', [
                'files' => $currentSnapshot->files ?? collect(),
                'canPlay' => true,
                'canDownload' => false,
                'canDelete' => false,
                'enableBulkActions' => false,
                'showComments' => false,
                'enableCommentCreation' => false,
                'headerIcon' => 'document-duplicate',
                'emptyStateMessage' => 'No files in this version',
                'emptyStateSubMessage' => 'Files will appear here when uploaded',
                'colorScheme' => 'green'
            ])

            {{-- File Approval Section --}}
            @if (($currentSnapshot->files ?? collect())->count() > 0 && (!isset($isPreview) || !$isPreview))
                <div class="mt-6 rounded-xl bg-green-50 p-6 dark:bg-green-900/20">
                    <flux:heading size="md" class="mb-4 text-green-700">
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
                        <flux:text size="sm" class="mb-4 text-green-600">
                            {{ $unapprovedFiles->count() }} file{{ $unapprovedFiles->count() > 1 ? 's' : '' }} pending your approval
                        </flux:text>
                        
                        <div class="space-y-3 mb-6">
                            @foreach ($unapprovedFiles as $file)
                                <div class="flex items-center justify-between p-3 bg-white border border-green-200 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        @if (in_array(pathinfo($file->file_name, PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'aac', 'flac']))
                                            <flux:icon.musical-note class="text-green-500" />
                                        @else
                                            <flux:icon.document class="text-green-500" />
                                        @endif
                                        <flux:text size="sm" class="font-medium">{{ $file->file_name }}</flux:text>
                                    </div>
                                    <form method="POST"
                                        x-data="approveFile({ url: '{{ URL::temporarySignedRoute('client.portal.files.approve', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}' })"
                                        @submit.prevent="submit">
                                        @csrf
                                        <flux:button type="submit" variant="primary" size="sm">
                                            <flux:icon.check class="mr-2" />
                                            <span x-show="!loading">Approve</span>
                                            <span x-show="loading">
                                                <flux:icon.arrow-path class="mr-1 animate-spin" />
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
                                <flux:button type="submit" variant="primary">
                                    <flux:icon.check-circle class="mr-2" />
                                    <span x-show="!loading">Approve All Files</span>
                                    <span x-show="loading">
                                        <flux:icon.arrow-path class="mr-1 animate-spin" />
                                        Approving...
                                    </span>
                                </flux:button>
                            </form>
                        </div>
                    @endif
                    
                    @if ($approvedFiles->count() > 0)
                        <div class="pt-4 border-t border-green-200">
                            <flux:text size="sm" class="text-green-700 font-medium">
                                <flux:icon.check-circle class="mr-1" />
                                {{ $approvedFiles->count() }} file{{ $approvedFiles->count() > 1 ? 's' : '' }} approved
                            </flux:text>
                            <div class="mt-2 space-y-1">
                                @foreach ($approvedFiles as $file)
                                    <flux:text size="xs" class="text-green-600 block">
                                        {{ $file->file_name }} - Approved {{ optional($file->client_approved_at)->diffForHumans() }}
                                    </flux:text>
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
        </div>
    @endif
</div>

