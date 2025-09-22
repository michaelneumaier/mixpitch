<?php

namespace App\Livewire;

use App\Models\ClientReminder;
use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class DeliveryPipelineBoard extends Component
{
    /** @var array<string, array> */
    public array $columns = [];

    /** @var array<string, mixed> */
    public array $columnMeta = [];

    /** @var array<string, int> */
    public array $limits = [
        'make' => 20,
        'review' => 20,
        'wrap' => 20,
    ];

    // Quick filters
    public bool $filterClientComments = false;

    public bool $filterUnpaidMilestones = false;

    public bool $filterRevisionsRequested = false;

    public bool $filterHasReminders = false;

    // Client filter for individual client pages
    public ?int $clientId = null;

    /** @var array<string, string> */
    public array $columnTitles = [
        'make' => 'Make',
        'review' => 'Review',
        'wrap' => 'Wrap Up',
    ];

    // Reminder modal state
    public bool $showReminderModal = false;

    public ?int $reminderProjectId = null;

    public ?int $reminderClientId = null;

    public string $reminderNote = '';

    public ?string $reminderDueAt = null; // HTML datetime-local string

    // Configurable recent window for client comments (days)
    public int $recentClientCommentDays = 7;

    public function updatedRecentClientCommentDays(): void
    {
        $this->loadBoard();
    }

    public function mount(): void
    {
        $this->loadBoard();
    }

    public function loadBoard(): void
    {
        $userId = Auth::id();

        // Load projects and their pitch + relations (assuming one pitch per CM project)
        $projects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->when($this->clientId, function ($query) {
                $query->where('client_id', $this->clientId);
            })
            ->with([
                'pitches' => function ($query) {
                    $query->with([
                        'user',
                        'files.comments' => function ($q) {
                            $q->orderByDesc('created_at');
                        },
                        'events',
                        'milestones',
                    ]);
                },
                'files',
            ])
            ->orderByDesc('created_at')
            ->get();

        $clientIds = $projects->pluck('client_id')->filter()->values()->all();
        $remindersByClientId = ClientReminder::query()
            ->where('user_id', $userId)
            ->whereIn('client_id', $clientIds ?: [-1])
            ->where('status', ClientReminder::STATUS_PENDING)
            ->orderBy('due_at')
            ->get()
            ->groupBy('client_id');

        $columns = [
            'make' => [],
            'review' => [],
            'wrap' => [],
        ];
        $columnMeta = [
            'wrap' => ['outstanding_amount' => 0.0],
        ];

        foreach ($projects as $project) {
            /** @var \App\Models\Pitch|null $pitch */
            $pitch = $project->pitches->first();
            if (! $pitch) {
                continue;
            }

            $stage = $this->deriveStage($pitch);
            $group = $this->mapStageToGroup($stage);
            $card = $this->buildCardData($project, $pitch, $remindersByClientId);
            $columns[$group][] = $card;

            if ($group === 'wrap') {
                $outstanding = 0.0;
                // If pitch has a payment amount and is not paid, count it
                $amount = (float) ($pitch->payment_amount ?? 0);
                if ($pitch->payment_status !== Pitch::PAYMENT_STATUS_PAID && $amount > 0) {
                    $outstanding += $amount;
                }
                // Add unpaid milestones
                $unpaidMilestonesAmount = $pitch->relationLoaded('milestones')
                    ? (float) $pitch->milestones
                        ->filter(fn ($m) => (float) $m->amount > 0 && $m->payment_status !== Pitch::PAYMENT_STATUS_PAID)
                        ->sum('amount')
                    : (float) $pitch->milestones()
                        ->where('amount', '>', 0)
                        ->where('payment_status', '!=', Pitch::PAYMENT_STATUS_PAID)
                        ->sum('amount');
                $outstanding += $unpaidMilestonesAmount;

                $columnMeta['wrap']['outstanding_amount'] = (
                    (float) ($columnMeta['wrap']['outstanding_amount'] ?? 0)
                ) + $outstanding;
            }
        }

        // Apply quick filters
        $columns = $this->applyFilters($columns);

        $this->columns = $columns;
        $this->columnMeta = $columnMeta;

        // Sort each column by delivery_sort_order (asc), fallback created_at desc
        foreach ($this->columns as $stage => &$cards) {
            usort($cards, function ($a, $b) {
                $aOrder = $a['delivery_sort_order'] ?? null;
                $bOrder = $b['delivery_sort_order'] ?? null;
                if ($aOrder !== null && $bOrder !== null) {
                    return $aOrder <=> $bOrder;
                }
                if ($aOrder !== null) {
                    return -1;
                }
                if ($bOrder !== null) {
                    return 1;
                }

                // Fallback newest first
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });
        }
    }

    private function mapStageToGroup(string $stage): string
    {
        return match ($stage) {
            'setup', 'in_progress' => 'make',
            'submitted', 'client_feedback' => 'review',
            'approved', 'payment_pending', 'completed_paid' => 'wrap',
            default => 'make',
        };
    }

    private function deriveStage(Pitch $pitch): string
    {
        // Completed & Paid (all milestones paid is checked in badge but payment_status=paid is primary)
        if ($pitch->status === Pitch::STATUS_COMPLETED && $pitch->payment_status === Pitch::PAYMENT_STATUS_PAID) {
            return 'completed_paid';
        }

        // Completed – Payment Pending (or milestones unpaid)
        if ($pitch->status === Pitch::STATUS_COMPLETED && $pitch->payment_status !== Pitch::PAYMENT_STATUS_PAID) {
            return 'payment_pending';
        }

        // Approved but not fully delivered/paid
        if ($pitch->status === Pitch::STATUS_APPROVED) {
            return 'approved';
        }

        // Client Feedback: revisions requested or recent client_comment
        if (in_array($pitch->status, [Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED], true)) {
            return 'client_feedback';
        }

        // If there are recent client comments, treat as Client Feedback
        // Stage derivation uses a fixed recent window (7 days) to avoid over-bubbling
        $recentClientCommentExists = $pitch->events()
            ->where('event_type', 'client_comment')
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
        if ($recentClientCommentExists && ! in_array($pitch->status, [Pitch::STATUS_COMPLETED, Pitch::STATUS_APPROVED], true)) {
            return 'client_feedback';
        }

        // Submitted / Awaiting review
        if (in_array($pitch->status, [Pitch::STATUS_READY_FOR_REVIEW, Pitch::STATUS_PENDING_REVIEW], true)) {
            return 'submitted';
        }

        // In Progress or Setup
        if ($pitch->status === Pitch::STATUS_IN_PROGRESS) {
            $hasFiles = $pitch->relationLoaded('files') ? $pitch->files->isNotEmpty() : $pitch->files()->exists();

            return $hasFiles ? 'in_progress' : 'setup';
        }

        // Pending (not started)
        if ($pitch->status === Pitch::STATUS_PENDING) {
            return 'setup';
        }

        // Fallback
        return 'in_progress';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCardData(Project $project, Pitch $pitch, Collection $remindersByClientId): array
    {
        $totalFiles = $pitch->relationLoaded('files') ? $pitch->files->count() : $pitch->files()->count();
        $approvedFiles = $pitch->relationLoaded('files')
            ? $pitch->files->where('client_approval_status', 'approved')->count()
            : $pitch->files()->where('client_approval_status', 'approved')->count();

        $milestones = $pitch->relationLoaded('milestones') ? $pitch->milestones : $pitch->milestones()->get();
        $milestonesPaid = (int) $milestones->where('payment_status', Pitch::PAYMENT_STATUS_PAID)->count();
        $milestonesUnpaid = (int) $milestones->filter(function ($m) {
            return (float) $m->amount > 0 && $m->payment_status !== Pitch::PAYMENT_STATUS_PAID;
        })->count();

        if ($this->recentClientCommentDays > 0) {
            $recentClientComments = $pitch->relationLoaded('events')
                ? $pitch->events->where('event_type', 'client_comment')->where('created_at', '>=', now()->subDays($this->recentClientCommentDays))->count()
                : $pitch->events()->where('event_type', 'client_comment')->where('created_at', '>=', now()->subDays($this->recentClientCommentDays))->count();
        } else {
            $recentClientComments = $pitch->relationLoaded('events')
                ? $pitch->events->where('event_type', 'client_comment')->count()
                : $pitch->events()->where('event_type', 'client_comment')->count();
        }

        // File-level client comments (unresolved and latest excerpt)
        $clientCommentsTotal = 0;
        $clientCommentsUnresolved = 0;
        $lastClientCommentExcerpt = null;
        $lastClientCommentAt = null;
        if ($pitch->relationLoaded('files')) {
            $comments = $pitch->files->flatMap(function ($file) {
                return $file->relationLoaded('comments') ? $file->comments : collect();
            })->filter(function ($c) {
                return (bool) ($c->is_client_comment ?? false);
            });
            $clientCommentsTotal = (int) $comments->count();
            $clientCommentsUnresolved = (int) $comments->where('resolved', false)->count();
            $latest = $comments->sortByDesc('created_at')->first();
            if ($latest) {
                $text = (string) ($latest->comment ?? '');
                $lastClientCommentExcerpt = mb_strimwidth($text, 0, 100, '…');
                $lastClientCommentAt = optional($latest->created_at)->diffForHumans(null, true);
            }
        } else {
            // Fallback lightweight aggregates if not loaded
            $clientCommentsTotal = (int) \App\Models\PitchFileComment::query()
                ->whereIn('pitch_file_id', $pitch->files()->pluck('id'))
                ->where('is_client_comment', true)
                ->count();
            $clientCommentsUnresolved = (int) \App\Models\PitchFileComment::query()
                ->whereIn('pitch_file_id', $pitch->files()->pluck('id'))
                ->where('is_client_comment', true)
                ->where('resolved', false)
                ->count();
            $latest = \App\Models\PitchFileComment::query()
                ->whereIn('pitch_file_id', $pitch->files()->pluck('id'))
                ->where('is_client_comment', true)
                ->orderByDesc('created_at')
                ->first();
            if ($latest) {
                $lastClientCommentExcerpt = mb_strimwidth((string) ($latest->comment ?? ''), 0, 100, '…');
                $lastClientCommentAt = optional($latest->created_at)->diffForHumans(null, true);
            }
        }

        $revisionsRequested = in_array($pitch->status, [Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED], true);

        // Time in current stage (based on latest status_change event to this status)
        $statusEvent = $pitch->relationLoaded('events')
            ? $pitch->events->where('event_type', 'status_change')->where('status', $pitch->status)->sortByDesc('created_at')->first()
            : $pitch->events()->where('event_type', 'status_change')->where('status', $pitch->status)->latest()->first();
        $timeInStageHuman = $statusEvent && $statusEvent->created_at
            ? $statusEvent->created_at->diffForHumans(null, true)
            : null;

        $reminders = $project->client_id ? ($remindersByClientId->get($project->client_id) ?? collect()) : collect();
        $overdueReminders = $reminders->filter(fn ($r) => $r->due_at && $r->due_at->isPast())->count();
        $upcomingReminders = $reminders->filter(fn ($r) => $r->due_at && $r->due_at->isFuture())->count();
        $nextReminder = $reminders->first();

        // Client uploads badge (project files uploaded by client)
        $clientUploads = $project->relationLoaded('files')
            ? $project->files->filter(function ($f) {
                $meta = (array) ($f->metadata ?? []);

                return ($meta['uploaded_by_client'] ?? false) === true;
            })->count()
            : $project->files()
                ->whereRaw("JSON_EXTRACT(metadata, '$.uploaded_by_client') = true")
                ->count();

        return [
            'project_id' => $project->id,
            'project_slug' => $project->slug,
            'project_name' => $project->name ?? $project->title ?? ('Project #'.$project->id),
            'client_email' => $project->client_email,
            'pitch_id' => $pitch->id,
            'status' => $pitch->status,
            'payment_status' => $pitch->payment_status,
            'delivery_sort_order' => $pitch->delivery_sort_order,
            'created_at' => optional($pitch->created_at)->toDateTimeString(),
            'files_approved' => $approvedFiles,
            'files_total' => $totalFiles,
            'milestones_paid' => $milestonesPaid,
            'milestones_unpaid' => $milestonesUnpaid,
            'recent_client_comments' => (int) $recentClientComments,
            'client_comments_total' => (int) $clientCommentsTotal,
            'client_comments_unresolved' => (int) $clientCommentsUnresolved,
            'last_client_comment_excerpt' => $lastClientCommentExcerpt,
            'last_client_comment_at' => $lastClientCommentAt,
            'revisions_requested' => $revisionsRequested,
            'overdue_reminders' => (int) $overdueReminders,
            'upcoming_reminders' => (int) $upcomingReminders,
            'next_reminder_id' => $nextReminder->id ?? null,
            'next_reminder_note' => isset($nextReminder->note) ? Str::limit((string) $nextReminder->note, 100, '…') : null,
            'next_reminder_due_human' => $nextReminder && $nextReminder->due_at ? $nextReminder->due_at->diffForHumans() : null,
            'client_uploads' => (int) $clientUploads,
            'time_in_stage' => $timeInStageHuman,
        ];
    }

    public function render()
    {
        return view('livewire.delivery-pipeline-board');
    }

    // ----- Actions -----

    public function moveToInProgress(int $projectId): void
    {
        $userId = Auth::id();
        $project = Project::where('id', $projectId)->where('user_id', $userId)->with('pitches')->first();
        if (! $project) {
            return;
        }
        /** @var Pitch|null $pitch */
        $pitch = $project->pitches->first();
        if (! $pitch) {
            return;
        }
        if ($pitch->status !== Pitch::STATUS_PENDING) {
            $this->loadBoard();

            return;
        }

        // Transition to in_progress and create event
        $pitch->status = Pitch::STATUS_IN_PROGRESS;
        $pitch->save();

        $pitch->events()->create([
            'event_type' => 'status_change',
            'comment' => 'Pitch moved to In Progress via Delivery Kanban.',
            'status' => $pitch->status,
            'created_by' => $userId,
        ]);

        $this->loadBoard();
    }

    public function submitForReview(int $projectId, ?\App\Services\PitchWorkflowService $pitchWorkflowService = null): void
    {
        $userId = Auth::id();
        $project = Project::where('id', $projectId)->where('user_id', $userId)->with('pitches.files')->first();
        if (! $project) {
            return;
        }
        /** @var Pitch|null $pitch */
        $pitch = $project->pitches->first();
        if (! $pitch) {
            return;
        }

        // Only allow from In Progress and when there are files
        $hasFiles = $pitch->files->isNotEmpty();
        if ($pitch->status !== Pitch::STATUS_IN_PROGRESS || ! $hasFiles) {
            $this->loadBoard();

            return;
        }

        // Policy check: user must be able to submit for review
        if (! Gate::allows('submitForReview', $pitch)) {
            $this->loadBoard();

            return;
        }

        try {
            $pitchWorkflowService = $pitchWorkflowService ?: app(\App\Services\PitchWorkflowService::class);
            $pitchWorkflowService->submitPitchForReview($pitch, Auth::user(), null);
        } catch (\Throwable $e) {
            Log::warning('DeliveryPipelineBoard submitForReview failed', [
                'project_id' => $projectId,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->loadBoard();
    }

    public function returnToInProgress(int $projectId): void
    {
        $userId = Auth::id();
        $project = Project::where('id', $projectId)->where('user_id', $userId)->with('pitches')->first();
        if (! $project) {
            return;
        }
        /** @var Pitch|null $pitch */
        $pitch = $project->pitches->first();
        if (! $pitch) {
            return;
        }
        if (! in_array($pitch->status, [Pitch::STATUS_READY_FOR_REVIEW, Pitch::STATUS_PENDING_REVIEW], true)) {
            $this->loadBoard();

            return;
        }

        try {
            $pitch->status = Pitch::STATUS_IN_PROGRESS;
            $pitch->save();
            $pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => 'Submission recalled via Delivery Kanban.',
                'status' => $pitch->status,
                'created_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DeliveryPipelineBoard returnToInProgress failed', [
                'project_id' => $projectId,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->loadBoard();
    }

    public function loadMore(string $stage): void
    {
        if (! isset($this->limits[$stage])) {
            return;
        }
        $this->limits[$stage] = min(($this->limits[$stage] ?? 20) + 20, 1000);
    }

    private function applyFilters(array $columns): array
    {
        $filtering = $this->filterClientComments || $this->filterUnpaidMilestones || $this->filterRevisionsRequested || $this->filterHasReminders;
        if (! $filtering) {
            return $columns;
        }
        foreach ($columns as $stage => $cards) {
            $columns[$stage] = array_values(array_filter($cards, function ($card) {
                if ($this->filterClientComments) {
                    $hasRecentPitchComments = ($card['recent_client_comments'] ?? 0) > 0;
                    $hasUnresolvedFileComments = ($card['client_comments_unresolved'] ?? 0) > 0;
                    $hasAnyFileComments = ($card['client_comments_total'] ?? 0) > 0;
                    if (! ($hasRecentPitchComments || $hasUnresolvedFileComments || $hasAnyFileComments)) {
                        return false;
                    }
                }
                if ($this->filterUnpaidMilestones && (($card['milestones_unpaid'] ?? 0) <= 0)) {
                    return false;
                }
                if ($this->filterRevisionsRequested && ! ($card['revisions_requested'] ?? false)) {
                    return false;
                }
                if ($this->filterHasReminders && ((($card['overdue_reminders'] ?? 0) + ($card['upcoming_reminders'] ?? 0)) <= 0)) {
                    return false;
                }

                return true;
            }));
        }

        return $columns;
    }

    public function toggleFilter(string $filterKey): void
    {
        if (! in_array($filterKey, ['filterClientComments', 'filterUnpaidMilestones', 'filterRevisionsRequested', 'filterHasReminders'], true)) {
            return;
        }
        $this->$filterKey = ! $this->$filterKey;
        $this->loadBoard();
    }

    public function addQuickReminder(int $projectId): void
    {
        $userId = Auth::id();
        $project = Project::where('id', $projectId)->where('user_id', $userId)->first();
        if (! $project || ! $project->client_id) {
            return;
        }

        try {
            ClientReminder::create([
                'user_id' => $userId,
                'client_id' => $project->client_id,
                'due_at' => now()->addDay(),
                'note' => 'Follow up on "'.($project->name ?? $project->title ?? ('Project #'.$project->id)).'"',
                'status' => ClientReminder::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create quick reminder from DeliveryPipelineBoard', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->loadBoard();
    }

    public function openReminderModal(int $projectId): void
    {
        $userId = Auth::id();
        $project = Project::where('id', $projectId)->where('user_id', $userId)->first();
        if (! $project || ! $project->client_id) {
            return;
        }
        $this->reminderProjectId = $project->id;
        $this->reminderClientId = $project->client_id;
        $this->reminderNote = 'Follow up on "'.($project->name ?? $project->title ?? ('Project #'.$project->id)).'"';
        $this->reminderDueAt = \Carbon\Carbon::now()->addDay()->format('Y-m-d\TH:i');
        $this->showReminderModal = true;
    }

    public function saveReminder(): void
    {
        if (! $this->reminderClientId || ! $this->reminderProjectId) {
            return;
        }
        $validated = $this->validate([
            'reminderNote' => 'required|string|min:3|max:2000',
            'reminderDueAt' => 'required|date',
        ]);

        try {
            ClientReminder::create([
                'user_id' => Auth::id(),
                'client_id' => $this->reminderClientId,
                'due_at' => \Carbon\Carbon::parse($validated['reminderDueAt']),
                'note' => $validated['reminderNote'],
                'status' => ClientReminder::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to save reminder from DeliveryPipelineBoard modal', [
                'project_id' => $this->reminderProjectId,
                'error' => $e->getMessage(),
            ]);
        }

        // Reset and close
        $this->showReminderModal = false;
        $this->reminderProjectId = null;
        $this->reminderClientId = null;
        $this->reminderNote = '';
        $this->reminderDueAt = null;

        $this->loadBoard();
    }

    public function completeReminder(int $reminderId): void
    {
        $reminder = ClientReminder::where('id', $reminderId)
            ->where('user_id', Auth::id())
            ->first();
        if (! $reminder) {
            return;
        }
        try {
            $reminder->status = ClientReminder::STATUS_COMPLETED;
            $reminder->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to complete reminder from DeliveryPipelineBoard', [
                'reminder_id' => $reminderId,
                'error' => $e->getMessage(),
            ]);
        }
        $this->loadBoard();
    }
}
