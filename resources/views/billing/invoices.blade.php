<x-layouts.app-sidebar>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">
                {{ __('Billing History') }}
            </flux:heading>
            <flux:button href="{{ route('billing') }}" wire:navigate icon="arrow-left" variant="outline" size="sm">
                Back to Billing
            </flux:button>
        </div>
    </div>

    <div>
        <div class="mx-auto sm:px-6 lg:px-8">
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

                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-4">Your Invoice History</h3>
                        
                        @if(count($invoices) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Number</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($invoices as $invoice)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $invoice->number ?? $invoice->id }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('M d, Y') : date('M d, Y', strtotime($invoice->date)) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    @if(isset($invoice->stripe_invoice))
                                                        ${{ number_format($invoice->total / 100, 2) }}
                                                    @else
                                                        ${{ number_format(floatval($invoice->total()) / 100, 2) }}
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($invoice->paid)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Paid
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Unpaid
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    @if(isset($invoice->metadata) && isset($invoice->metadata['pitch_id']))
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-music mr-1"></i> Pitch Payment
                                                        </span>
                                                        <a href="{{ route('projects.manage', $invoice->metadata['project_id']) }}" class="text-primary hover:underline ml-2 text-xs">
                                                            View Project
                                                        </a>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            Standard
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center gap-3">
                                                        <a href="{{ route('billing.invoice.show', $invoice->id) }}" class="text-primary hover:text-primary-focus">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="text-gray-700 hover:text-gray-900">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-gray-50 p-6 rounded-md border border-gray-200 text-center text-gray-700">
                                <i class="fas fa-receipt text-gray-400 text-4xl mb-3"></i>
                                <p>No billing history available.</p>
                                <p class="text-sm mt-2">Payments and invoices will appear here once you make a purchase.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.app-sidebar>