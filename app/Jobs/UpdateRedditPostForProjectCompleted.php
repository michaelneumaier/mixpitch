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

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }
            throw $e;
        }
    }
}
