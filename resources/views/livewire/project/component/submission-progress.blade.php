<div class="space-y-4">
    {{-- Progress Status Card --}}
    <flux:card class="{{ $colorScheme['bg'] }} {{ $colorScheme['border'] }}">
        <div class="flex items-start gap-4">
            {{-- Status Icon --}}
            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $colorScheme['bg'] }} border {{ $colorScheme['border'] }}">
                <flux:icon :name="$this->progressData['icon']" class="w-6 h-6 {{ $colorScheme['icon'] }}" />
            </div>

            {{-- Content --}}
            <div class="flex-1">
                <flux:heading size="lg" class="{{ $colorScheme['title'] }}">
                    {{ $this->progressData['title'] }}
                </flux:heading>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                    {{ $this->progressData['description'] }}
                </p>

                {{-- Progress Bar --}}
                @if($this->progressData['progress'])
                    <div class="flex items-center gap-3 mt-3">
                        <div class="flex-1 bg-white/50 dark:bg-gray-800/50 rounded-full h-2 border border-gray-200 dark:border-gray-700">
                            <div class="{{ $colorScheme['progress'] }} h-full rounded-full transition-all duration-500"
                                 style="width: {{ $this->progressData['progress'] }}%"></div>
                        </div>
                        <span class="text-xs font-medium {{ $colorScheme['title'] }} min-w-[3rem] text-right">
                            {{ $this->progressData['progress'] }}%
                        </span>
                    </div>
                @endif
            </div>

            {{-- Stats Sidebar --}}
            <div class="text-right min-w-fit hidden sm:block">
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    {{ $this->stats['submission_count'] }} {{ Str::plural('submission', $this->stats['submission_count']) }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Round {{ $this->stats['revision_round'] }}
                </div>
            </div>
        </div>
    </flux:card>

    {{-- Approved State (awaiting payment) --}}
    @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED && $this->paymentStatus)
        <flux:card class="bg-gradient-to-r from-blue-50 to-sky-50 dark:from-blue-950 dark:to-sky-950 border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900 border border-blue-200 dark:border-blue-800">
                    <flux:icon name="check-badge" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>

                <div class="flex-1">
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                        {{ $this->progressData['title'] }}
                    </flux:heading>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        {{ $this->progressData['description'] }}
                    </p>

                    @php $paymentStatus = $this->paymentStatus; @endphp

                    {{-- Payment Progress --}}
                    <div class="mt-4 p-3 bg-white/60 dark:bg-gray-800/60 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-blue-700 dark:text-blue-300">
                                Payment Progress
                            </span>
                            <flux:button
                                wire:click="$dispatch('switchTab', { tab: 'billing' })"
                                variant="ghost"
                                size="xs"
                            >
                                View Billing
                            </flux:button>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-blue-200 dark:bg-blue-900">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-sky-600 transition-all duration-500"
                                         style="width: {{ $paymentStatus['paid_percentage'] }}%"></div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300 min-w-[3rem] text-right">
                                {{ number_format($paymentStatus['paid_percentage'], 0) }}%
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-xs text-blue-600 dark:text-blue-400">
                            <span>${{ number_format($paymentStatus['paid_amount'], 2) }} paid</span>
                            <span>${{ number_format($paymentStatus['outstanding_amount'], 2) }} outstanding</span>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Completed State Enhancement (only when STATUS_COMPLETED) --}}
    @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $this->completionData)
        @php
            $paymentStatus = $this->paymentStatus;
            $hasMilestones = $paymentStatus !== null;
            $isPaidInFull = $paymentStatus['is_paid_in_full'] ?? true;

            // Color scheme: Green for fully paid, Amber for payment pending
            $cardColors = $isPaidInFull
                ? ['bg' => 'from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950', 'border' => 'border-green-200 dark:border-green-800']
                : ['bg' => 'from-amber-50 to-yellow-50 dark:from-amber-950 dark:to-yellow-950', 'border' => 'border-amber-200 dark:border-amber-800'];
        @endphp

        <flux:card class="bg-gradient-to-r {{ $cardColors['bg'] }} {{ $cardColors['border'] }}">
            {{-- Success Header --}}
            <div class="text-center py-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full
                    {{ $isPaidInFull ? 'bg-green-100 dark:bg-green-900' : 'bg-amber-100 dark:bg-amber-900' }} mb-4">
                    <flux:icon
                        :name="$isPaidInFull ? 'check-circle' : 'check-badge'"
                        variant="solid"
                        class="w-10 h-10 {{ $isPaidInFull ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}"
                    />
                </div>

                {{-- Dynamic Title based on payment state --}}
                @if($isPaidInFull)
                    <flux:heading size="xl" class="text-green-800 dark:text-green-200">
                        Project Complete & Paid In Full!
                    </flux:heading>
                    <p class="text-green-700 dark:text-green-300 mt-2">
                        Approved and paid by {{ $project->client_name ?: 'client' }} on
                        {{ $this->completionData['approved_at']->format('F j, Y') }}
                    </p>
                @elseif($hasMilestones)
                    <flux:heading size="xl" class="text-amber-800 dark:text-amber-200">
                        Project Approved - Payment in Progress
                    </flux:heading>
                    <p class="text-amber-700 dark:text-amber-300 mt-2">
                        Client approved on {{ $this->completionData['approved_at']->format('F j, Y') }}
                        <br>
                        <span class="text-sm">Awaiting milestone payments to finalize</span>
                    </p>
                @else
                    <flux:heading size="xl" class="text-green-800 dark:text-green-200">
                        Project Successfully Completed!
                    </flux:heading>
                    <p class="text-green-700 dark:text-green-300 mt-2">
                        Approved by {{ $project->client_name ?: 'client' }} on
                        {{ $this->completionData['approved_at']->format('F j, Y') }}
                    </p>
                @endif
            </div>

            {{-- Payment Status Section (only if milestones exist) --}}
            @if($hasMilestones)
                <div class="mt-4 pt-4 border-t {{ $isPaidInFull ? 'border-green-200 dark:border-green-800' : 'border-amber-200 dark:border-amber-800' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="currency-dollar"
                                class="w-5 h-5 {{ $isPaidInFull ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}"
                            />
                            <flux:heading size="sm" class="{{ $isPaidInFull ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200' }}">
                                Payment Status
                            </flux:heading>
                        </div>

                        {{-- Link to Billing Tab --}}
                        <flux:button
                            wire:click="$dispatch('switchTab', { tab: 'billing' })"
                            variant="ghost"
                            size="sm"
                            class="text-xs"
                        >
                            View Details
                            <flux:icon name="arrow-right" class="w-3 h-3 ml-1" />
                        </flux:button>
                    </div>

                    {{-- Payment Summary --}}
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="rounded-lg bg-white/60 dark:bg-gray-800/60 p-3">
                            <div class="text-xs {{ $isPaidInFull ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500' }} mb-1">
                                Paid Amount
                            </div>
                            <div class="text-lg font-bold {{ $isPaidInFull ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                                ${{ number_format($paymentStatus['paid_amount'], 2) }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-white/60 dark:bg-gray-800/60 p-3">
                            <div class="text-xs {{ $isPaidInFull ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500' }} mb-1">
                                {{ $isPaidInFull ? 'Total Project' : 'Outstanding' }}
                            </div>
                            <div class="text-lg font-bold {{ $isPaidInFull ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                                ${{ number_format($isPaidInFull ? $paymentStatus['total_amount'] : $paymentStatus['outstanding_amount'], 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar (only if not fully paid) --}}
                    @if(!$isPaidInFull)
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-amber-700 dark:text-amber-300">
                                    {{ $paymentStatus['paid_count'] }} of {{ $paymentStatus['total_milestones'] }} milestones paid
                                </span>
                                <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">
                                    {{ number_format($paymentStatus['paid_percentage'], 0) }}%
                                </span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-amber-200 dark:bg-amber-900">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-yellow-600 transition-all duration-500"
                                     style="width: {{ $paymentStatus['paid_percentage'] }}%"></div>
                            </div>
                        </div>

                        {{-- Processing/Pending Info --}}
                        <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300">
                            <flux:icon name="information-circle" class="w-4 h-4" />
                            @if($paymentStatus['processing_count'] > 0)
                                <span>{{ $paymentStatus['processing_count'] }} {{ Str::plural('payment', $paymentStatus['processing_count']) }} processing</span>
                            @else
                                <span>{{ $paymentStatus['pending_count'] }} {{ Str::plural('milestone', $paymentStatus['pending_count']) }} awaiting payment</span>
                            @endif
                        </div>
                    @else
                        {{-- Fully Paid Badge --}}
                        <div class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-2 dark:border-green-800 dark:bg-green-900/20">
                            <flux:icon name="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
                            <flux:text size="sm" class="font-medium text-green-800 dark:text-green-200">
                                All milestones paid - thank you!
                            </flux:text>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Project Summary Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 border-t {{ $isPaidInFull ? 'border-green-200 dark:border-green-800' : 'border-amber-200 dark:border-amber-800' }} pt-6">
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $isPaidInFull ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                        {{ $this->stats['submission_count'] }}
                    </div>
                    <div class="text-xs {{ $isPaidInFull ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500' }}">
                        Total {{ Str::plural('Submission', $this->stats['submission_count']) }}
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $isPaidInFull ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                        {{ $this->completionData['revision_rounds'] }}
                    </div>
                    <div class="text-xs {{ $isPaidInFull ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500' }}">
                        Revision {{ Str::plural('Round', $this->completionData['revision_rounds']) }}
                    </div>
                </div>
                <div class="text-center col-span-2 sm:col-span-1">
                    <div class="text-2xl font-bold {{ $isPaidInFull ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                        {{ $this->completionData['total_days'] }}
                    </div>
                    <div class="text-xs {{ $isPaidInFull ? 'text-green-600 dark:text-green-500' : 'text-amber-600 dark:text-amber-500' }}">
                        {{ Str::plural('Day', $this->completionData['total_days']) }} to Complete
                    </div>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Timeline Section --}}
    @if($this->timelineEntries->isNotEmpty())
        <div class="mt-6">
            <flux:heading size="sm" class="mb-4 text-gray-800 dark:text-gray-200">
                <flux:icon name="clock" class="mr-2 inline w-5 h-5" />
                Delivery History
            </flux:heading>

            {{-- Vertical timeline with nodes --}}
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                <ul class="space-y-4">
                    @foreach($this->timelineEntries->take($showFullTimeline ? null : $initialTimelineCount) as $entry)
                        <li class="relative pl-12">
                            {{-- Timeline Node --}}
                            <div class="absolute left-0 w-10 h-10 flex items-center justify-center">
                                <div class="w-8 h-8 rounded-full {{ $entry['bg_color'] }} border-2 border-white dark:border-gray-800 flex items-center justify-center z-10">
                                    <flux:icon :name="$entry['icon']" class="w-4 h-4 text-white" />
                                </div>
                            </div>

                            {{-- Entry Content --}}
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border-l-4 {{ $entry['border_color'] }}">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $entry['title'] }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-4 whitespace-nowrap">
                                        {{ $entry['timestamp']->diffForHumans() }}
                                    </span>
                                </div>

                                @if($entry['description'])
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $entry['description'] }}
                                    </div>
                                @endif

                                {{-- File count for submissions --}}
                                @if(isset($entry['file_count']) && $entry['file_count'] > 0)
                                    <div class="flex items-center gap-2 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <flux:icon name="document" class="w-4 h-4" />
                                        <span>{{ $entry['file_count'] }} {{ Str::plural('file', $entry['file_count']) }} submitted</span>
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Show more button --}}
            @if($this->timelineEntries->count() > $initialTimelineCount)
                <div class="mt-4 text-center">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="toggleTimeline"
                    >
                        @if($showFullTimeline)
                            <flux:icon name="chevron-up" class="mr-1 w-4 h-4" />
                            Show less
                        @else
                            <flux:icon name="chevron-down" class="mr-1 w-4 h-4" />
                            Show {{ $this->timelineEntries->count() - $initialTimelineCount }} more...
                        @endif
                    </flux:button>
                </div>
            @endif
        </div>
    @endif
</div>
