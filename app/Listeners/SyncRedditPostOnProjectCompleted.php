<?php

namespace App\Listeners;

use App\Events\ProjectCompleted;
use App\Jobs\UpdateRedditPostForProjectCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncRedditPostOnProjectCompleted implements ShouldQueue
{
    public function handle(ProjectCompleted $event): void
    {
        if (empty($event->project->reddit_post_id)) {
            return;
        }

        UpdateRedditPostForProjectCompleted::dispatch($event->project);
    }
}
