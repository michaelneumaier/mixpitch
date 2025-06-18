@props(['project', 'showDetails' => true])

@php
    $paymentStatus = $project->getContestPaymentStatus();
@endphp

@if($paymentStatus['has_cash_prizes'])
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                Prize Payment Status
            </h4>
            <x-contest.payment-status-badge :project="$project" />
        </div>

        <!-- Payment Summary Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $paymentStatus['prizes_paid'] }}</div>
                <div class="text-sm text-green-700">Prizes Paid</div>
            </div>
            <div class="text-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="text-2xl font-bold text-yellow-600">{{ $paymentStatus['prizes_pending'] }}</div>
                <div class="text-sm text-yellow-700">Pending Payment</div>
            </div>
            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">${{ number_format($paymentStatus['total_prize_amount'], 0) }}</div>
                <div class="text-sm text-blue-700">Total Prize Pool</div>
            </div>
        </div>

        @if($showDetails && !empty($paymentStatus['winners_with_status']))
            <!-- Individual Winner Payment Status -->
            <div class="space-y-3">
                <h5 class="text-sm font-medium text-gray-700 mb-3">Individual Prize Status</h5>
                @foreach($paymentStatus['winners_with_status'] as $winner)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center space-x-3">
                            <img class="h-8 w-8 rounded-full object-cover border border-gray-200" 
                                 src="{{ $winner['user']['profile_photo_url'] }}" 
                                 alt="{{ $winner['user']['name'] }}">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $winner['prize']['getPlacementDisplayName']() }}
                                </div>
                                <div class="text-xs text-gray-600">
                                    {{ $winner['user']['name'] }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">
                                ${{ number_format($winner['payment_amount'], 2) }}
                            </div>
                            @if($winner['is_paid'])
                                <div class="text-xs text-green-600 font-medium">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Paid {{ $winner['payment_date']->format('M j') }}
                                </div>
                            @elseif($winner['stripe_ready'])
                                <div class="text-xs text-yellow-600 font-medium">
                                    <i class="fas fa-clock mr-1"></i>
                                    Ready for Payment
                                </div>
                            @else
                                <div class="text-xs text-red-600 font-medium">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Stripe Setup Needed
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Payment Actions -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                @if($paymentStatus['payment_status'] === 'all_paid')
                    <a href="{{ route('contest.prizes.receipt', $project) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-receipt mr-2"></i>
                        View Payment Receipt
                    </a>
                @elseif($paymentStatus['payment_status'] === 'partially_paid')
                    <a href="{{ route('contest.prizes.overview', $project) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-clock mr-2"></i>
                        Continue Prize Payments
                    </a>
                @elseif($paymentStatus['prizes_pending'] > 0)
                    <a href="{{ route('contest.prizes.overview', $project) }}" 
                       class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        Process Prize Payments
                    </a>
                @endif
            </div>
        @endif
    </div>
@elseif($paymentStatus['payment_status'] === 'no_cash_prizes')
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
        <div class="text-center">
            <i class="fas fa-gift text-gray-400 text-3xl mb-3"></i>
            <h4 class="text-lg font-medium text-gray-700 mb-2">No Cash Prizes</h4>
            <p class="text-sm text-gray-600">This contest uses non-monetary prizes only.</p>
        </div>
    </div>
@endif 