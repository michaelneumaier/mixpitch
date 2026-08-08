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

class UpdateRedditPostForContestWinner implements ShouldQueue
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
        public Pitch $winningPitch,
    ) {}

    public function handle(RedditService $reddit): void
    {
        $this->project->refresh();

        // Post may have been removed since dispatch — nothing to sync.
        if (empty($this->project->reddit_post_id)) {
            return;
        }

        $fullname = 't3_'.$this->project->reddit_post_id;
        $winner = $this->winningPitch->user;
        $winnerLabel = $winner?->hasLinkedReddit()
            ? 'u/'.$winner->reddit_username
            : ($winner ? '@'.$winner->username.' on MixPitch' : 'a producer');

        $header = "---\n\n🏆 **WINNER SELECTED** — "
            .now()->format('M j, Y')
            .". Winner: {$winnerLabel}\n\n---\n\n";

        $newBody = $header.($this->project->reddit_original_body ?? '');

        try {
            $reddit->editPost($fullname, $newBody);
        } catch (\Throwable $e) {
            Log::warning('Failed to edit Reddit post for contest winner; continuing to comment', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            // Fall through — comment is the more visible signal anyway.
        }

        try {
            $reddit->postComment(
                $fullname,
                "This contest has a winner! Congratulations to **{$winnerLabel}**. "
                .'See the results on MixPitch → '.route('projects.show', $this->project)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to post contest-winner comment on Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }
}
