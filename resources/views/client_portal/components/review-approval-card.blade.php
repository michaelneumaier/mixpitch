@props(['project', 'pitch', 'currentSnapshot', 'milestones'])

@php
    // Extract the most recent client feedback event if in revisions requested state
    $latestFeedbackEvent = null;
    if ($pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
        $latestFeedbackEvent = $pitch->events()
            ->where('event_type', 'client_revisions_requested')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    // Check if milestones exist for this pitch
    $milestones = $milestones ?? collect();
    $hasMilestones = $milestones->count() > 0;
    $allMilestonesPaid = $hasMilestones &&
        $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count() === $milestones->count();
@endphp

@php
    // Check if we should show this card
    $shouldShowCard = in_array($pitch->status, [
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
        \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
        \App\Models\Pitch::STATUS_APPROVED
    ]);

    // Also show for COMPLETED if milestones exist and aren't all paid
    if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $hasMilestones && !$allMilestonesPaid) {
        $shouldShowCard = true;
    }
@endphp

@if ($shouldShowCard)
    <flux:card class="mb-2">
        @if ($pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED && $latestFeedbackEvent)
            {{-- Feedback Sent Confirmation State --}}
            <div class="mb-6 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-6 dark:from-amber-900/20 dark:to-orange-900/20">
                <div class="mb-4 flex items-start gap-3">
                    <flux:icon.check-circle class="mt-1 text-green-500" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="mb-2">Feedback Sent Successfully!</flux:heading>
                        <flux:subheading class="mb-4">Your revision request has been delivered to the producer. They'll review your feedback and submit an updated version soon.</flux:subheading>
                    </div>
                </div>

                {{-- Display the feedback that was sent --}}
                <div class="mb-4 rounded-lg border border-amber-200 bg-white/80 p-4 dark:border-amber-800 dark:bg-gray-800/80">
                    <div class="mb-2 flex items-center gap-2">
                        <flux:icon.pencil class="text-amber-500" />
                        <flux:heading size="sm">Your Feedback:</flux:heading>
                    </div>
                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">
                        {{ $latestFeedbackEvent->comment }}
                    </flux:text>
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <flux:icon.clock class="h-3 w-3" />
                        <span>Sent {{ $latestFeedbackEvent->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                {{-- Producer reviewing indicator --}}
                <div class="flex items-center gap-2 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                    <flux:icon.user-circle class="text-blue-500" />
                    <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                        <strong>{{ $pitch->user->name }}</strong> is reviewing your feedback and preparing the next version
                    </flux:text>
                </div>
            </div>

            {{-- Changed Your Mind Section --}}
            <div class="mb-6">
                <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-center gap-2">
                        <flux:icon.light-bulb class="text-yellow-500" />
                        <flux:heading size="sm">Changed Your Mind?</flux:heading>
                    </div>
                    <flux:text size="sm" class="mb-4 text-gray-600 dark:text-gray-400">
                        If you'd like to approve this current version instead of waiting for revisions, you can still do so below.
                    </flux:text>
                </div>
            </div>
        @elseif ($pitch->status === \App\Models\Pitch::STATUS_APPROVED)
            {{-- Approved State - Payment Pending --}}
            <div class="mb-6 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 p-6 dark:from-green-900/20 dark:to-emerald-900/20">
                <div class="mb-4 flex items-start gap-3">
                    <flux:icon.check-circle class="mt-1 text-green-500" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="mb-2">Project Approved!</flux:heading>
                        <flux:subheading class="mb-4">You've approved this work. Please complete the payment below to finalize and receive your deliverables.</flux:subheading>
                    </div>
                </div>
            </div>
        @elseif ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $hasMilestones && !$allMilestonesPaid)
            {{-- Completed but Unpaid Milestones - Payment Required --}}
            <div class="mb-6 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-6 dark:from-amber-900/20 dark:to-orange-900/20">
                <div class="mb-4 flex items-start gap-3">
                    <flux:icon.exclamation-triangle class="mt-1 text-amber-500" />
                    <div class="flex-1">
                        <flux:heading size="lg" class="mb-2">Payment Required</flux:heading>
                        <flux:subheading class="mb-4">The project is complete, but milestone payments are pending. Please complete all milestone payments below to access your deliverables.</flux:subheading>
                    </div>
                </div>
            </div>
        @else
            {{-- Original Ready for Review State --}}
            <div class="mb-6 flex items-center gap-3">
                <flux:icon.clipboard-document-check class="animate-pulse text-green-500" />
                <div>
                    <flux:heading size="lg">Review &amp; Approval</flux:heading>
                    <flux:subheading>The project is ready for your review. Please approve or request revisions.</flux:subheading>
                </div>
            </div>
        @endif

        {{-- Milestone Payment Section (if milestones exist) --}}
        @if ($hasMilestones)
            <div class="mb-6">
                @include('client_portal.components.milestone-payment-section', [
                    'project' => $project,
                    'pitch' => $pitch,
                    'milestones' => $milestones,
                ])
            </div>
        @elseif ($pitch->payment_amount > 0)
            {{-- Single Payment Information Banner (no milestones) --}}
            <flux:callout variant="info" class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <flux:icon.credit-card class="text-blue-500" />
                        <div>
                            <flux:heading size="sm">Payment Required: ${{ number_format($pitch->payment_amount, 2) }}</flux:heading>
                            <flux:subheading>Secure payment processing via Stripe</flux:subheading>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-green-600">
                        <flux:icon.shield-check class="h-4 w-4" />
                        <flux:text size="sm">Secure</flux:text>
                    </div>
                </div>
            </flux:callout>
        @endif

        @if (in_array($pitch->status, [\App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
            <div class="grid grid-cols-1 gap-6 {{ $pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED ? 'lg:grid-cols-1' : 'lg:grid-cols-2' }}">
                {{-- Approve Form --}}
                <div class="rounded-xl bg-green-50 p-6 dark:bg-green-900/20">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.check-circle class="text-green-500" />
                        <flux:heading size="sm">Approve Project</flux:heading>
                    </div>
                    <flux:text size="sm" class="mb-4">
                        @if ($hasMilestones)
                            Approve this submission to indicate you're satisfied with the work. Payment is handled separately through the milestone system above.
                        @elseif ($pitch->payment_amount > 0)
                            Clicking approve will redirect you to secure payment processing. You'll be charged ${{ number_format($pitch->payment_amount, 2) }} and the producer will be notified of completion.
                        @else
                            Clicking approve will notify the producer that the project is complete and satisfactory.
                        @endif
                    </flux:text>

                    <form action="{{ URL::temporarySignedRoute('client.portal.approve', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                        @csrf
                        <flux:button type="submit" variant="primary" icon="check-circle" class="w-full">
                            @if ($hasMilestones)
                                Approve Submission
                            @elseif ($pitch->payment_amount > 0)
                                Approve &amp; Pay ${{ number_format($pitch->payment_amount, 2) }}
                            @else
                                Approve Project
                            @endif
                        </flux:button>
                    </form>

                    @if (!$hasMilestones && $pitch->payment_amount > 0)
                        <div class="mt-3 flex items-center justify-center gap-1">
                            <flux:icon.lock-closed class="h-3 w-3 text-green-600" />
                            <flux:text size="xs" class="text-green-600">Powered by Stripe • SSL Encrypted</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Request Revisions Form - Only show in READY_FOR_REVIEW state --}}
                @if ($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                <div class="rounded-xl bg-amber-50 p-6 dark:bg-amber-900/20">
                <div class="mb-4 flex items-center gap-2">
                    <flux:icon.pencil class="text-amber-500" />
                    <flux:heading size="sm">Request Revisions</flux:heading>
                </div>

                {{-- Revision Status Information --}}
                @php
                    $revisionsUsed = $pitch->revisions_used ?? 0;
                    $includedRevisions = $pitch->included_revisions ?? 2;
                    $additionalRevisionPrice = $pitch->additional_revision_price ?? 0;
                    $revisionsRemaining = max(0, $includedRevisions - $revisionsUsed);
                    $nextRevisionIsFree = $revisionsUsed < $includedRevisions;
                @endphp

                <div class="mb-4 rounded-lg border-2 {{ $nextRevisionIsFree ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20' : 'border-amber-300 bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30' }} p-4">
                    <div class="flex items-start gap-3">
                        @if ($nextRevisionIsFree)
                            <flux:icon.check-circle class="mt-0.5 h-5 w-5 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-400" />
                        @endif
                        <div class="flex-1">
                            <flux:text size="sm" class="font-medium {{ $nextRevisionIsFree ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                                @if ($nextRevisionIsFree)
                                    <strong>{{ $revisionsRemaining }}</strong> {{ Str::plural('revision', $revisionsRemaining) }} remaining (included)
                                @else
                                    Additional revision required - <strong>${{ number_format($additionalRevisionPrice, 2) }}</strong>
                                @endif
                            </flux:text>
                            <flux:text size="xs" class="{{ $nextRevisionIsFree ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }} mt-1">
                                @if ($nextRevisionIsFree)
                                    You have {{ $revisionsRemaining }} free {{ Str::plural('revision', $revisionsRemaining) }} included with this project.
                                    @if ($additionalRevisionPrice > 0)
                                        <br>Additional revisions beyond {{ $includedRevisions }}: <strong>${{ number_format($additionalRevisionPrice, 2) }}</strong> each
                                    @endif
                                @else
                                    You've used all {{ $includedRevisions }} included revisions. Additional revisions require payment before the producer can deliver.
                                @endif
                            </flux:text>
                            @if ($pitch->revision_scope_guidelines)
                                <flux:text size="xs" class="mt-2 italic {{ $nextRevisionIsFree ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    Note: {{ $pitch->revision_scope_guidelines }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                </div>

                <flux:text size="sm" class="mb-4">
                    Use our structured feedback system to provide specific, organized feedback about what needs to be changed.
                </flux:text>

                {{-- Structured Feedback Form --}}
                <div class="mb-4 rounded-lg bg-white p-4 dark:bg-gray-700">
                    @livewire('structured-feedback-form', [
                        'pitch' => $pitch,
                        'pitchFile' => ($currentSnapshot->files ?? collect())->first(),
                        'clientEmail' => $project->client_email,
                    ])
                </div>

                {{-- Traditional Text Feedback --}}
                <div class="border-t border-amber-200 pt-4 dark:border-amber-800">
                    <flux:heading size="sm" class="mb-3">Or send traditional feedback:</flux:heading>
                    <form action="{{ URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                        @csrf
                        <flux:textarea name="feedback" rows="3"
                            placeholder="Additional feedback or specific requests..."
                            class="mb-3">{{ old('feedback') }}</flux:textarea>
                        @error('feedback')
                            <flux:text size="sm" class="mb-2 text-red-600">{{ $message }}</flux:text>
                        @enderror
                        <flux:button type="submit" variant="danger" size="sm" icon="paper-airplane" class="w-full">
                            Send Traditional Feedback
                        </flux:button>
                    </form>
                </div>
                </div>
                @endif
            </div>
        @endif
    </flux:card>
@endif
