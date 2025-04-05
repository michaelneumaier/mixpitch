@extends('components.layouts.app')

@section('content')

<div class="w-full mx-auto">
    <div class="flex justify-center">
        <div class="w-full max-w-6xl mx-auto">
            <div class="shadow mb-4">
                <!-- Adjusted text color for visibility -->
                <div class="py-3 px-4 text-primary text-3xl font-semibold text-center">{{ __('Dashboard') }}</div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-4 pl-2 flex items-center">Your Projects
                        <a href="{{ route('projects.create') }}"
                            class="bg-primary hover:bg-primary-focus text-white text-sm text-center ml-2 py-2 px-4 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Share
                            Your Project</a>
                    </h3>
                    @if ($projects->isEmpty())
                    <div class="bg-base-200/50 rounded-lg p-8 text-center">
                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">You haven't shared any projects yet.</p>
                        <p class="text-gray-500 text-sm mt-2">Create a project to start collaborating with other
                            creators.</p>
                    </div>
                    @else

                    @foreach ($projects as $project)
                    <div
                        class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
                        <a href="{{ route('projects.manage', $project) }}" class="block">
                            <div class="flex flex-col md:flex-row">
                                <!-- Project Image -->
                                <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                                    style="background-image: url('{{ $project->image_path ? $project->imageUrl : asset('images/default-project.jpg') }}');">
                                </div>

                                <!-- Project Info -->
                                <div class="p-4 flex-grow">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                                        <h4 class="text-lg font-semibold text-gray-800 mb-2 md:mb-0">{{ $project->name
                                            }}</h4>

                                        <!-- Project Status Badge -->
                                        <div class="inline-flex px-3 py-1 rounded-full text-sm font-medium
                                            {{ $project->status === 'active' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                               ($project->status === 'closed' ? 'bg-gray-100 text-gray-800 border border-gray-200' : 
                                               'bg-blue-100 text-blue-800 border border-blue-200') }}">
                                            {{ Str::title($project->status) }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-4 mt-3">
                                        <!-- Project Type -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-music mr-2"></i>
                                            <span>{{ Str::title($project->project_type) }}</span>
                                        </div>

                                        <!-- Budget -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            <span>{{ $project->budget == 0 ? 'Free' :
                                                '$'.number_format((float) $project->budget, 0) }}</span>
                                        </div>

                                        <!-- Deadline -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <span>{{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y')
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Project Stats -->
                                    <div class="mt-4 flex flex-wrap items-center justify-between">
                                        <div class="text-xs text-gray-500">
                                            <span>Created on {{ \Carbon\Carbon::parse($project->created_at)->format('M
                                                d, Y') }}</span>
                                        </div>

                                        <!-- Pitch Count Badge -->
                                        <div class="mt-2 md:mt-0 flex gap-2">
                                            <div
                                                class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-100 flex items-center">
                                                <i class="fas fa-user-edit text-indigo-400 mr-1"></i>
                                                <span>{{ $project->pitches->count() }} {{ Str::plural('Pitch',
                                                    $project->pitches->count()) }}</span>
                                            </div>
                                            
                                            @php
                                                $pendingCount = $project->pitches->where('status', \App\Models\Pitch::STATUS_PENDING)->count();
                                                $reviewCount = $project->pitches->where('status', \App\Models\Pitch::STATUS_READY_FOR_REVIEW)->count();
                                                $needsAttentionCount = $pendingCount + $reviewCount;
                                            @endphp
                                            
                                            @if($needsAttentionCount > 0)
                                            <div
                                                class="bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center">
                                                <i class="fas fa-bell text-red-400 mr-1"></i>
                                                <span>{{ $needsAttentionCount }} {{ Str::plural('Pitch', $needsAttentionCount) }} need{{ $needsAttentionCount === 1 ? 's' : '' }} attention</span>
                                            </div>
                                            @endif
                                            
                                            <!-- Payment Status Badges -->
                                            @php
                                                $completedPitches = $project->pitches->where('status', \App\Models\Pitch::STATUS_COMPLETED);
                                                $pendingPaymentCount = $completedPitches->whereIn('payment_status', [\App\Models\Pitch::PAYMENT_STATUS_PENDING, null])->count();
                                                $processingPaymentCount = $completedPitches->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)->count();
                                                $paidCount = $completedPitches->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->count();
                                                $failedPaymentCount = $completedPitches->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_FAILED)->count();
                                            @endphp
                                            
                                            <!-- Only show payment badges if there are payments to handle -->
                                            @if($project->budget > 0 && auth()->id() === $project->user_id)
                                                @if($pendingPaymentCount > 0)
                                                <div class="bg-amber-50 text-amber-700 text-xs px-2 py-1 rounded-full border border-amber-100 flex items-center mt-1">
                                                    <i class="fas fa-credit-card text-amber-400 mr-1"></i>
                                                    <span>{{ $pendingPaymentCount }} {{ Str::plural('Payment', $pendingPaymentCount) }} Required</span>
                                                </div>
                                                @endif
                                                
                                                @if($processingPaymentCount > 0)
                                                <div class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full border border-blue-100 flex items-center mt-1">
                                                    <i class="fas fa-spinner fa-spin text-blue-400 mr-1"></i>
                                                    <span>{{ $processingPaymentCount }} {{ Str::plural('Payment', $processingPaymentCount) }} Processing</span>
                                                </div>
                                                @endif
                                                
                                                @if($failedPaymentCount > 0)
                                                <div class="bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center mt-1">
                                                    <i class="fas fa-exclamation-circle text-red-400 mr-1"></i>
                                                    <span>{{ $failedPaymentCount }} {{ Str::plural('Payment', $failedPaymentCount) }} Failed</span>
                                                </div>
                                                @endif

                                                @if($paidCount > 0 && $paidCount === $completedPitches->count() && $completedPitches->count() > 0)
                                                <div class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full border border-green-100 flex items-center mt-1">
                                                    <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                                    <span>All Payments Complete</span>
                                                </div>
                                                @elseif($paidCount > 0)
                                                <div class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full border border-green-100 flex items-center mt-1">
                                                    <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                                    <span>{{ $paidCount }} {{ Str::plural('Payment', $paidCount) }} Complete</span>
                                                </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach


                    @endif
                </div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-4 pl-2">Your Pitches</h3>

                    @forelse($pitches as $pitch)
                    <div
                        class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
                        <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="block">
                            <div class="flex flex-col md:flex-row">
                                <!-- Project Image -->
                                <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                                    style="background-image: url('{{ $pitch->project->image_path ? $pitch->project->imageUrl : asset('images/default-project.jpg') }}');">
                                </div>

                                <!-- Project Info -->
                                <div class="p-4 flex-grow">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                                        <h4 class="text-lg font-semibold text-gray-800 mb-2 md:mb-0">{{
                                            $pitch->project->name }}</h4>
                                        <!-- Status Badge -->
                                        <div class="inline-flex px-3 py-1 rounded-full text-sm font-medium
                                            {{ $pitch->status === \App\Models\Pitch::STATUS_PENDING ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                               ($pitch->status === \App\Models\Pitch::STATUS_APPROVED ? 'bg-green-100 text-green-800 border border-green-200' : 
                                               ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                                               'bg-red-100 text-red-800 border border-red-200')) }}">
                                            {{ $pitch->getReadableStatusAttribute() }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-4 mt-3">
                                        <!-- Project Type -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-music mr-2"></i>
                                            <span>{{ Str::title($pitch->project->project_type) }}</span>
                                        </div>

                                        <!-- Budget -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            <span>{{ $pitch->project->budget == 0 ? 'Free' :
                                                '$'.number_format((float) $pitch->project->budget, 0) }}</span>
                                        </div>

                                        <!-- Deadline -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <span>{{ \Carbon\Carbon::parse($pitch->project->deadline)->format('M d, Y')
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Pitch Submission Date -->
                                    <div class="mt-4 flex flex-wrap items-center justify-between">
                                        <div class="text-xs text-gray-500">
                                            <span>Pitch submitted on {{
                                                \Carbon\Carbon::parse($pitch->created_at)->format('M d, Y') }}</span>

                                            @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                            <span class="mx-2">•</span>
                                            <span>Completed on {{ \Carbon\Carbon::parse($pitch->completed_at)->format('M
                                                d, Y') }}</span>
                                            @endif
                                        </div>

                                        <!-- Snapshot Count Badge -->
                                        <div class="mt-2 md:mt-0">
                                            <div
                                                class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-100 flex items-center">
                                                <i class="fas fa-history text-indigo-400 mr-1"></i>
                                                <span>{{ $pitch->snapshots->count() }} {{ Str::plural('Snapshot',
                                                    $pitch->snapshots->count()) }}</span>
                                            </div>
                                            
                                            @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                                            <div class="mt-1 bg-warning/20 text-amber-700 text-xs px-2 py-1 rounded-full border border-amber-200 flex items-center">
                                                <i class="fas fa-hourglass-half text-amber-500 mr-1"></i>
                                                <span>Pending Review</span>
                                            </div>
                                            @endif
                                            
                                            <!-- Payment Status for Pitches -->
                                            @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $pitch->project->budget > 0)
                                                @if(auth()->id() === $pitch->project->user_id)
                                                    @if(empty($pitch->payment_status) || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING)
                                                    <div class="mt-1 bg-amber-50 text-amber-700 text-xs px-2 py-1 rounded-full border border-amber-100 flex items-center">
                                                        <i class="fas fa-credit-card text-amber-500 mr-1"></i>
                                                        <span>Payment Required</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                                    <div class="mt-1 bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full border border-blue-100 flex items-center">
                                                        <i class="fas fa-spinner fa-spin text-blue-400 mr-1"></i>
                                                        <span>Payment Processing</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                    <div class="mt-1 bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full border border-green-100 flex items-center">
                                                        <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                                        <span>Paid</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
                                                    <div class="mt-1 bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center">
                                                        <i class="fas fa-exclamation-circle text-red-400 mr-1"></i>
                                                        <span>Payment Failed</span>
                                                    </div>
                                                    @endif
                                                @else
                                                    @if(empty($pitch->payment_status) || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING)
                                                    <div class="mt-1 bg-amber-50 text-amber-700 text-xs px-2 py-1 rounded-full border border-amber-100 flex items-center">
                                                        <i class="fas fa-clock text-amber-500 mr-1"></i>
                                                        <span>Awaiting Payment</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
                                                    <div class="mt-1 bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full border border-blue-100 flex items-center">
                                                        <i class="fas fa-spinner fa-spin text-blue-400 mr-1"></i>
                                                        <span>Payment Processing</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID)
                                                    <div class="mt-1 bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full border border-green-100 flex items-center">
                                                        <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                                        <span>Payment Received</span>
                                                    </div>
                                                    @elseif($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED)
                                                    <div class="mt-1 bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center">
                                                        <i class="fas fa-exclamation-circle text-red-400 mr-1"></i>
                                                        <span>Payment Failed</span>
                                                    </div>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="bg-base-200/50 rounded-lg p-8 text-center">
                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">You haven't submitted any pitches yet.</p>
                        <p class="text-gray-500 text-sm mt-2">Browse projects and submit pitches to collaborate with
                            other creators.</p>
                    </div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</div>

@endsection