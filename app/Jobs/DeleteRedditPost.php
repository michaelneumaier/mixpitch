<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteRedditPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 900;

    public function __construct(
        public Project $project,
    ) {}

    public function handle(RedditService $reddit): void
    {
        $this->project->refresh();

        if (empty($this->project->reddit_post_id)) {
            return;
        }

        $postId = $this->project->reddit_post_id;
        $fullname = 't3_'.$postId;

        try {
            $reddit->deletePost($fullname);
        } catch (\Throwable $e) {
            Log::error('Failed to delete Reddit post', [
                'project_id' => $this->project->id,
                'reddit_post_id' => $postId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }
            throw $e;
        }

        // On success clear the identifiers; preserve reddit_posted_at as audit.
        $this->project->update([
            'reddit_post_id' => null,
            'reddit_permalink' => null,
            'reddit_original_body' => null,
        ]);

        Log::info('Removed project from Reddit', [
            'project_id' => $this->project->id,
            'reddit_post_id' => $postId,
        ]);
    }
}
