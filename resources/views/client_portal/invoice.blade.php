@extends('layouts.app')

@section('title', 'Invoice - ' . $project->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden mb-8">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Invoice</h1>
                        <p class="text-purple-200">{{ $invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-bold text-lg">
                            MIXPITCH
                        </div>
                        <p class="text-purple-200 text-sm mt-2">Professional Audio Services</p>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Client Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Bill To:</h3>
                        <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                            <p class="text-white font-medium">{{ $project->client_name ?? 'Client' }}</p>
                            <p class="text-purple-200">{{ $project->client_email }}</p>
                        </div>
                    </div>

                    <!-- Invoice Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Invoice Details:</h3>
                        <div class="bg-white/5 rounded-lg p-4 border border-white/10 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-purple-200">Invoice Number:</span>
                                <span class="text-white font-medium">{{ $invoice_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-purple-200">Date:</span>
                                <span class="text-white font-medium">{{ $payment_date->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-purple-200">Status:</span>
                                <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">
                                    Paid
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Details -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-white mb-4">Project Details:</h3>
                    <div class="bg-white/5 rounded-lg p-6 border border-white/10">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-xl font-semibold text-white">{{ $project->title }}</h4>
                                @if($project->description)
                                    <p class="text-purple-200 mt-2">{{ $project->description }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-purple-200 text-sm">Producer:</p>
                                <p class="text-white font-medium">{{ $pitch->user->name }}</p>
                            </div>
                        </div>

                        <!-- Project Timeline -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-white/10">
                            <div>
                                <p class="text-purple-200 text-sm">Project Started:</p>
                                <p class="text-white font-medium">{{ $project->created_at->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-purple-200 text-sm">Completed:</p>
                                <p class="text-white font-medium">{{ $pitch->updated_at->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-purple-200 text-sm">Payment Date:</p>
                                <p class="text-white font-medium">{{ $payment_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-lg p-6 border border-purple-500/20">
                    <h3 class="text-lg font-semibold text-white mb-4">Payment Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-purple-200">Project Fee:</span>
                            <span class="text-white font-medium">${{ number_format($amount, 2) }}</span>
                        </div>
                        
                        <div class="border-t border-white/10 pt-3">
                            <div class="flex justify-between items-center text-xl font-bold">
                                <span class="text-white">Total Paid:</span>
                                <span class="text-green-400">${{ number_format($amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if($payment_event && $payment_event->metadata)
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <p class="text-purple-200 text-sm">
                                Payment processed via Stripe on {{ $payment_date->format('M d, Y \a\t g:i A') }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8">
                    <a href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}" 
                       class="flex-1 flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all duration-200 font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        View Deliverables
                    </a>
                    
                    <button onclick="window.print()" 
                            class="flex-1 flex items-center justify-center px-6 py-3 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-all duration-200 font-medium border border-white/20">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Invoice
                    </button>
                    
                    <a href="{{ URL::temporarySignedRoute('client.portal.view', now()->addDays(7), ['project' => $project->id]) }}" 
                       class="flex-1 flex items-center justify-center px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all duration-200 font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-purple-300 text-sm">
                Thank you for choosing MIXPITCH for your audio production needs.
            </p>
            <p class="text-purple-400 text-xs mt-2">
                This invoice was generated automatically upon payment completion.
            </p>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    body {
        background: white !important;
    }
    .bg-gradient-to-br,
    .bg-white\/10,
    .backdrop-blur-md {
        background: white !important;
    }
    .text-white {
        color: black !important;
    }
    .text-purple-200,
    .text-purple-300 {
        color: #6b7280 !important;
    }
    .border-white\/20,
    .border-white\/10 {
        border-color: #e5e7eb !important;
    }
    button,
    .no-print {
        display: none !important;
    }
}
</style>
@endsection 