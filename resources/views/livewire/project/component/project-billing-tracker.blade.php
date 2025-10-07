<div>
    @php
        $paymentSummary = $this->paymentSummary;
    @endphp

    <flux:card class="mb-4">
        <!-- Header -->
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon.currency-dollar variant="solid" class="{{ $workflowColors['icon'] }} h-6 w-6" />
                <div>
                    <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                        Billing & Payments
                    </flux:heading>
                    <flux:text size="xs" class="{{ $workflowColors['text_muted'] }}">
                        Track invoices & payment status
                    </flux:text>
                </div>
            </div>
        </div>

        <!-- Payment Summary Overview -->
        <div class="mb-4 space-y-3 rounded-lg border-2 border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <!-- Total Budget & Paid Amount -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <flux:text size="sm" class="mb-1 font-medium text-purple-700 dark:text-purple-300">
                        Total Budget
                    </flux:text>
                    <flux:heading size="lg" class="font-bold text-purple-900 dark:text-purple-100">
                        ${{ number_format($paymentSummary['total_budget'], 2) }}
                    </flux:heading>
                </div>
                <div class="flex-1 text-right">
                    <flux:text size="sm" class="mb-1 font-medium text-purple-700 dark:text-purple-300">
                        Paid to Date
                    </flux:text>
                    <flux:heading size="lg" class="font-bold text-green-600 dark:text-green-400">
                        ${{ number_format($paymentSummary['paid_amount'], 2) }}
                    </flux:heading>
                </div>
            </div>

            <!-- Progress Bar -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <flux:text size="xs" class="text-purple-700 dark:text-purple-300">
                        Payment Progress
                    </flux:text>
                    <flux:text size="xs" class="font-semibold text-purple-700 dark:text-purple-300">
                        {{ number_format($paymentSummary['paid_percentage'], 1) }}%
                    </flux:text>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-purple-200 dark:bg-purple-900">
                    <div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-600 transition-all duration-500"
                         style="width: {{ $paymentSummary['paid_percentage'] }}%"></div>
                </div>
            </div>

            <!-- Payment Stats Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-white/60 p-2 dark:bg-gray-800/60">
                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Outstanding</flux:text>
                    <flux:text size="sm" class="font-semibold text-amber-600 dark:text-amber-400">
                        ${{ number_format($paymentSummary['outstanding_amount'], 2) }}
                    </flux:text>
                </div>
                <div class="rounded-lg bg-white/60 p-2 dark:bg-gray-800/60">
                    <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Milestones Paid</flux:text>
                    <flux:text size="sm" class="font-semibold text-purple-600 dark:text-purple-400">
                        {{ $paymentSummary['paid_count'] }} / {{ $paymentSummary['total_milestones'] }}
                    </flux:text>
                </div>
            </div>

            <!-- All Paid Success -->
            @if ($paymentSummary['all_paid'])
                <div class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-2 dark:border-green-800 dark:bg-green-900/20">
                    <flux:icon.check-circle class="h-5 w-5 text-green-600 dark:text-green-400" />
                    <flux:text size="sm" class="font-medium text-green-800 dark:text-green-200">
                        All milestones paid!
                    </flux:text>
                </div>
            @endif
        </div>

        <!-- Milestone Payment Status Table -->
        @if ($this->milestones->count() > 0)
            <div class="mb-4">
                <flux:heading size="sm" class="{{ $workflowColors['text_primary'] }} mb-2">
                    Milestone Payment Status
                </flux:heading>

                <div class="max-h-96 space-y-2 overflow-y-auto">
                    @foreach ($this->milestones as $milestone)
                        @php
                            $isPaid = $milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID;
                            $isProcessing = $milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING;
                            $isPending = !$isPaid && !$isProcessing;
                            $isRevision = $milestone->is_revision_milestone ?? false;
                            $isManual = $milestone->stripe_invoice_id && str_starts_with($milestone->stripe_invoice_id, 'MANUAL_');

                            // Determine card styling
                            if ($isPaid) {
                                $borderClass = 'border-green-200 dark:border-green-800';
                                $bgClass = 'bg-green-50/50 dark:bg-green-900/10';
                            } elseif ($isProcessing) {
                                $borderClass = 'border-blue-200 dark:border-blue-800';
                                $bgClass = 'bg-blue-50/50 dark:bg-blue-900/10';
                            } else {
                                $borderClass = 'border-gray-200 dark:border-gray-700';
                                $bgClass = 'bg-white/70 dark:bg-gray-800/70';
                            }
                        @endphp

                        <div class="rounded-lg border p-3 {{ $borderClass }} {{ $bgClass }}">
                            <div class="flex items-start justify-between gap-3">
                                <!-- Milestone Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <flux:heading size="sm" class="break-words">
                                            {{ $milestone->name }}
                                        </flux:heading>

                                        <!-- Revision Badge -->
                                        @if ($isRevision)
                                            <flux:badge variant="warning" size="sm" icon="arrow-path">
                                                Revision
                                            </flux:badge>
                                        @endif

                                        <!-- Manual Payment Badge -->
                                        @if ($isManual && $isPaid)
                                            <flux:badge variant="outline" size="sm" icon="hand-raised">
                                                Manual
                                            </flux:badge>
                                        @endif
                                    </div>

                                    <!-- Amount & Status -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:text size="sm" class="font-semibold text-purple-600 dark:text-purple-400">
                                            ${{ number_format($milestone->amount, 2) }}
                                        </flux:text>

                                        @if ($isPaid)
                                            <flux:badge variant="success" size="sm" icon="check-circle">
                                                Paid {{ $milestone->payment_completed_at?->diffForHumans() }}
                                            </flux:badge>
                                        @elseif ($isProcessing)
                                            <flux:badge variant="info" size="sm" icon="clock">
                                                Processing
                                            </flux:badge>
                                        @else
                                            <flux:badge variant="outline" size="sm" icon="clock">
                                                Pending
                                            </flux:badge>
                                        @endif
                                    </div>

                                    <!-- Manual Payment Note -->
                                    @if ($isManual && $isPaid)
                                        @php
                                            $note = $this->getManualPaymentNote($milestone);
                                            $isExpanded = $this->expandedNotes[$milestone->id] ?? false;
                                            $needsTruncation = $note && strlen($note) > 60;
                                            $displayNote = $isExpanded || !$needsTruncation ? $note : Str::limit($note, 60);
                                        @endphp

                                        @if ($note)
                                            <div
                                                wire:click="toggleNoteExpansion({{ $milestone->id }})"
                                                class="mt-2 cursor-pointer rounded-md bg-gray-100 px-2 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750">
                                                <div class="flex items-start gap-1.5">
                                                    <flux:icon.information-circle class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-500 dark:text-gray-400" />
                                                    <div class="min-w-0 flex-1">
                                                        <span class="font-medium">Payment Note:</span>
                                                        <span class="{{ $needsTruncation && !$isExpanded ? 'line-clamp-1' : '' }}">
                                                            {{ $displayNote }}
                                                        </span>
                                                        @if ($needsTruncation)
                                                            <span class="ml-1 text-purple-600 dark:text-purple-400">
                                                                {{ $isExpanded ? '(click to collapse)' : '(click to expand)' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-1">
                                    @if ($isPaid && $milestone->stripe_invoice_id && !$isManual)
                                        <flux:button
                                            wire:click="viewInvoiceDetails('{{ $milestone->stripe_invoice_id }}')"
                                            variant="ghost"
                                            size="xs"
                                            icon="document-text">
                                            Invoice
                                        </flux:button>
                                    @endif

                                    @if (!$isPaid && !$isProcessing)
                                        <flux:button
                                            wire:click="openManualPaymentModal({{ $milestone->id }})"
                                            variant="outline"
                                            size="xs"
                                            class="whitespace-nowrap"
                                            icon="check">
                                            Mark Paid
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="py-6 text-center">
                <flux:icon.inbox class="mx-auto mb-2 h-8 w-8 {{ $workflowColors['text_muted'] }}" />
                <flux:text size="sm" class="{{ $workflowColors['text_muted'] }}">
                    No milestones configured yet
                </flux:text>
            </div>
        @endif

        <!-- Payment Timeline Section -->
        @if ($this->paymentTimeline->count() > 0)
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <flux:heading size="sm" class="{{ $workflowColors['text_primary'] }} mb-3">
                    Recent Activity
                </flux:heading>

                <div class="max-h-64 space-y-2 overflow-y-auto">
                    @foreach ($this->paymentTimeline as $event)
                        @php
                            $eventIcon = match($event->event_type) {
                                'milestone_paid' => 'check-circle',
                                'milestone_manually_marked_paid' => 'hand-raised',
                                'budget_updated' => 'currency-dollar',
                                'revision_policy_updated' => 'arrow-path',
                                default => 'information-circle',
                            };

                            $eventColor = match($event->event_type) {
                                'milestone_paid', 'milestone_manually_marked_paid' => 'text-green-600 dark:text-green-400',
                                'budget_updated' => 'text-purple-600 dark:text-purple-400',
                                'revision_policy_updated' => 'text-blue-600 dark:text-blue-400',
                                default => 'text-gray-600 dark:text-gray-400',
                            };
                        @endphp

                        <div class="flex items-start gap-2 rounded-lg bg-gray-50 p-2 dark:bg-gray-800/50">
                            <flux:icon :icon="$eventIcon" class="mt-0.5 h-4 w-4 flex-shrink-0 {{ $eventColor }}" />
                            <div class="min-w-0 flex-1">
                                <flux:text size="xs" class="text-gray-800 dark:text-gray-200">
                                    {{ $event->comment }}
                                </flux:text>
                                <flux:text size="xs" class="text-gray-500 dark:text-gray-500">
                                    {{ $event->created_at->diffForHumans() }}
                                    @if ($event->user)
                                        by {{ $event->user->name }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:card>

    <!-- Manual Payment Modal -->
    <flux:modal wire:model="showManualPaymentModal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="h-6 w-6 text-amber-600" />
                <flux:heading size="lg">Mark Milestone as Paid</flux:heading>
            </div>

            @if ($selectedMilestoneId)
                @php
                    $selectedMilestone = $this->milestones->firstWhere('id', $selectedMilestoneId);
                @endphp

                @if ($selectedMilestone)
                    <!-- Milestone Info -->
                    <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 dark:border-purple-800 dark:bg-purple-900/20">
                        <flux:text size="sm" class="mb-1 font-medium text-purple-900 dark:text-purple-100">
                            {{ $selectedMilestone->name }}
                        </flux:text>
                        <flux:text size="lg" class="font-bold text-purple-600 dark:text-purple-400">
                            ${{ number_format($selectedMilestone->amount, 2) }}
                        </flux:text>
                    </div>

                    <!-- Warning Box -->
                    <flux:callout variant="warning">
                        <div class="space-y-2">
                            <flux:text size="sm" class="font-semibold">
                                Important: Manual Payment Marking
                            </flux:text>
                            <flux:text size="sm">
                                Use this feature only when payment was received outside the platform (e.g., wire transfer, check, cash).
                                This will mark the milestone as paid without processing through Stripe.
                            </flux:text>
                        </div>
                    </flux:callout>

                    <!-- Note Field -->
                    <flux:field>
                        <flux:label>Reason / Note (Optional)</flux:label>
                        <flux:textarea
                            wire:model.defer="manualPaymentNote"
                            rows="3"
                            placeholder="e.g., Payment received via wire transfer on [date]..."
                        />
                        <flux:error name="manualPaymentNote" />
                    </flux:field>

                    <!-- Confirmation Checkbox -->
                    <flux:field>
                        <label class="flex items-start gap-2">
                            <input
                                type="checkbox"
                                wire:model.defer="confirmManualPayment"
                                class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-800"
                            />
                            <flux:text size="sm">
                                I confirm that payment for this milestone has been received outside the platform,
                                and I understand this action will be logged in the project history.
                            </flux:text>
                        </label>
                        <flux:error name="confirmManualPayment" />
                    </flux:field>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-4">
                        <flux:button wire:click="closeManualPaymentModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button
                            wire:click="markMilestoneAsPaidManually"
                            variant="primary"
                            icon="check">
                            Confirm & Mark as Paid
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>
    </flux:modal>

    <!-- Invoice Details Modal -->
    <flux:modal wire:model="showInvoiceModal" class="max-w-2xl">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <flux:icon.document-text class="h-6 w-6 {{ $workflowColors['icon'] }}" />
                <flux:heading size="lg">Invoice Details</flux:heading>
            </div>

            @if ($invoiceDetails)
                <div class="space-y-3">
                    <!-- Invoice Header -->
                    <div class="rounded-lg border bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Invoice Number</flux:text>
                                <flux:text size="sm" class="font-mono font-semibold">
                                    {{ $invoiceDetails->number ?? 'N/A' }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Date</flux:text>
                                <flux:text size="sm" class="font-semibold">
                                    {{ $invoiceDetails->date?->format('M d, Y') ?? 'N/A' }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Amount</flux:text>
                                <flux:text size="lg" class="font-bold text-purple-600 dark:text-purple-400">
                                    ${{ number_format($invoiceDetails->total / 100, 2) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" class="text-gray-600 dark:text-gray-400">Status</flux:text>
                                @if ($invoiceDetails->paid)
                                    <flux:badge variant="success">Paid</flux:badge>
                                @else
                                    <flux:badge variant="warning">Unpaid</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Description -->
                    @if ($invoiceDetails->description)
                        <div>
                            <flux:text size="xs" class="mb-1 text-gray-600 dark:text-gray-400">Description</flux:text>
                            <flux:text size="sm">{{ $invoiceDetails->description }}</flux:text>
                        </div>
                    @endif

                    <!-- Stripe Link -->
                    @if ($invoiceDetails->stripe_invoice)
                        <div class="pt-2">
                            <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                                Stripe Invoice ID: <span class="font-mono">{{ $invoiceDetails->id }}</span>
                            </flux:text>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Close Button -->
            <div class="flex justify-end pt-4">
                <flux:button wire:click="closeInvoiceModal" variant="primary">
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
