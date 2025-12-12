<?php

namespace App\Livewire\Project;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ProjectHeader extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public bool $hasPreviewTrack = false;

    public bool $showEditButton = true;

    public string $context = 'view';

    public bool $showActions = true;

    public ?Pitch $userPitch = null;

    public bool $canPitch = false;

    public ?bool $autoAllowAccess = null;

    public bool $showWorkflowStatus = false;

    public function mount(
        Project $project,
        bool $hasPreviewTrack = false,
        bool $showEditButton = true,
        string $context = 'view',
        bool $showActions = true,
        ?Pitch $userPitch = null,
        bool $canPitch = false,
        ?bool $autoAllowAccess = null,
        bool $showWorkflowStatus = false
    ): void {
        $this->project = $project;
        $this->hasPreviewTrack = $hasPreviewTrack;
        $this->showEditButton = $showEditButton;
        $this->context = $context;
        $this->showActions = $showActions;
        $this->userPitch = $userPitch;
        $this->canPitch = $canPitch;
        $this->autoAllowAccess = $autoAllowAccess ?? $project->auto_allow_access;
        $this->showWorkflowStatus = $showWorkflowStatus;
    }

    /**
     * Update project title (inline editing)
     */
    public function updateProjectTitle(string $title): void
    {
        $this->authorize('update', $this->project);
        $this->project->update(['name' => $title]);
        $this->project->refresh();
    }

    /**
     * Show image upload modal - dispatch to parent
     */
    public function showImageUpload(): void
    {
        $this->dispatch('show-image-upload');
    }

    /**
     * Remove project image - dispatch to parent
     */
    public function removeProjectImage(): void
    {
        $this->dispatch('remove-project-image');
    }

    /**
     * Publish project - dispatch to parent
     */
    public function publish(): void
    {
        $this->dispatch('publish-project');
    }

    /**
     * Unpublish project - dispatch to parent
     */
    public function unpublish(): void
    {
        $this->dispatch('unpublish-project');
    }

    /**
     * Preview client portal - dispatch to parent
     */
    public function previewClientPortal(): void
    {
        $this->dispatch('preview-client-portal');
    }

    /**
     * Resend client invite - dispatch to parent
     */
    public function resendClientInvite(): void
    {
        $this->dispatch('resend-client-invite');
    }

    /**
     * Post to Reddit - dispatch to parent
     */
    public function postToReddit(): void
    {
        $this->dispatch('post-to-reddit');
    }

    /**
     * Confirm delete project - dispatch to parent
     */
    public function confirmDeleteProject(): void
    {
        $this->dispatch('confirm-delete-project');
    }

    /**
     * Toggle auto-allow access
     */
    public function toggleAutoAllowAccess(): void
    {
        $this->autoAllowAccess = ! $this->autoAllowAccess;
        $this->dispatch('toggle-auto-allow-access', autoAllowAccess: $this->autoAllowAccess);
    }

    /**
     * Get other projects for the same client (for client management dropdown)
     */
    public function getClientProjectsProperty()
    {
        if (! $this->project->isClientManagement() || ! $this->project->client) {
            return collect();
        }

        return $this->project->client->projects()
            ->where('id', '!=', $this->project->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.project.project-header');
    }
}
