@props(['project', 'compact' => false, 'showAmount' => true])

@php
    $paymentStatus = $project->getContestPaymentStatus();
    
    // Determine badge styling based on payment status
    $badgeClasses = '';
    $icon = '';
    $text = '';
    
    switch ($paymentStatus['payment_status']) {
        case 'all_paid':
            $badgeClasses = 'bg-green-100 text-green-800 border-green-200';
            $icon = 'fas fa-check-circle';
            $text = $compact ? 'Paid' : 'All Prizes Paid';
            break;
            
        case 'partially_paid':
            $badgeClasses = 'bg-blue-100 text-blue-800 border-blue-200';
            $icon = 'fas fa-clock';
            $text = $compact ? 'Partial' : $paymentStatus['summary'];
            break;
            
        case 'none_paid':
            $badgeClasses = 'bg-yellow-100 text-yellow-800 border-yellow-200';
            $icon = 'fas fa-dollar-sign';
            $text = $compact ? 'Pending' : 'Payment Pending';
            break;
            
        case 'no_winners':
            $badgeClasses = 'bg-gray-100 text-gray-800 border-gray-200';
            $icon = 'fas fa-user-slash';
            $text = 'No Winners';
            break;
            
        case 'no_cash_prizes':
            $badgeClasses = 'bg-gray-100 text-gray-600 border-gray-200';
            $icon = 'fas fa-gift';
            $text = 'No Cash Prizes';
            break;
            
        default:
            $badgeClasses = 'bg-gray-100 text-gray-600 border-gray-200';
            $icon = 'fas fa-info-circle';
            $text = 'Not Applicable';
    }
@endphp

@if($paymentStatus['has_cash_prizes'] || $paymentStatus['payment_status'] === 'no_cash_prizes')
    <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $badgeClasses }}">
        <i class="{{ $icon }} mr-1.5"></i>
        <span>{{ $text }}</span>
        @if($showAmount && $paymentStatus['has_cash_prizes'] && !$compact)
            <span class="ml-1.5 font-semibold">
                (${{ number_format($paymentStatus['total_prize_amount'], 0) }})
            </span>
        @endif
    </div>
@endif 