@props(['project'])

@php
    // Determine current focus based on project status and workflow type
    $currentFocus = [
        'title' => '',
        'description' => '',
        'action' => null,
        'urgency' => 'normal', // normal, warning, urgent
        'icon' => 'check-circle',
        'progress' => null
    ];

    // Load pitches if not already loaded
    if (!$project->relationLoaded('pitches')) {
        $project->load(['pitches' => function($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }]);
    }

    $pitchCount = $project->pitches->count();
    $approvedPitch = $project->pitches->where('status', 'approved')->first();
    $completedPitch = $project->pitches->where('status', 'completed')->first();

    // Contest-specific logic
    if ($project->isContest()) {
        if (!$project->is_published) {
            $currentFocus = [
                'title' => 'Contest Ready to Launch',
                'description' => 'Publish your contest to start accepting entries',
                'action' => ['type' => 'wire', 'method' => 'publish', 'label' => 'Publish Contest'],
                'urgency' => 'warning',
                'icon' => 'megaphone',
                'progress' => 10
            ];
        } elseif ($project->deadline && now()->gt($project->deadline) && !$project->isJudgingFinalized()) {
            $currentFocus = [
                'title' => 'Ready for Judging',
                'description' => "Contest closed with {$pitchCount} entries. Time to select winners.",
                'action' => ['type' => 'route', 'name' => 'projects.contest.judging', 'params' => $project, 'label' => 'Start Judging'],
                'urgency' => 'urgent',
                'icon' => 'scale',
                'progress' => 80
            ];
        } elseif ($project->isJudgingFinalized()) {
            $currentFocus = [
                'title' => 'Contest Complete',
                'description' => 'Winners have been selected and prizes distributed',
                'action' => ['type' => 'route', 'name' => 'projects.contest.results', 'params' => $project, 'label' => 'View Results'],
                'urgency' => 'normal',
                'icon' => 'trophy',
                'progress' => 100
            ];
        } elseif ($project->deadline) {
            $daysLeft = now()->diffInDays($project->deadline, false);
            if ($daysLeft > 0) {
                $currentFocus = [
                    'title' => 'Contest Active',
                    'description' => "{$pitchCount} entries • {$daysLeft} " . ($daysLeft === 1 ? 'day' : 'days') . " remaining",
                    'action' => null,
                    'urgency' => $daysLeft <= 3 ? 'warning' : 'normal',
                    'icon' => 'clock',
                    'progress' => 50
                ];
            }
        }
    }
    // Client Management workflow
    elseif ($project->isClientManagement()) {
        if ($completedPitch) {
            $currentFocus = [
                'title' => 'Project Delivered',
                'description' => 'Work completed and delivered to client',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'check-circle',
                'progress' => 100
            ];
        } elseif ($approvedPitch) {
            $workSubmitted = $approvedPitch->status === 'submitted_for_review';
            $currentFocus = [
                'title' => $workSubmitted ? 'Client Review Pending' : 'Work in Progress',
                'description' => $workSubmitted ? 'Waiting for client feedback' : 'Producer is working on your project',
                'action' => $workSubmitted ? ['type' => 'wire', 'method' => 'resendClientInvite', 'label' => 'Resend Client Link'] : null,
                'urgency' => 'normal',
                'icon' => $workSubmitted ? 'eye' : 'cog',
                'progress' => $workSubmitted ? 85 : 60
            ];
        } else {
            $currentFocus = [
                'title' => 'Setup Client Review',
                'description' => 'Configure client access for project approval',
                'action' => ['type' => 'wire', 'method' => 'resendClientInvite', 'label' => 'Send Client Invite'],
                'urgency' => 'warning',
                'icon' => 'user-group',
                'progress' => 30
            ];
        }
    }
    // Standard and Direct Hire workflows
    else {
        if (!$project->is_published) {
            $currentFocus = [
                'title' => 'Ready to Publish',
                'description' => 'Publish your project to start receiving pitches',
                'action' => ['type' => 'wire', 'method' => 'publish', 'label' => 'Publish Project'],
                'urgency' => 'warning',
                'icon' => 'globe-alt',
                'progress' => 10
            ];
        } elseif ($completedPitch) {
            $requiresPayment = $project->budget > 0;
            $paymentStatus = $completedPitch->payment_status;
            
            if ($requiresPayment && in_array($paymentStatus, ['pending', 'failed', null])) {
                $currentFocus = [
                    'title' => 'Payment Required',
                    'description' => "Process \${$project->budget} payment to complete project",
                    'action' => ['type' => 'route', 'name' => 'projects.pitches.payment.overview', 'params' => [$project, $completedPitch], 'label' => 'Process Payment'],
                    'urgency' => 'urgent',
                    'icon' => 'credit-card',
                    'progress' => 95
                ];
            } else {
                $currentFocus = [
                    'title' => 'Project Complete',
                    'description' => "Completed by {$completedPitch->user->name}",
                    'action' => ['type' => 'route', 'name' => 'projects.pitches.show', 'params' => [$project, $completedPitch], 'label' => 'View Final Work'],
                    'urgency' => 'normal',
                    'icon' => 'check-circle',
                    'progress' => 100
                ];
            }
        } elseif ($approvedPitch) {
            $workSubmitted = $approvedPitch->status === 'submitted_for_review';
            $revisionRequested = $approvedPitch->status === 'revision_requested';
            
            if ($workSubmitted) {
                $currentFocus = [
                    'title' => 'Review Submitted Work',
                    'description' => 'Producer has submitted work for your approval',
                    'action' => ['type' => 'anchor', 'href' => '#pitch-review', 'label' => 'Review Work'],
                    'urgency' => 'urgent',
                    'icon' => 'eye',
                    'progress' => 85
                ];
            } elseif ($revisionRequested) {
                $currentFocus = [
                    'title' => 'Revisions in Progress',
                    'description' => 'Producer is implementing your requested changes',
                    'action' => null,
                    'urgency' => 'normal',
                    'icon' => 'arrow-path',
                    'progress' => 70
                ];
            } else {
                $fileCount = $approvedPitch->files->count();
                $currentFocus = [
                    'title' => 'Work in Progress',
                    'description' => $fileCount > 0 ? "{$fileCount} files uploaded • Producer is working" : 'Waiting for producer to begin work',
                    'action' => null,
                    'urgency' => 'normal',
                    'icon' => 'cog',
                    'progress' => $fileCount > 0 ? 60 : 40
                ];
            }
        } elseif ($pitchCount > 0) {
            $pendingPitches = $project->pitches->where('status', 'pending')->count();
            $currentFocus = [
                'title' => 'Review Pitches',
                'description' => "{$pendingPitches} pitch" . ($pendingPitches !== 1 ? 'es' : '') . " waiting for your review",
                'action' => ['type' => 'anchor', 'href' => '#pitches-section', 'label' => 'Review Pitches'],
                'urgency' => 'urgent',
                'icon' => 'clipboard-document-list',
                'progress' => 30
            ];
        } else {
            $daysSincePublished = $project->created_at->diffInDays(now());
            $currentFocus = [
                'title' => 'Waiting for Pitches',
                'description' => $daysSincePublished > 3 ? "Published {$daysSincePublished} days ago • Consider sharing" : 'Project is live and accepting pitches',
                'action' => ['type' => 'modal', 'modal' => 'shareProject', 'label' => 'Share Project'],
                'urgency' => $daysSincePublished > 7 ? 'warning' : 'normal',
                'icon' => 'share',
                'progress' => 20
            ];
        }
    }

    // Color scheme based on urgency
    $colorScheme = match($currentFocus['urgency']) {
        'urgent' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'icon' => 'text-red-600 dark:text-red-400',
            'title' => 'text-red-900 dark:text-red-100',
            'desc' => 'text-red-700 dark:text-red-300',
            'progress' => 'bg-red-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'title' => 'text-amber-900 dark:text-amber-100',
            'desc' => 'text-amber-700 dark:text-amber-300',
            'progress' => 'bg-amber-500'
        ],
        default => [
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'title' => 'text-blue-900 dark:text-blue-100',
            'desc' => 'text-blue-700 dark:text-blue-300',
            'progress' => 'bg-blue-500'
        ]
    };
@endphp

<!-- Current Focus Component -->
<flux:card class="{{ $colorScheme['bg'] }} {{ $colorScheme['border'] }} overflow-hidden">
        <div class="flex items-start gap-4">
            <!-- Icon -->
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-xl {{ $colorScheme['bg'] }} {{ $colorScheme['border'] }} flex items-center justify-center">
                    <flux:icon name="{{ $currentFocus['icon'] }}" class="w-6 h-6 {{ $colorScheme['icon'] }}" />
                </div>
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="lg" class="{{ $colorScheme['title'] }} mb-1">
                            {{ $currentFocus['title'] }}
                        </flux:heading>
                        <p class="text-sm {{ $colorScheme['desc'] }} mb-3">
                            {{ $currentFocus['description'] }}
                        </p>
                        
                        <!-- Progress Bar (if applicable) -->
                        @if($currentFocus['progress'])
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex-1 bg-white dark:bg-gray-800 rounded-full h-2 {{ $colorScheme['border'] }} border">
                                    <div class="{{ $colorScheme['progress'] }} h-full rounded-full transition-all duration-500" 
                                         style="width: {{ $currentFocus['progress'] }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $colorScheme['desc'] }} min-w-fit">
                                    {{ $currentFocus['progress'] }}%
                                </span>
                            </div>
                        @endif
                        
                        <!-- Action Button -->
                        @if($currentFocus['action'])
                            @php $action = $currentFocus['action']; @endphp
                            
                            @if($action['type'] === 'wire')
                                <flux:button 
                                    wire:click="{{ $action['method'] }}" 
                                    variant="primary" 
                                    size="sm"
                                    class="inline-flex items-center">
                                    {{ $action['label'] }}
                                </flux:button>
                            @elseif($action['type'] === 'route')
                                <flux:button 
                                    href="{{ route($action['name'], $action['params']) }}" 
                                    variant="primary" 
                                    size="sm"
                                    class="inline-flex items-center">
                                    {{ $action['label'] }}
                                </flux:button>
                            @elseif($action['type'] === 'anchor')
                                <flux:button 
                                    href="{{ $action['href'] }}" 
                                    variant="primary" 
                                    size="sm"
                                    class="inline-flex items-center">
                                    {{ $action['label'] }}
                                </flux:button>
                            @elseif($action['type'] === 'modal')
                                <flux:modal.trigger name="{{ $action['modal'] }}">
                                    <flux:button 
                                        variant="primary" 
                                        size="sm"
                                        icon="share"
                                        class="inline-flex items-center">
                                        {{ $action['label'] }}
                                    </flux:button>
                                </flux:modal.trigger>
                            @endif
                        @endif
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="flex flex-col gap-2 text-right min-w-fit">
                        @if(!$project->isClientManagement())
                            <div class="text-xs {{ $colorScheme['desc'] }}">
                                {{ $project->pitches->count() }} {{ $project->pitches->count() === 1 ? 'pitch' : 'pitches' }}
                            </div>
                        @endif
                        @if($project->deadline && !$project->isContest())
                            @php 
                                $daysToDeadline = now()->diffInDays($project->deadline, false);
                            @endphp
                            <div class="text-xs {{ $daysToDeadline < 0 ? 'text-red-600 dark:text-red-400' : $colorScheme['desc'] }}">
                                @if($daysToDeadline < 0)
                                    {{ abs($daysToDeadline) }} days overdue
                                @elseif($daysToDeadline === 0)
                                    Due today
                                @else
                                    {{ $daysToDeadline }} days left
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</flux:card>

<!-- Share Project Modal -->
<flux:modal name="shareProject" class="md:w-2xl" x-data="{ 
    copyToClipboard() {
        navigator.clipboard.writeText(this.$refs.projectUrl.value).then(() => {
            // You can add a toast notification here if you have a toast system
            console.log('URL copied to clipboard');
        });
    }
}">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Share Your Project</flux:heading>
            <flux:subheading>Help your project reach more producers by sharing it across platforms</flux:subheading>
        </div>

        <!-- Project URL -->
        <div class="space-y-2">
            <flux:field>
                <flux:label>Project URL</flux:label>
                <div class="flex gap-2">
                    <flux:input 
                        value="{{ route('projects.show', $project) }}" 
                        readonly 
                        x-ref="projectUrl"
                        class="flex-1" />
                    <flux:button 
                        variant="outline" 
                        x-on:click="copyToClipboard()"
                        icon="clipboard">
                        Copy
                    </flux:button>
                </div>
            </flux:field>
        </div>

        <!-- r/MixPitch Integration -->
        @if ($project->is_published)
            <div class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                            </svg>
                        </div>
                        <div>
                            <flux:heading size="sm" class="text-orange-900 dark:text-orange-100">r/MixPitch</flux:heading>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                @if ($project->hasBeenPostedToReddit())
                                    Already posted to our community
                                @else
                                    Share with our Reddit community
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($project->hasBeenPostedToReddit())
                        <flux:button 
                            href="{{ $project->getRedditUrl() }}" 
                            target="_blank"
                            variant="outline" 
                            size="sm">
                            View Post
                        </flux:button>
                    @else
                        <flux:button 
                            wire:click="postToReddit"
                            variant="primary" 
                            size="sm"
                            :disabled="$isPostingToReddit ?? false"
                            :loading="$isPostingToReddit ?? false">
                            @if($isPostingToReddit ?? false)
                                Posting...
                            @else
                                Post Now
                            @endif
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

        <!-- Social Sharing -->
        <div class="space-y-3">
            <flux:heading size="base">Share on Social Media</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                @php
                    $projectUrl = route('projects.show', $project);
                    $shareText = "Check out my music project: " . $project->name;
                    $twitterUrl = "https://twitter.com/intent/tweet?text=" . urlencode($shareText) . "&url=" . urlencode($projectUrl);
                    $facebookUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($projectUrl);
                    $linkedinUrl = "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($projectUrl);
                    $redditUrl = "https://reddit.com/submit?title=" . urlencode($shareText) . "&url=" . urlencode($projectUrl);
                @endphp
                
                <flux:button 
                    href="{{ $twitterUrl }}" 
                    target="_blank"
                    variant="outline" 
                    class="justify-start">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    Twitter
                </flux:button>
                
                <flux:button 
                    href="{{ $facebookUrl }}" 
                    target="_blank"
                    variant="outline" 
                    class="justify-start">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </flux:button>
                
                <flux:button 
                    href="{{ $linkedinUrl }}" 
                    target="_blank"
                    variant="outline" 
                    class="justify-start">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    LinkedIn
                </flux:button>
                
                <flux:button 
                    href="{{ $redditUrl }}" 
                    target="_blank"
                    variant="outline" 
                    class="justify-start">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                    </svg>
                    Reddit
                </flux:button>
            </div>
        </div>

        <!-- Close Button -->
        <div class="flex justify-end">
            <flux:button x-on:click="$flux.modal('shareProject').close()" variant="primary">
                Done
            </flux:button>
        </div>
    </div>
</flux:modal>