<x-layouts.app-sidebar title="Payout Setup">

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="mx-auto px-2 md:py-2">
        <div class="mx-auto">
            <!-- Header -->
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
                    Choose your preferred payout method to receive payments for your winning pitches and contest prizes.
                </flux:subheading>
            </flux:card>

            <!-- Provider Selection Component -->
            <flux:card class="mb-4">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-sm">
                        <flux:icon name="credit-card" class="text-white" size="lg" />
                    </div>
                    <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Payout Methods</flux:heading>
                </div>

                @livewire('payout-provider-selector')
            </flux:card>

            <!-- Information Section -->
            <flux:card>
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
                        <flux:heading size="sm" class="mb-2">Choose Provider</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Select your preferred payout method from Stripe, PayPal, or other available providers.</flux:text>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">2</span>
                            </div>
                        </div>
                        <flux:heading size="sm" class="mb-2">Complete Setup</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Follow the setup process for your chosen provider to verify your account.</flux:text>
                    </div>
                    
                    <div class="text-center">
                        <div class="flex justify-center mb-3">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">3</span>
                            </div>
                        </div>
                        <flux:heading size="sm" class="mb-2">Get Paid</flux:heading>
                        <flux:text size="sm" class="text-slate-600 dark:text-slate-400">Receive automatic payouts after {{ app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('standard')['description'] ?? '1 business day' }}, minus platform commission.</flux:text>
                    </div>
                </div>

                <!-- Commission Rates Section -->
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

                <!-- Provider Comparison -->
                <div class="mt-6">
                    <flux:heading size="md" class="mb-4">Provider Comparison</flux:heading>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 pr-4">Provider</th>
                                    <th class="text-left py-2 px-4">Setup</th>
                                    <th class="text-left py-2 px-4">Fees</th>
                                    <th class="text-left py-2 px-4">Processing Time</th>
                                    <th class="text-left py-2 pl-4">Best For</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4 font-medium">Stripe Connect</td>
                                    <td class="py-3 px-4">Account verification required</td>
                                    <td class="py-3 px-4">2.9% + $0.30</td>
                                    <td class="py-3 px-4">Instant</td>
                                    <td class="py-3 pl-4">Global users, instant transfers</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4 font-medium">PayPal</td>
                                    <td class="py-3 px-4">Just email address</td>
                                    <td class="py-3 px-4">Free domestic, varies international</td>
                                    <td class="py-3 px-4">24-48 hours</td>
                                    <td class="py-3 pl-4">Simple setup, lower fees</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-400">Wise</td>
                                    <td class="py-3 px-4 text-gray-400">Coming soon</td>
                                    <td class="py-3 px-4 text-gray-400">Low international fees</td>
                                    <td class="py-3 px-4 text-gray-400">1-2 business days</td>
                                    <td class="py-3 pl-4 text-gray-400">International transfers</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mt-6">
                    <flux:heading size="md" class="mb-4">Frequently Asked Questions</flux:heading>
                    <div class="space-y-3">
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between py-2 text-sm font-medium">
                                Can I use multiple payout methods?
                                <flux:icon name="chevron-down" size="sm" class="transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="pb-2 text-sm text-gray-600">
                                Yes! You can set up multiple payout methods and switch between them at any time. Your preferred method will be used for new payouts.
                            </div>
                        </details>
                        
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between py-2 text-sm font-medium">
                                How long do payouts take?
                                <flux:icon name="chevron-down" size="sm" class="transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="pb-2 text-sm text-gray-600">
                                Payout timing depends on your provider and project type. Stripe offers instant transfers, while PayPal typically takes 24-48 hours. Contest payouts are processed immediately with no hold period.
                            </div>
                        </details>
                        
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between py-2 text-sm font-medium">
                                Are there any additional fees?
                                <flux:icon name="chevron-down" size="sm" class="transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="pb-2 text-sm text-gray-600">
                                MixPitch absorbs all provider processing fees. You only pay the platform commission based on your subscription tier (6-10%).
                            </div>
                        </details>
                        
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between py-2 text-sm font-medium">
                                What if my payout fails?
                                <flux:icon name="chevron-down" size="sm" class="transition-transform group-open:rotate-180" />
                            </summary>
                            <div class="pb-2 text-sm text-gray-600">
                                If a payout fails, we'll automatically retry with your backup payment methods. You'll receive notifications about any issues that need your attention.
                            </div>
                        </details>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>

</x-layouts.app-sidebar>