<x-layouts.app-sidebar>

@php
    $user = auth()->user();
    $hasActiveSubscription = $user->hasActiveSubscription('default');
    $planDisplayName = $hasActiveSubscription 
        ? ucfirst($user->subscription_plan) . ' ' . ucfirst($user->subscription_tier)
        : 'Pro';
@endphp

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-2 py-2">
        <div class="mx-auto max-w-4xl">
            <!-- Success Header -->
            <flux:card class="mb-2 bg-white/50 text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full mb-6">
                    <flux:icon name="check" class="w-8 h-8 text-green-600 dark:text-green-400" />
                </div>

                @if($hasActiveSubscription)
                    <!-- User is actually subscribed -->
                    <flux:heading size="xl" class="mb-4 bg-gradient-to-r from-gray-900 to-purple-800 dark:from-blue-200 dark:to-purple-200 bg-clip-text text-transparent">
                        Welcome to {{ $planDisplayName }}!
                    </flux:heading>
                    <flux:subheading class="mb-8 text-slate-600 dark:text-slate-400">
                        Your subscription has been successfully activated. You now have access to all Pro features!
                    </flux:subheading>
                @else
                    <!-- Payment completed but subscription not yet active -->
                    <flux:heading size="xl" class="mb-4 bg-gradient-to-r from-gray-900 to-purple-800 dark:from-blue-200 dark:to-purple-200 bg-clip-text text-transparent">
                        Payment Successful!
                    </flux:heading>
                    <flux:subheading class="mb-6 text-slate-600 dark:text-slate-400">
                        Your payment has been processed successfully. Your subscription is being activated.
                    </flux:subheading>
                    
                    <flux:callout variant="warning" class="mb-8">
                        <flux:callout.text>
                            <strong>Note:</strong> It may take a few minutes for your subscription to be activated. 
                            If you don't see your Pro features within 5 minutes, please 
                            <a href="mailto:support@mixpitch.com" class="underline hover:no-underline">contact support</a>.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

            <!-- Feature Highlights -->
            <flux:card class="mb-2">
                <flux:heading size="lg" class="mb-6 text-center">Your Pro Features</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Unlimited Projects</flux:text>
                    </div>
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Unlimited Active Pitches</flux:text>
                    </div>
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Enhanced Storage Limits</flux:text>
                    </div>
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Priority Support</flux:text>
                    </div>
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Reduced Commission Rates</flux:text>
                    </div>
                    <div class="flex items-center">
                        <flux:icon name="check" class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" />
                        <flux:text class="text-gray-700 dark:text-gray-300">Visibility Boosts</flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Action Buttons -->
            <flux:card class="mb-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <flux:button 
                        href="{{ route('dashboard') }}" 
                        wire:navigate 
                        variant="primary"
                        icon="home"
                        class="w-full justify-center">
                        Go to Dashboard
                    </flux:button>
                    
                    <flux:button 
                        href="{{ route('projects.create') }}" 
                        wire:navigate 
                        variant="filled"
                        icon="plus"
                        class="w-full justify-center bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 !text-white">
                        Create Your First Project
                    </flux:button>
                    
                    <flux:button 
                        href="{{ route('subscription.index') }}" 
                        wire:navigate 
                        variant="outline"
                        icon="cog-6-tooth"
                        class="w-full justify-center">
                        Manage Subscription
                    </flux:button>
                </div>
            </flux:card>

            <!-- Receipt Information -->
            <flux:card class="mb-2 bg-blue-50/50 dark:bg-blue-950/50">
                <div class="flex items-center">
                    <flux:icon name="document-text" class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3 flex-shrink-0" />
                    <div>
                        <flux:text class="text-blue-900 dark:text-blue-100">
                            <strong>Receipt:</strong> A receipt has been sent to your email address.
                        </flux:text>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-300 mt-1">
                            You can also manage your subscription and download invoices from your 
                            <a href="{{ route('billing') }}" wire:navigate class="underline hover:no-underline font-medium">billing dashboard</a>.
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>

@if(!$hasActiveSubscription)
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

</x-layouts.app-sidebar>