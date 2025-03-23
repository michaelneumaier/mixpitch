<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice Details') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('billing.invoices') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Invoices
                </a>
                <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-focus text-white text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:p-8">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200">
                            <ul class="list-disc pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Invoice #{{ $invoice->number ?? $invoice->id }}</h3>
                                <p class="text-gray-600 mt-1">{{ $invoice->date()->format('F j, Y') }}</p>
                            </div>
                            <div class="mt-4 sm:mt-0">
                                @if($invoice->paid)
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Paid on {{ $invoice->asStripeInvoice()->status_transitions->paid_at ? \Carbon\Carbon::createFromTimestamp($invoice->asStripeInvoice()->status_transitions->paid_at)->format('M d, Y') : $invoice->date()->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Unpaid
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-3">Invoice Summary</h4>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">Subtotal:</span>
                                <span class="font-medium">${{ number_format(floatval($invoice->subtotal()) / 100, 2) }}</span>
                            </div>
                            @if($invoice->tax() > 0)
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">Tax:</span>
                                <span class="font-medium">${{ number_format(floatval($invoice->tax()) / 100, 2) }}</span>
                            </div>
                            @endif
                            @if($invoice->hasDiscount())
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">Discount:</span>
                                <span class="font-medium text-green-600">-${{ number_format(floatval($invoice->rawDiscount()) / 100, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-gray-200 mt-2">
                                <span class="text-gray-800 font-semibold">Total:</span>
                                <span class="font-bold text-gray-800">${{ number_format(floatval($invoice->total()) / 100, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-3">Invoice Items</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($invoice->invoiceItems() as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $item->asStripeInvoiceLineItem()->description ?? 'Product or Service' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $item->quantity ?? 1 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format(floatval($item->amount) / floatval($item->quantity ?? 1) / 100, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format(floatval($item->amount) / 100, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h4 class="text-lg font-semibold mb-3">Payment Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Payment Method</h5>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    @php
                                        $stripeInvoice = null;
                                        $paymentMethod = null;
                                        
                                        try {
                                            $stripeInvoice = $invoice->asStripeInvoice();
                                            
                                            // Only proceed if this invoice has been paid
                                            if ($invoice->paid) {
                                                $paymentIntent = $stripeInvoice->payment_intent ?? null;
                                                
                                                if (is_string($paymentIntent)) {
                                                    // If payment_intent is a string (ID), retrieve the object
                                                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                                                    $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntent);
                                                    
                                                    if (isset($paymentIntent->payment_method)) {
                                                        $paymentMethod = $stripe->paymentMethods->retrieve($paymentIntent->payment_method);
                                                    }
                                                } elseif (is_object($paymentIntent) && isset($paymentIntent->payment_method)) {
                                                    // If payment_intent is already an object
                                                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                                                    $paymentMethod = $stripe->paymentMethods->retrieve($paymentIntent->payment_method);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // Silently handle the error
                                        }
                                    @endphp

                                    @if($paymentMethod && isset($paymentMethod->card))
                                        <div class="flex items-center">
                                            <div>
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
                                            <div class="ml-3">
                                                <div class="font-medium">{{ ucfirst($brand) }} ending in {{ $paymentMethod->card->last4 }}</div>
                                                <div class="text-sm text-gray-600">Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-gray-700">Payment information not available</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Billing Details</h5>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    @php
                                        try {
                                            $stripeInvoice = $invoice->asStripeInvoice();
                                            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                                            $customer = $stripe->customers->retrieve($stripeInvoice->customer);
                                        } catch (\Exception $e) {
                                            $customer = null;
                                        }
                                    @endphp
                                    @if($customer)
                                        <p class="text-gray-700">{{ $customer->name }}</p>
                                        <p class="text-gray-700">{{ $customer->email }}</p>
                                        @if(isset($customer->address) && !empty($customer->address))
                                            <p class="text-gray-700 mt-2">{{ $customer->address->line1 ?? '' }}</p>
                                            @if(isset($customer->address->line2) && !empty($customer->address->line2))
                                                <p class="text-gray-700">{{ $customer->address->line2 }}</p>
                                            @endif
                                            <p class="text-gray-700">
                                                {{ isset($customer->address->city) ? $customer->address->city . ', ' : '' }}
                                                {{ isset($customer->address->state) ? $customer->address->state . ' ' : '' }} 
                                                {{ $customer->address->postal_code ?? '' }}
                                            </p>
                                            <p class="text-gray-700">{{ $customer->address->country ?? '' }}</p>
                                        @endif
                                    @else
                                        <p class="text-gray-700">Billing details not available</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 