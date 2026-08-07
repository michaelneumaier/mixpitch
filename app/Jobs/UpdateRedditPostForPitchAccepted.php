<?php

namespace App\Jobs;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateRedditPostForPitchAccepted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 900;

    public function __construct(
        public Project $project,
        public Pitch $pitch,
    ) {}

    public function handle(RedditService $reddit): void
    {
        $this->project->refresh();

        // Post may have been removed since dispatch — nothing to sync.
        if (empty($this->project->reddit_post_id)) {
            return;
        }

        $fullname = 't3_'.$this->project->reddit_post_id;
        $producer = $this->pitch->user;
        $producerLabel = $producer?->hasLinkedReddit()
            ? 'u/'.$producer->reddit_username
            : ($producer ? '@'.$producer->username.' on MixPitch' : 'a producer');

        $header = "---\n\n🎧 **IN PROGRESS** — accepted "
            .now()->format('M j, Y')
            .". Producer: {$producerLabel}\n\n---\n\n";

        $newBody = $header.($this->project->reddit_original_body ?? '');

        try {
            $reddit->editPost($fullname, $newBody);
        } catch (\Throwable $e) {
            Log::warning('Failed to edit Reddit post for pitch acceptance; continuing to comment', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            // Fall through — comment is the more visible signal anyway.
        }

        try {
            $reddit->postComment(
                $fullname,
                "This project has been accepted by **{$producerLabel}**. "
                .'Follow along on MixPitch → '.route('projects.show', $this->project)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to post pitch-accepted comment on Reddit', [
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
