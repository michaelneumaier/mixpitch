<?php

namespace App\Livewire\Pitch\Snapshot;

use Livewire\Component;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShowSnapshot extends Component
{
    public $pitch;
    public $pitchSnapshot;
    public $snapshotData;

    public function mount(Pitch $pitch, PitchSnapshot $pitchSnapshot)
    {
        $this->pitch = $pitch;
        $this->pitchSnapshot = $pitchSnapshot;
        $this->snapshotData = $pitchSnapshot->snapshot_data;

        // Check if the user is authorized to view this snapshot
        if (Auth::id() !== $pitch->user_id && Auth::id() !== $pitch->project->user_id) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        $conversationThread = $this->getConversationThread();
        return view('livewire.pitch.snapshot.show-snapshot', [
            'conversationThread' => $conversationThread
        ]);
    }

    /**
     * Get the conversation thread for feedback and responses.
     * This builds a focused view of feedback and responses for the current snapshot.
     * 
     * @return array
     */
    protected function getConversationThread()
    {
        $conversationThread = [];
        $pitch = $this->pitch;
        $pitchSnapshot = $this->pitchSnapshot;

        // STEP 1: Add response to feedback (if this snapshot is responding to previous feedback)
        if (
            isset($pitchSnapshot->snapshot_data['response_to_feedback']) &&
            !empty($pitchSnapshot->snapshot_data['response_to_feedback'])
        ) {

            $response = $pitchSnapshot->snapshot_data['response_to_feedback'];
            $responseDate = $pitchSnapshot->created_at;
            $responseUser = $pitchSnapshot->user;

            // Add response to conversation thread
            $conversationThread[] = [
                'type' => 'response',
                'message' => $response,
                'date' => $responseDate,
                'user' => $responseUser,
                'snapshot_id' => $pitchSnapshot->id,
                'status' => $pitchSnapshot->status,
                'previous_snapshot_id' => $pitchSnapshot->snapshot_data['previous_snapshot_id'] ?? null
            ];
        }

        // STEP 2: Add feedback for the current snapshot (if it exists)
        $currentFeedback = $this->getCurrentSnapshotFeedback();
        if ($currentFeedback) {
            $conversationThread[] = [
                'type' => 'feedback',
                'message' => $currentFeedback['message'],
                'date' => $currentFeedback['date'],
                'user' => $currentFeedback['user'],
                'snapshot_id' => $pitchSnapshot->id,
                'status' => $pitchSnapshot->status,
                'feedback_type' => $pitchSnapshot->status === 'denied' ? 'denial' : 'revision'
            ];
        }

        // STEP 3: For debugging, log the snapshot details
        Log::debug('Snapshot details', [
            'id' => $pitchSnapshot->id,
            'status' => $pitchSnapshot->status,
            'has_response' => isset($pitchSnapshot->snapshot_data['response_to_feedback']),
            'has_feedback' => !is_null($currentFeedback),
            'snapshot_data' => $pitchSnapshot->snapshot_data,
            'conversation_thread' => $conversationThread
        ]);

        // Sort conversation thread by date
        usort($conversationThread, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        return $conversationThread;
    }

    /**
     * Get feedback for the current snapshot if it exists.
     */
    protected function getCurrentSnapshotFeedback()
    {
        $pitch = $this->pitch;
        $pitchSnapshot = $this->pitchSnapshot;

        // Check for feedback in snapshot data
        if (isset($pitchSnapshot->snapshot_data['feedback']) && !empty($pitchSnapshot->snapshot_data['feedback'])) {
            return [
                'message' => $pitchSnapshot->snapshot_data['feedback'],
                'date' => $pitchSnapshot->updated_at,
                'user' => $pitch->project->user
            ];
        }

        // Check for feedback in events
        $feedbackEvents = $pitch->events()
            ->where(function ($query) {
                $query->where('event_type', 'snapshot_revisions_requested')
                    ->orWhere('event_type', 'snapshot_denied');
            })
            ->where('snapshot_id', $pitchSnapshot->id)
            ->first();

        if ($feedbackEvents) {
            $message = '';
            if ($feedbackEvents->event_type === 'snapshot_revisions_requested') {
                $message = preg_replace('/^Revisions requested\. Reason: /i', '', $feedbackEvents->comment);
            } else {
                $message = preg_replace('/^(Snapshot |)denied\. Reason: /i', '', $feedbackEvents->comment);
            }

            return [
                'message' => $message,
                'date' => $feedbackEvents->created_at,
                'user' => $feedbackEvents->user ?? ($feedbackEvents->created_by ?
                    \App\Models\User::find($feedbackEvents->created_by) : null)
            ];
        }

        // If we reach here, no feedback was found
        return null;
    }
}
