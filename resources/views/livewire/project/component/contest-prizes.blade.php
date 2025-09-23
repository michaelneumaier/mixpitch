@props(['workflowColors' => [], 'semanticColors' => []])

<flux:card class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-orange-50/30 to-amber-50/30 dark:from-orange-950/30 dark:to-amber-950/30' }} border {{ $workflowColors['border'] ?? 'border-orange-200/50 dark:border-orange-800/50' }}">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:icon name="trophy" variant="solid" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-8 w-8" />
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">Contest Prizes</flux:heading>
                    <x-contest.payment-status-badge :project="$project" compact="true" />
                </div>
                <flux:subheading class="{{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">Rewards and incentives for winners</flux:subheading>
            </div>
        </div>
        <flux:button variant="outline" size="sm" icon="pencil" href="{{ route('projects.edit', $project) }}">
            Edit Prizes
        </flux:button>
    </div>

    @if ($project->hasPrizes())
        <!-- New Contest Prize System -->
        <div class="rounded-xl border {{ $workflowColors['accent_border'] ?? 'border-orange-200/50 dark:border-orange-700/50' }} {{ $workflowColors['accent_bg'] ?? 'bg-gradient-to-br from-orange-50/80 to-amber-50/80 dark:from-orange-950/80 dark:to-amber-950/80' }} p-4 backdrop-blur-sm">
            <!-- Prize Summary Stats -->
            <div class="mb-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg border {{ $workflowColors['accent_border'] ?? 'border-orange-200/30 dark:border-orange-700/30' }} bg-white/60 dark:bg-gray-800/60 p-3 text-center backdrop-blur-sm">
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                        ${{ number_format($project->getTotalPrizeBudget()) }}
                    </flux:heading>
                    <flux:text size="xs" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">Total Cash Prizes</flux:text>
                </div>
                <div class="rounded-lg border {{ $workflowColors['accent_border'] ?? 'border-orange-200/30 dark:border-orange-700/30' }} bg-white/60 dark:bg-gray-800/60 p-3 text-center backdrop-blur-sm">
                    <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                        ${{ number_format($project->getTotalPrizeValue()) }}
                    </flux:heading>
                    <flux:text size="xs" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }}">Total Prize Value</flux:text>
                </div>
            </div>

                <!-- Prize Breakdown -->
                <div class="space-y-2">
                    @php
                        $paymentStatus = $project->getContestPaymentStatus();
                        $winnersWithStatus = collect($paymentStatus['winners_with_status'])->keyBy(function ($winner) {
                            return $winner['prize']->placement;
                        });
                    @endphp
                    
                    @foreach ($project->getPrizeSummary() as $prize)
                        @php
                            $winnerStatus = $winnersWithStatus->get($prize['placement_key'] ?? '');
                            $isPaid = $winnerStatus && $winnerStatus['is_paid'];
                            $hasWinner = $winnerStatus !== null;
                            $isCashPrize = isset($prize['type']) && $prize['type'] === 'cash';
                        @endphp
                        
                        <div class="flex items-center justify-between rounded-lg border border-orange-200/20 bg-white/40 p-3 backdrop-blur-sm">
                            <div class="flex items-center">
                                <span class="mr-3 text-lg">{{ $prize['emoji'] ?? '🏆' }}</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <flux:text size="sm" class="font-medium text-orange-900">{{ $prize['placement'] ?? 'Prize' }}</flux:text>
                                        @if($isCashPrize && $hasWinner)
                                            @if($isPaid)
                                                <flux:badge color="green" size="xs" icon="check">Paid</flux:badge>
                                            @else
                                                <flux:badge color="amber" size="xs" icon="clock">Pending</flux:badge>
                                            @endif
                                        @elseif($isCashPrize && !$hasWinner && $project->isJudgingFinalized())
                                            <flux:badge color="zinc" size="xs" icon="user">No Winner</flux:badge>
                                        @endif
                                    </div>
                                    @if (isset($prize['title']) && $prize['title'])
                                        <flux:text size="xs" class="text-orange-700">{{ $prize['title'] }}</flux:text>
                                    @endif
                                    @if($hasWinner)
                                        <div class="flex items-center">
                                            <flux:icon name="trophy" class="mr-1 h-3 w-3 text-green-700" />
                                            <flux:text size="xs" class="text-green-700 font-medium">{{ $winnerStatus['user']->name }}</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <flux:text size="sm" class="font-bold text-orange-900">
                                    {{ $prize['display_value'] ?? 'N/A' }}
                                </flux:text>
                                @if($isPaid && $winnerStatus['payment_date'])
                                    <flux:text size="xs" class="text-green-600">
                                        Paid {{ $winnerStatus['payment_date']->format('M j') }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
    @elseif($project->prize_amount && $project->prize_amount > 0)
        <!-- Legacy Prize Display (for backward compatibility) -->
        <div class="rounded-xl border border-green-200/50 bg-gradient-to-br from-green-50/80 to-emerald-50/80 p-4 backdrop-blur-sm">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="banknotes" class="h-6 w-6 text-green-600" />
                <flux:heading size="base" class="text-green-800">Prize Amount (Legacy)</flux:heading>
            </div>
            <flux:heading size="xl" class="text-green-900 mb-3">
                {{ $project->prize_currency ?? '$' }}{{ number_format($project->prize_amount ?: 0, 2) }}
            </flux:heading>
            <flux:button variant="outline" size="xs" icon="plus" href="{{ route('projects.edit', $project) }}">
                Configure New Prizes
            </flux:button>
        </div>
    @else
        <!-- No Prizes Display -->
        <div class="rounded-xl border border-gray-200/50 bg-gradient-to-br from-gray-50/80 to-gray-100/80 p-4 backdrop-blur-sm">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon name="gift" class="h-6 w-6 text-gray-600" />
                <flux:heading size="base" class="text-gray-700">No Prizes Set</flux:heading>
            </div>
            <flux:text size="sm" class="text-gray-600 mb-3">This contest doesn't have any prizes configured yet.</flux:text>
            <flux:button variant="outline" size="xs" icon="plus" href="{{ route('projects.edit', $project) }}">
                Add Prizes
            </flux:button>
        </div>
    @endif
</flux:card> 