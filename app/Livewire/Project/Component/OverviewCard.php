<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Services\CommunicationService;
use App\Services\WorkSessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class OverviewCard extends Component
{
    public Pitch $pitch;

    public Project $project;

    public array $workflowColors = [];

    // v2 properties
    public bool $showAllSessions = false;

    public function mount(Pitch $pitch, Project $project, array $workflowColors = [])
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    /**
     * Get current state context with status-specific information
     */
    public function getCurrentStateContextProperty(): array
    {
        $status = $this->pitch->status;

        return match ($status) {
            Pitch::STATUS_IN_PROGRESS => [
                'icon' => 'cog-6-tooth',
                'title' => 'Getting Started',
                'description' => 'Upload your files and submit for client review when ready',
                'progress' => 20,
                'urgency' => 'normal',
                'next_steps' => $this->getNextSteps($status),
            ],
            Pitch::STATUS_READY_FOR_REVIEW => [
                'icon' => 'eye',
                'title' => 'Awaiting Client Review',
                'description' => $this->getReviewDescription(),
                'progress' => 75,
                'urgency' => 'normal',
                'next_steps' => $this->getNextSteps($status),
            ],
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED => [
                'icon' => 'pencil',
                'title' => 'Revisions Requested',
                'description' => 'Review client feedback and upload your revised files',
                'progress' => 50,
                'urgency' => 'warning',
                'next_steps' => $this->getNextSteps($status),
            ],
            Pitch::STATUS_APPROVED => [
                'icon' => 'check-badge',
                'title' => 'Client Approved',
                'description' => 'Your work has been approved - awaiting payment completion',
                'progress' => 90,
                'urgency' => 'info',
                'next_steps' => $this->getNextSteps($status),
            ],
            Pitch::STATUS_COMPLETED => [
                'icon' => $this->getCompletedIcon(),
                'title' => $this->getCompletedTitle(),
                'description' => $this->getCompletionDescription(),
                'progress' => 100,
                'urgency' => $this->getCompletedUrgency(),
                'next_steps' => $this->getCompletedNextSteps(),
            ],
            default => [
                'icon' => 'information-circle',
                'title' => 'In Progress',
                'description' => 'Working on your project',
                'progress' => 30,
                'urgency' => 'normal',
                'next_steps' => $this->getNextSteps($status),
            ],
        };
    }

    /**
     * Get project metrics (total files, days, submissions, revisions)
     */
    public function getProjectMetricsProperty(): array
    {
        $clientFiles = $this->project->files()->count();
        $producerFiles = $this->pitch->files()->count();
        $snapshots = $this->pitch->snapshots;

        return [
            'total_files' => $clientFiles + $producerFiles,
            'client_files_count' => $clientFiles,
            'producer_files_count' => $producerFiles,
            'days_active' => now()->diffInDays($this->project->created_at),
            'submission_count' => $snapshots->count(),
            'revision_round' => $this->pitch->revisions_used ?? 0,
            'included_revisions' => $this->pitch->included_revisions ?? 0,
        ];
    }

    /**
     * Get client engagement information
     */
    public function getClientEngagementProperty(): array
    {
        return [
            'client_email' => $this->project->client_email,
            'client_name' => $this->project->client_name,
            'portal_status' => $this->getPortalLinkStatus(),
            'last_client_action' => $this->getLastClientAction(),
        ];
    }

    /**
     * Get payment status for completed projects
     */
    public function getPaymentStatusProperty(): ?array
    {
        // Only relevant for completed or approved status
        if (! in_array($this->pitch->status, [Pitch::STATUS_COMPLETED, Pitch::STATUS_APPROVED])) {
            return null;
        }

        $milestones = $this->pitch->milestones;

        if ($milestones->isEmpty()) {
            return null; // No payment tracking
        }

        $totalMilestones = $milestones->count();
        $paidMilestones = $milestones->where('payment_status', Pitch::PAYMENT_STATUS_PAID);
        $paidCount = $paidMilestones->count();
        $processingCount = $milestones->where('payment_status', Pitch::PAYMENT_STATUS_PROCESSING)->count();

        $totalAmount = $milestones->sum('amount');
        $paidAmount = $paidMilestones->sum('amount');
        $outstandingAmount = $totalAmount - $paidAmount;
        $paidPercentage = $totalAmount > 0 ? ($paidAmount / $totalAmount * 100) : 0;
        $isPaidInFull = $paidCount === $totalMilestones;

        return [
            'total_milestones' => $totalMilestones,
            'paid_count' => $paidCount,
            'processing_count' => $processingCount,
            'pending_count' => $totalMilestones - $paidCount - $processingCount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'paid_percentage' => min($paidPercentage, 100),
            'is_paid_in_full' => $isPaidInFull,
        ];
    }

    /**
     * Get recent milestone events (major events only)
     */
    public function getRecentMilestonesProperty(): Collection
    {
        $entries = collect();

        // Get major events only
        $events = $this->pitch->events()
            ->whereIn('event_type', [
                PitchEvent::TYPE_CLIENT_APPROVED,
                PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
                PitchEvent::TYPE_STATUS_CHANGE,
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get snapshots (submissions)
        $snapshots = $this->pitch->snapshots()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Process events
        foreach ($events as $event) {
            if ($this->isMajorMilestone($event)) {
                $entries->push($this->formatEventEntry($event));
            }
        }

        // Process snapshots (submissions are always major milestones)
        foreach ($snapshots as $snapshot) {
            $entries->push($this->formatSnapshotEntry($snapshot));
        }

        // Sort by timestamp descending and limit to 5
        return $entries->sortByDesc('timestamp')->take(5)->values();
    }

    /**
     * Get quick action buttons based on status
     */
    public function getQuickActionsProperty(): array
    {
        $actions = [];
        $status = $this->pitch->status;
        $hasFiles = $this->pitch->files()->count() > 0;

        // Status-specific actions only
        if ($status === Pitch::STATUS_IN_PROGRESS) {
            $actions[] = [
                'label' => 'Upload Files',
                'action' => 'switchToFilesTab',
                'icon' => 'arrow-up-tray',
                'variant' => 'primary',
            ];

            if ($hasFiles) {
                $actions[] = [
                    'label' => 'Submit for Review',
                    'action' => 'submitForReview',
                    'icon' => 'paper-airplane',
                    'variant' => 'primary',
                ];
            }
        }

        if ($status === Pitch::STATUS_READY_FOR_REVIEW) {
            $actions[] = [
                'label' => 'Recall Submission',
                'action' => 'recallSubmission',
                'icon' => 'arrow-uturn-left',
                'variant' => 'outline',
            ];
        }

        if ($status === Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
            $actions[] = [
                'label' => 'Upload Revised Files',
                'action' => 'switchToFilesTab',
                'icon' => 'arrow-up-tray',
                'variant' => 'primary',
            ];
        }

        return $actions;
    }

    /**
     * Get next steps based on status
     */
    protected function getNextSteps(string $status): array
    {
        return match ($status) {
            Pitch::STATUS_IN_PROGRESS => [
                'Upload your audio files in the "Your Files" tab',
                'Review client requirements and project details',
                'Submit your work when ready for client review',
            ],
            Pitch::STATUS_READY_FOR_REVIEW => [
                'Wait for client to review your submission',
                'Client portal link has been sent to: '.$this->project->client_email,
                'You can recall this submission if you need to make changes',
            ],
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED => [
                'Review the client feedback below carefully',
                'Upload revised files addressing their requests',
                'Resubmit when your revisions are complete',
            ],
            Pitch::STATUS_APPROVED => [
                'Your work has been approved by the client',
                'Awaiting payment processing',
                'You will be notified when payment is complete',
            ],
            default => [],
        };
    }

    /**
     * Get client feedback for CLIENT_REVISIONS_REQUESTED status
     */
    public function getClientFeedbackProperty(): ?array
    {
        if ($this->pitch->status !== Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
            return null;
        }

        $event = $this->pitch->events()
            ->where('event_type', PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED)
            ->latest()
            ->first();

        if (! $event) {
            return null;
        }

        return [
            'feedback' => $event->comment,
            'timestamp' => $event->created_at,
            'revision_round' => $this->pitch->revisions_used ?? 1,
        ];
    }

    /**
     * Get communication summary with pending actions (v2)
     */
    public function getCommunicationSummaryProperty(): array
    {
        $communicationService = app(CommunicationService::class);
        $pendingActions = $communicationService->getPendingActions($this->pitch, Auth::id());

        // Get latest message for preview
        $latestMessage = null;
        if ($pendingActions->where('type', 'unread_messages')->isNotEmpty()) {
            $messages = $communicationService->getMessages($this->pitch, 1);
            $latestMessage = $messages->first();
        }

        return [
            'pending_actions' => $pendingActions,
            'latest_message' => $latestMessage,
            'unread_count' => $communicationService->getUnreadCount($this->pitch, Auth::id()),
            'has_pending_communication' => $pendingActions->isNotEmpty(),
        ];
    }

    /**
     * Get unresolved file comments with details for display
     */
    public function getUnresolvedFileCommentsProperty(): array
    {
        $filesWithComments = $this->pitch->files()
            ->with(['comments' => function ($query) {
                $query->where('resolved', false)
                    ->with('user')
                    ->orderBy('timestamp', 'asc');
            }])
            ->get()
            ->filter(fn ($file) => $file->comments->isNotEmpty());

        $totalCount = $filesWithComments->sum(fn ($file) => $file->comments->count());

        $files = $filesWithComments->map(function ($file) {
            return [
                'file_id' => $file->id,
                'file_name' => $file->original_file_name ?? $file->file_name ?? 'Unnamed File',
                'comments' => $file->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'preview' => \Illuminate\Support\Str::limit($comment->comment, 100),
                        'timestamp' => $comment->formatted_timestamp ?? '0:00',
                        'author' => $comment->getAuthorName(),
                        'is_client' => $comment->isClientComment(),
                        'created_at' => $comment->created_at,
                    ];
                })->toArray(),
            ];
        })->values()->toArray();

        return [
            'count' => $totalCount,
            'files' => $files,
        ];
    }

    /**
     * Get work session status and data (v2)
     */
    public function getWorkSessionDataProperty(): array
    {
        $sessionService = app(WorkSessionService::class);
        $activeSession = $sessionService->getActiveSession($this->pitch, Auth::user());
        $recentSessions = $sessionService->getRecentSessions($this->pitch, days: 30, visibleOnly: true);
        $totalWorkTime = $sessionService->getTotalWorkTime($this->pitch, visibleOnly: true);

        return [
            'active_session' => $activeSession,
            'recent_sessions' => $recentSessions,
            'total_work_time' => $totalWorkTime,
            'total_work_time_formatted' => $sessionService->getFormattedTotalWorkTime($this->pitch),
            'has_sessions' => $recentSessions->isNotEmpty() || $activeSession !== null,
        ];
    }

    /**
     * Get portal link status
     */
    protected function getPortalLinkStatus(): string
    {
        // Check for the most recent submission
        $latestSnapshot = $this->pitch->snapshots()
            ->latest()
            ->first();

        if (! $latestSnapshot) {
            return 'No portal link yet - submit your first version';
        }

        // Portal links are valid for 7 days
        $expiryDate = $latestSnapshot->created_at->addDays(7);
        $daysRemaining = now()->diffInDays($expiryDate, false);

        if ($daysRemaining < 0) {
            return 'Portal link expired - resend invite';
        }

        if ($daysRemaining === 0) {
            return 'Portal link expires today';
        }

        if ($daysRemaining === 1) {
            return 'Portal link expires tomorrow';
        }

        return "Portal active - expires in {$daysRemaining} days";
    }

    /**
     * Get last client action description
     */
    protected function getLastClientAction(): string
    {
        $clientEvent = $this->pitch->events()
            ->whereIn('event_type', [
                PitchEvent::TYPE_CLIENT_APPROVED,
                PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
            ])
            ->latest()
            ->first();

        if (! $clientEvent) {
            return 'Client has not yet viewed the portal';
        }

        $timeAgo = $clientEvent->created_at->diffForHumans();
        $action = match ($clientEvent->event_type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'Approved submission',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'Requested revisions',
            default => 'Viewed portal',
        };

        return "{$action} {$timeAgo}";
    }

    /**
     * Check if event is a major milestone
     */
    protected function isMajorMilestone(PitchEvent $event): bool
    {
        return in_array($event->event_type, [
            PitchEvent::TYPE_CLIENT_APPROVED,
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
        ]);
    }

    /**
     * Format event entry for timeline
     */
    protected function formatEventEntry(PitchEvent $event): array
    {
        $type = $event->event_type;

        return [
            'type' => 'event',
            'timestamp' => $event->created_at,
            'icon' => $this->getEventIcon($type),
            'title' => $this->getEventTitle($event),
            'description' => $event->comment,
            'bg_color' => $this->getEventBgColor($type),
            'border_color' => $this->getEventBorderColor($type),
        ];
    }

    /**
     * Format snapshot entry for timeline
     */
    protected function formatSnapshotEntry($snapshot): array
    {
        $version = $snapshot->snapshot_data['version'] ?? 'Unknown';
        $fileCount = count($snapshot->snapshot_data['file_ids'] ?? []);

        return [
            'type' => 'snapshot',
            'timestamp' => $snapshot->created_at,
            'icon' => 'document-check',
            'title' => "Version {$version} Submitted",
            'description' => "{$fileCount} file(s) submitted for review",
            'bg_color' => 'bg-purple-500',
            'border_color' => 'border-purple-200 dark:border-purple-800',
        ];
    }

    /**
     * Get icon for event type
     */
    protected function getEventIcon(string $type): string
    {
        return match ($type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'check-circle',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'arrow-path',
            PitchEvent::TYPE_STATUS_CHANGE => 'arrow-right',
            default => 'information-circle',
        };
    }

    /**
     * Get event title
     */
    protected function getEventTitle(PitchEvent $event): string
    {
        return match ($event->event_type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'Client Approved',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'Revisions Requested',
            PitchEvent::TYPE_STATUS_CHANGE => 'Status Changed',
            default => 'Event',
        };
    }

    /**
     * Get background color for event icon
     */
    protected function getEventBgColor(string $type): string
    {
        return match ($type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'bg-green-500',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'bg-amber-500',
            PitchEvent::TYPE_STATUS_CHANGE => 'bg-blue-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get border color for event
     */
    protected function getEventBorderColor(string $type): string
    {
        return match ($type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'border-green-200 dark:border-green-800',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'border-amber-200 dark:border-amber-800',
            PitchEvent::TYPE_STATUS_CHANGE => 'border-blue-200 dark:border-blue-800',
            default => 'border-gray-200 dark:border-gray-800',
        };
    }

    /**
     * Get review description for READY_FOR_REVIEW status
     */
    protected function getReviewDescription(): string
    {
        $latestSnapshot = $this->pitch->snapshots()->latest()->first();

        if (! $latestSnapshot) {
            return 'Waiting for client to review your submission';
        }

        $submittedAt = $latestSnapshot->created_at->diffForHumans();

        return "Submitted {$submittedAt} - waiting for client review";
    }

    /**
     * Get completion description
     */
    protected function getCompletionDescription(): string
    {
        $paymentStatus = $this->paymentStatus;
        $approvedAt = $this->pitch->approved_at
            ? $this->pitch->approved_at->format('M j, Y')
            : 'recently';

        $revisionText = $this->pitch->revisions_used > 0
            ? ' after '.$this->pitch->revisions_used.' revision(s)'
            : '';

        if ($paymentStatus && ! $paymentStatus['is_paid_in_full']) {
            return "Client approved on {$approvedAt}{$revisionText} - Awaiting milestone payments";
        }

        if ($paymentStatus) {
            return "Approved and paid in full on {$approvedAt}{$revisionText}";
        }

        return "Client approved on {$approvedAt}{$revisionText}";
    }

    /**
     * Get completed state icon based on payment status
     */
    protected function getCompletedIcon(): string
    {
        $paymentStatus = $this->paymentStatus;

        if ($paymentStatus && ! $paymentStatus['is_paid_in_full']) {
            return 'check-badge'; // Approved but not paid
        }

        return 'check-circle'; // Fully complete
    }

    /**
     * Get completed state title based on payment status
     */
    protected function getCompletedTitle(): string
    {
        $paymentStatus = $this->paymentStatus;

        if ($paymentStatus && ! $paymentStatus['is_paid_in_full']) {
            return 'Project Approved - Payment in Progress';
        }

        if ($paymentStatus) {
            return 'Project Complete & Paid In Full!';
        }

        return 'Project Successfully Completed!';
    }

    /**
     * Get completed state urgency based on payment status
     */
    protected function getCompletedUrgency(): string
    {
        $paymentStatus = $this->paymentStatus;

        if ($paymentStatus && ! $paymentStatus['is_paid_in_full']) {
            return 'warning'; // Payment pending
        }

        return 'success'; // Fully complete
    }

    /**
     * Get next steps for completed state
     */
    protected function getCompletedNextSteps(): array
    {
        $paymentStatus = $this->paymentStatus;

        if ($paymentStatus && ! $paymentStatus['is_paid_in_full']) {
            return [
                'Client has approved your work',
                'Awaiting payment of $'.number_format($paymentStatus['outstanding_amount'], 2),
                'You will be notified when payment is complete',
            ];
        }

        return [];
    }

    /**
     * Action: Switch to Files tab
     */
    public function switchToFilesTab(): void
    {
        $this->dispatch('switchToTab', 'your-files');
    }

    /**
     * Action: Switch to Delivery tab
     */
    public function switchToDeliveryTab(): void
    {
        $this->dispatch('switchToTab', 'submission');
    }

    /**
     * Action: Preview client portal
     */
    public function previewClientPortal(): void
    {
        $this->dispatch('preview-client-portal');
    }

    /**
     * Action: Submit for review (delegate to parent)
     */
    public function submitForReview(): void
    {
        $this->dispatch('submit-for-review');
    }

    /**
     * Action: Recall submission (delegate to parent)
     */
    public function recallSubmission(): void
    {
        $this->dispatch('recall-submission');
    }

    /**
     * Action: Open Communication Hub modal (v2)
     */
    public function openCommunicationHub(): void
    {
        $this->modal('communication-hub')->show();
        $this->skipRender(); // Prevent component re-render
    }

    /**
     * Action: Mark specific message as read from overview (v2)
     */
    public function markMessageAsRead(int $eventId): void
    {
        $communicationService = app(CommunicationService::class);
        $event = PitchEvent::findOrFail($eventId);
        $communicationService->markAsRead($event, Auth::id());
        $this->dispatch('message-marked-read');
    }

    /**
     * Action: Toggle session history expansion (v2)
     */
    public function toggleSessionHistory(): void
    {
        $this->showAllSessions = ! $this->showAllSessions;
    }

    public function render()
    {
        return view('livewire.project.component.overview-card');
    }
}
