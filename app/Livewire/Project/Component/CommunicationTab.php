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
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CommunicationTab extends Component
{
    public Project $project;

    public Pitch $pitch;

    public array $workflowColors = [];

    // Search
    public string $searchQuery = '';

    // Message composition
    public string $newMessage = '';

    public bool $isUrgent = false;

    // Sidebar activity expansion state
    public bool $showAllActivity = false;

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

    public function mount(Project $project, Pitch $pitch, array $workflowColors = []): void
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->workflowColors = $workflowColors;

        // Mark messages as read when tab is first opened
        $this->markMessagesAsRead();

        // Scroll to bottom after initial render - $this->js() runs after DOM is hydrated
        $this->js('$wire.scrollMessagesToBottom()');
    }

    /**
     * JavaScript helper to scroll messages to bottom (called from blade via $wire)
     */
    public function scrollMessagesToBottom(): void
    {
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Get chat messages (computed property)
     */
    #[Computed]
    public function chatMessages(): Collection
    {
        $search = strlen(trim($this->searchQuery)) >= 2 ? $this->searchQuery : null;

        return $this->communicationService->getMessages($this->pitch, 50, $search);
    }

    /**
     * Get activity timeline (computed property)
     */
    #[Computed]
    public function activityTimeline(): Collection
    {
        return $this->communicationService->getActivityTimeline($this->pitch);
    }

    /**
     * Get activity timeline for sidebar (limited to 5 items)
     */
    #[Computed]
    public function sidebarActivity(): Collection
    {
        return $this->activityTimeline->take(5);
    }

    /**
     * Get pending actions (computed property)
     */
    #[Computed]
    public function pendingActions(): Collection
    {
        return $this->communicationService->getPendingActions($this->pitch, Auth::id());
    }

    /**
     * Get unread count (computed property)
     */
    #[Computed]
    public function unreadCount(): int
    {
        return $this->communicationService->getUnreadCount(
            $this->pitch,
            Auth::id()
        );
    }

    /**
     * Handle search query updates
     */
    public function updatedSearchQuery(): void
    {
        // Force recomputation of chatMessages
        unset($this->chatMessages);
    }

    /**
     * Clear search
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        unset($this->chatMessages);
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

            // Clear computed caches
            unset($this->chatMessages);
            unset($this->activityTimeline);
            unset($this->sidebarActivity);
            unset($this->unreadCount);

            Toaster::success('Message sent to client.');

            // Dispatch event for other components (parent, etc.) - Livewire event
            $this->dispatch('message-sent');

            // Dispatch browser event for Alpine scroll-to-bottom
            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            Log::error('Failed to send producer message', [
                'pitch_id' => $this->pitch->id,
                'error' => $e->getMessage(),
            ]);
            Toaster::error('Failed to send message. Please try again.');
        }
    }

    /**
     * Mark all messages as read
     */
    public function markMessagesAsRead(): void
    {
        try {
            $this->communicationService->markAllAsRead(
                $this->pitch,
                Auth::id()
            );

            // Clear cache and notify FAB
            unset($this->unreadCount);
            $this->dispatch('unread-count-updated', count: 0);

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
                // Clear computed caches
                unset($this->chatMessages);
                unset($this->activityTimeline);
                unset($this->sidebarActivity);

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
     * Refresh data from external events
     */
    #[On('refresh-communication-tab')]
    #[On('message-sent')]
    public function refreshTab(): void
    {
        $this->pitch->refresh();

        // Clear all computed caches
        unset($this->chatMessages);
        unset($this->activityTimeline);
        unset($this->sidebarActivity);
        unset($this->pendingActions);
        unset($this->unreadCount);
    }

    /**
     * Toggle activity expansion in sidebar
     */
    public function toggleActivityExpansion(): void
    {
        $this->showAllActivity = ! $this->showAllActivity;
    }

    /**
     * Navigate to another tab (dispatch to parent)
     */
    public function navigateToTab(string $tab): void
    {
        $this->dispatch('switchToTab', tab: $tab);
    }

    /**
     * Switch mobile section to messages
     */
    public function switchToMessages(): void
    {
        $this->dispatch('switch-mobile-section', section: 'messages');
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
        return view('livewire.project.component.communication-tab');
    }
}
