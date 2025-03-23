<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold">{{ __('Invoice') }} #{{ $stripeInvoice->number ?? $stripeInvoice->id }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::createFromTimestamp($stripeInvoice->created)->format('F j, Y') }}
                    </p>
                </div>
                <div>
                    @if($stripeInvoice->status === 'paid')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            {{ __('Paid') }}
                        </span>
                    @elseif($stripeInvoice->status === 'open')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            {{ __('Unpaid') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            {{ ucfirst($stripeInvoice->status) }}
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-lg font-semibold mb-3">{{ __('Invoice Summary') }}</h3>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Subtotal') }}:</span>
                        </div>
                        <div class="text-right">
                            <span>${{ number_format($stripeInvoice->subtotal / 100, 2) }}</span>
                        </div>
                        
                        @if($stripeInvoice->tax)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Tax') }}:</span>
                        </div>
                        <div class="text-right">
                            <span>${{ number_format($stripeInvoice->tax / 100, 2) }}</span>
                        </div>
                        @endif
                        
                        @if(isset($stripeInvoice->discount) && $stripeInvoice->discount)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Discount') }}:</span>
                        </div>
                        <div class="text-right">
                            <span class="text-green-600 dark:text-green-400">-${{ number_format(abs($stripeInvoice->discount->amount ?? 0) / 100, 2) }}</span>
                        </div>
                        @endif
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                            <span class="font-semibold">{{ __('Total') }}:</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 text-right">
                            <span class="font-semibold">${{ number_format($stripeInvoice->total / 100, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-lg font-semibold mb-3">{{ __('Invoice Items') }}</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Description') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Quantity') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Unit Price') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stripeInvoice->lines->data as $item)
                                <tr class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-6 py-4">
                                        {{ $item->description ?? 'Product or Service' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $item->quantity ?? 1 }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $unitAmount = isset($item->quantity) && $item->quantity > 0 
                                                ? $item->amount / $item->quantity 
                                                : $item->amount;
                                        @endphp
                                        ${{ number_format($unitAmount / 100, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        ${{ number_format($item->amount / 100, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center">
                                        {{ __('No items found on this invoice.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                <div>
                    <h3 class="text-lg font-semibold mb-3">{{ __('Payment Information') }}</h3>
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        @if($paymentMethod && isset($paymentMethod->card))
                            <div class="flex items-center">
                                <div class="mr-3">
                                    @php
                                        $brand = $paymentMethod->card->brand;
                                    @endphp
                                    @if($brand === 'visa')
                                        <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                                    @elseif($brand === 'mastercard')
                                        <i class="fab fa-cc-mastercard text-2xl text-orange-600"></i>
                                    @elseif($brand === 'amex')
                                        <i class="fab fa-cc-amex text-2xl text-blue-800"></i>
                                    @elseif($brand === 'discover')
                                        <i class="fab fa-cc-discover text-2xl text-orange-500"></i>
                                    @else
                                        <i class="fas fa-credit-card text-2xl text-gray-700"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucfirst($brand) }} ending in {{ $paymentMethod->card->last4 }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Expires') }} {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ __('Payment information not available') }}
                            </p>
                        @endif
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-3">{{ __('Billing Details') }}</h3>
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        @if($customer)
                            <p class="text-gray-900 dark:text-gray-100">{{ $customer->name }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $customer->email }}</p>
                            
                            @if(isset($customer->address) && !empty($customer->address))
                                <div class="mt-2">
                                    <p class="text-gray-900 dark:text-gray-100">{{ $customer->address->line1 ?? '' }}</p>
                                    @if(isset($customer->address->line2) && !empty($customer->address->line2))
                                        <p class="text-gray-900 dark:text-gray-100">{{ $customer->address->line2 }}</p>
                                    @endif
                                    <p class="text-gray-900 dark:text-gray-100">
                                        {{ isset($customer->address->city) ? $customer->address->city . ', ' : '' }}
                                        {{ isset($customer->address->state) ? $customer->address->state . ' ' : '' }} 
                                        {{ $customer->address->postal_code ?? '' }}
                                    </p>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $customer->address->country ?? '' }}</p>
                                </div>
                            @endif
                        @else
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ __('Billing details not available') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <x-filament::button tag="a" href="/billing/download-invoice/{{ $invoice }}" target="_blank" icon="heroicon-o-arrow-down-tray">
                    {{ __('Download PDF') }}
                </x-filament::button>
                
                <x-filament::button tag="a" href="{{ \App\Filament\Plugins\Billing\Pages\BillingDashboard::getUrl() }}" color="gray" icon="heroicon-o-arrow-left">
                    {{ __('Back to Billing') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page> 