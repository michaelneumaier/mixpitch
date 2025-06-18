@props(['project', 'showTitle' => true, 'compact' => false, 'badge' => false, 'context' => 'default'])

@php
    $prizes = $project->getPrizeSummary();
    $totalCashPrizes = $project->getTotalPrizeBudget();
    $totalPrizeValue = $project->getTotalPrizeValue();
    $hasPrizes = $project->hasPrizes();
@endphp

@if($hasPrizes)
    <div class="contest-prize-display {{ $compact ? 'compact' : '' }} {{ $badge ? 'badge' : '' }}">
        @if($badge)
            <!-- Badge Mode - Minimal display for cards -->
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                <i class="fas fa-trophy mr-1"></i>
                @if($totalCashPrizes > 0)
                    ${{ number_format($totalCashPrizes) }}+
                @else
                    {{ count($prizes) }} Prize{{ count($prizes) > 1 ? 's' : '' }}
                @endif
            </div>
        @elseif($compact)
            <!-- Compact Display Version -->
            <div class="contest-prize-compact bg-gradient-to-r from-amber-50/80 to-yellow-50/80 rounded-xl p-4 border border-amber-200/50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                        <i class="fas fa-trophy text-white"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-amber-800">Contest Prizes</div>
                        <div class="text-sm text-amber-700">
                            {{ count($prizes) }} tiers • ${{ number_format($totalCashPrizes) }} cash • ${{ number_format($totalPrizeValue) }} total value
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl">🏆</div>
                        <div class="text-xs text-amber-600 font-medium">{{ count($prizes) }} prizes</div>
                    </div>
                </div>

                <!-- Quick Prize List -->
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($prizes as $placement => $prize)
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $placement === '1st' ? 'bg-yellow-100 text-yellow-800' : ($placement === '2nd' ? 'bg-gray-100 text-gray-800' : ($placement === '3rd' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800')) }}">
                            {{ $prize['emoji'] }} {{ $prize['title'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @else
            <!-- Full Display Version -->
            <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
                <!-- Gradient Border Effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-amber-500/20 via-yellow-500/20 to-orange-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl m-0.5 p-8">
                    
                    @if($showTitle)
                        <!-- Header with Icon -->
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                <i class="fas fa-trophy text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                    Contest Prizes
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">Compete for amazing rewards</p>
                            </div>
                        </div>
                    @endif

                    <!-- Prize Summary -->
                    <div class="mb-8 p-6 bg-gradient-to-r from-amber-50/80 to-yellow-50/80 rounded-xl border border-amber-200/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                            <div>
                                <div class="text-3xl font-bold text-amber-600 mb-2">{{ count($prizes) }}</div>
                                <div class="text-sm font-medium text-amber-800">Prize Tiers</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-green-600 mb-2">${{ number_format($totalCashPrizes) }}</div>
                                <div class="text-sm font-medium text-green-800">Cash Prizes</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-purple-600 mb-2">${{ number_format($totalPrizeValue) }}</div>
                                <div class="text-sm font-medium text-purple-800">Total Value</div>
                            </div>
                        </div>
                    </div>

                    <!-- Prize Details -->
                    <div class="space-y-4">
                        @foreach($prizes as $placement => $prize)
                            <div class="group/prize relative bg-gradient-to-r {{ $placement === '1st' ? 'from-yellow-50/70 to-amber-50/70 border-yellow-300/50' : ($placement === '2nd' ? 'from-gray-50/70 to-slate-50/70 border-gray-300/50' : ($placement === '3rd' ? 'from-orange-50/70 to-amber-50/70 border-orange-300/50' : 'from-blue-50/70 to-indigo-50/70 border-blue-300/50')) }} rounded-xl p-6 border hover:scale-105 transition-all duration-200 hover:shadow-lg">
                                <div class="flex items-center">
                                    <!-- Prize Icon -->
                                    <div class="w-16 h-16 {{ $placement === '1st' ? 'bg-gradient-to-br from-yellow-400 to-amber-500' : ($placement === '2nd' ? 'bg-gradient-to-br from-gray-400 to-slate-500' : ($placement === '3rd' ? 'bg-gradient-to-br from-orange-400 to-amber-500' : 'bg-gradient-to-br from-blue-400 to-indigo-500')) }} rounded-xl flex items-center justify-center mr-6 shadow-lg group-hover/prize:scale-110 transition-transform duration-200">
                                        <span class="text-2xl">{{ $prize['emoji'] }}</span>
                                    </div>

                                    <!-- Prize Info -->
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold {{ $placement === '1st' ? 'text-yellow-800' : ($placement === '2nd' ? 'text-gray-800' : ($placement === '3rd' ? 'text-orange-800' : 'text-blue-800')) }} mb-2">
                                            {{ $prize['title'] }}
                                        </h4>
                                        <div class="text-lg font-semibold {{ $placement === '1st' ? 'text-yellow-700' : ($placement === '2nd' ? 'text-gray-700' : ($placement === '3rd' ? 'text-orange-700' : 'text-blue-700')) }}">
                                            {{ $prize['display_value'] }}
                                        </div>
                                        
                                        @if($prize['type'] === 'cash' && $prize['cash_value'] > 0)
                                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-dollar-sign mr-1"></i>
                                                Cash Prize
                                            </div>
                                        @elseif($prize['type'] === 'other')
                                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                                <i class="fas fa-gift mr-1"></i>
                                                Prize Item
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Prize Value Badge -->
                                    @if($prize['estimated_value'] > 0)
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">Estimated Value</div>
                                            <div class="text-lg font-bold text-gray-800">${{ number_format($prize['estimated_value']) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Contest Encouragement - Only show if contest is still accepting submissions AND not in judging context -->
                    @if($context !== 'judging' && !$project->isJudgingFinalized() && (!$project->submission_deadline || !$project->submission_deadline->isPast()))
                        <div class="mt-8 p-6 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-xl border border-blue-200/50 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <i class="fas fa-star text-white text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-blue-800 mb-2">Ready to Compete?</h4>
                            <p class="text-blue-700 leading-relaxed">
                                Submit your best work and compete for these amazing prizes. Show off your skills and win big!
                            </p>
                        </div>
                    @elseif($project->isJudgingFinalized())
                        <!-- Contest Completed Message -->
                        <div class="mt-8 p-6 bg-gradient-to-r from-green-50/80 to-emerald-50/80 rounded-xl border border-green-200/50 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <i class="fas fa-trophy text-white text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-green-800 mb-2">Contest Complete!</h4>
                            <p class="text-green-700 leading-relaxed">
                                This contest has concluded and results have been finalized. Check out the amazing winning entries!
                            </p>
                        </div>
                    @elseif($project->submission_deadline && $project->submission_deadline->isPast())
                        <!-- Submissions Closed Message -->
                        <div class="mt-8 p-6 bg-gradient-to-r from-amber-50/80 to-yellow-50/80 rounded-xl border border-amber-200/50 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-amber-800 mb-2">Submissions Closed</h4>
                            <p class="text-amber-700 leading-relaxed">
                                The submission deadline has passed. Contest entries are now being judged.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif 