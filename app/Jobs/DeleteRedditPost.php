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

    /**
     * Progressive backoff (seconds) between retry attempts: 15, 30, 45 minutes.
     *
     * @var array<int, int>
     */
    public array $backoff = [900, 1800, 2700];

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
