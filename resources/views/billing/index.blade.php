<x-layouts.app-sidebar>

<div class="container mx-auto p-2">
    <!-- Page Header -->
    <div class="mb-2">
        <flux:heading size="xl" class="mb-2">Billing & Payments</flux:heading>
        <flux:subheading>Manage your payment methods, billing history, and subscriptions</flux:subheading>
    </div>

    <!-- Include Subscription Overview -->
    @include('billing.components.subscription-overview', [
        'user' => $user,
        'isSubscribed' => $isSubscribed,
        'subscription' => $subscription,
        'onGracePeriod' => $onGracePeriod,
        'limits' => $limits,
        'usage' => $usage,
        'billingSummary' => $billingSummary
    ])
    
    <div class="space-y-8">
        @if (session('success'))
            <flux:callout variant="success" class="mb-6">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger" class="mb-6">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <!-- Payment Method Section -->
        <flux:card class="mb-2">
            <div class="">
                <div class="flex items-center mb-6">
                    <flux:icon name="credit-card" class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" />
                    <flux:heading size="lg">Payment Methods</flux:heading>
                </div>
                            
                @if($hasPaymentMethod && $paymentMethod)
                    <flux:card class="mb-6">
                        <div class="p-6">
                            <div class="flex items-center gap-6">
                                <div class="flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-xl">
                                    @if(isset($paymentMethod->card->brand))
                                        @if($paymentMethod->card->brand === 'visa')
                                            <i class="fab fa-cc-visa text-3xl text-blue-600"></i>
                                        @elseif($paymentMethod->card->brand === 'mastercard')
                                            <i class="fab fa-cc-mastercard text-3xl text-orange-600"></i>
                                        @elseif($paymentMethod->card->brand === 'amex')
                                            <i class="fab fa-cc-amex text-3xl text-blue-800"></i>
                                        @elseif($paymentMethod->card->brand === 'discover')
                                            <i class="fab fa-cc-discover text-3xl text-orange-500"></i>
                                        @else
                                            <i class="fas fa-credit-card text-3xl text-gray-700"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-credit-card text-3xl text-gray-700"></i>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ isset($paymentMethod->card->brand) ? ucfirst($paymentMethod->card->brand) : 'Payment Method' }} 
                                        @if(isset($paymentMethod->card->last4))
                                            •••• {{ $paymentMethod->card->last4 }}
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                        <flux:icon name="calendar" class="w-4 h-4 mr-2" />
                                        @if(isset($paymentMethod->card->exp_month) && isset($paymentMethod->card->exp_year))
                                            Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                        @else
                                            Payment method details available
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <flux:badge color="green" size="sm">
                                            <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                                            Default Payment Method
                                        </flux:badge>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('billing.payment.remove') }}" class="ml-auto">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="danger" size="sm" type="submit">
                                        <flux:icon name="trash" class="w-4 h-4 mr-2" />
                                        Remove
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    </flux:card>
                    <div class="flex flex-wrap gap-3">
                        <flux:button id="updateCard" variant="primary">
                            <flux:icon name="pencil" class="w-4 h-4 mr-2" />
                            Update Payment Method
                        </flux:button>
                        <flux:button href="{{ route('billing.payment-methods') }}" variant="outline">
                            <flux:icon name="credit-card" class="w-4 h-4 mr-2" />
                            Manage All Methods
                        </flux:button>
                    </div>
                @else
                    <flux:callout variant="warning" class="mb-6">
                        <div class="flex items-center">
                            <flux:icon name="exclamation-triangle" class="w-6 h-6 mr-3" />
                            <div>
                                <h4 class="font-semibold mb-1">No Payment Method</h4>
                                <p>Add a payment method to start making payments and manage your billing.</p>
                            </div>
                        </div>
                    </flux:callout>
                    <div class="flex flex-wrap gap-3">
                        <flux:button id="addCard" variant="primary">
                            <flux:icon name="plus" class="w-4 h-4 mr-2" />
                            Add Payment Method
                        </flux:button>
                        <flux:button href="{{ route('billing.payment-methods') }}" variant="outline">
                            <flux:icon name="credit-card" class="w-4 h-4 mr-2" />
                            Manage Payment Methods
                        </flux:button>
                    </div>
                @endif

                <!-- Payment Method Form (hidden by default) -->
                <div id="paymentMethodForm" class="mt-6 hidden">
                    <flux:card>
                        <div class="">
                            <div class="flex items-center mb-4">
                                <flux:icon name="lock-closed" class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" />
                                <flux:heading size="base">{{ $hasPaymentMethod ? 'Update Payment Method' : 'Add Payment Method' }}</flux:heading>
                            </div>
                                    
                            <form id="payment-form" action="{{ route('billing.payment.update') }}" method="POST">
                                @csrf
                                <div class="mb-6">
                                    <flux:label class="mb-3">
                                        <flux:icon name="credit-card" class="w-4 h-4 mr-2" />
                                        Credit or Debit Card Information
                                    </flux:label>
                                    <div class="relative">
                                        <div id="card-element" class="p-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-400 transition-all duration-200"></div>
                                        <div class="absolute top-2 right-2">
                                            <div class="flex items-center space-x-1">
                                                <i class="fab fa-cc-visa text-blue-600 text-sm"></i>
                                                <i class="fab fa-cc-mastercard text-orange-600 text-sm"></i>
                                                <i class="fab fa-cc-amex text-blue-800 text-sm"></i>
                                                <i class="fab fa-cc-discover text-orange-500 text-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="card-errors" class="text-red-600 text-sm mt-2 flex items-center">
                                        <flux:icon name="exclamation-circle" class="w-4 h-4 mr-1 hidden error-icon" />
                                        <span class="error-text"></span>
                                    </div>
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-400 flex items-center">
                                        <flux:icon name="shield-check" class="w-4 h-4 mr-1" />
                                        Your payment information is encrypted and secure
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <flux:button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" variant="primary">
                                        <span id="button-text" class="flex items-center">
                                            <flux:icon name="check" class="w-4 h-4 mr-2" />
                                            {{ $hasPaymentMethod ? 'Update Card' : 'Add Card' }}
                                        </span>
                                        <span id="spinner" class="hidden flex items-center">
                                            <flux:icon name="arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                                            Processing...
                                        </span>
                                    </flux:button>
                                    <flux:button type="button" onclick="document.getElementById('paymentMethodForm').classList.add('hidden')" variant="ghost">
                                        <flux:icon name="x-mark" class="w-4 h-4 mr-2" />
                                        Cancel
                                    </flux:button>
                                </div>
                            </form>
                        </div>
                    </flux:card>
                </div>
            </div>
        </flux:card>

        <!-- Stripe Connect Section -->
        <flux:card class="mb-2">
            <div class="">
                <div class="flex items-center mb-6">
                    <flux:icon name="building-office" class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3" />
                    <flux:heading size="lg">Payout Setup</flux:heading>
                </div>
                            
                @php
                    $stripeConnectService = app(\App\Services\StripeConnectService::class);
                    $accountStatus = $stripeConnectService->getDetailedAccountStatus($user);
                    $canReceivePayouts = $stripeConnectService->isAccountReadyForPayouts($user);
                @endphp
                
                <flux:card>
                    <div class="">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                @php
                                    $statusColors = [
                                        'not_created' => ['dot' => 'bg-gray-400', 'badge' => 'zinc'],
                                        'incomplete' => ['dot' => 'bg-yellow-400', 'badge' => 'amber'],
                                        'action_required' => ['dot' => 'bg-orange-400', 'badge' => 'orange'],
                                        'past_due' => ['dot' => 'bg-red-500', 'badge' => 'red'],
                                        'pending_verification' => ['dot' => 'bg-blue-400', 'badge' => 'blue'],
                                        'under_review' => ['dot' => 'bg-purple-400', 'badge' => 'purple'],
                                        'restricted' => ['dot' => 'bg-red-400', 'badge' => 'red'],
                                        'active' => ['dot' => 'bg-green-400', 'badge' => 'green'],
                                        'error' => ['dot' => 'bg-red-500', 'badge' => 'red'],
                                    ];
                                    $colors = $statusColors[$accountStatus['status']] ?? $statusColors['error'];
                                @endphp

                                <div class="flex items-center mb-3">
                                    <div class="w-3 h-3 {{ $colors['dot'] }} rounded-full mr-3"></div>
                                    <flux:heading size="base">{{ $accountStatus['status_display'] ?? 'Unknown Status' }}</flux:heading>
                                </div>
                                
                                <flux:text class="text-gray-600 dark:text-gray-400 mb-4">{{ $accountStatus['status_description'] ?? 'Unable to determine account status.' }}</flux:text>
                                        
                                @if(isset($accountStatus['deadline']) && $accountStatus['deadline'])
                                    <flux:callout variant="warning" class="mb-4">
                                        <flux:icon name="clock" class="w-4 h-4 mr-2" />
                                        Deadline: {{ $accountStatus['deadline']->format('M j, Y \a\t g:i A') }}
                                    </flux:callout>
                                @endif

                                @if(isset($accountStatus['next_steps']) && !empty($accountStatus['next_steps']))
                                    <div class="mb-4">
                                        <flux:text class="font-medium mb-2">Next Steps:</flux:text>
                                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            @foreach(array_slice($accountStatus['next_steps'], 0, 3) as $step)
                                                <li class="flex items-start">
                                                    @if(str_starts_with($step, '•'))
                                                        <span class="text-gray-400 mr-2 mt-0.5">•</span>
                                                        <span>{{ substr($step, 2) }}</span>
                                                    @else
                                                        <span>{{ $step }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                            @if(count($accountStatus['next_steps']) > 3)
                                                <li class="text-gray-500 italic">... and {{ count($accountStatus['next_steps']) - 3 }} more steps</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif

                                <!-- Capability Status -->
                                @if($accountStatus['status'] !== 'not_created')
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="flex items-center">
                                            @if($accountStatus['charges_enabled'] ?? false)
                                                <flux:icon name="check-circle" class="w-4 h-4 text-green-500 mr-2" />
                                            @else
                                                <flux:icon name="x-circle" class="w-4 h-4 text-red-500 mr-2" />
                                            @endif
                                            <flux:text size="sm">Charges {{ ($accountStatus['charges_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</flux:text>
                                        </div>
                                        <div class="flex items-center">
                                            @if($accountStatus['payouts_enabled'] ?? false)
                                                <flux:icon name="check-circle" class="w-4 h-4 text-green-500 mr-2" />
                                            @else
                                                <flux:icon name="x-circle" class="w-4 h-4 text-red-500 mr-2" />
                                            @endif
                                            <flux:text size="sm">Payouts {{ ($accountStatus['payouts_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</flux:text>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            @if($accountStatus['status'] === 'not_created' || $accountStatus['status'] === 'incomplete' || $accountStatus['status'] === 'action_required')
                                <flux:button href="{{ route('stripe.connect.setup') }}" variant="primary">
                                    <flux:icon name="building-office" class="w-4 h-4 mr-2" />
                                    {{ $accountStatus['status'] === 'not_created' ? 'Set Up Stripe Connect' : 'Complete Setup' }}
                                </flux:button>
                            @elseif($accountStatus['status'] === 'active')
                                <flux:button href="{{ route('stripe.connect.dashboard') }}" variant="outline">
                                    <flux:icon name="arrow-top-right-on-square" class="w-4 h-4 mr-2" />
                                    Manage Account
                                </flux:button>
                            @endif
                            
                            <flux:button href="{{ route('stripe.connect.setup') }}" variant="ghost">
                                <flux:icon name="information-circle" class="w-4 h-4 mr-2" />
                                View Details
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </div>
        </flux:card>

        <!-- Billing History Section -->
        <flux:card>
            <div class="">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <flux:icon name="clock" class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3" />
                        <flux:heading size="lg">Billing History</flux:heading>
                    </div>
                    @if(count($invoices) > 0)
                        <flux:button href="{{ route('billing.invoices') }}" variant="outline" size="sm">
                            <flux:icon name="arrow-right" class="w-4 h-4 mr-2" />
                            View All
                        </flux:button>
                    @endif
                </div>
                            
                @if(count($invoices) > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>
                                Date
                            </flux:table.column>
                            <flux:table.column>
                                Amount
                            </flux:table.column>
                            <flux:table.column>
                                Status
                            </flux:table.column>
                            <flux:table.column>
                                Actions
                            </flux:table.column>
                        </flux:table.columns>
                        
                        <flux:table.rows>
                            @foreach($invoices as $invoice)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div class="flex items-center">
                                            <flux:icon name="document-text" class="w-5 h-5 text-gray-400 mr-3" />
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('M d, Y') : $invoice->date()->format('M d, Y') }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('g:i A') : $invoice->date()->format('g:i A') }}
                                                </div>
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                            @if(isset($invoice->stripe_invoice))
                                                ${{ number_format($invoice->total / 100, 2) }}
                                            @else
                                                ${{ number_format(floatval($invoice->total()) / 100, 2) }}
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">USD</div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($invoice->paid)
                                            <flux:badge color="green" size="sm">
                                                <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                                                Paid
                                            </flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm">
                                                <flux:icon name="exclamation-circle" class="w-3 h-3 mr-1" />
                                                Unpaid
                                            </flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <flux:button href="{{ route('billing.invoice.show', $invoice->id) }}" variant="outline" size="xs">
                                                <flux:icon name="eye" class="w-3 h-3 mr-1" />
                                                View
                                            </flux:button>
                                            <flux:button href="{{ route('billing.invoice.download', $invoice->id) }}" variant="ghost" size="xs">
                                                <flux:icon name="arrow-down-tray" class="w-3 h-3 mr-1" />
                                                Download
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="document-text" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                        <flux:heading size="lg" class="mb-2">No Billing History</flux:heading>
                        <flux:text class="text-gray-600 dark:text-gray-400 mb-4">You haven't made any payments yet.</flux:text>
                        <flux:text size="sm" class="text-gray-500 dark:text-gray-500">Invoices and payment history will appear here once you make your first purchase.</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Billing Portal -->
        @if($hasPaymentMethod)
            <flux:card class="mt-8">
                <div class="">
                    <div class="flex items-center mb-6">
                        <flux:icon name="arrow-top-right-on-square" class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mr-3" />
                        <flux:heading size="lg">Billing Portal</flux:heading>
                    </div>
                    
                    <flux:card class="mb-6">
                        <div class="">
                            <div class="flex items-center mb-4">
                                <flux:icon name="shield-check" class="w-8 h-8 text-indigo-600 dark:text-indigo-400 mr-4" />
                                <div>
                                    <flux:heading size="base">Secure Stripe Portal</flux:heading>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Powered by Stripe's enterprise-grade security</flux:text>
                                </div>
                            </div>
                            <flux:text class="mb-4">
                                Access Stripe's secure customer portal to manage your subscription, update payment methods, view detailed billing history, and download invoices.
                            </flux:text>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <flux:icon name="credit-card" class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mx-auto mb-2" />
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Payment Methods</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <flux:icon name="document-text" class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mx-auto mb-2" />
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Invoice History</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                                    <flux:icon name="cog-6-tooth" class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mx-auto mb-2" />
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Account Settings</div>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                    @if(session('errors') && session('errors')->has('error') && str_contains(session('errors')->first('error'), 'Customer Portal'))
                        <flux:callout variant="warning" class="mb-4">
                            <flux:heading size="sm">Configuration Required</flux:heading>
                            <flux:text class="mt-2">
                                {{ session('errors')->first('error') }}
                            </flux:text>
                            <flux:text class="mt-1">To fix this, please follow these steps:</flux:text>
                            <ol class="list-decimal pl-5 mt-1 space-y-1 text-sm">
                                <li>Log in to your <a href="https://dashboard.stripe.com/test/settings/billing/portal" class="underline font-medium" target="_blank">Stripe Dashboard</a></li>
                                <li>Go to Settings > Customer Portal</li>
                                <li>Configure your portal settings (branding, features, etc.)</li>
                                <li>Save your configuration</li>
                                <li>Return here and try again</li>
                            </ol>
                        </flux:callout>
                        
                        <!-- Local Payment Method Management -->
                        <flux:card class="mb-6">
                            <div class="p-4">
                                <flux:heading size="sm" class="mb-2">Manage Your Payment Methods Locally</flux:heading>
                                <flux:text size="sm" class="mb-4">
                                    While the Stripe Customer Portal is being configured, you can still manage your payment methods here:
                                </flux:text>
                                
                                <!-- Current Payment Method -->
                                <div class="mb-4">
                                    <flux:text size="sm" class="font-medium mb-2">Current Default Payment Method</flux:text>
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border flex items-center">
                                        <div>
                                            @if(isset($paymentMethod->card->brand))
                                                @if($paymentMethod->card->brand === 'visa')
                                                    <i class="fab fa-cc-visa text-xl text-blue-600"></i>
                                                @elseif($paymentMethod->card->brand === 'mastercard')
                                                    <i class="fab fa-cc-mastercard text-xl text-orange-600"></i>
                                                @elseif($paymentMethod->card->brand === 'amex')
                                                    <i class="fab fa-cc-amex text-xl text-blue-800"></i>
                                                @elseif($paymentMethod->card->brand === 'discover')
                                                    <i class="fab fa-cc-discover text-xl text-orange-500"></i>
                                                @else
                                                    <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <div class="font-medium">
                                                @if(isset($paymentMethod->card->brand) && isset($paymentMethod->card->last4))
                                                    {{ ucfirst($paymentMethod->card->brand) }} ending in {{ $paymentMethod->card->last4 }}
                                                @else
                                                    Payment method configured
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                @if(isset($paymentMethod->card->exp_month) && isset($paymentMethod->card->exp_year))
                                                    Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                                @else
                                                    Details available in billing portal
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <flux:button id="updateCardLocal" variant="primary" size="sm">
                                            Update Payment Method
                                        </flux:button>
                                        <form method="POST" action="{{ route('billing.payment.remove') }}" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <flux:button type="submit" variant="danger" size="sm">
                                                Remove Payment Method
                                            </flux:button>
                                        </form>
                                    </div>
                                    
                                    <!-- Local Payment Method Form (hidden by default) -->
                                    <div id="paymentMethodFormLocal" class="mt-4 hidden">
                                        <flux:text size="sm" class="font-medium mb-2">Enter New Payment Details</flux:text>
                                        <form id="payment-form-local" action="{{ route('billing.payment.update') }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <div id="card-element-local" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"></div>
                                                <div id="card-errors-local" class="text-red-600 text-xs mt-1"></div>
                                            </div>
                                            <flux:button type="submit" id="card-button-local" data-secret="{{ $intent->client_secret }}" variant="primary" size="sm">
                                                <span id="button-text-local">Update Card</span>
                                                <span id="spinner-local" class="hidden">
                                                    <flux:icon name="arrow-path" class="w-4 h-4 animate-spin" />
                                                </span>
                                            </flux:button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    @endif
                    
                    <flux:button href="{{ route('billing.portal') }}" variant="primary">
                        <flux:icon name="arrow-top-right-on-square" class="w-4 h-4 mr-2" />
                        Access Billing Portal
                        <flux:icon name="arrow-right" class="w-4 h-4 ml-2" />
                    </flux:button>
                </div>
            </flux:card>
        @endif
    </div>
</div>

</x-layouts.app-sidebar>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Stripe
                const stripe = Stripe(`{{ env('STRIPE_KEY') }}`);
                const elements = stripe.elements();
                
                // Enhanced Glass Morphism Styling for Stripe Elements
                const style = {
                    base: {
                        color: '#1f2937',
                        fontFamily: '"Inter", "Helvetica Neue", Helvetica, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        fontWeight: '500',
                        '::placeholder': {
                            color: '#9ca3af'
                        },
                        ':focus': {
                            color: '#1f2937'
                        }
                    },
                    invalid: {
                        color: '#dc2626',
                        iconColor: '#dc2626'
                    },
                    complete: {
                        color: '#059669',
                        iconColor: '#059669'
                    }
                };
                
                // Create card element
                const cardElement = elements.create('card', { style: style });
                cardElement.mount('#card-element');
                
                // Enhanced validation error handling
                cardElement.on('change', function(event) {
                    const displayError = document.getElementById('card-errors');
                    const errorIcon = displayError.querySelector('.error-icon');
                    const errorText = displayError.querySelector('.error-text');
                    
                    if (event.error) {
                        errorIcon.classList.remove('hidden');
                        errorText.textContent = event.error.message;
                        displayError.classList.add('text-red-600');
                        displayError.classList.remove('text-green-600');
                    } else if (event.complete) {
                        errorIcon.classList.add('hidden');
                        errorText.textContent = 'Payment information is valid';
                        displayError.classList.remove('text-red-600');
                        displayError.classList.add('text-green-600');
                    } else {
                        errorIcon.classList.add('hidden');
                        errorText.textContent = '';
                        displayError.classList.remove('text-red-600', 'text-green-600');
                    }
                });
                
                // Handle form submission
                const form = document.getElementById('payment-form');
                if (form) {
                    const cardButton = document.getElementById('card-button');
                    const clientSecret = cardButton.dataset.secret;
                    
                    form.addEventListener('submit', async function(event) {
                        event.preventDefault();
                        
                        // Disable the submit button to prevent repeated clicks
                        cardButton.disabled = true;
                        document.getElementById('button-text').classList.add('hidden');
                        document.getElementById('spinner').classList.remove('hidden');
                        
                        const { setupIntent, error } = await stripe.confirmCardSetup(
                            clientSecret, {
                                payment_method: {
                                    card: cardElement
                                }
                            }
                        );
                        
                        if (error) {
                            // Show error to your customer
                            const errorElement = document.getElementById('card-errors');
                            errorElement.textContent = error.message;
                            
                            // Re-enable the submit button
                            cardButton.disabled = false;
                            document.getElementById('button-text').classList.remove('hidden');
                            document.getElementById('spinner').classList.add('hidden');
                        } else {
                            // The card has been verified successfully
                            // Create a hidden input with the payment method ID
                            const hiddenInput = document.createElement('input');
                            hiddenInput.setAttribute('type', 'hidden');
                            hiddenInput.setAttribute('name', 'payment_method');
                            hiddenInput.setAttribute('value', setupIntent.payment_method);
                            form.appendChild(hiddenInput);
                            
                            // Submit the form
                            form.submit();
                        }
                    });
                }
                
                // Toggle payment method form
                const addCardButton = document.getElementById('addCard');
                const updateCardButton = document.getElementById('updateCard');
                const paymentMethodForm = document.getElementById('paymentMethodForm');
                
                if (addCardButton) {
                    addCardButton.addEventListener('click', function() {
                        paymentMethodForm.classList.toggle('hidden');
                    });
                }
                
                if (updateCardButton) {
                    updateCardButton.addEventListener('click', function() {
                        paymentMethodForm.classList.toggle('hidden');
                    });
                }
                
                // --- Local Payment Form Handling ---
                const updateCardLocalButton = document.getElementById('updateCardLocal');
                const paymentMethodFormLocal = document.getElementById('paymentMethodFormLocal');
                
                if (updateCardLocalButton && paymentMethodFormLocal) {
                    // Create local card element
                    const cardElementLocal = elements.create('card', { style: style });
                    cardElementLocal.mount('#card-element-local');
                    
                    // Handle validation errors for local form
                    cardElementLocal.on('change', function(event) {
                        const displayError = document.getElementById('card-errors-local');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
                    
                    // Toggle local payment method form
                    updateCardLocalButton.addEventListener('click', function() {
                        paymentMethodFormLocal.classList.toggle('hidden');
                    });
                    
                    // Handle local form submission
                    const formLocal = document.getElementById('payment-form-local');
                    const cardButtonLocal = document.getElementById('card-button-local');
                    const clientSecretLocal = cardButtonLocal.dataset.secret;
                    
                    formLocal.addEventListener('submit', async function(event) {
                        event.preventDefault();
                        
                        // Disable the submit button to prevent repeated clicks
                        cardButtonLocal.disabled = true;
                        document.getElementById('button-text-local').classList.add('hidden');
                        document.getElementById('spinner-local').classList.remove('hidden');
                        
                        const { setupIntent, error } = await stripe.confirmCardSetup(
                            clientSecretLocal, {
                                payment_method: {
                                    card: cardElementLocal
                                }
                            }
                        );
                        
                        if (error) {
                            // Show error to your customer
                            const errorElement = document.getElementById('card-errors-local');
                            errorElement.textContent = error.message;
                            
                            // Re-enable the submit button
                            cardButtonLocal.disabled = false;
                            document.getElementById('button-text-local').classList.remove('hidden');
                            document.getElementById('spinner-local').classList.add('hidden');
                        } else {
                            // The card has been verified successfully
                            // Create a hidden input with the payment method ID
                            const hiddenInput = document.createElement('input');
                            hiddenInput.setAttribute('type', 'hidden');
                            hiddenInput.setAttribute('name', 'payment_method');
                            hiddenInput.setAttribute('value', setupIntent.payment_method);
                            formLocal.appendChild(hiddenInput);
                            
                            // Submit the form
                            formLocal.submit();
                        }
                    });
                }
            });
        </script>
    @endpush 