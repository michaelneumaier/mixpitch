<!-- Usage Analytics Dashboard -->
<div class="mb-8">
    <div class="bg-gradient-to-br from-white/90 to-purple-50/90 backdrop-blur-sm border border-white/50 rounded-2xl shadow-xl p-8">
        <div class="flex items-center mb-6">
            <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                <i class="fas fa-chart-line text-white"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Usage & Analytics</h3>
                <p class="text-gray-600">Track your plan usage and feature utilization</p>
            </div>
        </div>
        
        <!-- Plan Limits Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Projects Usage -->
            <div class="bg-gradient-to-br from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg mr-3">
                            <i class="fas fa-folder text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-blue-700">Projects</div>
                            <div class="text-xs text-blue-600">
                                {{ $usage['projects_count'] }} / {{ $limits && $limits->max_projects_owned ? $limits->max_projects_owned : '∞' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-900">{{ $usage['projects_count'] }}</div>
                    </div>
                </div>
                @if($limits && $limits->max_projects_owned)
                    @php $projectUsagePercent = min(($usage['projects_count'] / $limits->max_projects_owned) * 100, 100); @endphp
                    <div class="relative">
                        <div class="overflow-hidden h-2 text-xs flex rounded-full bg-blue-200/50">
                            <div style="width:{{ $projectUsagePercent }}%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $projectUsagePercent >= 90 ? 'bg-red-500' : ($projectUsagePercent >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }} transition-all duration-300"></div>
                        </div>
                        <div class="mt-2 text-xs text-blue-600">
                            {{ number_format($projectUsagePercent, 1) }}% used
                        </div>
                    </div>
                @else
                    <div class="text-xs text-green-600 font-medium flex items-center">
                        <i class="fas fa-infinity mr-1"></i>
                        Unlimited
                    </div>
                @endif
            </div>
            
            <!-- Active Pitches Usage -->
            <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-lg mr-3">
                            <i class="fas fa-paper-plane text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-green-700">Active Pitches</div>
                            <div class="text-xs text-green-600">
                                {{ $usage['active_pitches_count'] }} / {{ $limits && $limits->max_active_pitches ? $limits->max_active_pitches : '∞' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-green-900">{{ $usage['active_pitches_count'] }}</div>
                    </div>
                </div>
                @if($limits && $limits->max_active_pitches)
                    @php $pitchUsagePercent = min(($usage['active_pitches_count'] / $limits->max_active_pitches) * 100, 100); @endphp
                    <div class="relative">
                        <div class="overflow-hidden h-2 text-xs flex rounded-full bg-green-200/50">
                            <div style="width:{{ $pitchUsagePercent }}%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $pitchUsagePercent >= 90 ? 'bg-red-500' : ($pitchUsagePercent >= 70 ? 'bg-yellow-500' : 'bg-green-500') }} transition-all duration-300"></div>
                        </div>
                        <div class="mt-2 text-xs text-green-600">
                            {{ number_format($pitchUsagePercent, 1) }}% used
                        </div>
                    </div>
                @else
                    <div class="text-xs text-green-600 font-medium flex items-center">
                        <i class="fas fa-infinity mr-1"></i>
                        Unlimited
                    </div>
                @endif
            </div>
            
            <!-- Storage Information -->
            <div class="bg-gradient-to-br from-orange-50/80 to-red-50/80 backdrop-blur-sm border border-orange-200/50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-orange-100 rounded-lg mr-3">
                            <i class="fas fa-hdd text-orange-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-orange-700">Storage per Project</div>
                            <div class="text-xs text-orange-600">
                                {{ $user->getFileRetentionDays() }} days retention
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-orange-900">{{ $user->getStoragePerProjectGB() }}GB</div>
                    </div>
                </div>
                <div class="text-xs text-orange-600 font-medium flex items-center">
                    <i class="fas fa-check-circle mr-1"></i>
                    Per project allocation
                </div>
            </div>
        </div>
        
        <!-- Monthly Features Section -->
        @if($user->isProPlan())
        <div class="border-t border-gray-200/50 pt-8">
            <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-purple-600"></i>
                Monthly Features Usage
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Visibility Boosts -->
                @if($user->getMonthlyVisibilityBoosts() > 0)
                <div class="bg-gradient-to-br from-violet-50/80 to-purple-50/80 backdrop-blur-sm border border-violet-200/50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-rocket text-violet-600 mr-2"></i>
                            <div class="text-sm font-medium text-violet-700">Visibility Boosts</div>
                        </div>
                        <div class="text-lg font-bold text-violet-900">{{ $user->getRemainingVisibilityBoosts() }}</div>
                    </div>
                    <div class="text-xs text-violet-600">
                        {{ $user->getRemainingVisibilityBoosts() }} / {{ $user->getMonthlyVisibilityBoosts() }} remaining
                    </div>
                </div>
                @endif
                
                <!-- Private Projects -->
                @if($user->getMaxPrivateProjectsMonthly() !== 0)
                <div class="bg-gradient-to-br from-red-50/80 to-pink-50/80 backdrop-blur-sm border border-red-200/50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-lock text-red-600 mr-2"></i>
                            <div class="text-sm font-medium text-red-700">Private Projects</div>
                        </div>
                        <div class="text-lg font-bold text-red-900">
                            @if($user->getRemainingPrivateProjects() === null)
                                ∞
                            @else
                                {{ $user->getRemainingPrivateProjects() }}
                            @endif
                        </div>
                    </div>
                    <div class="text-xs text-red-600">
                        @if($user->getRemainingPrivateProjects() === null)
                            Unlimited this month
                        @else
                            {{ $user->getRemainingPrivateProjects() }} remaining this month
                        @endif
                    </div>
                </div>
                @endif
                
                <!-- License Templates -->
                <div class="bg-gradient-to-br from-emerald-50/80 to-green-50/80 backdrop-blur-sm border border-emerald-200/50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-file-contract text-emerald-600 mr-2"></i>
                            <div class="text-sm font-medium text-emerald-700">License Templates</div>
                        </div>
                        <div class="text-lg font-bold text-emerald-900">{{ $usage['license_templates_count'] }}</div>
                    </div>
                    <div class="text-xs text-emerald-600">
                        @if($user->getMaxLicenseTemplates() === null)
                            Unlimited custom templates
                        @else
                            {{ $usage['license_templates_count'] }} / {{ $user->getMaxLicenseTemplates() }} created
                        @endif
                    </div>
                </div>
                
                <!-- Commission Rate -->
                <div class="bg-gradient-to-br from-yellow-50/80 to-amber-50/80 backdrop-blur-sm border border-yellow-200/50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <i class="fas fa-percentage text-yellow-600 mr-2"></i>
                            <div class="text-sm font-medium text-yellow-700">Commission Rate</div>
                        </div>
                        <div class="text-lg font-bold text-yellow-900">{{ $billingSummary['commission_rate'] }}%</div>
                    </div>
                    <div class="text-xs text-green-600 font-medium">
                        @if($billingSummary['commission_savings'] > 0)
                            Saved: ${{ number_format($billingSummary['commission_savings'], 2) }}
                        @else
                            vs 10% on Free plan
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Cost Savings Summary -->
        @if($user->isProPlan())
        <div class="border-t border-gray-200/50 pt-8 mt-8">
            <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-piggy-bank mr-2 text-green-600"></i>
                Your Savings & Benefits
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Yearly Savings -->
                @if($billingSummary['yearly_savings'])
                <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-xl mb-3 mx-auto">
                        <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-green-900">${{ number_format($billingSummary['yearly_savings'], 2) }}</div>
                    <div class="text-sm text-green-700 font-medium">Yearly Savings</div>
                    <div class="text-xs text-green-600 mt-1">vs monthly billing</div>
                </div>
                @endif
                
                <!-- Commission Savings -->
                @if($billingSummary['commission_savings'] > 0)
                <div class="bg-gradient-to-br from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-3 mx-auto">
                        <i class="fas fa-hand-holding-usd text-blue-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-blue-900">${{ number_format($billingSummary['commission_savings'], 2) }}</div>
                    <div class="text-sm text-blue-700 font-medium">Commission Savings</div>
                    <div class="text-xs text-blue-600 mt-1">vs free plan</div>
                </div>
                @endif
                
                <!-- Total Earnings -->
                @if($billingSummary['total_earnings'] > 0)
                <div class="bg-gradient-to-br from-purple-50/80 to-indigo-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-xl mb-3 mx-auto">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-purple-900">${{ number_format($billingSummary['total_earnings'], 2) }}</div>
                    <div class="text-sm text-purple-700 font-medium">Total Earnings</div>
                    <div class="text-xs text-purple-600 mt-1">net after commission</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div> 