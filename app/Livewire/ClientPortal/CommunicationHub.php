<?php

namespace App\Livewire\ClientPortal;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Services\CommunicationExportService;
use App\Services\CommunicationService;
use App\Services\WorkSessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CommunicationHub extends Component
{
    public Project $project;

    public Pitch $pitch;

    public string $clientEmail;

    public ?string $clientName;

    // Tab state
    public string $activeTab = 'messages';

    // Search
    public string $searchQuery = '';

    // Message composition
    public string $newMessage = '';

    // Data collections
    public Collection $chatMessages;

    public Collection $activityTimeline;

    public int $unreadCount = 0;

    public array $producerPresence = [];

    protected CommunicationService $communicationService;

    protected CommunicationExportService $exportService;

    protected WorkSessionService $sessionService;

    protected $rules = [
        'newMessage' => 'required|string|max:2000',
    ];

    public function boot(
        CommunicationService $communicationService,
        CommunicationExportService $exportService,
        WorkSessionService $sessionService
    ): void {
        $this->communicationService = $communicationService;
        $this->exportService = $exportService;
        $this->sessionService = $sessionService;
    }

    public function mount(Project $project, Pitch $pitch, string $clientEmail, ?string $clientName = null): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->clientEmail = $clientEmail;
        $this->clientName = $clientName ?? $project->client_name;
        $this->loadData();
    }

    /**
     * Load all communication data
     */
    public function loadData(): void
    {
        $search = strlen(trim($this->searchQuery)) >= 2 ? $this->searchQuery : null;
        $this->chatMessages = $this->communicationService->getMessages($this->pitch, 50, $search);
        $this->activityTimeline = $this->communicationService->getActivityTimeline($this->pitch);
        $this->producerPresence = $this->sessionService->getProducerPresenceForPitch($this->pitch);
        $this->updateUnreadCount();
    }

    /**
     * Handle search query updates
     */
    public function updatedSearchQuery(): void
    {
        $search = strlen(trim($this->searchQuery)) >= 2 ? $this->searchQuery : null;
        $this->chatMessages = $this->communicationService->getMessages($this->pitch, 50, $search);
    }

    /**
     * Clear search
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->chatMessages = $this->communicationService->getMessages($this->pitch);
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
     * Send a message to the producer
     */
    public function sendMessage(): void
    {
        $this->validate();

        try {
            $this->communicationService->sendClientMessage(
                $this->pitch,
                $this->clientEmail,
                $this->newMessage,
                $this->clientName
            );

            $this->newMessage = '';
            $this->loadData();

            // Show success toast (handled by client-side)
            $this->dispatch('message-sent');

        } catch (\Exception $e) {
            Log::error('Failed to send client message', [
                'pitch_id' => $this->pitch->id,
                'client_email' => $this->clientEmail,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('message-error');
        }
    }

    /**
     * Mark all messages as read when hub is opened
     */
    #[On('communication-hub-opened')]
    public function markMessagesAsRead(): void
    {
        try {
            $this->communicationService->markAllAsRead(
                $this->pitch,
                userId: null,
                isClient: true,
                clientEmail: $this->clientEmail
            );
            $this->updateUnreadCount();

            // Notify FAB to update its count
            $this->dispatch('unread-count-updated', count: $this->unreadCount);

        } catch (\Exception $e) {
            Log::warning('Failed to mark messages as read', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Switch active tab
     */
    public function setActiveTab(string $tab): void
    {
        if (in_array($tab, ['messages', 'activity'])) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Refresh data from external events
     */
    #[On('refresh-communication-hub')]
    public function refreshHub(): void
    {
        $this->pitch->refresh();
        $this->loadData();
    }

    /**
     * Export conversation as JSON download
     */
    public function exportJson(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->exportService->exportAsJson($this->pitch);
        $filename = sprintf(
            'conversation_%s_%s.json',
            \Str::slug($this->project->name),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Open printable conversation view in new tab (via signed URL)
     */
    public function exportPrint(): void
    {
        $url = \URL::signedRoute('client.portal.communication.print', [
            'project' => $this->project->id,
            'pitch' => $this->pitch->id,
        ]);

        $this->dispatch('open-print-view', url: $url);
    }

    /**
     * Get the display type for an event
     */
    public function getEventDisplayType(PitchEvent $event): string
    {
        return match ($event->event_type) {
            PitchEvent::TYPE_CLIENT_MESSAGE => 'client_message',
            PitchEvent::TYPE_PRODUCER_MESSAGE => 'producer_message',
            PitchEvent::TYPE_CLIENT_APPROVED => 'approval',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'revision_request',
            PitchEvent::TYPE_STATUS_CHANGE => 'status_change',
            PitchEvent::TYPE_FILE_UPLOADED => 'file_uploaded',
            PitchEvent::TYPE_WORK_SESSION_COMPLETED => 'work_session_completed',
            'revisions_requested' => 'revision_request',
            default => 'activity',
        };
    }

    /**
     * Get color classes for event type
     */
    public function getEventColorClass(string $eventType): string
    {
        return match ($eventType) {
            'client_message' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
            'producer_message' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200',
            'approval' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
            'revision_request' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
            'status_change' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            'file_uploaded' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-200',
            'work_session_completed' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    /**
     * Get icon for event type
     */
    public function getEventIcon(string $eventType): string
    {
        return match ($eventType) {
            'client_message' => 'chat-bubble-left',
            'producer_message' => 'chat-bubble-left-ellipsis',
            'approval' => 'check-circle',
            'revision_request' => 'arrow-path',
            'status_change' => 'arrow-right-circle',
            'file_uploaded' => 'document-arrow-up',
            'work_session_completed' => 'clock',
            default => 'information-circle',
        };
    }

    public function render()
    {
        return view('livewire.client-portal.communication-hub');
    }
}
