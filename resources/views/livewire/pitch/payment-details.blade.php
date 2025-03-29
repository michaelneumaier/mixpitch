<div>
    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}
</div>

<div>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-primary/10 p-4 border-b border-primary/20">
            <div class="flex flex-wrap justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-primary">
                        Payment Receipt
                    </h2>
                    <p class="text-gray-600">
                        Reference #{{ $pitch->id }}
                    </p>
                </div>
                
                <div class="mt-2 md:mt-0">
                    @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                    <div class="bg-success/10 text-success rounded-md px-4 py-2 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Payment Completed
                    </div>
                    @else
                    <div class="bg-amber-100 text-amber-700 rounded-md px-4 py-2 flex items-center">
                        <i class="fas fa-clock mr-2"></i> Payment {{ $pitch->payment_status ?: 'Pending' }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Payment Information -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Payment Details</h3>
                    
                    @if($pitch->payment_completed_at)
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Date:</span> {{ $pitch->payment_completed_at->format('F j, Y') }}
                    </div>
                    @endif
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            <tr class="border-b">
                                <td class="py-3 text-gray-600">Status</td>
                                <td class="py-3 font-medium text-right">
                                    @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                        <span class="text-success flex items-center justify-end">
                                            <i class="fas fa-check-circle mr-1"></i> Paid
                                        </span>
                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                        <span class="text-amber-600 flex items-center justify-end">
                                            <i class="fas fa-circle-notch fa-spin mr-1"></i> Processing
                                        </span>
                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
                                        <span class="text-red-600 flex items-center justify-end">
                                            <i class="fas fa-times-circle mr-1"></i> Failed
                                        </span>
                                    @else
                                        <span class="text-gray-600 flex items-center justify-end">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 text-gray-600">Item</td>
                                <td class="py-3 font-medium text-right">
                                    Pitch for Project: {{ $pitch->project->name }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 text-gray-600">Amount</td>
                                <td class="py-3 font-medium text-right">
                                    @if((int) $pitch->project->budget === 0)
                                        <span class="text-green-600">Free Project</span>
                                    @else
                                        ${{ number_format($pitch->payment_amount ?? $pitch->project->budget, 2) }}
                                    @endif
                                </td>
                            </tr>
                            @if($pitch->final_invoice_id && $pitch->final_invoice_id !== 'free_project')
                            <tr class="border-b">
                                <td class="py-3 text-gray-600">Invoice</td>
                                <td class="py-3 font-mono text-sm text-right">
                                    {{ $pitch->final_invoice_id }}
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="py-3 text-gray-600">Payment Processing</td>
                                <td class="py-3 font-medium text-right">
                                    @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                        <span class="text-gray-600">
                                            Receipt sent to both parties
                                        </span>
                                    @else
                                        <span class="text-gray-600">
                                            No receipt available
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                @if($pitch->final_invoice_id && $pitch->final_invoice_id !== 'free_project')
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('billing.invoice.show', $pitch->final_invoice_id) }}" 
                       target="_blank" 
                       class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/80 transition-colors inline-flex items-center text-sm">
                        <i class="fas fa-file-invoice mr-2"></i> View Invoice
                    </a>
                </div>
                @endif
            </div>
            
            <!-- Participants -->
            <div>
                <h3 class="text-lg font-bold mb-4 text-gray-800">Project & Participants</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="border rounded-md p-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Project Owner</h4>
                        <div class="text-sm text-gray-600">Name:</div>
                        <div class="font-medium mb-2">{{ $pitch->project->user->name }}</div>
                        <div class="text-sm text-gray-600">Email:</div>
                        <div class="font-medium">{{ $pitch->project->user->email }}</div>
                    </div>
                    
                    <div class="border rounded-md p-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Pitch Creator</h4>
                        <div class="text-sm text-gray-600">Name:</div>
                        <div class="font-medium mb-2">{{ $pitch->user->name }}</div>
                        <div class="text-sm text-gray-600">Email:</div>
                        <div class="font-medium">{{ $pitch->user->email }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Actions -->
            @if(auth()->id() === $pitch->project->user_id && $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID)
            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-bold mb-4 text-gray-800">Payment Actions</h3>
                
                <div class="bg-amber-50 border border-amber-200 rounded-md p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-amber-600 mt-1 mr-2"></i>
                        <div>
                            <h4 class="font-semibold text-amber-700 mb-1">Payment Required</h4>
                            <p class="text-amber-700 text-sm">
                                @if((int) $pitch->project->budget === 0)
                                    This is a free project, but you need to mark it as completed.
                                @else
                                    This project requires payment of <strong>${{ number_format($pitch->project->budget, 2) }}</strong> to complete the project.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}" class="btn btn-primary w-full">
                    <i class="fas fa-credit-card mr-2"></i> Process Payment
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
