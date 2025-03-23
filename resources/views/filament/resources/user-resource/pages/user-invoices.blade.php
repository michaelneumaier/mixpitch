<x-filament::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <div class="prose dark:prose-invert max-w-none">
                    <h2>{{ __('Invoices for') }} {{ $record->name }}</h2>
                    <p>{{ __('View and download invoice history for this user.') }}</p>
                </div>
                
                <div>
                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.users.payment', ['record' => $record]) }}"
                        icon="heroicon-m-credit-card"
                        color="success"
                    >
                        {{ __('Process Payment') }}
                    </x-filament::button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                @if(count($invoices) > 0)
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Number') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Due Date') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Amount') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">{{ $invoice['number'] }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $invoice['date'] }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $invoice['due_date'] }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">${{ number_format($invoice['amount'], 2) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $this->getStatusColor($invoice['status']) }}-100 text-{{ $this->getStatusColor($invoice['status']) }}-800 dark:bg-{{ $this->getStatusColor($invoice['status']) }}-800 dark:text-{{ $this->getStatusColor($invoice['status']) }}-100">
                                            {{ ucfirst($invoice['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @if($invoice['url'])
                                                <a href="{{ $invoice['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ __('View') }}
                                                </a>
                                            @endif
                                            
                                            @if($invoice['pdf'])
                                                <a href="{{ $invoice['pdf'] }}" target="_blank" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                    {{ __('Download PDF') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('No invoices found') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('This user does not have any invoices yet.') }}
                            @if(!$record->stripe_id)
                                {{ __('The user has not been registered as a Stripe customer.') }}
                            @endif
                        </p>
                        @if(!$record->stripe_id)
                            <div class="mt-4">
                                <form action="{{ route('filament.admin.resources.users.create-stripe-customer', ['record' => $record]) }}" method="POST">
                                    @csrf
                                    <x-filament::button type="submit" color="primary">
                                        {{ __('Create Stripe Customer') }}
                                    </x-filament::button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament::page> 