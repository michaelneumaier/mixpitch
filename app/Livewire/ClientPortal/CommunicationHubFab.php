<?php

namespace App\Livewire\ClientPortal;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\CommunicationService;
use App\Services\WorkSessionService;
use Livewire\Attributes\On;
use Livewire\Component;

class CommunicationHubFab extends Component
{
    public Project $project;

    public Pitch $pitch;

    public string $clientEmail;

    public int $unreadCount = 0;

    public array $producerPresence = [];

    protected CommunicationService $communicationService;

    protected WorkSessionService $sessionService;

    public function boot(
        CommunicationService $communicationService,
        WorkSessionService $sessionService
    ): void {
        $this->communicationService = $communicationService;
        $this->sessionService = $sessionService;
    }

    public function mount(Project $project, Pitch $pitch, string $clientEmail): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->clientEmail = $clientEmail;
        $this->updateUnreadCount();
        $this->updatePresence();
    }

    /**
     * Update the unread message count
     */
    public function updateUnreadCount(): void
    {
        $this->unreadCount = $this->communicationService->getUnreadCount(
            $this->pitch,
            userId: null,
            isClient: true,
            clientEmail: $this->clientEmail
        );
    }

    /**
     * Update the producer presence status
     */
    public function updatePresence(): void
    {
        $this->producerPresence = $this->sessionService->getProducerPresenceForPitch($this->pitch);
    }

    /**
     * Open the communication hub modal
     */
    public function openHub(): void
    {
        // Open the Flux modal using Livewire's modal control
        $this->modal('communication-hub')->show();

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
        $this->updatePresence();
    }

    public function render()
    {
        return view('livewire.client-portal.communication-hub-fab');
    }
}
