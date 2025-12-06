<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CommunicationService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Send a message from producer to client
     *
     * @param  Pitch  $pitch  The pitch to send the message on
     * @param  User  $producer  The producer sending the message
     * @param  string  $message  The message content
     * @param  bool  $isUrgent  Whether this is an urgent message
     * @return PitchEvent The created event
     */
    public function sendProducerMessage(
        Pitch $pitch,
        User $producer,
        string $message,
        bool $isUrgent = false
    ): PitchEvent {
        $event = $pitch->events()->create([
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => $message,
            'status' => $pitch->status,
            'created_by' => $producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'is_urgent' => $isUrgent,
            'metadata' => [
                'visible_to_client' => true,
                'comment_type' => 'producer_update',
            ],
        ]);

        // Send email notification to client
        $project = $pitch->project;
        if ($project->client_email) {
            try {
                $this->notificationService->notifyClientProducerCommented(
                    $pitch,
                    $message
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send client notification for producer message', [
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Producer message sent', [
            'pitch_id' => $pitch->id,
            'producer_id' => $producer->id,
            'event_id' => $event->id,
            'is_urgent' => $isUrgent,
        ]);

        return $event;
    }

    /**
     * Send a message from client to producer
     *
     * @param  Pitch  $pitch  The pitch to send the message on
     * @param  string  $clientEmail  The client's email address
     * @param  string  $message  The message content
     * @param  string|null  $clientName  The client's name (optional)
     * @return PitchEvent The created event
     */
    public function sendClientMessage(
        Pitch $pitch,
        string $clientEmail,
        string $message,
        ?string $clientName = null
    ): PitchEvent {
        $project = $pitch->project;
        $clientName = $clientName ?? $project->client_name;

        $event = $pitch->events()->create([
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => $message,
            'status' => $pitch->status,
            'created_by' => null, // Guest client - no user ID
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => [
                'client_email' => $clientEmail,
                'client_name' => $clientName,
                'visible_to_client' => true,
            ],
        ]);

        // Notify producer
        try {
            $this->notificationService->notifyProducerClientCommented(
                $pitch,
                $message
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send producer notification for client message', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Client message sent', [
            'pitch_id' => $pitch->id,
            'client_email' => $clientEmail,
            'event_id' => $event->id,
        ]);

        return $event;
    }

    /**
     * Mark an event as read
     *
     * @param  PitchEvent  $event  The event to mark as read
     * @param  int|null  $userId  The user ID (null for guest/client)
     * @param  bool  $isClient  Whether this is a client reading
     * @param  string|null  $clientEmail  Client email if applicable
     */
    public function markAsRead(
        PitchEvent $event,
        ?int $userId = null,
        bool $isClient = false,
        ?string $clientEmail = null
    ): void {
        $event->markAsRead($userId, $isClient, $clientEmail);
    }

    /**
     * Mark all unread messages as read for a pitch
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int|null  $userId  The user ID (null for guest/client)
     * @param  bool  $isClient  Whether this is a client reading
     * @param  string|null  $clientEmail  Client email if applicable
     * @return int Number of events marked as read
     */
    public function markAllAsRead(
        Pitch $pitch,
        ?int $userId = null,
        bool $isClient = false,
        ?string $clientEmail = null
    ): int {
        $unreadEvents = $pitch->events()
            ->messages()
            ->unread()
            ->get();

        foreach ($unreadEvents as $event) {
            // Only mark as read if the reader didn't create the event
            if ($userId !== null && $event->created_by === $userId) {
                continue;
            }

            // For clients, don't mark their own messages
            if ($isClient && $event->event_type === PitchEvent::TYPE_CLIENT_MESSAGE) {
                $eventClientEmail = $event->metadata['client_email'] ?? null;
                if ($eventClientEmail === $clientEmail) {
                    continue;
                }
            }

            $event->markAsRead($userId, $isClient, $clientEmail);
        }

        return $unreadEvents->count();
    }

    /**
     * Get unread count for a pitch (for a specific reader)
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int|null  $userId  The user ID (null for all)
     * @param  bool  $isClient  Whether checking for client
     * @param  string|null  $clientEmail  Client email if checking for client
     * @return int Unread count
     */
    public function getUnreadCount(
        Pitch $pitch,
        ?int $userId = null,
        bool $isClient = false,
        ?string $clientEmail = null
    ): int {
        $query = $pitch->events()->messages()->unread();

        // For producers, don't count their own messages
        // Note: We need to handle NULL created_by (client messages) properly
        if ($userId !== null && ! $isClient) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', '!=', $userId);
            });
        }

        // For clients, count producer messages only
        if ($isClient) {
            $query->where('event_type', PitchEvent::TYPE_PRODUCER_MESSAGE);
        }

        return $query->count();
    }

    /**
     * Get messages for the communication hub
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int  $limit  Maximum number of messages
     * @param  string|null  $search  Optional search query
     * @return Collection Messages ordered oldest to newest
     */
    public function getMessages(Pitch $pitch, int $limit = 50, ?string $search = null): Collection
    {
        $query = $pitch->events()
            ->messages()
            ->with('user');

        // Apply search filter if provided
        if ($search && strlen(trim($search)) >= 2) {
            $searchTerm = '%'.trim($search).'%';
            $query->where('comment', 'like', $searchTerm);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Search messages across all communication
     *
     * @param  Pitch  $pitch  The pitch
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum number of results
     * @return Collection Matching messages
     */
    public function searchMessages(Pitch $pitch, string $query, int $limit = 50): Collection
    {
        if (strlen(trim($query)) < 2) {
            return collect();
        }

        $searchTerm = '%'.trim($query).'%';

        return $pitch->events()
            ->messages()
            ->with('user')
            ->where('comment', 'like', $searchTerm)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity timeline for the communication hub
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int  $limit  Maximum number of events
     * @return Collection Events ordered newest first
     */
    public function getActivityTimeline(Pitch $pitch, int $limit = 30): Collection
    {
        return $pitch->events()
            ->forCommunicationHub()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending actions for a user
     *
     * @param  Pitch  $pitch  The pitch
     * @param  int|null  $userId  The user ID
     * @return Collection Pending actions
     */
    public function getPendingActions(Pitch $pitch, ?int $userId = null): Collection
    {
        $actions = collect();

        // Pending revision feedback (producer needs to respond)
        if (in_array($pitch->status, [
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
        ])) {
            $latestRevision = $pitch->events()
                ->whereIn('event_type', [
                    'revisions_requested',
                    PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
                ])
                ->latest()
                ->first();

            if ($latestRevision) {
                $actions->push([
                    'type' => 'revision_pending',
                    'title' => 'Respond to Revision Request',
                    'description' => $latestRevision->comment,
                    'event' => $latestRevision,
                    'action_url' => '#response-to-feedback',
                    'priority' => 'high',
                ]);
            }
        }

        // Unread messages
        $unreadCount = $this->getUnreadCount($pitch, $userId);
        if ($unreadCount > 0) {
            $actions->push([
                'type' => 'unread_messages',
                'title' => 'Unread Messages',
                'description' => "{$unreadCount} message(s) waiting for your review",
                'action_url' => '#messages',
                'priority' => 'medium',
            ]);
        }

        // Unresolved file comments
        $unresolvedCount = $pitch->files()
            ->with(['comments' => fn ($q) => $q->where('resolved', false)])
            ->get()
            ->flatMap->comments
            ->count();

        if ($unresolvedCount > 0) {
            $actions->push([
                'type' => 'unresolved_comments',
                'title' => 'Unresolved File Comments',
                'description' => "{$unresolvedCount} comment(s) need attention",
                'action_url' => '#producer-deliverables',
                'priority' => 'low',
            ]);
        }

        return $actions->sortByDesc(function ($action) {
            return match ($action['priority']) {
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 0,
            };
        })->values();
    }

    /**
     * Delete a message (only own messages)
     *
     * @param  PitchEvent  $event  The event to delete
     * @param  User  $user  The user attempting deletion
     * @return bool Whether deletion was successful
     */
    public function deleteMessage(PitchEvent $event, User $user): bool
    {
        // Can only delete own messages
        if ($event->created_by !== $user->id) {
            Log::warning('Attempted to delete message not owned by user', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'event_created_by' => $event->created_by,
            ]);

            return false;
        }

        // Can only delete message-type events
        if (! $event->isMessage()) {
            Log::warning('Attempted to delete non-message event', [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
            ]);

            return false;
        }

        $event->delete();

        Log::info('Message deleted', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Get conversation items formatted for display
     * Similar to existing conversationItems in ManageClientProject
     *
     * @param  Pitch  $pitch  The pitch
     * @return Collection Formatted conversation items
     */
    public function getConversationItems(Pitch $pitch): Collection
    {
        $events = $pitch->events()
            ->forCommunicationHub()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'type' => $this->getDisplayType($event),
                'content' => $event->comment,
                'timestamp' => $event->created_at,
                'user' => $event->user,
                'metadata' => $event->metadata ?? [],
                'read_at' => $event->read_at,
                'is_urgent' => $event->is_urgent,
                'delivery_status' => $event->delivery_status,
                'event' => $event,
            ];
        });
    }

    /**
     * Get display type for an event
     */
    private function getDisplayType(PitchEvent $event): string
    {
        return match ($event->event_type) {
            PitchEvent::TYPE_CLIENT_MESSAGE => 'client_message',
            PitchEvent::TYPE_PRODUCER_MESSAGE => 'producer_message',
            PitchEvent::TYPE_CLIENT_APPROVED => 'approval',
            PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED => 'revision_request',
            PitchEvent::TYPE_STATUS_CHANGE => 'status_update',
            PitchEvent::TYPE_FILE_UPLOADED => 'file_activity',
            'submission_recalled' => 'recall',
            default => 'unknown',
        };
    }
}
