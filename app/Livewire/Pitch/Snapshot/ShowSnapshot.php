<?php

namespace App\Livewire\Pitch\Snapshot;

use Livewire\Component;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use Illuminate\Support\Facades\Auth;

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

        // Check if the authenticated user is the project owner or the pitch owner
        if (!Auth::check() || (Auth::id() !== $pitch->user_id && Auth::id() !== $pitch->project->user_id)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function accept()
    {
        $this->pitchSnapshot->status = 'accepted';
        $this->pitchSnapshot->save();
        $comment = "The Project Owner has Accepted this Pitch!";
        $this->pitch->changeStatus('forward', Pitch::STATUS_APPROVED, $comment);
    }

    public function revise()
    {
        $this->pitchSnapshot->status = 'revise';
        $this->pitchSnapshot->save();
        $comment = "The Project Owner has requested this Pitch to be revised.";
        $this->pitch->changeStatus('backward', Pitch::STATUS_PENDING_REVIEW, $comment);
    }

    public function decline()
    {
        $this->pitchSnapshot->status = 'declined';
        $this->pitchSnapshot->save();
        $comment = "The Project Owner has Declined this Pitch.";
        $this->pitch->changeStatus('backward', Pitch::STATUS_DENIED, $comment);
    }

    public function render()
    {
        return view('livewire.pitch.snapshot.show-snapshot');
    }
}
