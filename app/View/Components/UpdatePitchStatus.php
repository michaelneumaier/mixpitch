<?php

namespace App\View\Components;

use App\Models\Pitch;
use Illuminate\View\Component;

class UpdatePitchStatus extends Component
{
    public $pitch;

    public $status;

    public $hasCompletedPitch;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Pitch $pitch, $hasCompletedPitch = false)
    {
        $this->pitch = $pitch;
        $this->status = $pitch->status;
        $this->hasCompletedPitch = $hasCompletedPitch;

        // Debug status
        \Illuminate\Support\Facades\Log::info('UpdatePitchStatus Component', [
            'pitch_id' => $pitch->id,
            'status' => $this->status,
            'revisions_requested_const' => Pitch::STATUS_REVISIONS_REQUESTED,
            'is_matching' => $this->status === Pitch::STATUS_REVISIONS_REQUESTED,
        ]);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.update-pitch-status');
    }
}
