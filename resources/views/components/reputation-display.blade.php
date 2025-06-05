@props(['user', 'size' => 'md', 'showBreakdown' => false, 'detailed' => false])

@php
    $reputation = $user->getReputation();
    $reputationTier = $user->getReputationTier();
    $reputationData = $showBreakdown ? $user->getReputationWithMultiplier() : null;
    
    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'text-sm px-2 py-1',
        'lg' => 'text-lg px-4 py-3',
        default => 'text-base px-3 py-2'
    };
    
    $badgeSize = match($size) {
        'sm' => 'text-sm',
        'lg' => 'text-xl',
        default => 'text-lg'
    };
@endphp

<div class="reputation-display">
    @if($detailed)
        <!-- Detailed View -->
        <div class="bg-white rounded-lg border shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <span class="{{ $badgeSize }}">{{ $reputationTier['badge'] }}</span>
                    <div>
                        <h4 class="font-semibold {{ $reputationTier['color'] }} capitalize">
                            {{ $reputationTier['tier'] }}
                        </h4>
                        <p class="text-sm text-gray-600">{{ number_format($reputation, 1) }} reputation</p>
                    </div>
                </div>
                
                @if($user->getReputationMultiplier() > 1.0)
                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                        <i class="fas fa-rocket mr-1"></i>
                        {{ $user->getReputationMultiplier() }}× boost
                    </span>
                @endif
            </div>
            
            @if($showBreakdown && $reputationData)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Base reputation:</span>
                        <span class="font-medium">{{ number_format($reputationData['base_reputation'], 1) }}</span>
                    </div>
                    @if($reputationData['subscription_benefit'])
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subscription bonus:</span>
                            <span class="font-medium text-blue-600">+{{ number_format($reputationData['multiplier_bonus'], 1) }}</span>
                        </div>
                    @endif
                    <div class="border-t pt-2 flex justify-between font-semibold">
                        <span>Total reputation:</span>
                        <span class="{{ $reputationTier['color'] }}">{{ number_format($reputation, 1) }}</span>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- Compact View -->
        <div class="inline-flex items-center space-x-2">
            <span class="inline-flex items-center {{ $sizeClasses }} rounded-full font-medium bg-gradient-to-r from-yellow-100 to-orange-100 text-orange-800 border border-orange-200"
                  title="Reputation: {{ number_format($reputation, 1) }} ({{ ucfirst($reputationTier['tier']) }})">
                <span class="mr-1">{{ $reputationTier['badge'] }}</span>
                <span class="font-semibold">{{ number_format($reputation, 1) }}</span>
            </span>
            
            @if($user->getReputationMultiplier() > 1.0 && $size !== 'sm')
                <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full"
                      title="Subscription boost active">
                    <i class="fas fa-rocket"></i>
                </span>
            @endif
        </div>
    @endif
</div> 