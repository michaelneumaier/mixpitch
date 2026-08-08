<?php

namespace App\Listeners;

use App\Events\ContestWinnerSelected;
use App\Jobs\UpdateRedditPostForContestWinner;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncRedditPostOnContestWinnerSelected implements ShouldQueue
{
    public function handle(ContestWinnerSelected $event): void
    {
        if (empty($event->project->reddit_post_id)) {
            return;
        }

        UpdateRedditPostForContestWinner::dispatch($event->project, $event->winningPitch);
    }
}
