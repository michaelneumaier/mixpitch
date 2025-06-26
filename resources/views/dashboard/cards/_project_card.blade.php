{{-- resources/views/dashboard/cards/_project_card.blade.php --}}
@php
    $projectUrl = route('projects.manage', $project);
    $needsAttentionCount = $project->pitches->whereIn('status', [
        \App\Models\Pitch::STATUS_PENDING,
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW
    ])->count();
@endphp

<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Gradient Border Effect -->
    @if($project->isContest())
        <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/20 via-orange-500/20 to-red-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 via-purple-500/20 to-indigo-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    @endif
    
    <a href="{{ $projectUrl }}" class="relative block m-0.5 bg-white/95 backdrop-blur-sm rounded-2xl overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Enhanced Project Image --}}
            <div class="relative lg:w-64 h-48 lg:h-auto bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                @if($project->image_path)
                    <img src="{{ $project->imageUrl }}" 
                         alt="{{ $project->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-blue-100 via-purple-100 to-indigo-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-music text-4xl text-blue-400/60 mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">{{ $project->name }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Workflow Type Badge -->
                <div class="absolute bottom-4 left-4">
                    @php
                        $workflowConfig = [
                            'standard' => ['bg' => 'bg-blue-100/90', 'text' => 'text-blue-800', 'icon' => 'fa-users', 'label' => 'Standard'],
                            'contest' => ['bg' => 'bg-purple-100/90', 'text' => 'text-purple-800', 'icon' => 'fa-trophy', 'label' => 'Contest'],
                            'direct_hire' => ['bg' => 'bg-green-100/90', 'text' => 'text-green-800', 'icon' => 'fa-user-check', 'label' => 'Direct Hire'],
                            'client_management' => ['bg' => 'bg-orange-100/90', 'text' => 'text-orange-800', 'icon' => 'fa-briefcase', 'label' => 'Client Project'],
                        ];
                        $workflowType = $project->workflow_type ?? 'standard';
                        $workflowStyle = $workflowConfig[$workflowType] ?? $workflowConfig['standard'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold {{ $workflowStyle['bg'] }} {{ $workflowStyle['text'] }} border border-white/20 backdrop-blur-sm shadow-lg">
                        <i class="fas {{ $workflowStyle['icon'] }} mr-2"></i>{{ $workflowStyle['label'] }}
                    </span>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold {{ $project->getStatusColorClass() }} border border-white/20 backdrop-blur-sm shadow-lg">
                        <div class="w-2 h-2 rounded-full {{ $project->status === 'open' ? 'bg-green-500' : ($project->status === 'completed' ? 'bg-purple-500' : 'bg-gray-400') }} mr-2"></div>
                        {{ Str::title(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>
            </div>

            {{-- Enhanced Project Info --}}
            <div class="flex-1 p-6 lg:p-8">
                <!-- Header Section -->
                <div class="mb-6">
                    <h3 class="text-xl lg:text-2xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">
                        {{ $project->name }}
                    </h3>
                    <div class="flex items-center text-gray-600 mb-4">
                        <div class="flex items-center justify-center w-6 h-6 bg-purple-100 rounded-full mr-2">
                            <i class="fas fa-layer-group text-purple-600 text-xs"></i>
                        </div>
                        <span class="text-sm font-medium">{{ $project->readableWorkflowTypeAttribute }}</span>
                    </div>
                </div>
                
                {{-- Enhanced Key Details Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @if($project->isContest())
                        @if($project->hasPrizes())
                            <!-- New Contest Prize System -->
                            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-4 border border-amber-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-trophy text-amber-600 mr-2"></i>
                                    <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Contest Prizes</span>
                                </div>
                                <div class="text-sm font-bold text-amber-900">
                                    @php $totalCash = $project->getTotalPrizeBudget(); @endphp
                                    @if($totalCash > 0)
                                        ${{ number_format($totalCash) }}+
                                    @else
                                        {{ $project->contestPrizes()->count() }} Prize{{ $project->contestPrizes()->count() > 1 ? 's' : '' }}
                                    @endif
                                </div>
                                <div class="text-xs text-amber-600 mt-1">{{ $project->contestPrizes()->count() }} tiers</div>
                            </div>
                        @elseif($project->prize_amount > 0)
                            <!-- Legacy Prize Display -->
                            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl p-4 border border-yellow-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                                    <span class="text-xs font-medium text-yellow-700 uppercase tracking-wide">Prize (Legacy)</span>
                                </div>
                                <div class="text-sm font-bold text-yellow-900">{{ Number::currency($project->prize_amount, $project->prize_currency) }}</div>
                            </div>
                        @else
                            <!-- No Prizes Set -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-gift text-gray-500 mr-2"></i>
                                    <span class="text-xs font-medium text-gray-600 uppercase tracking-wide">No Prizes</span>
                                </div>
                                <div class="text-sm font-bold text-gray-700">Contest</div>
                            </div>
                        @endif
                    @elseif($project->budget > 0)
                        @php
                            // Check if project has completed pitch and payment status
                            $completedPitch = $project->pitches->where('status', 'completed')->first();
                            $requiresPayment = $project->budget > 0;
                            $paymentStatus = $completedPitch ? $completedPitch->payment_status : null;
                        @endphp
                        
                        @if($completedPitch && $requiresPayment)
                            <!-- Payment Status for Completed Projects -->
                            @if($paymentStatus === 'paid')
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Payment Complete</span>
                                    </div>
                                    <div class="text-sm font-bold text-green-900">{{ Number::currency($project->budget, 'USD') }}</div>
                                    <div class="text-xs text-green-600 mt-1">Paid & Complete</div>
                                </div>
                            @elseif($paymentStatus === 'pending' || $paymentStatus === 'failed' || empty($paymentStatus))
                                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-200/50">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                                        <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Payment Required</span>
                                    </div>
                                    <div class="text-sm font-bold text-amber-900">{{ Number::currency($project->budget, 'USD') }}</div>
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
                                    <div class="text-sm font-bold text-blue-900">{{ Number::currency($project->budget, 'USD') }}</div>
                                    <div class="text-xs text-blue-600 mt-1">Processing...</div>
                                </div>
                            @endif
                        @else
                            <!-- Standard Budget Display for Non-Completed Projects -->
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200/50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                    <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Budget</span>
                                </div>
                                <div class="text-sm font-bold text-green-900">{{ Number::currency($project->budget, 'USD') }}</div>
                                <div class="text-xs text-green-600 mt-1">
                                    @if($project->status === 'completed')
                                        Project Complete
                                    @else
                                        Available
                                    @endif
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-heart text-blue-600 mr-2"></i>
                                <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Type</span>
                            </div>
                            <div class="text-sm font-bold text-blue-900">Free Project</div>
                            <div class="text-xs text-blue-600 mt-1">No payment required</div>
                        </div>
                    @endif
                    
                    @if($project->isContest() ? $project->submission_deadline : $project->deadline)
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calendar text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">{{ $project->isContest() ? 'Submission Deadline' : 'Deadline' }}</span>
                            </div>
                            @if($project->isContest())
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                                @php
                                    $deadlineField = $project->submission_deadline;
                                    $userDate = auth()->user() ? auth()->user()->formatDate($deadlineField) : $deadlineField->format('M d, Y');
                                    $daysUntilDeadline = auth()->user() ? 
                                        auth()->user()->now()->diffInDays($deadlineField, false) : 
                                        now()->diffInDays($deadlineField, false);
                                @endphp
                            @else
                                <div class="text-sm font-bold text-purple-900">
                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="M d, Y" />
                                </div>
                                <div class="text-xs text-purple-600 mt-1">
                                    <x-datetime :date="$project->deadline" :user="$project->user" :convertToViewer="true" format="g:i A T" />
                                </div>
                                @php
                                    $deadlineField = $project->deadline;
                                    $userDate = auth()->user() ? auth()->user()->formatDate($deadlineField) : $deadlineField->format('M d, Y');
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

                    @if($project->targetProducer)
                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-4 border border-indigo-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-check text-indigo-600 mr-2"></i>
                                <span class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Assigned</span>
                            </div>
                            <div class="text-sm font-bold text-indigo-900">
                                @if(isset($components) && isset($components['user-link']))
                                    <x-user-link :user="$project->targetProducer" />
                                @else
                                    {{ $project->targetProducer->name }}
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($project->client_email)
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-200/50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-tie text-purple-600 mr-2"></i>
                                <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Client</span>
                            </div>
                            <div class="text-sm font-bold text-purple-900">{{ $project->client_name ?? $project->client_email }}</div>
                        </div>
                    @endif
                </div>

                {{-- Enhanced Stats and Attention Indicators --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Updated <x-datetime :date="$project->updated_at" relative="true" /></span>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        @if($project->pitches->count() > 0)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-800 border border-indigo-200/50 shadow-sm">
                                <i class="fas fa-paper-plane text-indigo-600 mr-2"></i>
                                <span>{{ $project->pitches->count() }} {{ Str::plural('Pitch', $project->pitches->count()) }}</span>
                            </div>
                        @endif
                        
                        @if($needsAttentionCount > 0)
                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-red-100 to-pink-100 text-red-800 border border-red-200/50 shadow-sm animate-pulse">
                                <i class="fas fa-bell text-red-600 mr-2"></i>
                                <span>{{ $needsAttentionCount }} need{{ $needsAttentionCount === 1 ? 's' : '' }} attention</span>
                            </div>
                        @endif

                        @if($project->status === 'completed')
                            @php
                                $completedPitch = $project->pitches->where('status', 'completed')->first();
                                $requiresPayment = $project->budget > 0;
                                $paymentStatus = $completedPitch ? $completedPitch->payment_status : null;
                            @endphp
                            
                            @if($requiresPayment && $completedPitch)
                                <!-- Payment Status Badge for Completed Projects -->
                                @if($paymentStatus === 'paid')
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        <span>Payment Complete</span>
                                    </div>
                                @elseif($paymentStatus === 'pending' || $paymentStatus === 'failed' || empty($paymentStatus))
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800 border border-amber-200/50 shadow-sm animate-pulse">
                                        <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                                        <span>
                                            @if($paymentStatus === 'failed')
                                                Payment Failed
                                            @else
                                                Payment Required
                                            @endif
                                        </span>
                                    </div>
                                @elseif($paymentStatus === 'processing')
                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm">
                                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                                        <span>Processing Payment</span>
                                    </div>
                                @endif
                            @elseif(!$requiresPayment)
                                <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200/50 shadow-sm">
                                    <i class="fas fa-heart text-blue-600 mr-2"></i>
                                    <span>Free Project Complete</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 