<x-layouts.app-sidebar title="Stripe Connect Setup">

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Compact Dashboard Header -->
            <flux:card class="mb-2 bg-white/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-indigo-800 to-purple-800 dark:from-gray-100 dark:via-indigo-300 dark:to-purple-300 bg-clip-text text-transparent">
                        Payout Setup
                    </flux:heading>
                    
                    <div class="flex items-center gap-2">
                        <flux:button href="{{ route('payouts.index') }}" icon="clock" variant="outline" size="xs">
                            History
                        </flux:button>
                        <flux:button href="{{ route('dashboard') }}" icon="arrow-left" variant="ghost" size="xs">
                            Dashboard
                        </flux:button>
                    </div>
                </div>
                
                <flux:subheading class="text-slate-600 dark:text-slate-400">
                    Set up your Stripe Connect account to receive payments for your winning pitches and contest prizes.
                </flux:subheading>
            </flux:card>

            <!-- Status Messages -->
            @if(session('success'))
                <flux:callout icon="check-circle" color="green" class="mb-4">
                    {{ session('success') }}
                </flux:callout>
            @endif

            @if(session('info'))
                <flux:callout icon="information-circle" color="blue" class="mb-4">
                    {{ session('info') }}
                </flux:callout>
            @endif

            @if(session('warning'))
                <flux:callout icon="exclamation-triangle" color="amber" class="mb-4">
                    {{ session('warning') }}
                </flux:callout>
            @endif

            @if($errors->any())
                <flux:callout icon="exclamation-circle" color="red" class="mb-4">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </flux:callout>
            @endif

            <!-- Main Content -->
            <flux:card>
                <!-- Account Status Section -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                            <flux:icon name="building-office" class="text-white" size="lg" />
                        </div>
                        <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Account Status</flux:heading>
                    </div>
                
                    <div id="account-status" class="space-y-4">
                        @php
                            $statusBadgeColor = match($accountStatus['status']) {
                                'active' => 'green',
                                'pending_verification', 'under_review' => 'blue',
                                'incomplete' => 'amber',
                                'action_required' => 'orange',
                                'past_due', 'restricted', 'error' => 'red',
                                default => 'gray'
                            };
                            
                            $statusCalloutColor = match($accountStatus['status']) {
                                'active' => 'green',
                                'pending_verification', 'under_review' => 'blue', 
                                'incomplete' => 'amber',
                                'action_required' => 'orange',
                                'past_due', 'restricted', 'error' => 'red',
                                default => 'gray'
                            };
                        @endphp

                        <flux:callout :color="$statusCalloutColor">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <flux:callout.heading>{{ $accountStatus['status_display'] ?? 'Unknown Status' }}</flux:callout.heading>
                                    <flux:callout.text class="mt-1">{{ $accountStatus['status_description'] ?? 'Unable to determine account status.' }}</flux:callout.text>
                                    
                                    @if(isset($accountStatus['deadline']) && $accountStatus['deadline'])
                                        <div class="mt-3 p-3 bg-white/70 rounded-lg border border-orange-200">
                                            <div class="flex items-center gap-2 text-sm font-medium text-orange-800">
                                                <flux:icon name="clock" size="sm" />
                                                <span>Deadline: {{ $accountStatus['deadline']->format('M j, Y \a\t g:i A') }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($accountStatus['next_steps']) && !empty($accountStatus['next_steps']))
                                        <div class="mt-4">
                                            <div class="text-sm font-medium mb-2">Next Steps:</div>
                                            <ul class="text-sm space-y-1">
                                                @foreach($accountStatus['next_steps'] as $step)
                                                    <li class="flex items-start gap-2">
                                                        @if(str_starts_with($step, '•'))
                                                            <span class="text-slate-400 mt-0.5">•</span>
                                                            <span>{{ substr($step, 2) }}</span>
                                                        @else
                                                            <span>{{ $step }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                                <flux:badge :color="$statusBadgeColor" size="sm" class="ml-4">
                                    {{ ucfirst(str_replace('_', ' ', $accountStatus['status'])) }}
                                </flux:badge>
                            </div>
                        </flux:callout>

                        <!-- Account Details -->
                        @if($accountStatus['status'] !== 'not_created')
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <flux:card class="p-4">
                                    <div class="flex items-center gap-3">
                                        @if($accountStatus['charges_enabled'])
                                            <div class="p-1.5 bg-green-500 rounded-full">
                                                <flux:icon name="check" class="text-white" size="xs" />
                                            </div>
                                        @else
                                            <div class="p-1.5 bg-red-500 rounded-full">
                                                <flux:icon name="x-mark" class="text-white" size="xs" />
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Charges</div>
                                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ $accountStatus['charges_enabled'] ? 'Enabled' : 'Disabled' }}</div>
                                        </div>
                                    </div>
                                </flux:card>

                                <flux:card class="p-4">
                                    <div class="flex items-center gap-3">
                                        @if($accountStatus['payouts_enabled'])
                                            <div class="p-1.5 bg-green-500 rounded-full">
                                                <flux:icon name="check" class="text-white" size="xs" />
                                            </div>
                                        @else
                                            <div class="p-1.5 bg-red-500 rounded-full">
                                                <flux:icon name="x-mark" class="text-white" size="xs" />
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Payouts</div>
                                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ $accountStatus['payouts_enabled'] ? 'Enabled' : 'Disabled' }}</div>
                                        </div>
                                    </div>
                                </flux:card>

                                <flux:card class="p-4">
                                    <div class="flex items-center gap-3">
                                        @if($accountStatus['details_submitted'])
                                            <div class="p-1.5 bg-green-500 rounded-full">
                                                <flux:icon name="check" class="text-white" size="xs" />
                                            </div>
                                        @else
                                            <div class="p-1.5 bg-red-500 rounded-full">
                                                <flux:icon name="x-mark" class="text-white" size="xs" />
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-slate-100">Details</div>
                                            <div class="text-xs text-slate-600 dark:text-slate-400">{{ $accountStatus['details_submitted'] ? 'Complete' : 'Incomplete' }}</div>
                                        </div>
                                    </div>
                                </flux:card>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions Section -->
                <flux:separator class="my-6" />
                
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-sm">
                        <flux:icon name="bolt" class="text-white" size="lg" />
                    </div>
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Actions</flux:heading>
                </div>
                
                <div class="space-y-4">
                    @if($accountStatus['status'] === 'not_created')
                        <form action="{{ route('stripe.connect.onboarding') }}" method="POST">
                            @csrf
                            <flux:button type="submit" icon="plus" variant="primary">
                                Set Up Stripe Connect Account
                            </flux:button>
                        </form>
                    @elseif(in_array($accountStatus['status'], ['incomplete', 'action_required', 'past_due', 'restricted']))
                        <form action="{{ route('stripe.connect.onboarding') }}" method="POST">
                            @csrf
                            <flux:button type="submit" icon="exclamation-circle" variant="primary" class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700">
                                Complete Required Information
                            </flux:button>
                        </form>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <flux:icon name="information-circle" size="sm" />
                            This will take you to Stripe to complete your account setup.
                        </flux:text>
                    @elseif(in_array($accountStatus['status'], ['pending_verification', 'under_review']))
                        <flux:callout icon="clock" color="blue">
                            <flux:callout.heading>Account Under Review</flux:callout.heading>
                            <flux:callout.text>
                                Your account is being reviewed by Stripe. This typically takes 1-2 business days. 
                                You'll receive an email when the review is complete.
                            </flux:callout.text>
                        </flux:callout>
                        <flux:button id="refresh-status" icon="arrow-path" variant="outline">
                            Check Status
                        </flux:button>
                    @endif

                    @if($accountStatus['status'] === 'active')
                        <div class="flex flex-col sm:flex-row gap-4">
                            <flux:button href="{{ route('stripe.connect.dashboard') }}" icon="chart-bar" variant="primary">
                                Access Stripe Dashboard
                            </flux:button>
                            
                            <flux:button href="{{ route('payouts.index') }}" icon="document-text" variant="outline">
                                View Payout History
                            </flux:button>
                        </div>
                    @endif

                    <!-- Always show refresh button for non-active accounts -->
                    @if($accountStatus['status'] !== 'active' && $accountStatus['status'] !== 'not_created')
                        @if(!in_array($accountStatus['status'], ['pending_verification', 'under_review']))
                            <flux:button id="refresh-status" icon="arrow-path" variant="outline">
                                Refresh Status
                            </flux:button>
                        @endif
                    @endif
                </div>

            </flux:card>

            <!-- Information Section -->
            <flux:card class="mt-4">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-sm">
                        <flux:icon name="information-circle" class="text-white" size="lg" />
                    </div>
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">How It Works</flux:heading>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">1</span>
                            </div>
                        </div>
                        <flux:heading size="sm" class="mb-2">Set Up Account</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Create your Stripe Connect account with your business or personal information.</flux:text>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">2</span>
                            </div>
                        </div>
                        <flux:heading size="sm" class="mb-2">Win Projects</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Submit winning pitches and earn contest prizes as usual.</flux:text>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">3</span>
                            </div>
                        </div>
                        <flux:heading size="sm" class="mb-2">Get Paid</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Receive automatic payouts after {{ app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('standard')['description'] }}, minus platform commission.</flux:text>
                    </div>
                </div>

                <flux:callout icon="currency-dollar" color="blue">
                    <flux:callout.heading>Commission Rates</flux:callout.heading>
                    <flux:callout.text>
                        <div class="space-y-1 mt-2">
                            <div><strong>Free Plan:</strong> 10% platform commission</div>
                            <div><strong>Pro Artist:</strong> 8% platform commission</div>
                            <div><strong>Pro Engineer:</strong> 6% platform commission</div>
                        </div>
                        <div class="mt-3 text-sm">
                            Commission rates are based on your subscription tier. 
                            <flux:button href="{{ route('subscription.index') }}" variant="ghost" size="xs" class="underline p-0 h-auto">
                                Upgrade your plan
                            </flux:button>
                            to reduce commission rates.
                        </div>
                    </flux:callout.text>
                </flux:callout>
            </flux:card>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh status button
    const refreshButton = document.getElementById('refresh-status');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = `
                <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Refreshing...
            `;
            button.disabled = true;
            
            // Fetch updated status
            fetch('{{ route("stripe.connect.status") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated status
                        window.location.reload();
                    } else {
                        throw new Error('Failed to refresh status');
                    }
                })
                .catch(error => {
                    console.error('Error refreshing status:', error);
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // Show error message
                    alert('Failed to refresh status. Please try again.');
                });
        });
    }
});
</script>

</x-layouts.app-sidebar> 