@props(['project', 'compact' => false, 'showAmount' => true])

@php
    $paymentStatus = $project->getContestPaymentStatus();
    
    // Determine badge styling based on payment status
    $badgeColor = '';
    $icon = '';
    $text = '';
    
    switch ($paymentStatus['payment_status']) {
        case 'all_paid':
            $badgeColor = 'green';
            $icon = 'check-circle';
            $text = $compact ? 'Paid' : 'All Prizes Paid';
            break;
            
        case 'partially_paid':
            $badgeColor = 'blue';
            $icon = 'clock';
            $text = $compact ? 'Partial' : $paymentStatus['summary'];
            break;
            
        case 'none_paid':
            $badgeColor = 'amber';
            $icon = 'banknotes';
            $text = $compact ? 'Pending' : 'Payment Pending';
            break;
            
        case 'no_winners':
            $badgeColor = 'zinc';
            $icon = 'user';
            $text = 'No Winners';
            break;
            
        case 'no_cash_prizes':
            $badgeColor = 'zinc';
            $icon = 'gift';
            $text = 'No Cash Prizes';
            break;
            
        default:
            $badgeColor = 'zinc';
            $icon = 'information-circle';
            $text = 'Not Applicable';
    }
@endphp

@if($paymentStatus['has_cash_prizes'] || $paymentStatus['payment_status'] === 'no_cash_prizes')
    <flux:badge color="{{ $badgeColor }}" size="xs" icon="{{ $icon }}">
        {{ $text }}
        @if($showAmount && $paymentStatus['has_cash_prizes'] && !$compact)
            (${{ number_format($paymentStatus['total_prize_amount'], 0) }})
        @endif
    </flux:badge>
@endif 