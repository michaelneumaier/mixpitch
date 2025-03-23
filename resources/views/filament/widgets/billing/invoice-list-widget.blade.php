<x-filament::section>
    <div class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="px-4 sm:px-6 py-5 flex items-center justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">
                {{ $this->getHeading() }}
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8">Date</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Invoice</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Amount</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse($this->getInvoices() as $invoice)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 dark:text-gray-400 sm:pl-6 lg:pl-8">{{ $invoice['date'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $invoice['number'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $invoice['amount'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @php
                                    $statusColorClasses = [
                                        'success' => 'inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-700/20 dark:text-green-400',
                                        'warning' => 'inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800 dark:bg-yellow-700/20 dark:text-yellow-400',
                                        'danger' => 'inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-700/20 dark:text-red-400',
                                        'gray' => 'inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700/20 dark:text-gray-400',
                                    ]
                                @endphp
                                <span class="{{ $statusColorClasses[$invoice['status_color']] }}">
                                    {{ ucfirst($invoice['status']) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                                <a href="{{ $invoice['view_url'] }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">
                                    <x-heroicon-m-eye class="w-5 h-5 inline" />
                                </a>
                                <a href="{{ $invoice['download_url'] }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    <x-heroicon-m-arrow-down-tray class="w-5 h-5 inline" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <x-heroicon-o-document-text class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-2" />
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No invoices yet</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your invoice history will appear here once you make a payment.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::section> 