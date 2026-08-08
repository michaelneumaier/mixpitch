<?php

namespace App\Livewire\Project;

use App\Livewire\Concerns\HasRedditPosting;
use App\Models\Project;
use App\Services\RedditService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * ShareProjectModal - Unified share sheet for a project.
 *
 * Aggregates the multiple share "sinks" (copy public link, post to r/MixPitch,
 * social share targets) into a single Flux modal. Owns the Reddit posting
 * state via the HasRedditPosting trait so the CTA reflects real server state
 * (isPostingToReddit, hasBeenPostedToReddit, rate-limit outcomes) without
 * needing to bounce events through the parent Manage component.
 *
 * Hidden entirely for Client Management projects (mirrors the existing
 * workflow gate on the header dropdown).
 */
class ShareProjectModal extends Component
{
    use AuthorizesRequests;
    use HasRedditPosting;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * The public URL producers land on when they click through from Reddit
     * or a social share.
     */
    #[Computed]
    public function publicUrl(): string
    {
        return route('projects.show', $this->project);
    }

    /**
     * Preview of the Markdown body that will be submitted to r/MixPitch.
     * Exposed as a computed so tests can assert it renders in the modal.
     */
    #[Computed]
    public function redditPreviewBody(): string
    {
        return app(RedditService::class)->buildPostBody($this->project);
    }

    /**
     * True when the project owner has connected their Reddit account.
     * Drives the "Post as u/MixPitch bot — Connect your Reddit account"
     * nudge, which is the highest-leverage OAuth conversion point.
     */
    #[Computed]
    public function ownerHasLinkedReddit(): bool
    {
        return $this->project->user?->hasLinkedReddit() ?? false;
    }

    /**
     * The Reddit share section is meaningful only for public workflows.
     * Client Management projects never surface community sharing.
     */
    #[Computed]
    public function showsRedditSection(): bool
    {
        return ! $this->project->isClientManagement();
    }

    public function render()
    {
        return view('livewire.project.share-project-modal');
    }
}
