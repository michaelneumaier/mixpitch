<div class="relative">
    <!-- Background Effects -->
    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-amber-50/30 via-yellow-50/20 to-orange-50/30"></div>
    <div class="absolute left-2 top-2 h-16 w-16 rounded-full bg-amber-400/10 blur-xl"></div>
    <div class="absolute bottom-2 right-2 h-12 w-12 rounded-full bg-yellow-400/10 blur-lg"></div>

    <!-- Content -->
    <div class="relative rounded-2xl border border-white/20 bg-white/95 p-6 shadow-xl backdrop-blur-md">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center">
                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-yellow-600">
                    <i class="fas fa-trophy text-lg text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h3 class="text-lg font-bold text-amber-800">Contest Prizes</h3>
                        <x-contest.payment-status-badge :project="$project" compact="true" />
                    </div>
                    <p class="text-sm text-amber-600">Rewards and incentives for winners</p>
                </div>
            </div>
            <a href="{{ route('projects.edit', $project) }}"
                class="inline-flex items-center rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-800 transition-colors hover:bg-amber-200">
                <i class="fas fa-edit mr-2"></i>
                Edit Prizes
            </a>
        </div>

        @if ($project->hasPrizes())
            <!-- New Contest Prize System -->
            <div class="rounded-xl border border-amber-200/50 bg-gradient-to-br from-amber-50/80 to-yellow-50/80 p-4 backdrop-blur-sm">
                <!-- Prize Summary Stats -->
                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-amber-200/30 bg-white/60 p-3 text-center backdrop-blur-sm">
                        <div class="text-lg font-bold text-amber-900">
                            ${{ number_format($project->getTotalPrizeBudget()) }}
                        </div>
                        <div class="text-xs text-amber-700">Total Cash Prizes</div>
                    </div>
                    <div class="rounded-lg border border-amber-200/30 bg-white/60 p-3 text-center backdrop-blur-sm">
                        <div class="text-lg font-bold text-amber-900">
                            ${{ number_format($project->getTotalPrizeValue()) }}
                        </div>
                        <div class="text-xs text-amber-700">Total Prize Value</div>
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
                        
                        <div class="flex items-center justify-between rounded-lg border border-amber-200/20 bg-white/40 p-3 backdrop-blur-sm">
                            <div class="flex items-center">
                                <span class="mr-3 text-lg">{{ $prize['emoji'] ?? '🏆' }}</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-amber-900">{{ $prize['placement'] ?? 'Prize' }}</span>
                                        @if($isCashPrize && $hasWinner)
                                            @if($isPaid)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Paid
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-clock mr-1"></i>Pending
                                                </span>
                                            @endif
                                        @elseif($isCashPrize && !$hasWinner && $project->isJudgingFinalized())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-user-slash mr-1"></i>No Winner
                                            </span>
                                        @endif
                                    </div>
                                    @if (isset($prize['title']) && $prize['title'])
                                        <div class="text-xs text-amber-700">{{ $prize['title'] }}</div>
                                    @endif
                                    @if($hasWinner)
                                        <div class="text-xs text-green-700 font-medium">
                                            <i class="fas fa-crown mr-1"></i>{{ $winnerStatus['user']->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-amber-900">
                                    {{ $prize['display_value'] ?? 'N/A' }}
                                </div>
                                @if($isPaid && $winnerStatus['payment_date'])
                                    <div class="text-xs text-green-600">
                                        Paid {{ $winnerStatus['payment_date']->format('M j') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($project->prize_amount && $project->prize_amount > 0)
            <!-- Legacy Prize Display (for backward compatibility) -->
            <div class="rounded-xl border border-green-200/50 bg-gradient-to-br from-green-50/80 to-emerald-50/80 p-4 backdrop-blur-sm">
                <div class="mb-3 flex items-center">
                    <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-green-500 to-emerald-600">
                        <i class="fas fa-dollar-sign text-sm text-white"></i>
                    </div>
                    <h4 class="font-bold text-green-800">Prize Amount (Legacy)</h4>
                </div>
                <p class="text-2xl font-bold text-green-900 mb-3">
                    {{ $project->prize_currency ?? '$' }}{{ number_format($project->prize_amount ?: 0, 2) }}
                </p>
                <div>
                    <a href="{{ route('projects.edit', $project) }}"
                        class="inline-flex items-center rounded-lg bg-green-100 px-3 py-1 text-xs font-medium text-green-800 transition-colors hover:bg-green-200">
                        <i class="fas fa-plus mr-1"></i>
                        Configure New Prizes
                    </a>
                </div>
            </div>
        @else
            <!-- No Prizes Display -->
            <div class="rounded-xl border border-gray-200/50 bg-gradient-to-br from-gray-50/80 to-gray-100/80 p-4 backdrop-blur-sm">
                <div class="mb-3 flex items-center">
                    <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-gray-400 to-gray-500">
                        <i class="fas fa-gift text-sm text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-700">No Prizes Set</h4>
                </div>
                <p class="mb-3 text-sm text-gray-600">This contest doesn't have any prizes configured yet.</p>
                <a href="{{ route('projects.edit', $project) }}"
                    class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200">
                    <i class="fas fa-plus mr-1"></i>
                    Add Prizes
                </a>
            </div>
        @endif
    </div>
</div> 