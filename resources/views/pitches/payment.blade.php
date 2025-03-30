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
            <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="hover:text-primary">Pitch Details</a>
            <svg class="h-4 w-4 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Payment</span>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b">
                <h1 class="text-xl font-bold text-gray-900">Payment Details</h1>
                <p class="text-gray-600">
                    Project: <a href="{{ route('projects.show', $pitch->project) }}" class="text-primary hover:underline">{{ $pitch->project->name }}</a>
                </p>
            </div>

            <!-- Payment Information -->
            <div class="px-6 py-4">
                @if($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                    <!-- Payment Complete -->
                    <div class="mb-6">
                        <div class="flex items-center justify-center">
                            <div class="bg-success/10 text-success rounded-full p-3 mb-4">
                                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-center text-success mb-2">Payment Complete</h2>
                        <p class="text-gray-600 text-center">
                            The payment has been successfully processed and the collaborator has been paid.
                        </p>
                    </div>

                    <!-- Payment Details -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h3 class="font-semibold text-gray-700 mb-3">Payment Summary</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Amount Paid</p>
                                <p class="font-bold text-xl">${{ number_format($pitch->payment_amount, 2) }} USD</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500">Date Completed</p>
                                <p class="font-medium">
                                    {{ $pitch->payment_completed_at ? $pitch->payment_completed_at->format('F j, Y') : 'N/A' }}
                                </p>
                            </div>
                            
                            @if($pitch->final_invoice_id)
                            <div>
                                <p class="text-sm text-gray-500">Invoice ID</p>
                                <p class="font-mono font-medium">{{ $pitch->final_invoice_id }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                    <!-- Payment Processing -->
                    <div class="mb-6 text-center">
                        <div class="flex items-center justify-center">
                            <div class="bg-amber-100 text-amber-700 rounded-full p-3 mb-4">
                                <svg class="animate-spin h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-amber-700 mb-2">Payment Processing</h2>
                        <p class="text-gray-600">
                            Your payment is currently being processed. This process may take a few minutes.
                        </p>
                    </div>
                @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
                    <!-- Payment Failed -->
                    <div class="mb-6 text-center">
                        <div class="flex items-center justify-center">
                            <div class="bg-red-100 text-red-700 rounded-full p-3 mb-4">
                                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-red-700 mb-2">Payment Failed</h2>
                        <p class="text-gray-600 mb-4">
                            There was an issue processing your payment. Please try again or contact support if the problem persists.
                        </p>
                        
                        @if(auth()->id() === $pitch->project->user_id)
                            <div class="mt-4">
                                <button class="btn btn-primary">
                                    <i class="fas fa-sync-alt mr-2"></i> Try Again
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- No Payment Yet -->
                    <div class="mb-6 text-center">
                        <div class="flex items-center justify-center">
                            <div class="bg-blue-100 text-blue-700 rounded-full p-3 mb-4">
                                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-blue-700 mb-2">Payment Pending</h2>
                        <p class="text-gray-600">
                            @if($pitch->project->budget == 0)
                                This is a free project. No payment is required.
                            @else
                                The project owner has not initiated payment for this completed pitch yet.
                            @endif
                        </p>
                        
                        @if(auth()->id() === $pitch->project->user_id && $pitch->project->budget > 0)
                            <div class="mt-4">
                                <a href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}" class="btn btn-primary">
                                    <i class="fas fa-credit-card mr-2"></i> Process Payment
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Project Information -->
            <div class="px-6 py-4 border-t border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-3">Project Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Project Owner</p>
                        <p class="font-medium">{{ $pitch->project->user->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Collaborator</p>
                        <p class="font-medium">{{ $pitch->user->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Completion Date</p>
                        <p class="font-medium">
                            {{ $pitch->completed_at ? $pitch->completed_at->format('F j, Y') : 'Not completed yet' }}
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Project Budget</p>
                        <p class="font-medium">
                            @if($pitch->project->budget == 0)
                                Free Project
                            @else
                                ${{ number_format($pitch->project->budget, 2) }} USD
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between">
                <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Pitch
                </a>
                
                @if(auth()->id() === $pitch->project->user_id && $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID && $pitch->final_invoice_id)
                    <a href="#" class="text-primary hover:text-primary-dark">
                        <i class="fas fa-file-invoice mr-1"></i> View Invoice
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 