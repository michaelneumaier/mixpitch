<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Recent Transactions</h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Last updated: {{ now()->format('M d, Y H:i') }}
                </div>
            </div>
            
            <div class="overflow-x-auto">
                @php $transactions = $this->getTransactions(); @endphp
                
                @if(count($transactions) > 0)
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Date</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Customer</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Description</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Amount</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Payment Method</th>
                                <th class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($transactions as $transaction)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $transaction['customer'] }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-md truncate">
                                        {{ $transaction['description'] }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                        ${{ number_format($transaction['amount'], 2) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($transaction['status']) }}">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $transaction['payment_method'] }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        @if(!empty($transaction['receipt_url']))
                                            <a 
                                                href="{{ $transaction['receipt_url'] }}" 
                                                target="_blank" 
                                                class="text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400 inline-flex items-center"
                                            >
                                                <span>View</span>
                                                <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No transactions yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start processing payments to see transaction history.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 