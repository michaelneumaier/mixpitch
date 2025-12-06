<?php

namespace App\Livewire\Project\Component;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Services\CommunicationExportService;
use App\Services\CommunicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CommunicationHub extends Component
{
    public Project $project;

    public Pitch $pitch;

    // Tab state
    public string $activeTab = 'messages';

    // Search
    public string $searchQuery = '';

    // Message composition
    public string $newMessage = '';

    public bool $isUrgent = false;

    // Data collections
    public Collection $chatMessages;

    public Collection $activityTimeline;

    public Collection $pendingActions;

    public int $unreadCount = 0;

    protected CommunicationService $communicationService;

    protected CommunicationExportService $exportService;

    protected $rules = [
        'newMessage' => 'required|string|max:2000',
    ];

    protected $validationMessages = [
        'newMessage.required' => 'Please enter a message.',
        'newMessage.max' => 'Message cannot exceed 2000 characters.',
    ];

    public function boot(
        CommunicationService $communicationService,
        CommunicationExportService $exportService
    ): void {
        $this->communicationService = $communicationService;
        $this->exportService = $exportService;
    }

    public function mount(Project $project, Pitch $pitch): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
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
        $this->pendingActions = $this->communicationService->getPendingActions($this->pitch, Auth::id());
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
            Auth::id()
        );
    }

    /**
     * Send a message to the client
     */
    public function sendMessage(): void
    {
        $this->validate();

        try {
            $this->communicationService->sendProducerMessage(
                $this->pitch,
                Auth::user(),
                $this->newMessage,
                $this->isUrgent
            );

            $this->newMessage = '';
            $this->isUrgent = false;
            $this->loadData();

            Toaster::success('Message sent to client.');

            // Dispatch event for other components
            $this->dispatch('message-sent');

        } catch (\Exception $e) {
            Log::error('Failed to send producer message', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to send message. Please try again.');
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
                Auth::id()
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
     * Delete a message
     */
    public function deleteMessage(int $eventId): void
    {
        try {
            $event = PitchEvent::findOrFail($eventId);

            if ($this->communicationService->deleteMessage($event, Auth::user())) {
                $this->loadData();
                Toaster::success('Message deleted.');
            } else {
                Toaster::error('Cannot delete this message.');
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete message', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to delete message.');
        }
    }

    /**
     * Switch active tab
     */
    public function setActiveTab(string $tab): void
    {
        if (in_array($tab, ['messages', 'activity', 'actions'])) {
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
     * Open printable conversation view in new tab
     */
    public function exportPrint(): void
    {
        $url = route('pitch.communication.print', [
            'project' => $this->project,
            'pitch' => $this->pitch,
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
            'submission_recalled' => 'recall',
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
            'recall' => 'arrow-uturn-left',
            'work_session_completed' => 'clock',
            default => 'information-circle',
        };
    }

    /**
     * Get priority badge class
     */
    public function getPriorityClass(string $priority): string
    {
        return match ($priority) {
            'high' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200',
            'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    /**
     * Get available quick response templates
     */
    public function getQuickTemplates(): array
    {
        $clientName = $this->project->client_name ?? 'there';

        return [
            [
                'label' => 'Ready for Review',
                'icon' => 'check-circle',
                'message' => "Hi {$clientName},\n\nI've uploaded the latest version for your review. Please take a listen and let me know your thoughts!\n\nBest regards",
            ],
            [
                'label' => 'Revisions Complete',
                'icon' => 'arrow-path',
                'message' => "Hi {$clientName},\n\nI've made the revisions you requested. The updated files are ready for your review.\n\nPlease let me know if there's anything else you'd like me to adjust.",
            ],
            [
                'label' => 'Question',
                'icon' => 'question-mark-circle',
                'message' => "Hi {$clientName},\n\nI had a quick question about the project:\n\n[Your question here]\n\nLooking forward to hearing from you!",
            ],
            [
                'label' => 'Progress Update',
                'icon' => 'clock',
                'message' => "Hi {$clientName},\n\nJust wanted to give you a quick update on the project progress.\n\n[Update details here]\n\nI'll keep you posted on the next steps!",
            ],
            [
                'label' => 'Thank You',
                'icon' => 'heart',
                'message' => "Hi {$clientName},\n\nThank you so much for your feedback! I really appreciate you taking the time to share your thoughts.\n\nBest regards",
            ],
        ];
    }

    /**
     * Use a quick template to fill the message input
     */
    public function useTemplate(int $index): void
    {
        $templates = $this->getQuickTemplates();

        if (isset($templates[$index])) {
            $this->newMessage = $templates[$index]['message'];
        }
    }

    public function render()
    {
        return view('livewire.project.component.communication-hub');
    }
}
