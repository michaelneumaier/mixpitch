<?php

namespace App\Events;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContestWinnerSelected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public Pitch $winningPitch,
        public User $selectingUser,
    ) {}
}
