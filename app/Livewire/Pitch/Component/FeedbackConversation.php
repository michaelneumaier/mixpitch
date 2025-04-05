<?php

namespace App\Livewire\Pitch\Component;

use Livewire\Component;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\PitchEvent;
use Illuminate\Support\Facades\Log;

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

        // 3. Add completion feedback if pitch is completed
        if ($this->pitch->status === 'completed' && !empty($this->pitch->completion_feedback)) {
            // Get the current snapshot (the one that was accepted)
            $currentSnapshot = $this->pitch->currentSnapshot;
            
            if ($currentSnapshot) {
                // Ensure we have a Carbon date object for the date
                $completionDate = null;
                if ($this->pitch->completion_date) {
                    $completionDate = is_string($this->pitch->completion_date) 
                        ? \Carbon\Carbon::parse($this->pitch->completion_date)
                        : $this->pitch->completion_date;
                } else if ($this->pitch->completed_at) {
                    $completionDate = is_string($this->pitch->completed_at)
                        ? \Carbon\Carbon::parse($this->pitch->completed_at)
                        : $this->pitch->completed_at;
                } else {
                    $completionDate = now();
                }
                
                $items->push([
                    'type' => 'completion',
                    'snapshot' => $currentSnapshot,
                    'content' => $this->pitch->completion_feedback,
                    'date' => $completionDate,
                    'user' => $this->pitch->project->user,
                ]);
            }
        }

        // Sort items by date in descending order (newest first)
        return $items->sortByDesc('date')->values();
    }

    /**
     * Get feedback for a specific snapshot if it exists (Denial Reason or Revision Request).
     */
    protected function getSnapshotFeedback(PitchSnapshot $snapshot): ?array
    {
        // Query for the specific event associated with this snapshot receiving feedback.
        $feedbackEvent = $this->pitch->events()
            ->where('snapshot_id', $snapshot->id)
            ->where(function ($query) {
                $query->where('event_type', 'revision_request') // Specific type for revisions
                      ->orWhere(function($q) { // Handle denial (stored as status_change)
                          $q->where('event_type', 'status_change')
                            ->where('comment', 'LIKE', 'Pitch submission denied%');
                      });
            })
            ->orderBy('created_at', 'desc') // Get the most recent relevant event for this snapshot
            ->first();

        if ($feedbackEvent) {
             Log::debug('[FeedbackConversation] Found relevant feedback event for snapshot.', [
                'snapshot_id' => $snapshot->id,
                'event_id' => $feedbackEvent->id, 
                'event_type' => $feedbackEvent->event_type,
                'event_comment' => $feedbackEvent->comment, 
                'event_metadata' => $feedbackEvent->metadata
            ]);

            $message = '';
            // Check if it's a revision request
            if ($feedbackEvent->event_type === 'revision_request') {
                // Prioritize metadata for revisions if available
                if (isset($feedbackEvent->metadata['feedback']) && !empty($feedbackEvent->metadata['feedback'])) {
                    $message = $feedbackEvent->metadata['feedback'];
                } else {
                    // Fallback to parsing comment
                    $message = preg_replace('/^Revisions requested\.\s*(Feedback:\s*)?/i', '', $feedbackEvent->comment);
                }
            }
            // Check if it's a denial (type is 'status_change')
            elseif ($feedbackEvent->event_type === 'status_change' && str_starts_with(strtolower($feedbackEvent->comment), 'pitch submission denied')) {
                // More robust parsing for denial reason
                $commentLower = strtolower($feedbackEvent->comment);
                $prefix = 'pitch submission denied.';
                $reasonPrefix = 'reason:';
                
                $message = '';
                // Check if the comment includes the reason prefix after the initial denial text
                $reasonPos = strpos($commentLower, $reasonPrefix, strlen($prefix));
                
                if ($reasonPos !== false) {
                    // Extract text after "Reason:"
                    $message = trim(substr($feedbackEvent->comment, $reasonPos + strlen($reasonPrefix)));
                } else {
                    // Check if there is *any* text after the initial prefix (even without "Reason:")
                    $potentialReason = trim(substr($feedbackEvent->comment, strlen($prefix)));
                    if (!empty($potentialReason)) {
                         // Consider this part the reason if it exists, even without the explicit prefix
                         // This handles cases where maybe the prefix wasn't added but text was still entered.
                         // $message = $potentialReason; // Option 1: Use the remaining text
                         
                         // Option 2: If we ONLY want reasons explicitly marked with "Reason:", 
                         //           and want to show a generic message otherwise, do this:
                         $message = '[Pitch Denied - No explicit reason provided]'; // Or keep empty if preferred
                    } else {
                        // The comment is ONLY "Pitch submission denied."
                        $message = '[Pitch Denied]'; // Provide a generic indicator
                    }
                }
            }

            $message = trim($message);

            if (!empty($message)) {
                 Log::debug('[FeedbackConversation] Extracted feedback content.', ['snapshot_id' => $snapshot->id, 'content' => $message]);
                return [
                    'content' => $message,
                    'date' => $feedbackEvent->created_at
                ];
            } else {
                 Log::warning('[FeedbackConversation] Feedback event found but message extraction failed.', [
                    'snapshot_id' => $snapshot->id,
                    'event_id' => $feedbackEvent->id,
                    'event_type' => $feedbackEvent->event_type,
                    'comment' => $feedbackEvent->comment,
                    'metadata' => $feedbackEvent->metadata
                ]);
            }
        }

        // Fallback check in snapshot_data (for potentially older data structures?)
        // This might be removable if all feedback is now consistently in events.
        if (isset($snapshot->snapshot_data['feedback']) && !empty($snapshot->snapshot_data['feedback'])) {
            Log::debug('[FeedbackConversation] Found feedback in snapshot_data (fallback). ', ['snapshot_id' => $snapshot->id]);
            return [
                'content' => $snapshot->snapshot_data['feedback'],
                'date' => $snapshot->updated_at // Use snapshot update time as best guess
            ];
        }
        
        Log::debug('[FeedbackConversation] No relevant feedback found for snapshot.', ['snapshot_id' => $snapshot->id]);
        return null;
    }

    public function render()
    {
        return view('livewire.pitch.component.feedback-conversation');
    }
}
