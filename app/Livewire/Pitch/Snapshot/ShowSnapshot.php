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
    public $pitch;
    public $pitchSnapshot;
    public $snapshotData;

    public function mount($pitch = null, $pitchSnapshot = null, $project = null, $snapshot = null)
    {
        // Handle the case where we have slug-based routing (project and pitch are slugs)
        if (isset($project) && isset($pitch) && isset($snapshot)) {
            // When using the new URL pattern with slugs, we need to find models by slug
            if (is_string($project)) {
                // Find project by slug
                $project = Project::where('slug', $project)->firstOrFail();
            }
            
            if (is_string($pitch)) {
                // Find pitch by slug
                $pitch = Pitch::where('slug', $pitch)->where('project_id', $project->id)->firstOrFail();
            }
            
            // Verify pitch belongs to project
            if ($pitch->project_id != $project->id) {
                abort(404, 'Pitch not found for this project');
            }
            
            // Resolve snapshot by ID (snapshots don't have slugs)
            $pitchSnapshot = PitchSnapshot::findOrFail($snapshot);
            
            // Verify snapshot belongs to pitch
            if ($pitchSnapshot->pitch_id != $pitch->id) {
                abort(404, 'Snapshot not found for this pitch');
            }
            
            // Now we can safely set the properties
            $this->pitch = $pitch;
            $this->pitchSnapshot = $pitchSnapshot;
            $this->snapshotData = $pitchSnapshot->snapshot_data;
        } 
        // Old route pattern is no longer supported
        else {
            abort(404, 'Invalid URL pattern. Please use the project/pitch/snapshot URL structure.');
        }

        // Check if the user is authorized to view this snapshot
        if (Auth::id() !== $this->pitch->user_id && Auth::id() !== $this->pitch->project->user_id) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        // Safety check
        if (!isset($this->pitch) || !isset($this->pitchSnapshot)) {
            return view('livewire.pitch.snapshot.show-snapshot', [
                'conversationThread' => [],
                'error' => 'Cannot load snapshot data'
            ]);
        }
        
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
        // Safety checks
        if (!isset($this->pitch) || !isset($this->pitchSnapshot)) {
            Log::error('Missing data for snapshot feedback', [
                'has_pitch' => isset($this->pitch),
                'has_snapshot' => isset($this->pitchSnapshot)
            ]);
            return null;
        }
        
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
