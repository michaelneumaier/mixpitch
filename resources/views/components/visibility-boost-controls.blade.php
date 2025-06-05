@props(['user', 'project' => null, 'pitch' => null, 'type' => 'project'])

@php
    $remaining = $user->getRemainingVisibilityBoosts();
    $monthlyLimit = $user->getMonthlyVisibilityBoosts();
    $activeBoosts = $user->activeVisibilityBoosts();
    $canCreateBoost = $user->canCreateVisibilityBoost();
    
    // Get target-specific boosts
    $targetBoosts = collect();
    if ($project) {
        $targetBoosts = $project->visibilityBoosts()->active()->get();
    } elseif ($pitch) {
        $targetBoosts = $pitch->visibilityBoosts()->active()->get();
    }
@endphp

<div class="space-y-6">
    <!-- Current Boost Status -->
    @if($targetBoosts->isNotEmpty())
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class="fas fa-rocket text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-blue-900">🚀 Visibility Boost Active!</h4>
                    @foreach($targetBoosts as $boost)
                        <div class="mt-2 text-sm text-blue-800">
                            <p><strong>{{ ucfirst($boost->boost_type) }} boost</strong> is active until {{ $boost->expires_at->format('M j, Y g:i A') }}</p>
                            <p class="text-blue-700 mt-1">
                                {{ $boost->ranking_multiplier }}× ranking multiplier • 
                                {{ $boost->expires_at->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Monthly Usage Overview -->
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-gray-900 flex items-center">
                <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                Monthly Visibility Boosts
            </h4>
            <span class="text-sm text-gray-600">{{ now()->format('F Y') }}</span>
        </div>
        
        @if($monthlyLimit > 0)
            <div class="space-y-3">
                <!-- Usage Bar -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Used this month</span>
                        <span class="font-medium">{{ $monthlyLimit - $remaining }} / {{ $monthlyLimit }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-300"
                             style="width: {{ $monthlyLimit > 0 ? (($monthlyLimit - $remaining) / $monthlyLimit) * 100 : 0 }}%"></div>
                    </div>
                </div>
                
                <!-- Remaining Count -->
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Remaining boosts:</span>
                    <span class="font-semibold text-lg {{ $remaining > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $remaining }}
                    </span>
                </div>
            </div>
        @else
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-lock text-3xl mb-3"></i>
                <p class="font-medium">Visibility boosts not available</p>
                <p class="text-sm">Upgrade to Pro to access visibility boosts</p>
            </div>
        @endif
    </div>

    <!-- Create Boost Section -->
    @if($monthlyLimit > 0)
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-plus text-green-500 mr-2"></i>
                Create Visibility Boost
            </h4>
            
            @if($canCreateBoost && $remaining > 0)
                <form x-data="{ selectedType: 'project', duration: '72' }" class="space-y-4">
                    <!-- Boost Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Boost Type
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                                   :class="{ 'border-blue-500 bg-blue-50': selectedType === 'project' }">
                                <input type="radio" 
                                       x-model="selectedType" 
                                       value="project"
                                       class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="font-medium text-gray-900">Project Boost</span>
                                    <p class="text-sm text-gray-600">Boost project visibility</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                                   :class="{ 'border-blue-500 bg-blue-50': selectedType === 'pitch' }">
                                <input type="radio" 
                                       x-model="selectedType" 
                                       value="pitch"
                                       class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="font-medium text-gray-900">Pitch Boost</span>
                                    <p class="text-sm text-gray-600">Boost pitch visibility</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center space-x-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                                   :class="{ 'border-blue-500 bg-blue-50': selectedType === 'profile' }">
                                <input type="radio" 
                                       x-model="selectedType" 
                                       value="profile"
                                       class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="font-medium text-gray-900">Profile Boost</span>
                                    <p class="text-sm text-gray-600">Boost profile visibility</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Duration Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Boost Duration
                        </label>
                        <select x-model="duration" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="24">24 hours</option>
                            <option value="48">48 hours</option>
                            <option value="72">72 hours (Recommended)</option>
                        </select>
                    </div>

                    <!-- Target Selection (if applicable) -->
                    <div x-show="selectedType !== 'profile'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select <span x-text="selectedType"></span> to boost
                        </label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a <span x-text="selectedType"></span>...</option>
                            <!-- This would be populated dynamically based on user's projects/pitches -->
                        </select>
                    </div>

                    <!-- Boost Preview -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                            <div>
                                <h5 class="font-medium text-yellow-800">Boost Effects</h5>
                                <ul class="text-sm text-yellow-700 mt-1 space-y-1">
                                    <li>🚀 <strong>Higher ranking</strong> in search results and listings</li>
                                    <li>⭐ <strong>Featured placement</strong> in relevant categories</li>
                                    <li>📈 <strong>Increased visibility</strong> to potential collaborators</li>
                                    <li>⏰ <strong>Duration:</strong> <span x-text="duration"></span> hours</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button type="button" 
                                wire:click="createVisibilityBoost"
                                class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 px-4 rounded-lg font-medium transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                            <i class="fas fa-rocket mr-2"></i>
                            Activate Boost ({{ $remaining }} remaining)
                        </button>
                    </div>
                </form>
            @elseif($remaining <= 0)
                <div class="text-center py-6">
                    <i class="fas fa-calendar-times text-gray-400 text-3xl mb-3"></i>
                    <p class="font-medium text-gray-600">No boosts remaining this month</p>
                    <p class="text-sm text-gray-500 mb-4">Your monthly boosts will reset on {{ now()->addMonth()->startOfMonth()->format('M j, Y') }}</p>
                    
                    @if(!$user->isProPlan() || $user->subscription_tier !== 'engineer')
                        <a href="{{ route('subscription.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-arrow-up mr-2"></i>
                            Upgrade for More Boosts
                        </a>
                    @endif
                </div>
            @else
                <div class="text-center py-6">
                    <i class="fas fa-clock text-gray-400 text-3xl mb-3"></i>
                    <p class="font-medium text-gray-600">Boost currently unavailable</p>
                    <p class="text-sm text-gray-500">Please try again later</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Active Boosts Summary -->
    @if($activeBoosts->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-list text-gray-500 mr-2"></i>
                Your Active Boosts
            </h4>
            
            <div class="space-y-3">
                @foreach($activeBoosts as $boost)
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-200">
                        <div class="flex items-center space-x-3">
                            <div class="bg-blue-100 p-2 rounded-lg">
                                <i class="fas fa-{{ $boost->boost_type === 'profile' ? 'user' : ($boost->boost_type === 'project' ? 'folder' : 'paper-plane') }} text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-blue-900">{{ ucfirst($boost->boost_type) }} Boost</p>
                                <p class="text-sm text-blue-700">
                                    {{ $boost->ranking_multiplier }}× multiplier • 
                                    {{ $boost->expires_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                <i class="fas fa-check mr-1"></i>
                                Active
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Upgrade Prompt -->
    @if($monthlyLimit === 0)
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-rocket text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-lg mb-2">Unlock Visibility Boosts</h4>
                    <p class="text-blue-100 mb-4">
                        Get more exposure for your work with powerful visibility boosts. 
                        Pro Artists get 4 boosts per month, Pro Engineers get 1 boost per month.
                    </p>
                    <a href="{{ route('subscription.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-arrow-up mr-2"></i>
                        Upgrade to Pro
                    </a>
                </div>
            </div>
        </div>
    @endif
</div> 