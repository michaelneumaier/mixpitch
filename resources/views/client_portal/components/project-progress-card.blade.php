@php
    $branding = $branding ?? [];
    $milestones = $milestones ?? collect();
    $hasMilestones = $milestones->count() > 0;
    $statusVariant = 'ghost';
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_PENDING:
        case \App\Models\Pitch::STATUS_AWAITING_ACCEPTANCE:
            $statusVariant = 'ghost';
            break;
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
        case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
            $statusVariant = 'info';
            break;
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
        case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
        case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
            $statusVariant = 'warning';
            break;
        case \App\Models\Pitch::STATUS_APPROVED:
            $statusVariant = 'info';
            break;
        case \App\Models\Pitch::STATUS_COMPLETED:
        case \App\Models\Pitch::STATUS_CONTEST_WINNER:
        case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
            $statusVariant = 'success';
            break;
        case \App\Models\Pitch::STATUS_DENIED:
        case \App\Models\Pitch::STATUS_CLOSED:
        case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
            $statusVariant = 'danger';
            break;
    }

    $progressWidth = '25%';
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_PENDING:
            $progressWidth = '0%';
            break;
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
            $progressWidth = '25%';
            break;
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
        case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
            $progressWidth = '50%';
            break;
        case \App\Models\Pitch::STATUS_APPROVED:
            $progressWidth = '75%';
            break;
        case \App\Models\Pitch::STATUS_COMPLETED:
            $progressWidth = '100%';
            break;
    }
@endphp

<flux:card class="mb-6">
    <div class="mb-6 flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <div class="flex items-center gap-4">
            @if (!empty($branding['logo_url']))
                <img src="{{ $branding['logo_url'] }}" alt="Brand Logo"
                    class="h-12 w-12 rounded-xl bg-white object-contain p-1 shadow-lg sm:h-16 sm:w-16">
            @else
                <div
                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg sm:h-16 sm:w-16">
                    <flux:icon.briefcase class="text-lg text-white sm:text-2xl" />
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <flux:heading size="lg" class="break-words mb-1 text-xl sm:text-2xl lg:text-3xl">
                    {{ $project->name ?: 'Untitled Project' }}
                </flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400">
                    Managed by {{ $branding['brand_display'] ?? $pitch->user->name }}
                </flux:subheading>
            </div>
        </div>

        <flux:badge size="lg" :variant="$statusVariant" class="self-start sm:self-auto">
            <div class="mr-2 h-2 w-2 animate-pulse rounded-full bg-current"></div>
            {{ $pitch->readable_status }}
        </flux:badge>
    </div>

    <div class="mb-6 rounded-xl bg-gray-50 p-2 md:p-6 dark:bg-gray-800">
        <div class="mb-4 flex items-center gap-2">
            <flux:icon.map class="text-blue-500" />
            <flux:heading size="sm">Project Progress</flux:heading>
        </div>

        <div class="relative flex items-center justify-between overflow-x-auto">
            <div class="absolute left-4 right-4 top-4 hidden h-0.5 rounded-full bg-gray-200 sm:block"></div>
            <div class="absolute left-4 top-4 hidden h-0.5 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-1000 ease-out sm:block"
                style="width: {{ $progressWidth }};"></div>

            @foreach ([
                'Started' => [
                    \App\Models\Pitch::STATUS_PENDING,
                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                    \App\Models\Pitch::STATUS_APPROVED,
                    \App\Models\Pitch::STATUS_COMPLETED,
                ],
                'In Progress' => [
                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                    \App\Models\Pitch::STATUS_APPROVED,
                    \App\Models\Pitch::STATUS_COMPLETED,
                ],
                'Review' => [
                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                    \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                    \App\Models\Pitch::STATUS_APPROVED,
                    \App\Models\Pitch::STATUS_COMPLETED,
                ],
                'Approved' => [
                    \App\Models\Pitch::STATUS_APPROVED,
                    \App\Models\Pitch::STATUS_COMPLETED,
                ],
                'Complete' => [
                    \App\Models\Pitch::STATUS_COMPLETED,
                ],
            ] as $label => $statuses)
                <div class="relative flex min-w-0 flex-1 flex-col items-center">
                    @php
                        $isActive = in_array($pitch->status, $statuses, true);
                        $variants = [
                            'Started' => 'bg-blue-500 border-blue-500',
                            'In Progress' => 'bg-purple-500 border-purple-500',
                            'Review' => 'bg-amber-500 border-amber-500',
                            'Approved' => 'bg-green-500 border-green-500',
                            'Complete' => 'bg-emerald-500 border-emerald-500',
                        ];
                    @endphp
                    <div class="{{ $isActive ? ($variants[$label] ?? 'bg-blue-500 border-blue-500') . ' text-white' : 'bg-white border-gray-300 text-gray-400' }} flex h-6 w-6 items-center justify-center rounded-full border-2 sm:h-8 sm:w-8">
                        @switch($label)
                            @case('Started')
                                <i class="fas fa-play text-xs"></i>
                                @break
                            @case('In Progress')
                                <i class="fas fa-cog {{ $pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS ? 'animate-spin' : '' }} text-xs"></i>
                                @break
                            @case('Review')
                                <i class="fas fa-eye {{ $pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW ? 'animate-pulse' : '' }} text-xs"></i>
                                @break
                            @case('Approved')
                                <i class="fas fa-check text-xs"></i>
                                @break
                            @case('Complete')
                                <i class="fas fa-trophy text-xs"></i>
                                @break
                        @endswitch
                    </div>
                    <span class="mt-1 w-full truncate text-center text-xs font-medium text-gray-600 sm:mt-2">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-lg border border-white/40 bg-white/60 p-4 backdrop-blur-sm">
            <p class="text-sm text-gray-700">
                @switch($pitch->status)
                    @case(\App\Models\Pitch::STATUS_PENDING)
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        Your project has been created and the producer is preparing your deliverables.
                        @break
                    @case(\App\Models\Pitch::STATUS_IN_PROGRESS)
                        <i class="fas fa-clock mr-2 text-purple-500"></i>
                        The producer is actively working on your project. You'll be notified when it's ready for review.
                        @break
                    @case(\App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                        <i class="fas fa-bell mr-2 animate-pulse text-amber-500"></i>
                        <strong>Action Required:</strong> Your project is ready for review! Please check the deliverables below and approve or request revisions.
                        @break
                    @case(\App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED)
                        <i class="fas fa-sync-alt mr-2 text-amber-500"></i>
                        <strong>Revisions Requested:</strong> The producer is reviewing your feedback and will submit an updated version soon. You'll be notified when the next version is ready.
                        @break
                    @case(\App\Models\Pitch::STATUS_APPROVED)
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        Great! You've approved the project.
                        @if ($pitch->payment_amount > 0)
                            Payment processing is in progress.
                        @endif
                        @break
                    @case(\App\Models\Pitch::STATUS_COMPLETED)
                        <i class="fas fa-star mr-2 text-emerald-500"></i>
                        🎉 Project completed successfully! All deliverables are available below.
                        @break
                    @default
                        <i class="fas fa-question-circle mr-2 text-gray-500"></i>
                        Project status: {{ $pitch->readable_status }}
                @endswitch
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <flux:icon.user-circle class="flex-shrink-0 text-blue-500" />
                <div class="min-w-0 flex-1">
                    @if ($project->client_name)
                        <flux:heading size="sm" class="truncate">{{ $project->client_name }}</flux:heading>
                        <flux:subheading class="truncate">{{ $project->client_email }}</flux:subheading>
                    @else
                        <flux:heading size="sm" class="truncate">{{ $project->client_email }}</flux:heading>
                    @endif
                </div>
            </div>
        </div>

        @if ($hasMilestones)
            {{-- Milestone-based payment progress --}}
            @php
                $paidMilestones = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count();
                $totalMilestones = $milestones->count();
                $paidAmount = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount');
                $totalAmount = $milestones->sum('amount');
                $allMilestonesPaid = $paidMilestones === $totalMilestones;
            @endphp
            <div class="rounded-xl bg-purple-50 p-4 dark:bg-purple-900/20">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <flux:icon.flag class="flex-shrink-0 text-purple-500" />
                        <div class="min-w-0 flex-1">
                            <flux:heading size="sm">
                                {{ $paidMilestones }} of {{ $totalMilestones }} Milestones
                            </flux:heading>
                            <flux:subheading>${{ number_format($paidAmount, 2) }} / ${{ number_format($totalAmount, 2) }} Paid</flux:subheading>
                        </div>
                    </div>
                    <div class="ml-2 flex-shrink-0 text-right">
                        @if ($allMilestonesPaid)
                            <flux:badge variant="success" size="sm">
                                <flux:icon.check-circle class="mr-1" />
                                <span class="hidden sm:inline">Complete</span>
                                <span class="sm:hidden">✓</span>
                            </flux:badge>
                        @else
                            <flux:badge variant="warning" size="sm">
                                <flux:icon.clock class="mr-1" />
                                <span class="hidden sm:inline">In Progress</span>
                                <span class="sm:hidden">{{ $paidMilestones }}/{{ $totalMilestones }}</span>
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($pitch->payment_amount > 0)
            {{-- Single payment display (no milestones) --}}
            <div class="rounded-xl bg-green-50 p-4 dark:bg-green-900/20">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <flux:icon.currency-dollar class="flex-shrink-0 text-green-500" />
                        <div class="min-w-0 flex-1">
                            <flux:heading size="sm">
                                ${{ number_format($pitch->payment_amount, 2) }}
                            </flux:heading>
                            <flux:subheading>Project Value</flux:subheading>
                        </div>
                    </div>
                    <div class="ml-2 flex-shrink-0 text-right">
                        @if ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                            <flux:badge variant="success" size="sm">
                                <flux:icon.check-circle class="mr-1" />
                                <span class="hidden sm:inline">Paid</span>
                                <span class="sm:hidden">✓</span>
                            </flux:badge>
                        @else
                            <flux:badge variant="warning" size="sm">
                                <flux:icon.clock class="mr-1" />
                                <span class="hidden sm:inline">Payment Due</span>
                                <span class="sm:hidden">Due</span>
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</flux:card>

