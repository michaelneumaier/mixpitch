{{-- resources/views/dashboard/cards/_pitch_card.blade.php --}}
@php
    $pitchUrl = \App\Helpers\RouteHelpers::pitchUrl($pitch);
    $isClientProject = $pitch->project && $pitch->project->isClientManagement();
    $cardType = $isClientProject ? 'client' : 'pitch';
    $statusColor = match($pitch->status) {
        'pending' => 'from-yellow-100 to-amber-100 text-yellow-800 border-yellow-200/50',
        'accepted' => 'from-green-100 to-emerald-100 text-green-800 border-green-200/50',
        'rejected' => 'from-red-100 to-pink-100 text-red-800 border-red-200/50',
        'ready_for_review' => 'from-blue-100 to-indigo-100 text-blue-800 border-blue-200/50',
        'in_progress' => 'from-purple-100 to-indigo-100 text-purple-800 border-purple-200/50',
        'completed' => 'from-green-100 to-emerald-100 text-green-800 border-green-200/50',
        default => 'from-gray-100 to-gray-200 text-gray-800 border-gray-200/50'
    };
    $statusIcon = match($pitch->status) {
        'pending' => 'fa-clock',
        'accepted' => 'fa-check-circle',
        'rejected' => 'fa-times-circle',
        'ready_for_review' => 'fa-eye',
        'in_progress' => 'fa-spinner',
        'completed' => 'fa-check-double',
        default => 'fa-question-circle'
    };
@endphp

<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r {{ $isClientProject ? 'from-purple-500/20 via-pink-500/20 to-purple-500/20' : 'from-indigo-500/20 via-blue-500/20 to-purple-500/20' }} rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <a href="{{ $pitchUrl }}" class="relative block m-0.5 bg-white/95 backdrop-blur-sm rounded-2xl overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Enhanced Project Image --}}
            <div class="relative lg:w-64 h-48 lg:h-auto bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                @if($pitch->project && $pitch->project->image_path)
                    <img src="{{ $pitch->project->imageUrl }}" 
                         alt="{{ $pitch->project->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full bg-gradient-to-br {{ $isClientProject ? 'from-purple-100 via-pink-100 to-purple-100' : 'from-indigo-100 via-blue-100 to-purple-100' }} flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas {{ $isClientProject ? 'fa-briefcase' : 'fa-paper-plane' }} text-4xl {{ $isClientProject ? 'text-purple-400/60' : 'text-indigo-400/60' }} mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">{{ $pitch->project ? $pitch->project->name : 'Pitch' }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Workflow Type Badge -->
                <div class="absolute bottom-4 left-4">
                    @if($pitch->project)
                        @php
                            // For pitches, show "Pitch" as the primary type
                            $workflowConfig = [
                                'standard' => ['bg' => 'bg-indigo-100/90', 'text' => 'text-indigo-800', 'icon' => 'fa-paper-plane', 'label' => 'Pitch'],
                                'contest' => ['bg' => 'bg-purple-100/90', 'text' => 'text-purple-800', 'icon' => 'fa-paper-plane', 'label' => 'Pitch'],
                                'direct_hire' => ['bg' => 'bg-green-100/90', 'text' => 'text-green-800', 'icon' => 'fa-paper-plane', 'label' => 'Pitch'],
                                'client_management' => ['bg' => 'bg-orange-100/90', 'text' => 'text-orange-800', 'icon' => 'fa-paper-plane', 'label' => 'Pitch'],
                            ];
                            $workflowType = $pitch->project->workflow_type ?? 'standard';
                            $workflowStyle = $workflowConfig[$workflowType] ?? $workflowConfig['standard'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }} border border-white/20 backdrop-blur-sm shadow-lg">
                            <i class="fas {{ $workflowStyle['icon'] }} mr-2"></i>{{ $workflowStyle['label'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-gradient-to-r {{ $statusColor }} backdrop-blur-sm shadow-lg">
                        <i class="fas {{ $statusIcon }} mr-2"></i>
                        {{ Str::title(str_replace('_', ' ', $pitch->status)) }}
                    </span>
                </div>
            </div>

            {{-- Enhanced Pitch Info --}}
            <div class="flex-1 p-6 lg:p-8">
                <!-- Header Section -->
                <div class="mb-6">
                    <h3 class="text-xl lg:text-2xl font-bold text-gray-900 mb-2 group-hover:text-{{ $isClientProject ? 'purple' : 'indigo' }}-600 transition-colors duration-200">
                        {{ $pitch->project ? $pitch->project->name : 'Pitch' }}
                    </h3>
                    <div class="flex items-center text-gray-600 mb-4">
                        <div class="flex items-center justify-center w-6 h-6 {{ $isClientProject ? 'bg-purple-100' : 'bg-indigo-100' }} rounded-full mr-2">
                            <i class="fas fa-layer-group {{ $isClientProject ? 'text-purple-600' : 'text-indigo-600' }} text-xs"></i>
                        </div>
                        <span class="text-sm font-medium">{{ $pitch->project ? $pitch->project->readableWorkflowTypeAttribute : 'Pitch' }}</span>
                    </div>
                </div>
                
                {{-- Enhanced Key Details Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @if($pitch->amount && $pitch->amount > 0)
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Amount</span>
                            </div>
                            <div class="text-sm font-bold text-green-900">{{ Number::currency($pitch->amount, 'USD') }}</div>
                        </div>
                    @endif

                    @if($pitch->status === 'completed' && $pitch->project && $pitch->project->budget > 0)
                        <!-- Payment Status for Completed Pitches -->
                        @php
                            $paymentStatus = $pitch->payment_status;
                            $projectBudget = $pitch->project->budget;
                        @endphp
                        
                        @if($paymentStatus === 'paid')
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                    <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Payment Received</span>
                                </div>
                                <div class="text-sm font-bold text-green-900">{{ Number::currency($projectBudget, 'USD') }}</div>
                                <div class="text-xs text-green-600 mt-1">Paid & Complete</div>
                            </div>
                        @elseif($paymentStatus === 'pending' || $paymentStatus === 'failed' || empty($paymentStatus))
                            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-clock text-amber-600 mr-2"></i>
                                    <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Payment Pending</span>
                                </div>
                                <div class="text-sm font-bold text-amber-900">{{ Number::currency($projectBudget, 'USD') }}</div>
                                <div class="text-xs text-amber-600 mt-1">
                                    @if($paymentStatus === 'failed')
                                        Payment Failed
                                    @else
                                        Awaiting Payment
                                    @endif
                                </div>
                            </div>
                        @elseif($paymentStatus === 'processing')
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                                    <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Processing Payment</span>
                                </div>
                                <div class="text-sm font-bold text-blue-900">{{ Number::currency($projectBudget, 'USD') }}</div>
                                <div class="text-xs text-blue-600 mt-1">Processing...</div>
                            </div>
                        @endif
                    @endif
                    
                    @if($pitch->project && ($pitch->project->isContest() ? $pitch->project->submission_deadline : $pitch->project->deadline))
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calendar text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">{{ $pitch->project->isContest() ? 'Submission Deadline' : 'Deadline' }}</span>
                            </div>
                            @if($pitch->project->isContest())
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$pitch->project->submission_deadline" :user="$pitch->project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$pitch->project->submission_deadline" :user="$pitch->project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                                @php
                                    $deadlineField = $pitch->project->submission_deadline;
                                    $daysUntilDeadline = auth()->user() ? 
                                        auth()->user()->now()->diffInDays($deadlineField, false) : 
                                        now()->diffInDays($deadlineField, false);
                                @endphp
                            @else
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$pitch->project->deadline" :user="$pitch->project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$pitch->project->deadline" :user="$pitch->project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                                @php
                                    $deadlineField = $pitch->project->deadline;
                                    $daysUntilDeadline = auth()->user() ? 
                                        auth()->user()->now()->diffInDays($deadlineField, false) : 
                                        now()->diffInDays($deadlineField, false);
                                @endphp
                            @endif
                            <div class="text-xs text-purple-600 mt-1">
                                @if($daysUntilDeadline < 0)
                                    {{ abs($daysUntilDeadline) }} days overdue
                                @elseif($daysUntilDeadline === 0)
                                    Due today
                                @else
                                    {{ $daysUntilDeadline }} days left
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($pitch->project && $pitch->project->user)
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Project Owner</span>
                            </div>
                            <div class="text-sm font-bold text-blue-900">
                                @if(isset($components) && isset($components['user-link']))
                                    <x-user-link :user="$pitch->project->user" />
                                @else
                                    {{ $pitch->project->user->name }}
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($pitch->project && $pitch->project->client_email)
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-tie text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Client</span>
                            </div>
                            <div class="text-sm font-bold text-purple-900">{{ $pitch->project->client_name ?? $pitch->project->client_email }}</div>
                        </div>
                    @endif

                    @if($pitch->delivery_date)
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-truck text-amber-600 mr-2"></i>
                                <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Delivery</span>
                            </div>
                            <div class="text-sm font-bold text-amber-900">{{ \Carbon\Carbon::parse($pitch->delivery_date)->format('M d, Y') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Enhanced Stats and Action Indicators --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Updated <x-datetime :date="$pitch->updated_at" relative="true" /></span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        @if($pitch->status === 'pending')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border border-yellow-200/50 shadow-sm animate-pulse">
                                <i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>
                                <span>Awaiting Response</span>
                            </div>
                        @elseif($pitch->status === 'ready_for_review')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm animate-pulse">
                                <i class="fas fa-eye text-blue-600 mr-2"></i>
                                <span>Ready for Review</span>
                            </div>
                        @elseif($pitch->status === 'in_progress')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-800 border border-purple-200/50 shadow-sm">
                                <i class="fas fa-cog fa-spin text-purple-600 mr-2"></i>
                                <span>In Progress</span>
                            </div>
                        @elseif($pitch->status === 'accepted')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span>Accepted</span>
                            </div>
                        @elseif($pitch->status === 'completed')
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                <i class="fas fa-check-double text-green-600 mr-2"></i>
                                <span>Completed</span>
                            </div>
                            
                            @if($pitch->project && $pitch->project->budget > 0)
                                <!-- Payment Status Badge for Completed Pitches -->
                                @php $paymentStatus = $pitch->payment_status; @endphp
                                @if($paymentStatus === 'paid')
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                        <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                        <span>Paid</span>
                                    </div>
                                @elseif($paymentStatus === 'pending' || $paymentStatus === 'failed' || empty($paymentStatus))
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800 border border-amber-200/50 shadow-sm animate-pulse">
                                        <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                                        <span>
                                            @if($paymentStatus === 'failed')
                                                Payment Failed
                                            @else
                                                Payment Due
                                            @endif
                                        </span>
                                    </div>
                                @elseif($paymentStatus === 'processing')
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm">
                                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                                        <span>Processing Payment</span>
                                    </div>
                                @endif
                            @endif
                        @endif
                        
                        @if($pitch->files->count() > 0)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200/50 shadow-sm">
                                <i class="fas fa-paperclip text-gray-600 mr-2"></i>
                                <span>{{ $pitch->files->count() }} {{ Str::plural('File', $pitch->files->count()) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 