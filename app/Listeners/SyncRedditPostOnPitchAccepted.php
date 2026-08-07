<?php

namespace App\Listeners;

use App\Events\ProjectPitchAccepted;
use App\Jobs\UpdateRedditPostForPitchAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncRedditPostOnPitchAccepted implements ShouldQueue
{
    public function handle(ProjectPitchAccepted $event): void
    {
        // Only touch Reddit if the project was actually posted there.
        if (empty($event->project->reddit_post_id)) {
            return;
        }

        UpdateRedditPostForPitchAccepted::dispatch($event->project, $event->pitch);
    }
}
