<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class ResponseToFeedback extends Component
{
    public Pitch $pitch;

    public Project $project;

    public array $workflowColors = [];

    public string $responseToFeedback = '';

    protected $rules = [
        'responseToFeedback' => 'required|string|max:5000',
    ];

    public function mount(Pitch $pitch, Project $project, array $workflowColors = [])
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->workflowColors = $workflowColors;
    }

    /**
     * Send a response to client feedback without changing pitch status
     */
    public function sendFeedbackResponse()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // Create producer comment event for feedback response
                $this->pitch->events()->create([
                    'event_type' => 'producer_comment',
                    'comment' => $this->responseToFeedback,
                    'status' => $this->pitch->status,
                    'created_by' => auth()->id(),
                    'metadata' => [
                        'visible_to_client' => true,
                        'comment_type' => 'feedback_response',
                        'responding_to_feedback' => true,
                    ],
                ]);

                // Notify client if project has client email
                if ($this->project->client_email) {
                    app(NotificationService::class)->notifyClientProducerCommented(
                        $this->pitch,
                        $this->responseToFeedback
                    );
                }
            });

            $this->responseToFeedback = '';
            $this->pitch->refresh();

            Toaster::success('Response sent to client successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to send feedback response', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to send response. Please try again.');
        }
    }

    /**
     * Get the latest feedback message from client
     */
    public function getStatusFeedbackMessageProperty(): ?string
    {
        if (in_array($this->pitch->status, [Pitch::STATUS_REVISIONS_REQUESTED, Pitch::STATUS_CLIENT_REVISIONS_REQUESTED, Pitch::STATUS_DENIED])) {
            $latestEvent = $this->pitch->events()
                ->whereIn('event_type', ['revisions_requested', 'client_revisions_requested', 'pitch_denied'])
                ->latest()
                ->first();

            return $latestEvent ? $latestEvent->comment : null;
        }

        return null;
    }

    /**
     * Get the latest feedback event with metadata
     */
    public function getLatestFeedbackEventProperty(): ?\App\Models\PitchEvent
    {
        return $this->pitch->events()
            ->whereIn('event_type', ['revisions_requested', 'client_revisions_requested', 'pitch_denied'])
            ->latest()
            ->first();
    }

    /**
     * Get file comments summary grouped by file for feedback overview
     */
    public function getFileCommentsSummaryProperty()
    {
        // Get all pitch files for this pitch
        $pitchFileIds = $this->pitch->files()->pluck('id')->toArray();

        // Get comments for all pitch files using the new file_comments system
        $comments = \App\Models\FileComment::where('commentable_type', \App\Models\PitchFile::class)
            ->whereIn('commentable_id', $pitchFileIds)
            ->whereNull('parent_id') // Only parent comments, not replies
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                // Map the FileComment to match the expected structure
                $comment->client_name = $this->project->client_name ?: 'Client';
                $comment->producer_name = $comment->user->name ?? 'Producer';

                return $comment;
            });

        $files = $this->pitch->files()->get()->keyBy('id');

        return $comments->groupBy('commentable_id')->map(function ($fileComments, $fileId) use ($files) {
            $file = $files->get($fileId);
            if (! $file) {
                return null;
            }

            $unresolvedCount = $fileComments->where('resolved', false)->count();
            $resolvedCount = $fileComments->where('resolved', true)->count();
            $totalCount = $fileComments->count();

            // Get the most recent unresolved comment for preview
            $latestUnresolved = $fileComments->where('resolved', false)->sortByDesc('created_at')->first();

            return [
                'file' => $file,
                'total_comments' => $totalCount,
                'unresolved_count' => $unresolvedCount,
                'resolved_count' => $resolvedCount,
                'latest_unresolved' => $latestUnresolved,
                'needs_attention' => $unresolvedCount > 0,
            ];
        })->filter()->values(); // Remove null entries and reset keys
    }

    /**
     * Get file comments summary totals for template display
     */
    public function getFileCommentsTotalsProperty()
    {
        $summary = $this->fileCommentsSummary;

        return [
            'unresolved' => $summary->sum('unresolved_count'),
            'total' => $summary->sum('total_comments'),
        ];
    }

    /**
     * Get previous feedback responses sent by producer
     */
    public function getPreviousResponsesProperty()
    {
        return $this->pitch->events()
            ->where('event_type', 'producer_comment')
            ->whereJsonContains('metadata->comment_type', 'feedback_response')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.project.component.response-to-feedback');
    }
}
