<x-layouts.app-sidebar>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Enhanced Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-4 rounded-full">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Payout Dashboard</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Track your earnings, monitor payout status, and manage your payment settings.
            </p>
        </div>

        <!-- Quick Action Bar -->
        <div class="mb-8">
            <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-3 rounded-xl">
                            <i class="fas fa-wallet text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Total Lifetime Earnings</h3>
                            <p class="text-3xl font-bold text-green-600">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('stripe.connect.setup') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Payment Settings
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Earnings</dt>
                                <dd class="text-2xl font-bold text-gray-900">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</dd>
                                <dd class="text-xs text-green-600 font-medium">Net amount received</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed Payouts</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ $statistics['completed_payouts'] ?? 0 }}</dd>
                                <dd class="text-xs text-blue-600 font-medium">Successfully processed</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Payouts</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ $statistics['pending_payouts'] ?? 0 }}</dd>
                                <dd class="text-xs text-yellow-600 font-medium">Being processed</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Commission Rate</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ auth()->user()->getPlatformCommissionRate() }}%</dd>
                                <dd class="text-xs text-purple-600 font-medium">Current rate</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-8">
            <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Filter Payouts</h3>
                <form method="GET" action="{{ route('payouts.index') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
                    <div class="flex-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Statuses</option>
                            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="workflow_type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select name="workflow_type" id="workflow_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">All Types</option>
                            <option value="contest" {{ request('workflow_type') === 'contest' ? 'selected' : '' }}>Contest Prizes</option>
                            <option value="standard" {{ request('workflow_type') === 'standard' ? 'selected' : '' }}>Standard Projects</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-search mr-2"></i>
                            Filter
                        </button>
                        <a href="{{ route('payouts.index') }}" class="inline-flex items-center px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payout History -->
        <div class="bg-white shadow-xl overflow-hidden rounded-2xl border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                <h3 class="text-lg font-semibold text-gray-900">Payout History</h3>
                <p class="text-sm text-gray-600 mt-1">Complete record of all your payouts and their status</p>
            </div>
            
            @if($payouts->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($payouts as $payout)
                        <div class="px-6 py-6 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center space-x-4">
                                            <!-- Project/Contest Icon -->
                                            <div class="flex-shrink-0">
                                                @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                                                        <i class="fas fa-trophy text-white"></i>
                                                    </div>
                                                @else
                                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg">
                                                        <i class="fas fa-folder text-white"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Payout Details -->
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-lg font-semibold text-gray-900 truncate">
                                                    @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                                        Contest Prize: {{ $payout->contestPrize->placement }} Place
                                                    @else
                                                        Project: {{ $payout->project->name ?? 'Unknown Project' }}
                                                    @endif
                                                </h4>
                                                <div class="flex items-center space-x-4 text-sm text-gray-500 mt-1">
                                                    <span class="flex items-center">
                                                        <i class="fas fa-music mr-1"></i>
                                                        {{ $payout->pitch->title ?? 'Unknown Pitch' }}
                                                    </span>
                                                    <span class="flex items-center">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        {{ $payout->created_at->format('M j, Y') }}
                                                    </span>
                                                    @if($payout->status === 'scheduled')
                                                        <span class="flex items-center text-blue-600">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            Releases {{ $payout->hold_release_date->format('M j, Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                            @if($payout->status === 'completed') bg-green-100 text-green-800
                                            @elseif($payout->status === 'scheduled') bg-blue-100 text-blue-800
                                            @elseif($payout->status === 'processing') bg-yellow-100 text-yellow-800
                                            @elseif($payout->status === 'failed') bg-red-100 text-red-800
                                            @elseif($payout->status === 'cancelled') bg-gray-100 text-gray-800
                                            @elseif($payout->status === 'reversed') bg-purple-100 text-purple-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            @if($payout->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($payout->status === 'scheduled')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($payout->status === 'processing')
                                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                            @elseif($payout->status === 'failed')
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                            @elseif($payout->status === 'cancelled')
                                                <i class="fas fa-ban mr-1"></i>
                                            @endif
                                            {{ ucfirst($payout->status) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Amount and Commission Details -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-6 text-sm">
                                            <div>
                                                <span class="text-gray-500">Net Amount:</span>
                                                <span class="font-semibold text-gray-900 ml-1">${{ number_format($payout->net_amount, 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Gross:</span>
                                                <span class="text-gray-700 ml-1">${{ number_format($payout->gross_amount, 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Commission:</span>
                                                <span class="text-gray-700 ml-1">{{ $payout->commission_rate }}%</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-2">
                                            @if($payout->status === 'completed' && $payout->stripe_transfer_id)
                                                <a href="{{ route('payouts.show', $payout) }}" 
                                                   class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-lg transition-colors duration-200">
                                                    <i class="fas fa-receipt mr-1"></i>
                                                    View Details
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($payout->status === 'failed' && $payout->failure_reason)
                                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <div class="flex items-center">
                                                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                                <div>
                                                    <strong class="text-red-800 text-sm">Failure reason:</strong>
                                                    <span class="text-red-700 text-sm ml-1">{{ $payout->failure_reason }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($payout->stripe_transfer_id)
                                        <div class="mt-2 text-xs text-gray-500">
                                            <i class="fas fa-external-link-alt mr-1"></i>
                                            Transfer ID: {{ $payout->stripe_transfer_id }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{ $payouts->appends(request()->query())->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No payouts found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['status', 'workflow_type', 'date_from']))
                            Try adjusting your filters or 
                            <a href="{{ route('payouts.index') }}" class="text-indigo-600 hover:text-indigo-500 font-medium">clear all filters</a>.
                        @else
                            Start submitting winning pitches to see your payouts here.
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Information Section -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-2xl p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-blue-800">How Payouts Work</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Automatic Processing:</strong> Payouts are automatically scheduled when projects are completed or contest prizes are awarded</li>
                            <li><strong>Hold Period:</strong> {{ ucfirst(app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('standard')['description']) }} for fraud protection</li>
                            <li><strong>Direct Transfer:</strong> Funds are transferred directly to your connected Stripe account</li>
                            <li><strong>Commission:</strong> Platform commission is deducted from gross amount before payout</li>
                            <li><strong>Notifications:</strong> You'll receive email and in-app notifications for all payout status changes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refund Requests Section -->
        @if($refundRequests->count() > 0)
        <div class="mt-8 bg-white shadow-xl overflow-hidden rounded-2xl border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-pink-50">
                <h3 class="text-lg font-semibold text-gray-900">Recent Refund Requests</h3>
                <p class="text-sm text-gray-600 mt-1">Client refund requests that may affect your payouts</p>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($refundRequests as $request)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">{{ $request->pitch->project->name ?? 'Unknown Project' }}</h4>
                            <p class="text-sm text-gray-500">{{ $request->created_at->format('M j, Y') }} • {{ ucfirst($request->status) }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Refund Request
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</x-layouts.app-sidebar> 