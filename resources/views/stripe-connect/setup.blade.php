<x-layouts.app-sidebar title="Stripe Connect Setup">
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-cyan-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 rounded-full">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v2a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Payout Setup</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Set up your Stripe Connect account to receive payments for your winning pitches and contest prizes.
            </p>
        </div>

        <!-- Status Messages -->
        @if(session('success'))
            <div class="mb-8 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-8 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-red-800">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Account Status Section -->
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Account Status</h2>
                
                <div id="account-status" class="space-y-4">
                    @php
                        $statusColors = [
                            'not_created' => ['bg' => 'bg-gray-50', 'icon' => 'bg-gray-400', 'text' => 'text-gray-900'],
                            'incomplete' => ['bg' => 'bg-yellow-50', 'icon' => 'bg-yellow-400', 'text' => 'text-yellow-900'],
                            'action_required' => ['bg' => 'bg-orange-50', 'icon' => 'bg-orange-400', 'text' => 'text-orange-900'],
                            'past_due' => ['bg' => 'bg-red-50', 'icon' => 'bg-red-500', 'text' => 'text-red-900'],
                            'pending_verification' => ['bg' => 'bg-blue-50', 'icon' => 'bg-blue-400', 'text' => 'text-blue-900'],
                            'under_review' => ['bg' => 'bg-purple-50', 'icon' => 'bg-purple-400', 'text' => 'text-purple-900'],
                            'restricted' => ['bg' => 'bg-red-50', 'icon' => 'bg-red-400', 'text' => 'text-red-900'],
                            'active' => ['bg' => 'bg-green-50', 'icon' => 'bg-green-400', 'text' => 'text-green-900'],
                            'error' => ['bg' => 'bg-red-50', 'icon' => 'bg-red-500', 'text' => 'text-red-900'],
                        ];
                        $colors = $statusColors[$accountStatus['status']] ?? $statusColors['error'];
                    @endphp

                    <div class="flex items-start p-4 {{ $colors['bg'] }} rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 {{ $colors['icon'] }} rounded-full flex items-center justify-center">
                                @if($accountStatus['status'] === 'active')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @elseif(in_array($accountStatus['status'], ['pending_verification', 'under_review']))
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @elseif(in_array($accountStatus['status'], ['past_due', 'restricted', 'error']))
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                @elseif($accountStatus['status'] === 'action_required')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-medium {{ $colors['text'] }}">{{ $accountStatus['status_display'] ?? 'Unknown Status' }}</h3>
                            <p class="text-gray-600 mt-1">{{ $accountStatus['status_description'] ?? 'Unable to determine account status.' }}</p>
                            
                            @if(isset($accountStatus['deadline']) && $accountStatus['deadline'])
                                <div class="mt-2 p-2 bg-white/50 rounded border border-orange-200">
                                    <p class="text-sm font-medium text-orange-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        Deadline: {{ $accountStatus['deadline']->format('M j, Y \a\t g:i A') }}
                                    </p>
                                </div>
                            @endif

                            @if(isset($accountStatus['next_steps']) && !empty($accountStatus['next_steps']))
                                <div class="mt-3">
                                    <p class="text-sm font-medium {{ $colors['text'] }} mb-2">Next Steps:</p>
                                    <ul class="text-sm text-gray-700 space-y-1">
                                        @foreach($accountStatus['next_steps'] as $step)
                                            <li class="flex items-start">
                                                @if(str_starts_with($step, '•'))
                                                    <span class="text-gray-400 mr-2 mt-0.5">•</span>
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
                    </div>

                    <!-- Account Details -->
                    @if($accountStatus['status'] !== 'not_created')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($accountStatus['charges_enabled'])
                                            <div class="w-6 h-6 bg-green-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 bg-red-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Charges</p>
                                        <p class="text-xs text-gray-600">{{ $accountStatus['charges_enabled'] ? 'Enabled' : 'Disabled' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($accountStatus['payouts_enabled'])
                                            <div class="w-6 h-6 bg-green-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 bg-red-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Payouts</p>
                                        <p class="text-xs text-gray-600">{{ $accountStatus['payouts_enabled'] ? 'Enabled' : 'Disabled' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($accountStatus['details_submitted'])
                                            <div class="w-6 h-6 bg-green-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 bg-red-400 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Details</p>
                                        <p class="text-xs text-gray-600">{{ $accountStatus['details_submitted'] ? 'Complete' : 'Incomplete' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions Section -->
            <div class="px-8 py-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Actions</h2>
                
                <div class="space-y-4">
                    @if($accountStatus['status'] === 'not_created')
                        <form action="{{ route('stripe.connect.onboarding') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Set Up Stripe Connect Account
                            </button>
                        </form>
                    @elseif(in_array($accountStatus['status'], ['incomplete', 'action_required', 'past_due', 'restricted']))
                        <form action="{{ route('stripe.connect.onboarding') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Complete Required Information
                            </button>
                        </form>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            This will take you to Stripe to complete your account setup.
                        </p>
                    @elseif(in_array($accountStatus['status'], ['pending_verification', 'under_review']))
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Account Under Review</h3>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Your account is being reviewed by Stripe. This typically takes 1-2 business days. 
                                        You'll receive an email when the review is complete.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <button id="refresh-status" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Check Status
                        </button>
                    @endif

                    @if($accountStatus['status'] === 'active')
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="{{ route('stripe.connect.dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Access Stripe Dashboard
                            </a>
                            
                            <a href="{{ route('payouts.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                View Payout History
                            </a>
                        </div>
                    @endif

                    <!-- Always show refresh button for non-active accounts -->
                    @if($accountStatus['status'] !== 'active' && $accountStatus['status'] !== 'not_created')
                        @if(!in_array($accountStatus['status'], ['pending_verification', 'under_review']))
                            <button id="refresh-status" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh Status
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Information Section -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">How It Works</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600">1</span>
                            </div>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-2">Set Up Account</h4>
                        <p class="text-sm text-gray-600">Create your Stripe Connect account with your business or personal information.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600">2</span>
                            </div>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-2">Win Projects</h4>
                        <p class="text-sm text-gray-600">Submit winning pitches and earn contest prizes as usual.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600">3</span>
                            </div>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-2">Get Paid</h4>
                        <p class="text-sm text-gray-600">Receive automatic payouts after {{ app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('standard')['description'] }}, minus platform commission.</p>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-medium text-blue-900 mb-2">Commission Rates</h4>
                    <div class="text-sm text-blue-800 space-y-1">
                        <p><strong>Free Plan:</strong> 10% platform commission</p>
                        <p><strong>Pro Artist:</strong> 8% platform commission</p>
                        <p><strong>Pro Engineer:</strong> 6% platform commission</p>
                    </div>
                    <p class="text-xs text-blue-700 mt-2">
                        Commission rates are based on your subscription tier. <a href="{{ route('subscription.index') }}" class="underline">Upgrade your plan</a> to reduce commission rates.
                    </p>
                </div>
            </div>
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