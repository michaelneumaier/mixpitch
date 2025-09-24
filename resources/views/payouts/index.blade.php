<x-layouts.app-sidebar>

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Compact Dashboard Header -->
            <flux:card class="mb-2 bg-white/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-purple-800 to-indigo-800 dark:from-gray-100 dark:via-purple-300 dark:to-indigo-300 bg-clip-text text-transparent">
                        Payout Dashboard
                    </flux:heading>
                    
                    <div class="flex items-center gap-2">
                        <flux:button href="{{ route('payouts.setup.index') }}" icon="cog-6-tooth" variant="outline" size="xs">
                            Settings
                        </flux:button>
                        <flux:button href="{{ route('dashboard') }}" icon="arrow-left" variant="ghost" size="xs">
                            Dashboard
                        </flux:button>
                    </div>
                </div>
                
                <flux:subheading class="text-slate-600 dark:text-slate-400">
                    Track your earnings, monitor payout status, and manage your payment settings.
                </flux:subheading>
            </flux:card>

            <!-- Quick Summary Card -->
            <flux:card class="mb-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg shadow-md">
                            <flux:icon name="currency-dollar" class="text-white" size="lg" />
                        </div>
                        <div>
                            <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Total Lifetime Earnings</flux:heading>
                            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg shadow-sm">
                            <flux:icon name="currency-dollar" class="text-white" size="sm" />
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Earnings</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">${{ number_format($statistics['total_earnings'] ?? 0, 2) }}</div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-400">Net amount received</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg shadow-sm">
                            <flux:icon name="check-circle" class="text-white" size="sm" />
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Completed Payouts</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $statistics['completed_payouts'] ?? 0 }}</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400">Successfully processed</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg shadow-sm">
                            <flux:icon name="clock" class="text-white" size="sm" />
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Pending Payouts</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $statistics['pending_payouts'] ?? 0 }}</div>
                            <div class="text-xs text-amber-600 dark:text-amber-400">Being processed</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg shadow-sm">
                            <flux:icon name="chart-bar" class="text-white" size="sm" />
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Commission Rate</div>
                            <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->getPlatformCommissionRate() }}%</div>
                            <div class="text-xs text-purple-600 dark:text-purple-400">Current rate</div>
                        </div>
                    </div>
                </flux:card>
            </div>

            <!-- Filters -->
            <flux:card class="mb-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-gradient-to-r from-slate-500 to-gray-600 rounded-lg shadow-sm">
                        <flux:icon name="funnel" class="text-white" size="sm" />
                    </div>
                    <flux:heading size="lg">Filter Payouts</flux:heading>
                </div>
                
                <form method="GET" action="{{ route('payouts.index') }}" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:gap-4">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                <option value="">All Statuses</option>
                                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </flux:field>
                    </div>
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Type</flux:label>
                            <select name="workflow_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                <option value="">All Types</option>
                                <option value="contest" {{ request('workflow_type') === 'contest' ? 'selected' : '' }}>Contest Prizes</option>
                                <option value="standard" {{ request('workflow_type') === 'standard' ? 'selected' : '' }}>Standard Projects</option>
                            </select>
                        </flux:field>
                    </div>
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>From Date</flux:label>
                            <flux:input type="date" name="date_from" value="{{ request('date_from') }}" />
                        </flux:field>
                    </div>
                    <div class="flex gap-2">
                        <flux:button type="submit" icon="magnifying-glass" variant="primary">
                            Filter
                        </flux:button>
                        <flux:button href="{{ route('payouts.index') }}" icon="x-mark" variant="ghost">
                            Clear
                        </flux:button>
                    </div>
                </form>
            </flux:card>

            <!-- Payout History -->
            <flux:card>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-md">
                        <flux:icon name="clock" class="text-white" size="lg" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Payout History</flux:heading>
                        <flux:subheading class="text-slate-600 dark:text-slate-400">Complete record of all your payouts and their status</flux:subheading>
                    </div>
                </div>
                
                @if($payouts->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Project</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Amount</flux:table.column>
                            <flux:table.column class="hidden md:table-cell">Commission</flux:table.column>
                            <flux:table.column class="hidden lg:table-cell">Date</flux:table.column>
                            <flux:table.column>Actions</flux:table.column>
                        </flux:table.columns>
                        
                        <flux:table.rows>
                            @foreach($payouts as $payout)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0">
                                                @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                                    <div class="p-2 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg shadow-sm">
                                                        <flux:icon name="trophy" class="text-white" size="sm" />
                                                    </div>
                                                @else
                                                    <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg shadow-sm">
                                                        <flux:icon name="folder" class="text-white" size="sm" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-slate-900 dark:text-slate-100 truncate">
                                                    @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                                        Contest Prize: {{ $payout->contestPrize->placement }} Place
                                                    @else
                                                        {{ $payout->project->name ?? 'Unknown Project' }}
                                                    @endif
                                                </div>
                                                <div class="text-sm text-slate-600 dark:text-slate-400 truncate">
                                                    {{ $payout->pitch->title ?? 'Unknown Pitch' }}
                                                </div>
                                                @if($payout->status === 'scheduled')
                                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                        Releases {{ $payout->hold_release_date->format('M j, Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge 
                                            :color="match($payout->status) {
                                                'completed' => 'green',
                                                'scheduled' => 'blue', 
                                                'processing' => 'amber',
                                                'failed' => 'red',
                                                'cancelled' => 'gray',
                                                'reversed' => 'purple',
                                                default => 'gray'
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $payout->status)) }}
                                        </flux:badge>
                                        
                                        @if($payout->status === 'failed' && $payout->failure_reason)
                                            <div class="mt-2">
                                                <flux:callout variant="danger" size="sm">
                                                    {{ $payout->failure_reason }}
                                                </flux:callout>
                                            </div>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div>
                                            <div class="font-semibold text-slate-900 dark:text-slate-100">${{ number_format($payout->net_amount, 2) }}</div>
                                            <div class="text-sm text-slate-600 dark:text-slate-400">Net amount</div>
                                            <div class="text-xs text-slate-500 dark:text-slate-500">Gross: ${{ number_format($payout->gross_amount, 2) }}</div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden md:table-cell">
                                        <div class="text-sm text-slate-700 dark:text-slate-300">{{ $payout->commission_rate }}%</div>
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden lg:table-cell">
                                        <div class="text-sm text-slate-700 dark:text-slate-300">{{ $payout->created_at->format('M j, Y') }}</div>
                                        @if($payout->stripe_transfer_id)
                                            <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">ID: {{ Str::limit($payout->stripe_transfer_id, 12) }}</div>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($payout->status === 'completed' && $payout->stripe_transfer_id)
                                            <flux:button href="{{ route('payouts.show', $payout) }}" icon="eye" variant="outline" size="xs">
                                                View
                                            </flux:button>
                                        @else
                                            <span class="text-sm text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                
                    <!-- Pagination -->
                    <flux:separator class="my-4" />
                    <div class="flex justify-center">
                        {{ $payouts->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mb-4">
                            <flux:icon name="document-text" class="mx-auto text-slate-400 dark:text-slate-500" size="2xl" />
                        </div>
                        <flux:heading size="lg" class="mb-2">No payouts found</flux:heading>
                        <flux:text class="text-slate-600 dark:text-slate-400 mb-6">
                            @if(request()->hasAny(['status', 'workflow_type', 'date_from']))
                                Try adjusting your filters or clear all filters to see all payouts.
                            @else
                                Start submitting winning pitches to see your payouts here.
                            @endif
                        </flux:text>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            @if(request()->hasAny(['status', 'workflow_type', 'date_from']))
                                <flux:button href="{{ route('payouts.index') }}" icon="arrow-path" variant="primary">
                                    Clear Filters
                                </flux:button>
                            @endif
                            <flux:button href="{{ route('dashboard') }}" icon="arrow-left" variant="outline">
                                Back to Dashboard
                            </flux:button>
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Information Section -->
            <flux:callout icon="information-circle" color="blue" class="mt-4">
                <flux:callout.heading>How Payouts Work</flux:callout.heading>
                <flux:callout.text>
                    <ul class="list-disc pl-5 space-y-2 mt-3">
                        <li><strong>Automatic Processing:</strong> Payouts are automatically scheduled when projects are completed or contest prizes are awarded</li>
                        <li><strong>Hold Period:</strong> {{ ucfirst(app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('standard')['description']) }} for fraud protection</li>
                        <li><strong>Direct Transfer:</strong> Funds are transferred directly to your connected Stripe account</li>
                        <li><strong>Commission:</strong> Platform commission is deducted from gross amount before payout</li>
                        <li><strong>Notifications:</strong> You'll receive email and in-app notifications for all payout status changes</li>
                    </ul>
                </flux:callout.text>
            </flux:callout>

            <!-- Refund Requests Section -->
            @if($refundRequests->count() > 0)
            <flux:card class="mt-4">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg shadow-md">
                        <flux:icon name="exclamation-triangle" class="text-white" size="lg" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Recent Refund Requests</flux:heading>
                        <flux:subheading class="text-slate-600 dark:text-slate-400">Client refund requests that may affect your payouts</flux:subheading>
                    </div>
                </div>
                
                <div class="space-y-3">
                    @foreach($refundRequests as $request)
                    <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-800">
                        <div>
                            <div class="font-medium text-slate-900 dark:text-slate-100">{{ $request->pitch->project->name ?? 'Unknown Project' }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $request->created_at->format('M j, Y') }} • {{ ucfirst($request->status) }}</div>
                        </div>
                        <flux:badge color="red" size="sm">
                            Refund Request
                        </flux:badge>
                    </div>
                    @endforeach
                </div>
            </flux:card>
            @endif
        </div>
    </div>
</div>

</x-layouts.app-sidebar> 