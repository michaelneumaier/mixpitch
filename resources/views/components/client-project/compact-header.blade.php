@props([
    'project',
    'pitch',
    'component' => null
])

@php
    // Get milestone and payment data
    $milestones = $pitch->milestones()->get();
    $milestoneTotal = $milestones->sum('amount');
    $milestonePaid = $milestones->where('payment_status', \App\Models\Pitch::PAYMENT_STATUS_PAID)->sum('amount');
    $baseBudget = $pitch->payment_amount > 0 ? $pitch->payment_amount : ($project->budget ?? 0);
    $totalBudget = $milestoneTotal > 0 ? $milestoneTotal : $baseBudget;
    
    // File approval data
    $approvedFiles = $pitch->files->where('client_approval_status', 'approved')->count();
    $totalFiles = $pitch->files->count();
    
    // Quick Stats data
    $messagesCount = $pitch->events->whereIn('event_type', ['client_comment', 'producer_comment'])->count();
    $lastActivity = $pitch->events->first()?->created_at_for_user?->diffForHumans();
    
    // Status and next action logic
    $nextAction = 'Awaiting updates';
    $statusColor = 'text-gray-600';
    
    if ($pitch->status === 'ready_for_review') {
        $nextAction = 'Client review needed';
        $statusColor = 'text-amber-600';
    } elseif ($pitch->status === 'client_revisions_requested') {
        $nextAction = 'Revisions needed';
        $statusColor = 'text-orange-600';
    } elseif ($pitch->status === 'completed') {
        $nextAction = 'Project completed';
        $statusColor = 'text-green-600';
    } elseif ($totalFiles === 0) {
        $nextAction = 'Upload deliverables';
        $statusColor = 'text-blue-600';
    } elseif ($approvedFiles < $totalFiles) {
        $nextAction = 'File approvals pending';
        $statusColor = 'text-amber-600';
    }
    
    // Calculate progress percentage
    $progressPercentage = $totalBudget > 0 ? round(($milestonePaid / max($totalBudget, 0.01)) * 100) : 0;
    $fileProgressPercentage = $totalFiles > 0 ? round(($approvedFiles / $totalFiles) * 100) : 0;
    
    // Calculate days remaining
    $daysRemaining = null;
    if ($project->deadline) {
        $deadline = \Carbon\Carbon::parse($project->deadline);
        $daysRemaining = now()->diffInDays($deadline, false);
    }
@endphp

<!-- Compact Client Management Header -->
<div class="bg-white/95 backdrop-blur-sm border border-gray-200/60 rounded-xl shadow-lg p-3 lg:p-4 mb-4 lg:mb-6 relative z-10">
    <!-- Mobile Layout -->
    <div class="block lg:hidden space-y-3">
        <!-- Project Title Row -->
        <div class="mb-2">
            <h1 class="font-bold text-gray-900 text-lg truncate">{{ $project->name }}</h1>
        </div>
        
        <!-- Client Info Row -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 min-w-0 flex-1">
                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-white text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-gray-900 truncate text-sm">
                        {{ $project->client_name ?: 'Client' }}
                    </div>
                    @if($project->client_email)
                        <div class="text-xs text-gray-500 truncate">{{ $project->client_email }}</div>
                    @endif
                </div>
            </div>
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $pitch->getStatusColorClass() }}">
                    {{ $pitch->readable_status }}
                </span>
            </div>
        </div>
        
        <!-- Progress Bars Row -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Payment Progress -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-2 border border-green-200/50">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-green-700">Payment</span>
                    <span class="text-xs font-bold text-green-900">{{ $progressPercentage }}%</span>
                </div>
                <div class="w-full bg-green-200 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                </div>
                <div class="text-xs text-green-800 font-medium mt-1">
                    ${{ number_format($milestonePaid, 0) }} / ${{ number_format($totalBudget, 0) }}
                </div>
            </div>
            
            <!-- Files Progress -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-2 border border-blue-200/50">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-blue-700">Files</span>
                    <span class="text-xs font-bold text-blue-900">{{ $approvedFiles }}/{{ $totalFiles }}</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $fileProgressPercentage }}%"></div>
                </div>
                <div class="text-xs text-blue-800 font-medium mt-1">
                    {{ $fileProgressPercentage }}% approved
                </div>
            </div>
        </div>
        
        <!-- Timeline & Quick Stats Row -->
        <div class="flex items-center justify-between text-xs">
            <div class="flex items-center space-x-1 {{ $statusColor }}">
                <i class="fas fa-clock"></i>
                <span class="font-medium">Next:</span>
                <span>{{ $nextAction }}</span>
            </div>
            <div class="flex items-center space-x-2">
                @if($messagesCount > 0)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-purple-100 text-purple-800">
                        <i class="fas fa-comments text-[10px] mr-1"></i>{{ $messagesCount }}
                    </span>
                @endif
                @if($daysRemaining !== null)
                    <div class="flex items-center space-x-1 text-gray-600">
                        <i class="fas fa-calendar-alt"></i>
                        @if($daysRemaining > 0)
                            <span class="font-medium">{{ $daysRemaining }}d left</span>
                        @elseif($daysRemaining === 0)
                            <span class="font-medium text-amber-600">Due today</span>
                        @else
                            <span class="font-medium text-red-600">{{ abs($daysRemaining) }}d overdue</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Additional Client Details (Expandable) -->
        @if($totalBudget > 0 || $lastActivity)
        <div class="border-t border-gray-100 pt-2 mt-2" x-data="{ showDetails: false }">
            <button @click="showDetails = !showDetails" class="w-full flex items-center justify-between text-xs text-gray-500 hover:text-gray-700 transition-colors">
                <span class="font-medium">Client Info & Activity</span>
                <i class="fas fa-chevron-down transition-transform" :class="showDetails ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="showDetails" x-collapse class="mt-2 space-y-1 text-xs">
                @if($totalBudget > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Project Value:</span>
                        <span class="font-medium text-gray-900">${{ number_format($totalBudget, 0) }}</span>
                    </div>
                @endif
                @if($lastActivity)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Activity:</span>
                        <span class="font-medium text-gray-900">{{ $lastActivity }}</span>
                    </div>
                @endif
                @if($project->client_email && $project->client_email !== $project->client_name)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Contact:</span>
                        <span class="font-medium text-gray-900 truncate ml-2">{{ $project->client_email }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif
        
        <!-- Action Button Row -->
        <div class="flex justify-center">
            @if($component)
                <button onclick="
                    const textarea = document.getElementById('newComment');
                    if (textarea) {
                        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => textarea.focus(), 800);
                    } else {
                        const messageSection = Array.from(document.querySelectorAll('h4')).find(h => h.textContent.includes('Send Message to Client'));
                        if (messageSection) messageSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                " 
                        class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105 w-full max-w-xs">
                    <i class="fas fa-envelope mr-2"></i>
                    <span>Send Message to Client</span>
                </button>
            @endif
        </div>
    </div>
    
    <!-- Desktop Layout -->
    <div class="hidden lg:block">
        <!-- Project Title -->
        <div class="mb-3">
            <h1 class="text-xl font-bold text-gray-900">{{ $project->name }}</h1>
        </div>
        
        <!-- Top Row: Client Info, Status, and Key Metrics -->
        <div class="flex items-center justify-between mb-3">
            <!-- Client Info -->
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div>
                    <div class="font-bold text-gray-900">{{ $project->client_name ?: 'Client' }}</div>
                    @if($project->client_email)
                        <div class="text-sm text-gray-600">{{ $project->client_email }}</div>
                    @endif
                </div>
            </div>
            
            <!-- Status and Progress -->
            <div class="flex items-center space-x-6">
                <!-- Payment Progress -->
                <div class="text-center">
                    <div class="text-lg font-bold text-green-900">${{ number_format($milestonePaid, 0) }}</div>
                    <div class="text-xs text-green-600">of ${{ number_format($totalBudget, 0) }} paid</div>
                    <div class="w-20 bg-green-200 rounded-full h-2 mt-1">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                    </div>
                </div>
                
                <!-- File Approvals -->
                <div class="text-center">
                    <div class="text-lg font-bold text-blue-900">{{ $approvedFiles }}/{{ $totalFiles }}</div>
                    <div class="text-xs text-blue-600">files approved</div>
                    <div class="w-20 bg-blue-200 rounded-full h-2 mt-1">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ $fileProgressPercentage }}%"></div>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium {{ $pitch->getStatusColorClass() }}">
                    {{ $pitch->readable_status }}
                </span>
            </div>
        </div>
        
        <!-- Bottom Row: Timeline, Next Action, Quick Stats, and Actions -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 text-sm">
                @if($daysRemaining !== null)
                    <div class="flex items-center space-x-1 text-gray-600">
                        <i class="fas fa-calendar-alt"></i>
                        <span>
                            @if($daysRemaining > 0)
                                <span class="font-medium">{{ $daysRemaining }}d left</span>
                            @elseif($daysRemaining === 0)
                                <span class="font-medium text-amber-600">Due today</span>
                            @else
                                <span class="font-medium text-red-600">{{ abs($daysRemaining) }}d overdue</span>
                            @endif
                        </span>
                    </div>
                @endif
                
                <div class="flex items-center space-x-1 {{ $statusColor }}">
                    <i class="fas fa-bullseye"></i>
                    <span><strong>Next:</strong> {{ $nextAction }}</span>
                </div>
                
                <!-- Quick Stats Badges -->
                <div class="flex items-center space-x-2">
                    @if($messagesCount > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-purple-100 text-purple-800 text-xs font-medium">
                            <i class="fas fa-comments text-xs mr-1"></i>{{ $messagesCount }} messages
                        </span>
                    @endif
                    @if($lastActivity)
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs" title="Last activity: {{ $lastActivity }}">
                            <i class="fas fa-clock text-xs mr-1"></i>{{ $lastActivity }}
                        </span>
                    @endif
                    @if($totalBudget > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-indigo-100 text-indigo-800 text-xs font-medium" title="Project value">
                            <i class="fas fa-dollar-sign text-xs mr-1"></i>${{ number_format($totalBudget, 0) }}
                        </span>
                    @endif
                </div>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="flex items-center space-x-2">
                @if($component)
                    <button onclick="
                        const textarea = document.getElementById('newComment');
                        if (textarea) {
                            textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            setTimeout(() => textarea.focus(), 800);
                        } else {
                            const messageSection = Array.from(document.querySelectorAll('h4')).find(h => h.textContent.includes('Send Message to Client'));
                            if (messageSection) messageSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    " 
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                        <i class="fas fa-envelope mr-2"></i>
                        Send Message to Client
                    </button>
                @endif
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                        <!-- Client Actions -->
                        <div class="px-3 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-100">Client Tools</div>
                        @if($component)
                            <button wire:click="resendClientInvite" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-paper-plane mr-2 text-purple-500"></i>Resend Client Invite
                            </button>
                            <button wire:click="previewClientPortal" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-external-link-alt mr-2 text-blue-500"></i>Preview Client Portal
                            </button>
                        @endif
                        <!-- Project Actions -->
                        <div class="px-3 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-t border-gray-100 mt-1">Project</div>
                        <a href="{{ route('projects.show', $project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                            <i class="fas fa-eye mr-2 text-blue-500"></i>View Project
                        </a>
                        <a href="{{ route('projects.edit', $project) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                            <i class="fas fa-edit mr-2 text-amber-500"></i>Edit Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>