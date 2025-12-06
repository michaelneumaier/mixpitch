<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\CommunicationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CommunicationHubFab extends Component
{
    public Project $project;

    public Pitch $pitch;

    public int $unreadCount = 0;

    protected CommunicationService $communicationService;

    public function boot(CommunicationService $communicationService): void
    {
        $this->communicationService = $communicationService;
    }

    public function mount(Project $project, Pitch $pitch): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->updateUnreadCount();
    }

    /**
     * Update the unread message count
     */
    public function updateUnreadCount(): void
    {
        $this->unreadCount = $this->communicationService->getUnreadCount(
            $this->pitch,
            Auth::id()
        );
    }

    /**
     * Open the communication hub modal
     */
    public function openHub(): void
    {
        // Dispatch event to open the Flux modal
        $this->dispatch('open-modal', name: 'communication-hub');

        // Notify the hub that it's been opened (for marking messages as read)
        $this->dispatch('communication-hub-opened');
    }

    /**
     * Update count when notified by the hub
     */
    #[On('unread-count-updated')]
    public function handleUnreadCountUpdated(int $count): void
    {
        $this->unreadCount = $count;
    }

    /**
     * Refresh count when a message is sent
     */
    #[On('message-sent')]
    public function handleMessageSent(): void
    {
        $this->updateUnreadCount();
    }

    /**
     * Refresh count from external events
     */
    #[On('refresh-communication-fab')]
    public function refreshFab(): void
    {
        $this->pitch->refresh();
        $this->updateUnreadCount();
    }

    public function render()
    {
        return view('livewire.project.component.communication-hub-fab');
    }
}
