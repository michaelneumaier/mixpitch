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

class UpdateRedditPostForProjectCompleted implements ShouldQueue
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

        $fullname = 't3_'.$this->project->reddit_post_id;
        $completedOn = ($this->project->completed_at ?? now())->format('M j, Y');

        $header = "---\n\n✅ **COMPLETED** on {$completedOn}\n\n---\n\n";
        $newBody = $header.($this->project->reddit_original_body ?? '');

        try {
            $reddit->editPost($fullname, $newBody);
        } catch (\Throwable $e) {
            Log::warning('Failed to edit Reddit post for project completion; continuing to comment', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
        }

        try {
            $reddit->postComment(
                $fullname,
                'This project is now complete. See the finished work on MixPitch → '
                .route('projects.show', $this->project)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to post completion comment on Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }
}
