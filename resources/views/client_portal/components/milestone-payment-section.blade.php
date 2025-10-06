@props(['project', 'pitch', 'milestones'])

@php
    // Calculate milestone payment progress
    $totalMilestones = $milestones->count();
    $paidMilestones = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count();
    $totalAmount = $milestones->sum('amount');
    $paidAmount = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount');

    // Find next payable milestone (first unpaid milestone in sort order)
    $nextPayableMilestone = $milestones
        ->where('payment_status', '!=', \App\Models\Pitch::PAYMENT_STATUS_PAID)
        ->where('payment_status', '!=', \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
        ->sortBy('sort_order')
        ->first();

    $allMilestonesPaid = $paidMilestones === $totalMilestones && $totalMilestones > 0;
@endphp

<div class="rounded-xl bg-gradient-to-br from-purple-50 to-blue-50 p-4 sm:p-6 dark:from-purple-900/20 dark:to-blue-900/20">
    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-blue-600 sm:h-12 sm:w-12">
                <flux:icon.flag class="text-white" />
            </div>
            <div class="min-w-0 flex-1">
                <flux:heading size="lg">Milestone Payments</flux:heading>
                <flux:subheading class="hidden sm:block">Pay securely through each project milestone</flux:subheading>
                <flux:subheading class="block sm:hidden">Secure milestone payments</flux:subheading>
            </div>
        </div>

        @if (!$allMilestonesPaid)
            <div class="hidden items-center gap-2 self-start rounded-lg bg-white px-3 py-2 shadow-sm sm:flex dark:bg-gray-800">
                <flux:icon.shield-check class="h-4 w-4 text-green-600" />
                <flux:text size="sm" class="text-green-600">Secure</flux:text>
            </div>
        @endif
    </div>

    {{-- Progress Overview --}}
    <div class="mb-4 rounded-xl bg-white p-3 shadow-sm sm:mb-6 sm:p-4 dark:bg-gray-800">
        <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">
                Payment Progress
            </flux:text>
            <flux:text size="sm" class="font-semibold text-purple-600 dark:text-purple-400">
                <span class="hidden sm:inline">{{ $paidMilestones }} of {{ $totalMilestones }} milestones paid</span>
                <span class="inline sm:hidden">{{ $paidMilestones }}/{{ $totalMilestones }} paid</span>
            </flux:text>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-200 sm:h-3 dark:bg-gray-700">
            <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-blue-600 transition-all duration-500"
                 style="width: {{ $totalMilestones > 0 ? ($paidMilestones / $totalMilestones * 100) : 0 }}%">
            </div>
        </div>

        <div class="flex items-center justify-between text-sm">
            <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                ${{ number_format($paidAmount, 2) }} paid
            </flux:text>
            <flux:text size="xs" class="text-gray-600 dark:text-gray-400">
                ${{ number_format($totalAmount, 2) }} total
            </flux:text>
        </div>
    </div>

    {{-- All Milestones Paid Success --}}
    @if ($allMilestonesPaid)
        <flux:callout variant="success" class="mb-4">
            <div class="flex items-center gap-3">
                <flux:icon.check-circle class="text-green-500" />
                <div>
                    <flux:heading size="sm">All Milestones Paid!</flux:heading>
                    <flux:text size="sm">
                        You've completed all milestone payments. Your deliverables are ready for download.
                    </flux:text>
                </div>
            </div>
        </flux:callout>
    @endif

    {{-- Milestones List --}}
    <div class="space-y-3 sm:space-y-4">
        @foreach ($milestones->sortBy('sort_order') as $index => $milestone)
            @php
                $isPaid = $milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID;
                $isProcessing = $milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING;
                $isNextPayable = $nextPayableMilestone && $nextPayableMilestone->id === $milestone->id;
                $isFuture = !$isPaid && !$isProcessing && !$isNextPayable;

                // Determine card styling
                if ($isPaid) {
                    $borderClass = 'border-green-200 dark:border-green-800';
                    $bgClass = 'bg-green-50/50 dark:bg-green-900/10';
                } elseif ($isNextPayable) {
                    $borderClass = 'border-purple-300 dark:border-purple-700 ring-2 ring-purple-200 dark:ring-purple-800';
                    $bgClass = 'bg-white dark:bg-gray-800';
                } else {
                    $borderClass = 'border-gray-200 dark:border-gray-700';
                    $bgClass = 'bg-white/70 dark:bg-gray-800/70';
                }
            @endphp

            <div class="group relative rounded-xl border-2 p-3 transition-all sm:p-4 {{ $borderClass }} {{ $bgClass }}">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between md:gap-6">
                    {{-- Milestone Info --}}
                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex items-start gap-2">
                            {{-- Status Icon --}}
                            <div class="flex-shrink-0 pt-0.5">
                                @if ($isPaid)
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-500">
                                        <flux:icon.check class="h-3 w-3 text-white" />
                                    </div>
                                @elseif ($isProcessing)
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500">
                                        <div class="h-2 w-2 animate-pulse rounded-full bg-white"></div>
                                    </div>
                                @elseif ($isNextPayable)
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500">
                                        <flux:icon.arrow-right class="h-3 w-3 text-white" />
                                    </div>
                                @else
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $index + 1 }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <flux:heading size="sm" class="break-words leading-snug">
                                    {{ $milestone->name }}
                                </flux:heading>

                                {{-- Next Badge - Show on separate line on mobile --}}
                                @if ($isNextPayable)
                                    <div class="mt-1">
                                        <flux:badge variant="primary" size="xs">
                                            <flux:icon.arrow-right class="mr-1" />
                                            Next
                                        </flux:badge>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($milestone->description)
                            <flux:text size="sm" class="mb-2 line-clamp-3 text-gray-600 md:line-clamp-none dark:text-gray-400">
                                {{ $milestone->description }}
                            </flux:text>
                        @endif

                        {{-- Status & Payment Info --}}
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            @if ($isPaid)
                                <flux:badge variant="success" size="xs">
                                    <flux:icon.check-circle class="mr-1" />
                                    <span class="hidden sm:inline">Paid {{ $milestone->payment_completed_at?->diffForHumans() }}</span>
                                    <span class="inline sm:hidden">Paid</span>
                                </flux:badge>
                            @elseif ($isProcessing)
                                <flux:badge variant="info" size="xs">
                                    <flux:icon.clock class="mr-1" />
                                    Processing
                                </flux:badge>
                            @elseif ($isNextPayable)
                                <flux:badge variant="warning" size="xs">
                                    <flux:icon.credit-card class="mr-1" />
                                    <span class="hidden sm:inline">Ready for Payment</span>
                                    <span class="inline sm:hidden">Ready</span>
                                </flux:badge>
                            @else
                                <flux:badge variant="ghost" size="xs">
                                    <flux:icon.lock-closed class="mr-1" />
                                    Locked
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    {{-- Payment Amount & Action --}}
                    <div class="flex flex-col gap-2 md:flex-shrink-0 md:items-end">
                        <flux:heading size="base" class="text-purple-600 sm:text-lg dark:text-purple-400">
                            ${{ number_format($milestone->amount, 2) }}
                        </flux:heading>

                        @if ($isPaid)
                            <flux:badge variant="success" class="self-start md:self-auto">
                                <flux:icon.check-circle class="mr-1" />
                                Completed
                            </flux:badge>
                        @elseif ($isProcessing)
                            <flux:button variant="ghost" size="sm" class="w-full md:w-auto" disabled>
                                Processing...
                            </flux:button>
                        @elseif ($isNextPayable && $milestone->amount > 0)
                            <form method="POST" class="w-full md:w-auto"
                                  action="{{ route('client.portal.milestones.approve', ['project' => $project->id, 'milestone' => $milestone->id]) }}">
                                @csrf
                                <flux:button type="submit" variant="primary" size="sm" icon="credit-card" class="w-full md:w-auto">
                                    Pay Now
                                </flux:button>
                            </form>
                            <div class="flex items-center gap-1 self-start text-xs text-gray-500 md:self-auto">
                                <flux:icon.lock-closed class="h-3 w-3" />
                                <span>Secure</span>
                            </div>
                        @elseif ($isNextPayable && $milestone->amount == 0)
                            <form method="POST" class="w-full md:w-auto"
                                  action="{{ route('client.portal.milestones.approve', ['project' => $project->id, 'milestone' => $milestone->id]) }}">
                                @csrf
                                <flux:button type="submit" variant="primary" size="sm" class="w-full md:w-auto">
                                    <flux:icon.check class="mr-2" />
                                    Mark Complete
                                </flux:button>
                            </form>
                        @else
                            <flux:button variant="ghost" size="sm" class="w-full md:w-auto" disabled>
                                <flux:icon.lock-closed class="mr-2" />
                                Locked
                            </flux:button>
                        @endif
                    </div>
                </div>

                {{-- Next Milestone Indicator --}}
                @if ($isNextPayable)
                    <div class="mt-3 rounded-lg bg-purple-100 p-2.5 sm:p-3 dark:bg-purple-900/30">
                        <div class="flex items-start gap-2">
                            <flux:icon.information-circle class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-600 dark:text-purple-400" />
                            <flux:text size="xs" class="text-purple-800 dark:text-purple-300">
                                <span class="hidden sm:inline">This is your next milestone payment. Complete this payment to proceed with the project.</span>
                                <span class="inline sm:hidden">Next milestone - complete payment to continue.</span>
                            </flux:text>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Payment Information Footer --}}
    @if (!$allMilestonesPaid)
        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 sm:mt-6 sm:p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <div class="flex items-start gap-2 sm:gap-3">
                <flux:icon.information-circle class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-600 sm:h-5 sm:w-5 dark:text-blue-400" />
                <div class="flex-1">
                    <flux:heading size="sm" class="mb-1 text-blue-900 dark:text-blue-100">
                        How Milestone Payments Work
                    </flux:heading>
                    <flux:text size="sm" class="text-blue-800 dark:text-blue-300">
                        <span class="hidden sm:inline">Payments are processed sequentially. Complete each milestone payment to unlock the next one. All payments are secure and processed through Stripe.</span>
                        <span class="inline sm:hidden">Pay milestones in order. Secure payments via Stripe.</span>
                    </flux:text>
                </div>
            </div>
        </div>
    @endif
</div>
