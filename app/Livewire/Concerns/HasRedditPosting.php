<?php

namespace App\Livewire\Concerns;

use App\Jobs\DeleteRedditPost;
use App\Jobs\PostProjectToReddit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

/**
 * Trait for posting projects to r/MixPitch and polling Reddit posting status.
 * Used by ManageStandardProject and ManageContestProject.
 *
 * Requires a `public Project $project` on the consuming component.
 */
trait HasRedditPosting
{
    public bool $isPostingToReddit = false;

    public $redditPostingStartedAt = null;

    #[On('post-to-reddit')]
    public function postToReddit(): void
    {
        try {
            $this->authorize('update', $this->project);

            if ($this->isPostingToReddit) {
                Toaster::warning('Reddit posting is already in progress. Please wait...');

                return;
            }

            if (! $this->project->is_published) {
                Toaster::error('Project must be published before posting to Reddit.');

                return;
            }

            if (empty($this->project->title) || empty($this->project->description)) {
                Toaster::error('Project must have a title and description to post to Reddit.');

                return;
            }

            if ($this->project->hasBeenPostedToReddit()) {
                Toaster::warning('This project has already been posted to Reddit.');

                return;
            }

            $recentPosts = auth()->user()->projects()
                ->whereNotNull('reddit_posted_at')
                ->where('reddit_posted_at', '>', now()->subHour())
                ->count();

            if ($recentPosts >= 3) {
                Toaster::error('You can only post 3 projects per hour to Reddit. Please try again later.');

                return;
            }

            $this->isPostingToReddit = true;
            $this->redditPostingStartedAt = now();

            PostProjectToReddit::dispatch($this->project);

            Toaster::success('Your project is being posted to r/MixPitch! This may take a few moments...');

            $this->dispatch('start-reddit-polling');

        } catch (AuthorizationException $e) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;
            Toaster::error('You are not authorized to post this project.');
        } catch (\Exception $e) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;
            Log::error('Error posting project to Reddit', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An error occurred while posting to Reddit. Please try again.');
        }
    }

    #[On('unpost-from-reddit')]
    public function unpostFromReddit(): void
    {
        try {
            $this->authorize('update', $this->project);

            if (! $this->project->hasBeenPostedToReddit()) {
                Toaster::warning('This project is not currently posted to Reddit.');

                return;
            }

            DeleteRedditPost::dispatch($this->project);

            Toaster::success('Removing project from r/MixPitch...');

        } catch (AuthorizationException $e) {
            Toaster::error('You are not authorized to modify this project.');
        } catch (\Exception $e) {
            Log::error('Error dispatching Reddit post deletion', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('An error occurred while removing from Reddit. Please try again.');
        }
    }

    #[On('checkRedditStatus')]
    public function checkRedditStatus(): void
    {
        $this->project->refresh();

        if ($this->project->hasBeenPostedToReddit()) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::success('Successfully posted to r/MixPitch!');
            $this->dispatch('stop-reddit-polling');

            return;
        }

        if ($this->redditPostingStartedAt && now()->diffInMinutes($this->redditPostingStartedAt) > 5) {
            $this->isPostingToReddit = false;
            $this->redditPostingStartedAt = null;

            Toaster::warning('Reddit posting is taking longer than expected. Please check back later.');
            $this->dispatch('stop-reddit-polling');
        }
    }
}
