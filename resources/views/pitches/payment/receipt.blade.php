@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs -->
        <div class="flex items-center text-sm mb-4 text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-primary">Dashboard</a>
            <svg class="h-4 w-4 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <a href="{{ route('pitches.show', $pitch) }}" class="hover:text-primary">Pitch Details</a>
            <svg class="h-4 w-4 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Payment Receipt</span>
        </div>

        <!-- Payment Processing Step Indicator -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-success rounded-full flex items-center justify-center text-white font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="mt-2 text-sm font-medium text-success">Overview</span>
                </div>
                <div class="flex-1 h-1 bg-success mx-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-success rounded-full flex items-center justify-center text-white font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="mt-2 text-sm font-medium text-success">Processing</span>
                </div>
                <div class="flex-1 h-1 bg-success mx-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-success rounded-full flex items-center justify-center text-white font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="mt-2 text-sm font-medium text-success">Receipt</span>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-success-50 border border-success-100 text-success-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="h-5 w-5 text-success-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <!-- Header -->
            <div class="bg-success-50 border-b border-success-100 px-6 py-4">
                <div class="flex items-center">
                    <div class="bg-success-100 rounded-full p-2 mr-3">
                        <svg class="h-6 w-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Payment Successful</h1>
                        <p class="text-gray-600">
                            @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                Your payment is being processed
                            @else
                                Your payment has been processed successfully
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Receipt Details -->
            <div class="px-6 py-6">
                <div class="max-w-2xl mx-auto">
                    <!-- Receipt Header -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">PAYMENT RECEIPT</h2>
                        <p class="text-gray-500">
                            @if($pitch->payment_completed_at && is_object($pitch->payment_completed_at))
                                {{ $pitch->payment_completed_at->format('F j, Y') }}
                            @else
                                {{ now()->format('F j, Y') }}
                            @endif
                        </p>
                    </div>
                    
                    <!-- Invoice ID -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6 text-center">
                        <div class="text-sm text-gray-500">Invoice ID</div>
                        <div class="font-mono text-lg">{{ $pitch->final_invoice_id }}</div>
                    </div>

                    <!-- Customer and Seller Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2">Customer</h3>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="font-semibold">{{ $project->user->name }}</p>
                                <p class="text-gray-600 text-sm">{{ $project->user->email }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2">Recipient</h3>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="font-semibold">{{ $pitch->user->name }}</p>
                                <p class="text-gray-600 text-sm">{{ $pitch->user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden mb-8">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Description</th>
                                    <th class="py-3 px-4 text-right font-semibold text-gray-700">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-t border-gray-200">
                                    <td class="py-4 px-4">
                                        <div class="font-medium">{{ $project->name }}</div>
                                        <div class="text-sm text-gray-600">Pitch collaboration</div>
                                    </td>
                                    <td class="py-4 px-4 text-right">${{ number_format($pitch->payment_amount, 2) }}</td>
                                </tr>
                                
                                <!-- Add fees or taxes if needed -->
                                
                                <!-- Total -->
                                <tr class="border-t border-gray-200 bg-gray-50">
                                    <td class="py-3 px-4 font-semibold">Total</td>
                                    <td class="py-3 px-4 text-right font-bold">${{ number_format($pitch->payment_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payment Status -->
                    <div class="mb-8">
                        <h3 class="font-medium text-gray-700 mb-2">Payment Status</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                    <div class="w-8 h-8 bg-success rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-success">Paid</div>
                                        <div class="text-sm text-gray-600">
                                            @if($pitch->payment_completed_at && is_object($pitch->payment_completed_at))
                                                {{ $pitch->payment_completed_at->format('F j, Y \a\t g:i A') }}
                                            @else
                                                {{ now()->format('F j, Y \a\t g:i A') }}
                                            @endif
                                        </div>
                                    </div>
                                @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                    <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-amber-600">Processing</div>
                                        <div class="text-sm text-gray-600">
                                            Your payment is currently being processed
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-600 mb-6">
                        <p class="mb-2"><strong>Note:</strong> A copy of this receipt has been sent to both party's email addresses.</p>
                        <p>All files are now available for download in the project. The collaborator has been notified about the successful payment.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-center space-x-4">
                        <a href="{{ route('pitches.show', $pitch) }}" class="btn btn-outline">
                            <i class="fas fa-arrow-left mr-2"></i> Return to Pitch
                        </a>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print mr-2"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 