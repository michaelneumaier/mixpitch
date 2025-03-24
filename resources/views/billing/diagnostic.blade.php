<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice Diagnostic') }}
            </h2>
            <a href="{{ route('billing') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Back to Billing
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:p-8">
                    <h3 class="text-lg font-semibold mb-4">Stripe Customer Information</h3>
                    <div class="mb-6 p-4 bg-gray-50 rounded-md border border-gray-200">
                        <p><strong>Stripe Customer ID:</strong> {{ $stripeId }}</p>
                        <p><strong>Cashier Invoice Count:</strong> {{ $cashierInvoiceCount }}</p>
                        <p><strong>Raw Stripe Invoice Count:</strong> {{ count($rawInvoiceData) }}</p>
                    </div>

                    <h3 class="text-lg font-semibold mb-4">Raw Invoice Data from Stripe</h3>
                    @if(count($rawInvoiceData) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($rawInvoiceData as $invoice)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $invoice->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $invoice->number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('M d, Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format($invoice->total / 100, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $invoice->description ?? 'No description' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-gray-50 p-6 rounded-md border border-gray-200 text-center text-gray-700">
                            <p>No raw invoice data available from Stripe.</p>
                        </div>
                    @endif
                    
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4">Debugging Steps</h3>
                        <div class="p-4 bg-blue-50 rounded-md border border-blue-200">
                            <ol class="list-decimal pl-4">
                                <li class="mb-2">Verify that your Stripe customer ID exists and is correct.</li>
                                <li class="mb-2">Check if there are any invoices in the raw data from Stripe.</li>
                                <li class="mb-2">If raw invoices exist but don't show up in your billing history, there might be an issue with Laravel Cashier's invoice retrieval.</li>
                                <li class="mb-2">Try running <code class="px-1 py-0.5 bg-gray-100 rounded">php artisan stripe:sync-invoices --all</code> to manually sync invoices.</li>
                                <li class="mb-2">Make a test payment through the billing interface and check if a new invoice appears.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="mt-12 text-center">
                        <a href="{{ route('billing') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Return to Billing
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 