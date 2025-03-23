<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="flex items-center justify-center">
                <div class="bg-green-100 text-green-700 p-4 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            
            <div class="text-center">
                <h1 class="text-2xl font-bold">Billing System Setup Complete!</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Your Stripe integration is now ready to use.</p>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h2 class="text-xl font-semibold mb-4">What's Included</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 text-blue-700 p-2 rounded mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h3 class="font-medium">Payment Method Management</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Users can add, update, and remove their payment methods securely.</p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center mb-3">
                            <div class="bg-green-100 text-green-700 p-2 rounded mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="font-medium">One-Time Payments</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Process immediate payments with description and custom amounts.</p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center mb-3">
                            <div class="bg-purple-100 text-purple-700 p-2 rounded mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="font-medium">Invoice Management</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Detailed invoice listings with downloadable PDF receipts.</p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center mb-3">
                            <div class="bg-yellow-100 text-yellow-700 p-2 rounded mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="font-medium">Invoice Details</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Detailed view of individual invoices with payment information.</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h2 class="text-xl font-semibold mb-4">Next Steps</h2>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="bg-indigo-100 text-indigo-700 p-2 rounded-full flex-shrink-0 mr-3">
                            <span class="font-semibold">1</span>
                        </div>
                        <div>
                            <h3 class="font-medium">Configure Your Stripe Keys</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                Make sure to set your Stripe API keys in the .env file:
                            </p>
                            <pre class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-xs mt-2 overflow-x-auto">
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
CASHIER_CURRENCY=usd</pre>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-indigo-100 text-indigo-700 p-2 rounded-full flex-shrink-0 mr-3">
                            <span class="font-semibold">2</span>
                        </div>
                        <div>
                            <h3 class="font-medium">Configure Stripe Webhooks</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                Set up a webhook in your Stripe dashboard pointing to:
                            </p>
                            <pre class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-xs mt-2 overflow-x-auto">{{ route('cashier.webhook') }}</pre>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                                Then add your webhook secret to your .env file:
                            </p>
                            <pre class="bg-gray-50 dark:bg-gray-800 rounded p-3 text-xs mt-2 overflow-x-auto">STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret</pre>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-indigo-100 text-indigo-700 p-2 rounded-full flex-shrink-0 mr-3">
                            <span class="font-semibold">3</span>
                        </div>
                        <div>
                            <h3 class="font-medium">Access Your Billing Dashboard</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">
                                Navigate to your billing dashboard to start managing payments.
                            </p>
                            <div class="mt-3">
                                <x-filament::button tag="a" href="{{ \App\Filament\Plugins\Billing\Pages\BillingDashboard::getUrl() }}" color="primary">
                                    {{ __('Go to Billing Dashboard') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h2 class="text-xl font-semibold mb-4">Additional Resources</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="https://stripe.com/docs" target="_blank" class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="font-medium">Stripe Documentation</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Official Stripe API documentation and guides.</p>
                    </a>
                    
                    <a href="https://laravel.com/docs/billing" target="_blank" class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="font-medium">Laravel Cashier</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Laravel's official documentation for Cashier Stripe.</p>
                    </a>
                    
                    <a href="https://filamentphp.com/docs" target="_blank" class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h3 class="font-medium">Filament Docs</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Filament PHP documentation for customizing admin panels.</p>
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page> 