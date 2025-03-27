<div class="bg-white rounded-lg shadow-md">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Invoice #{{ $invoice->number ?? $invoice->id }}</h3>
            <div class="flex items-center">
                <span class="px-3 py-1 rounded-full text-sm {{ $invoice->paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $invoice->paid ? 'Paid' : 'Unpaid' }}
                </span>
            </div>
        </div>
    </div>
    
    <div class="px-6 py-4">
        <!-- Invoice details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-1">Date</h4>
                <p class="text-gray-800">
                    @if(isset($invoice->date) && $invoice->date)
                        {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('F j, Y') : date('F j, Y', strtotime($invoice->date)) }}
                    @else
                        {{ date('F j, Y') }}
                    @endif
                </p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-1">Amount</h4>
                <p class="text-gray-800">${{ number_format(($invoice->total ?? 0) / 100, 2) }}</p>
            </div>
            @if(isset($invoice->description) && !empty($invoice->description))
            <div class="md:col-span-2">
                <h4 class="text-sm font-medium text-gray-600 mb-1">Description</h4>
                <p class="text-gray-800">{{ $invoice->description }}</p>
            </div>
            @endif
        </div>
        
        <!-- Line Items -->
        <div class="border rounded-md overflow-hidden mb-6">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @if(isset($invoice->lines) && isset($invoice->lines->data))
                        @foreach($invoice->lines->data as $lineItem)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-800">{{ $lineItem->description ?? 'Item' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 text-right">${{ number_format(($lineItem->amount ?? 0) / 100, 2) }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-800">{{ $invoice->description ?? 'Invoice item' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 text-right">${{ number_format(($invoice->total ?? 0) / 100, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">Total</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 text-right">${{ number_format(($invoice->total ?? 0) / 100, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Actions -->
        <div class="flex justify-end space-x-3">
            @if(isset($invoice->id))
            <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md transition-colors text-sm">
                <i class="fas fa-download mr-1"></i> Download PDF
            </a>
            @endif
            @if(isset($viewAllUrl))
            <a href="{{ $viewAllUrl }}" class="text-primary hover:underline flex items-center px-4 py-2">
                View all invoices
            </a>
            @endif
        </div>
    </div>
</div>
