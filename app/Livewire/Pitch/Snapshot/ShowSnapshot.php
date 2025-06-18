<?php

namespace App\Livewire\Pitch\Snapshot;

use Livewire\Component;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\PitchSnapshot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShowSnapshot extends Component
{
    public Project $project;
    public Pitch $pitch;
    public PitchSnapshot $pitchSnapshot;
    public $snapshotData;

    public function mount(Project $project, Pitch $pitch, PitchSnapshot $snapshot)
    {
        // Verify relationships (optional but good practice)
        if ($pitch->project_id !== $project->id || $snapshot->pitch_id !== $pitch->id) {
            abort(404);
        }

        // Check authorization
        if (Auth::id() !== $pitch->user_id && Auth::id() !== $project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Enhanced redirect logic for project owners to latest snapshot
        $user = Auth::user();
        if ($user && $user->id === $project->user_id && 
            !$project->isClientManagement() && !$project->isDirectHire()) {
            
            // Check if this is not already the latest snapshot
            $latestSnapshot = $pitch->snapshots()->orderBy('created_at', 'desc')->first();
            
            if ($latestSnapshot && $latestSnapshot->id !== $snapshot->id) {
                return redirect()->route('projects.pitches.snapshots.show', [
                    'project' => $project->slug, 
                    'pitch' => $pitch->slug, 
                    'snapshot' => $latestSnapshot->id
                ])->with('info', 'Redirected to the latest snapshot for review.');
            }
        }

        $this->project = $project;
        $this->pitch = $pitch;
        $this->pitchSnapshot = $snapshot;
        $this->snapshotData = $snapshot->snapshot_data;
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
        
        // Safety checks
        if (!isset($this->pitch) || !isset($this->pitchSnapshot) || !isset($this->snapshotData)) {
            Log::error('Missing data for conversation thread', [
                'has_pitch' => isset($this->pitch),
                'has_snapshot' => isset($this->pitchSnapshot),
                'has_snapshot_data' => isset($this->snapshotData)
            ]);
            return $conversationThread;
        }
        
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
                'status' => $pitchSnapshot->status, // Status of the snapshot that contained the response
                'previous_snapshot_id' => $pitchSnapshot->snapshot_data['previous_snapshot_id'] ?? null
            ];
        }

        // STEP 2: Add feedback *about* the current snapshot (if it exists)
        $currentFeedback = $this->getCurrentSnapshotFeedback();
        if ($currentFeedback) {
            $conversationThread[] = [
                'type' => 'feedback',
                'message' => $currentFeedback['message'],
                'date' => $currentFeedback['date'],
                'user' => $currentFeedback['user'],
                'snapshot_id' => $pitchSnapshot->id, // Refers to the snapshot receiving the feedback
                'status' => $pitchSnapshot->status, // Current status of the snapshot (might have changed)
                'feedback_type' => $currentFeedback['feedback_type'] // Use the type from the helper
            ];
        }

        // STEP 3: For debugging, log the snapshot details
        Log::debug('Snapshot conversation thread data', [
            'snapshot_id' => $pitchSnapshot->id,
            'status' => $pitchSnapshot->status,
            'snapshot_data' => $pitchSnapshot->snapshot_data,
            'conversation_thread' => $conversationThread
        ]);

        // Sort conversation thread by date
        usort($conversationThread, function ($a, $b) {
            // Ensure dates are Carbon instances for comparison
            $dateA = $a['date'] instanceof \Carbon\Carbon ? $a['date'] : \Carbon\Carbon::parse($a['date']);
            $dateB = $b['date'] instanceof \Carbon\Carbon ? $b['date'] : \Carbon\Carbon::parse($b['date']);
            return $dateA <=> $dateB;
        });

        return $conversationThread;
    }

    /**
     * Get feedback related to the current snapshot (Denial Reason or Revision Request).
     */
    protected function getCurrentSnapshotFeedback()
    {
        // Safety checks
        if (!isset($this->pitch) || !isset($this->pitchSnapshot)) {
            Log::warning('Missing data for snapshot feedback', [
                'has_pitch' => isset($this->pitch),
                'has_snapshot' => isset($this->pitchSnapshot)
            ]);
            return null;
        }
        
        $pitchSnapshot = $this->pitchSnapshot;

        // Query for the specific event associated with this snapshot receiving feedback.
        // This could be a denial or a revision request.
        $feedbackEvent = $this->pitch->events()
            ->where('snapshot_id', $pitchSnapshot->id)
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
            Log::debug('[ShowSnapshot] Found relevant feedback event.', [
                'event_id' => $feedbackEvent->id, 
                'event_type' => $feedbackEvent->event_type,
                'event_comment' => $feedbackEvent->comment, 
                'event_metadata' => $feedbackEvent->metadata
            ]);
            
            $message = '';
            $feedbackType = 'unknown';

            // Check if it's a revision request
            if ($feedbackEvent->event_type === 'revision_request') {
                $feedbackType = 'revision';
                // Prioritize metadata for revisions if available
                if (isset($feedbackEvent->metadata['feedback']) && !empty($feedbackEvent->metadata['feedback'])) {
                    $message = $feedbackEvent->metadata['feedback'];
                    Log::debug('[ShowSnapshot] Revision feedback extracted from metadata.', ['message' => $message]);
                } else {
                    // Fallback to parsing comment
                    $message = preg_replace('/^Revisions requested\.\s*(Feedback:\s*)?/i', '', $feedbackEvent->comment);
                    Log::debug('[ShowSnapshot] Revision feedback parsed from comment (fallback).', ['event_id' => $feedbackEvent->id, 'parsed_message' => $message]);
                }
            }
            // Check if it's a denial (type is 'status_change')
            elseif ($feedbackEvent->event_type === 'status_change' && str_starts_with($feedbackEvent->comment, 'Pitch submission denied')) {
                $feedbackType = 'denial';
                // Parse reason from comment
                $message = preg_replace('/^Pitch submission denied\.\s*(Reason:\s*)?/i', '', $feedbackEvent->comment);
                 Log::debug('[ShowSnapshot] Denial reason parsed from comment.', ['event_id' => $feedbackEvent->id, 'parsed_message' => $message]);
            }

            // Trim potential whitespace from parsing
            $message = trim($message);

            // Only return if a message was actually extracted
            if (!empty($message)) {
                 Log::debug('[ShowSnapshot] Final feedback message extracted.', ['message' => $message, 'type' => $feedbackType]);
                 return [
                    'message' => $message,
                    'date' => $feedbackEvent->created_at,
                    'user' => $feedbackEvent->creator ?? \App\Models\User::find($feedbackEvent->created_by),
                    'feedback_type' => $feedbackType
                ];
            } else {
                Log::warning('[ShowSnapshot] Feedback event found but message parsing/extraction failed.', [
                    'event_id' => $feedbackEvent->id,
                    'event_type' => $feedbackEvent->event_type,
                    'comment' => $feedbackEvent->comment,
                    'metadata' => $feedbackEvent->metadata
                ]);
            }
        } else {
             Log::debug('[ShowSnapshot] No relevant feedback event found for snapshot.', ['snapshot_id' => $pitchSnapshot->id]);
        }

        return null;
    }
}
