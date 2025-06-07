<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Processing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                @if(auth()->user()->hasActiveSubscription('default'))
                    <!-- User is actually subscribed -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        Welcome to {{ ucfirst(auth()->user()->subscription_plan) }} {{ ucfirst(auth()->user()->subscription_tier) }}!
                    </h3>
                    <p class="text-lg text-gray-600 mb-8">
                        Your subscription has been successfully activated. You now have access to all Pro features!
                    </p>
                @else
                    <!-- Payment completed but subscription not yet active -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        Payment Successful!
                    </h3>
                    <p class="text-lg text-gray-600 mb-4">
                        Your payment has been processed successfully. Your subscription is being activated.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
                        <p class="text-sm text-yellow-800">
                            <strong>Note:</strong> It may take a few minutes for your subscription to be activated. 
                            If you don't see your Pro features within 5 minutes, please 
                            <a href="#" class="text-yellow-900 underline">contact support</a>.
                        </p>
                    </div>
                @endif

                <!-- Feature Highlights -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Unlimited Projects</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Unlimited Active Pitches</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">500MB Storage per Project</span>
                    </div>
                    <div class="flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Priority Support</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                        Go to Dashboard
                    </a>
                    <a href="{{ route('projects.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                        Create Your First Project
                    </a>
                    <a href="{{ route('subscription.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                        Manage Subscription
                    </a>
                </div>

                <!-- Receipt Information -->
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <strong>Receipt:</strong> A receipt has been sent to your email address. 
                        You can also manage your subscription and download invoices from your 
                        <a href="{{ route('billing') }}" class="text-blue-600 hover:text-blue-800 underline">billing dashboard</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(!auth()->user()->hasActiveSubscription('default'))
    <!-- Auto-refresh script to check for subscription activation -->
    <script>
        setTimeout(function() {
            // Refresh the page after 30 seconds if subscription is not active
            // This gives time for webhooks to process
            if (!document.hidden) {
                window.location.reload();
            }
        }, 30000);
    </script>
    @endif
</x-app-layout> 