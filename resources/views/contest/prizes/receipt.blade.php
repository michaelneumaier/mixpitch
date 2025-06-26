@extends('components.layouts.app')

@section('title', 'Contest Prize Payment Receipt')

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4 rounded-full">
                    <i class="fas fa-check-circle text-4xl text-white"></i>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">All Prizes Paid Successfully!</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Contest prizes have been paid and payouts have been scheduled for all winners.
            </p>
            <div class="mt-4 inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                <i class="fas fa-trophy mr-2"></i>
                Contest: {{ $project->name }}
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Prize Payment Summary</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Completed on {{ now()->format('M j, Y \a\t g:i A') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i>
                            All Payments Complete
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Prize Recipients</h3>
                
                <div class="space-y-4">
                    @foreach($winners as $winner)
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <!-- Prize Placement Badge -->
                                    <div class="flex-shrink-0">
                                        @if($winner['prize']->placement === '1st')
                                            <div class="w-14 h-14 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg">1st</span>
                                            </div>
                                        @elseif($winner['prize']->placement === '2nd')
                                            <div class="w-14 h-14 bg-gray-400 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg">2nd</span>
                                            </div>
                                        @elseif($winner['prize']->placement === '3rd')
                                            <div class="w-14 h-14 bg-amber-600 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg">3rd</span>
                                            </div>
                                        @else
                                            <div class="w-14 h-14 bg-purple-500 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-sm">{{ $winner['prize']->placement }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Winner Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ $winner['prize']->getPlacementDisplayName() }}
                                            </h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Paid
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-3">
                                            <!-- Profile Image -->
                                            <div class="flex-shrink-0">
                                                <img class="h-8 w-8 rounded-full object-cover border border-gray-200" 
                                                     src="{{ $winner['user']->profile_photo_url }}" 
                                                     alt="{{ $winner['user']->name ?? 'User' }}">
                                            </div>
                                            <!-- User Name with Link -->
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <x-user-link :user="$winner['user']" />
                                                </div>
                                                @if($winner['pitch']->payment_completed_at)
                                                    <p class="text-xs text-gray-500">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        Paid {{ $winner['pitch']->payment_completed_at->format('M j, Y \a\t g:i A') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prize Amount & Actions -->
                                <div class="text-right">
                                    <div class="text-xl font-bold text-green-600 mb-2">
                                        ${{ number_format($winner['prize']->cash_amount, 2) }}
                                    </div>
                                    
                                    @if($winner['pitch']->final_invoice_id)
                                        <a href="{{ route('projects.pitches.payment.receipt', ['project' => $project, 'pitch' => $winner['pitch']]) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition-colors">
                                            <i class="fas fa-receipt mr-1"></i>
                                            View Invoice
                                        </a>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            No Invoice Available
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-xl font-semibold text-gray-900">Total Amount Paid:</div>
                        <div class="text-3xl font-bold text-green-600">
                            ${{ number_format($totalPrizeAmount, 2) }}
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Individual invoices available for each payment above
                    </div>
                </div>
            </div>
        </div>

        <!-- Payout Information -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Payout Schedule</h3>
            </div>
            <div class="px-8 py-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Payout Processing</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p class="mb-2">
                                    <strong>Hold Period:</strong> {{ ucfirst(app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('contest')['description']) }} before being released to winners.
                                </p>
                                <p class="mb-2">
                                    <strong>Expected Release:</strong> 
                                    @php
                                        $releaseDate = now();
                                        $businessDaysToAdd = 3;
                                        $daysAdded = 0;
                                        
                                        while ($daysAdded < $businessDaysToAdd) {
                                            $releaseDate->addDay();
                                            // Skip weekends (Saturday = 6, Sunday = 0)
                                            if ($releaseDate->dayOfWeek !== 0 && $releaseDate->dayOfWeek !== 6) {
                                                $daysAdded++;
                                            }
                                        }
                                    @endphp
                                    {{ $releaseDate->format('l, M j, Y') }}
                                </p>
                                <p>
                                    <strong>Commission:</strong> Platform commission will be deducted based on each winner's subscription tier.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Winner Notifications -->
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Winner Notifications</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>All contest winners have been automatically notified about their prize payouts and the expected release date.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <a href="{{ route('projects.manage', $project) }}" 
               class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200 hover:scale-105">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Contest Management
            </a>
            
            <a href="{{ route('projects.contest.results', $project) }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 transition-all duration-200 hover:scale-105 shadow-lg">
                <i class="fas fa-trophy mr-2"></i>
                View Contest Results
            </a>

            <a href="{{ route('contest.prizes.overview', $project) }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 hover:scale-105 shadow-lg">
                <i class="fas fa-dollar-sign mr-2"></i>
                Payment Overview
            </a>
        </div>

        <!-- Print/Download Options -->
        <div class="mt-8 text-center">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors no-print">
                <i class="fas fa-print mr-2"></i>
                Print Receipt
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    .bg-gradient-to-br {
        background: white !important;
    }
}
</style>
@endsection 