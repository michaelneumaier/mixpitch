<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Component;

class SubmissionProgress extends Component
{
    public Pitch $pitch;

    public Project $project;

    public array $workflowColors = [];

    public bool $showFullTimeline = false;

    public int $initialTimelineCount = 5;

    public function mount(Pitch $pitch, Project $project, array $workflowColors = [])
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    public function toggleTimeline(): void
    {
        $this->showFullTimeline = ! $this->showFullTimeline;
    }

    /**
     * Get current progress data based on pitch status
     */
    public function getProgressDataProperty(): array
    {
        $status = $this->pitch->status;

        return match ($status) {
            Pitch::STATUS_IN_PROGRESS => [
                'icon' => 'cog-6-tooth',
                'title' => 'Getting Started',
                'description' => 'Upload your files and submit for client review',
                'progress' => 20,
                'urgency' => 'normal',
            ],
            Pitch::STATUS_READY_FOR_REVIEW => [
                'icon' => 'eye',
                'title' => 'Awaiting Client Review',
                'description' => $this->getReviewDescription(),
                'progress' => 75,
                'urgency' => 'normal',
            ],
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED => [
                'icon' => 'pencil',
                'title' => 'Revisions Requested',
                'description' => 'Review client feedback and submit your updates',
                'progress' => 50,
                'urgency' => 'warning',
            ],
            Pitch::STATUS_APPROVED => [
                'icon' => 'check-badge',
                'title' => 'Client Approved',
                'description' => 'Work approved - awaiting payment completion',
                'progress' => 90,
                'urgency' => 'info',
            ],
            Pitch::STATUS_COMPLETED => [
                'icon' => 'check-circle',
                'title' => 'Project Completed',
                'description' => $this->getCompletionDescription(),
                'progress' => 100,
                'urgency' => 'success',
            ],
            default => [
                'icon' => 'information-circle',
                'title' => 'In Progress',
                'description' => 'Working on your project',
                'progress' => 30,
                'urgency' => 'normal',
            ],
        };
    }

    /**
     * Get timeline entries from events and snapshots
     */
    public function getTimelineEntriesProperty(): Collection
    {
        $entries = collect();

        // Get relevant events
        $events = $this->pitch->events()
            ->whereIn('event_type', [
                PitchEvent::TYPE_STATUS_CHANGE,
                PitchEvent::TYPE_CLIENT_APPROVED,
                PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get snapshots
        $snapshots = $this->pitch->snapshots()
            ->orderBy('created_at', 'desc')
            ->get();

        // Process events
        foreach ($events as $event) {
            $entries->push($this->formatEventEntry($event));
        }

        // Process snapshots (submissions)
        foreach ($snapshots as $snapshot) {
            $entries->push($this->formatSnapshotEntry($snapshot));
        }

        // Sort by timestamp descending (most recent first)
        return $entries->sortByDesc('timestamp')->values();
    }

    /**
     * Get project statistics
     */
    public function getStatsProperty(): array
    {
        $snapshots = $this->pitch->snapshots;
        $firstSnapshot = $snapshots->sortBy('created_at')->first();

        return [
            'submission_count' => $snapshots->count(),
            'revision_round' => $this->pitch->revisions_used ?? 0,
            'days_in_project' => $firstSnapshot
                ? now()->diffInDays($firstSnapshot->created_at)
                : 0,
            'days_in_current_state' => now()->diffInDays($this->pitch->updated_at),
        ];
    }

    /**
     * Get completion data for completed state
     */
    public function getCompletionDataProperty(): ?array
    {
        if ($this->pitch->status !== Pitch::STATUS_COMPLETED) {
            return null;
        }

        $approvalEvent = $this->pitch->events()
            ->where('event_type', PitchEvent::TYPE_CLIENT_APPROVED)
            ->latest()
            ->first();

        $finalSnapshot = $this->pitch->snapshots()
            ->where('status', 'accepted')
            ->latest()
            ->first();

        $firstSnapshot = $this->pitch->snapshots()
            ->orderBy('created_at')
            ->first();

        $totalDays = $firstSnapshot && $approvalEvent
            ? $approvalEvent->created_at->diffInDays($firstSnapshot->created_at)
            : 0;

        return [
            'approved_at' => $approvalEvent?->created_at ?? $this->pitch->updated_at,
            'final_snapshot_id' => $finalSnapshot?->id,
            'total_days' => $totalDays,
            'revision_rounds' => $this->pitch->revisions_used ?? 0,
        ];
    }

    /**
     * Get payment status data for completed/approved state
     */
    public function getPaymentStatusProperty(): ?array
    {
        // Only relevant for completed or approved states
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
     * Format event entry for timeline
     */
    protected function formatEventEntry(PitchEvent $event): array
    {
        $type = match ($event->event_type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'client_approved',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'client_revisions',
            default => 'status_change',
        };

        return [
            'type' => $type,
            'icon' => $this->getEventIcon($type),
            'bg_color' => $this->getEventBgColor($type),
            'border_color' => $this->getEventBorderColor($type),
            'title' => $this->getEventTitle($event),
            'description' => $event->comment,
            'timestamp' => $event->created_at,
            'metadata' => $event->metadata ?? [],
        ];
    }

    /**
     * Format snapshot entry for timeline
     */
    protected function formatSnapshotEntry($snapshot): array
    {
        $version = $snapshot->snapshot_data['version'] ?? 1;
        $isRevision = $version > 1;
        $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];
        $responseToFeedback = $snapshot->snapshot_data['response_to_feedback'] ?? null;

        return [
            'type' => $isRevision ? 'revision_submission' : 'submission',
            'icon' => $this->getEventIcon($isRevision ? 'revision_submission' : 'submission'),
            'bg_color' => $this->getEventBgColor($isRevision ? 'revision_submission' : 'submission'),
            'border_color' => $this->getEventBorderColor($isRevision ? 'revision_submission' : 'submission'),
            'title' => $isRevision
                ? "Submitted Revisions (Version {$version})"
                : "Submitted for Review (Version {$version})",
            'description' => $responseToFeedback,
            'timestamp' => $snapshot->created_at,
            'file_count' => count($fileIds),
            'snapshot_id' => $snapshot->id,
            'version' => $version,
        ];
    }

    /**
     * Get icon name for event type
     */
    protected function getEventIcon(string $type): string
    {
        return match ($type) {
            'submission' => 'paper-airplane',
            'revision_submission' => 'arrow-up-circle',
            'client_approved' => 'check-circle',
            'client_revisions' => 'chat-bubble-left',
            'status_change' => 'arrow-path',
            'completed' => 'trophy',
            default => 'information-circle',
        };
    }

    /**
     * Get background color for event type
     */
    protected function getEventBgColor(string $type): string
    {
        return match ($type) {
            'submission' => 'bg-purple-500',
            'revision_submission' => 'bg-blue-500',
            'client_approved', 'completed' => 'bg-green-500',
            'client_revisions' => 'bg-amber-500',
            'status_change' => 'bg-gray-400',
            default => 'bg-gray-400',
        };
    }

    /**
     * Get border color for event type
     */
    protected function getEventBorderColor(string $type): string
    {
        return match ($type) {
            'submission' => 'border-l-purple-500',
            'revision_submission' => 'border-l-blue-500',
            'client_approved', 'completed' => 'border-l-green-500',
            'client_revisions' => 'border-l-amber-500',
            'status_change' => 'border-l-gray-400',
            default => 'border-l-gray-400',
        };
    }

    /**
     * Get event title from PitchEvent
     */
    protected function getEventTitle(PitchEvent $event): string
    {
        return match ($event->event_type) {
            PitchEvent::TYPE_CLIENT_APPROVED => 'Client Approved',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'Client Requested Revisions',
            default => 'Status Changed',
        };
    }

    /**
     * Get review description with timing
     */
    protected function getReviewDescription(): string
    {
        $lastSnapshot = $this->pitch->snapshots()->latest()->first();

        if ($lastSnapshot) {
            $daysAgo = now()->diffInDays($lastSnapshot->created_at);
            $timeText = $daysAgo === 0
                ? 'today'
                : ($daysAgo === 1 ? 'yesterday' : "{$daysAgo} days ago");

            return "Your submission was sent {$timeText}. The client will be notified to review.";
        }

        return 'Your submission is being reviewed. The client will be notified.';
    }

    /**
     * Get completion description
     */
    protected function getCompletionDescription(): string
    {
        $approvalEvent = $this->pitch->events()
            ->where('event_type', PitchEvent::TYPE_CLIENT_APPROVED)
            ->latest()
            ->first();

        if ($approvalEvent) {
            $approvalDate = $approvalEvent->created_at->format('F j, Y');

            return "Congratulations! Client approved your work on {$approvalDate}.";
        }

        return 'Congratulations! Your work has been approved.';
    }

    /**
     * Get color scheme based on urgency
     */
    protected function getColorScheme(): array
    {
        $urgency = $this->progressData['urgency'];

        return match ($urgency) {
            'success' => [
                'bg' => 'bg-green-50 dark:bg-green-950',
                'border' => 'border-green-200 dark:border-green-800',
                'icon' => 'text-green-600 dark:text-green-400',
                'title' => 'text-green-900 dark:text-green-100',
                'progress' => 'bg-green-500',
            ],
            'warning' => [
                'bg' => 'bg-amber-50 dark:bg-amber-950',
                'border' => 'border-amber-200 dark:border-amber-800',
                'icon' => 'text-amber-600 dark:text-amber-400',
                'title' => 'text-amber-900 dark:text-amber-100',
                'progress' => 'bg-amber-500',
            ],
            'info' => [
                'bg' => 'bg-blue-50 dark:bg-blue-950',
                'border' => 'border-blue-200 dark:border-blue-800',
                'icon' => 'text-blue-600 dark:text-blue-400',
                'title' => 'text-blue-900 dark:text-blue-100',
                'progress' => 'bg-blue-500',
            ],
            default => [
                'bg' => $this->workflowColors['bg'] ?? 'bg-purple-50 dark:bg-purple-950',
                'border' => $this->workflowColors['border'] ?? 'border-purple-200 dark:border-purple-800',
                'icon' => $this->workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400',
                'title' => $this->workflowColors['title'] ?? 'text-purple-900 dark:text-purple-100',
                'progress' => $this->workflowColors['accent'] ?? 'bg-purple-500',
            ],
        };
    }

    public function render()
    {
        return view('livewire.project.component.submission-progress', [
            'colorScheme' => $this->getColorScheme(),
        ]);
    }
}
