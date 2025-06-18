@props(['pitch', 'user' => null])

@php
    $user = $user ?? auth()->user();
    if (!$user || $pitch->user_id !== $user->id) return;
    
    // Get payouts for this specific pitch
    $payouts = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
        ->where('pitch_id', $pitch->id)
        ->with(['project', 'contestPrize'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($payouts->isEmpty()) return;
    
    $latestPayout = $payouts->first();
@endphp

@if($payouts->isNotEmpty())
<div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg shadow-sm">
                @if($latestPayout->status === 'completed')
                    <i class="fas fa-check text-white text-sm"></i>
                @elseif($latestPayout->status === 'scheduled')
                    <i class="fas fa-clock text-white text-sm"></i>
                @elseif($latestPayout->status === 'processing')
                    <i class="fas fa-spinner fa-spin text-white text-sm"></i>
                @elseif($latestPayout->status === 'failed')
                    <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                @endif
            </div>
            <div>
                <h4 class="text-sm font-semibold text-green-800">
                    @if($latestPayout->status === 'completed')
                        Payment Completed
                    @elseif($latestPayout->status === 'scheduled')
                        Payment Scheduled
                    @elseif($latestPayout->status === 'processing')
                        Payment Processing
                    @elseif($latestPayout->status === 'failed')
                        Payment Failed
                    @endif
                </h4>
                <p class="text-xs text-green-600">
                    @if($latestPayout->status === 'completed')
                        Paid {{ $latestPayout->completed_at?->format('M j, Y') }}
                    @elseif($latestPayout->status === 'scheduled')
                        Releases {{ $latestPayout->hold_release_date?->format('M j, Y') }}
                    @elseif($latestPayout->status === 'processing')
                        Being processed
                    @elseif($latestPayout->status === 'failed')
                        {{ $latestPayout->failure_reason ?? 'Contact support' }}
                    @endif
                </p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-lg font-bold text-green-800">
                ${{ number_format($latestPayout->net_amount, 2) }}
            </div>
            @if($latestPayout->workflow_type === 'contest' && $latestPayout->contestPrize)
                <div class="text-xs text-green-600">
                    {{ $latestPayout->contestPrize->placement }} Prize
                </div>
            @endif
        </div>
    </div>
    
    @if($latestPayout->status === 'scheduled')
        <div class="mt-3 text-xs text-blue-600 bg-blue-50 rounded p-2">
            <i class="fas fa-info-circle mr-1"></i>
            Your payment will be automatically released on {{ $latestPayout->hold_release_date?->format('M j, Y') }}
        </div>
    @endif
</div>
@endif 