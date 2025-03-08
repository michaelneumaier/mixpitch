<?php

namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\PitchEvent;

class FeedbackConversation extends Component
{
    public $pitch;

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    public function getConversationItemsProperty()
    {
        $items = collect();

        // Get all relevant snapshots 
        $snapshots = $this->pitch->snapshots()
            ->whereIn('status', ['revisions_requested', 'revision_addressed', 'accepted', 'denied', 'pending'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Process each snapshot to get its feedback and responses
        foreach ($snapshots as $snapshot) {
            // 1. Check for response to feedback
            if (
                isset($snapshot->snapshot_data['response_to_feedback']) &&
                !empty($snapshot->snapshot_data['response_to_feedback'])
            ) {

                $items->push([
                    'type' => 'response',
                    'snapshot' => $snapshot,
                    'content' => $snapshot->snapshot_data['response_to_feedback'],
                    'date' => $snapshot->created_at,
                    'user' => $snapshot->user,
                    'previous_snapshot_id' => $snapshot->snapshot_data['previous_snapshot_id'] ?? null
                ]);
            }

            // 2. Check for feedback in the snapshot
            $feedback = $this->getSnapshotFeedback($snapshot);
            if ($feedback) {
                $items->push([
                    'type' => 'feedback',
                    'snapshot' => $snapshot,
                    'content' => $feedback['content'],
                    'date' => $feedback['date'],
                    'user' => $snapshot->project->user,
                    'feedback_type' => $snapshot->status === 'denied' ? 'denial' : 'revision'
                ]);
            }
        }

        // Sort items by date
        return $items->sortBy('date')->values();
    }

    /**
     * Get feedback for a snapshot if it exists
     */
    protected function getSnapshotFeedback($snapshot)
    {
        // Check for feedback in snapshot data
        if (isset($snapshot->snapshot_data['feedback']) && !empty($snapshot->snapshot_data['feedback'])) {
            return [
                'content' => $snapshot->snapshot_data['feedback'],
                'date' => $snapshot->updated_at
            ];
        }

        // Check for feedback in events
        $feedbackEvents = $this->pitch->events()
            ->where(function ($query) {
                $query->where('event_type', 'snapshot_revisions_requested')
                    ->orWhere('event_type', 'snapshot_denied');
            })
            ->where('snapshot_id', $snapshot->id)
            ->first();

        if ($feedbackEvents) {
            $content = $feedbackEvents->comment;
            if ($feedbackEvents->event_type === 'snapshot_revisions_requested') {
                $content = preg_replace('/^Revisions requested\. Reason: /', '', $content);
                // If content is empty after stripping, show a default message
                if (empty(trim($content))) {
                    $content = 'No specific feedback was provided.';
                }
            } else if ($feedbackEvents->event_type === 'snapshot_denied') {
                $content = preg_replace('/^(Snapshot |)denied\. Reason: /i', '', $content);
                // If content is empty after stripping, show a default message
                if (empty(trim($content))) {
                    $content = 'No specific reason was provided.';
                }
            }

            return [
                'content' => $content,
                'date' => $feedbackEvents->created_at
            ];
        }

        // If we reach here, no feedback was found
        return null;
    }

    public function render()
    {
        return view('livewire.pitch.component.feedback-conversation');
    }
}
