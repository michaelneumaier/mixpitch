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

    public int $backoff = 900; // 15 minutes

    public function __construct(
        public Project $project
    ) {}

    public function handle(RedditService $redditService): void
    {
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

            // Update project with Reddit post information
            $this->project->update([
                'reddit_post_id' => $postId,
                'reddit_permalink' => $permalink,
                'reddit_posted_at' => now(),
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

            // Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }

            throw $e;
        }
    }
}
