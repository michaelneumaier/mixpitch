@props(['user', 'showReputation' => false, 'showPlan' => true, 'size' => 'md'])

@php
    $badge = $user->getUserBadge();
    $planName = ucfirst($user->subscription_plan);
    if ($user->subscription_tier !== 'basic') {
        $planName .= ' ' . ucfirst($user->subscription_tier);
    }
    
    $reputationTier = $showReputation ? $user->getReputationTier() : null;
    $reputation = $showReputation ? $user->getReputation() : null;
    
    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-1 text-xs',
        'lg' => 'px-4 py-2 text-base',
        default => 'px-3 py-1.5 text-sm'
    };
@endphp

<div class="inline-flex items-center space-x-2">
    @if($showPlan)
        @if($badge)
        <span class="user-badge inline-flex items-center {{ $sizeClasses }} rounded-full font-medium 
            {{ $user->isProPlan() ? 'bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 border border-blue-200' : 'bg-gray-100 text-gray-800 border border-gray-200' }}">
            <span class="mr-1">{{ $badge }}</span>
            <span class="font-semibold">{{ $planName }}</span>
        </span>
        @elseif($user->isProPlan())
        <span class="user-badge inline-flex items-center {{ $sizeClasses }} rounded-full font-medium bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 border border-blue-200">
            <i class="fas fa-crown mr-1.5 text-blue-600"></i>
            <span class="font-semibold">{{ $planName }}</span>
        </span>
        @else
        <span class="user-badge inline-flex items-center {{ $sizeClasses }} rounded-full font-medium bg-gray-100 text-gray-700 border border-gray-200">
            <i class="fas fa-user mr-1.5 text-gray-500"></i>
            <span class="font-medium">{{ $planName }}</span>
        </span>
        @endif
    @endif

    @if($showReputation && $reputationTier)
        <span class="reputation-tier inline-flex items-center {{ $sizeClasses }} rounded-full font-medium bg-gradient-to-r from-yellow-100 to-orange-100 text-orange-800 border border-orange-200"
              title="Reputation: {{ number_format($reputation, 1) }}">
            <span class="mr-1">{{ $reputationTier['badge'] }}</span>
            <span class="font-semibold capitalize">{{ $reputationTier['tier'] }}</span>
        </span>
    @endif
</div>