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

class PostProjectToReddit implements ShouldQueue
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
        public Project $project
    ) {}

    public function handle(RedditService $redditService): void
    {
        // Idempotency guard: if a previous attempt already succeeded (Reddit
        // accepted the post but a later step threw before the job finished),
        // don't submit again — that would create a duplicate Reddit post.
        if ($this->project->fresh()->reddit_post_id) {
            Log::info('Skipping Reddit submission; project already has a reddit_post_id', [
                'project_id' => $this->project->id,
            ]);

            return;
        }

        try {
            $response = $redditService->submitProject($this->project);

            // Extract Reddit post information
            $postId = $response['json']['data']['id'] ?? null;
            $permalink = $response['json']['data']['url'] ?? null;

            if (! $postId) {
                Log::error('Reddit API response missing post ID', [
                    'project_id' => $this->project->id,
                    'response' => $response,
                ]);
                throw new \Exception('Reddit API did not return a post ID - submission may have failed');
            }

            // Persist the post id immediately so a retry (triggered by any
            // failure further down) sees reddit_post_id already set and bails
            // out via the idempotency guard above, instead of posting again.
            $this->project->update([
                'reddit_post_id' => $postId,
                'reddit_permalink' => $permalink,
                'reddit_posted_at' => now(),
            ]);

            // `reddit_original_body` snapshots the initial post so Phase 5
            // status updates can prepend a header without losing the
            // original content.
            $this->project->update([
                'reddit_original_body' => $redditService->buildPostBody($this->project),
            ]);

            Log::info('Project posted to Reddit successfully', [
                'project_id' => $this->project->id,
                'reddit_post_id' => $postId,
                'reddit_url' => $permalink,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to post project to Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }
}
