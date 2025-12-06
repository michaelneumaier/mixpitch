<?php

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\NotificationService;
use Mockery\MockInterface;

beforeEach(function () {
    // Create test fixtures
    $this->producer = User::factory()->create(['name' => 'Test Producer']);
    $this->projectOwner = User::factory()->create(['name' => 'Project Owner']);

    $this->project = Project::factory()->create([
        'user_id' => $this->projectOwner->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'client_email' => 'client@example.com',
        'client_name' => 'Test Client',
    ]);

    $this->pitch = Pitch::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->producer->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    // Mock NotificationService to avoid side effects
    $this->notificationService = mock(NotificationService::class, function (MockInterface $mock) {
        $mock->shouldReceive('notifyClientProducerCommented')->andReturnNull();
        $mock->shouldReceive('notifyProducerClientCommented')->andReturnNull();
    });

    $this->communicationService = new CommunicationService($this->notificationService);
});

describe('sendProducerMessage', function () {
    it('creates a producer message event', function () {
        $message = 'Hello client, here are the updated files!';

        $event = $this->communicationService->sendProducerMessage(
            $this->pitch,
            $this->producer,
            $message
        );

        expect($event)
            ->toBeInstanceOf(PitchEvent::class)
            ->and($event->event_type)->toBe(PitchEvent::TYPE_PRODUCER_MESSAGE)
            ->and($event->comment)->toBe($message)
            ->and($event->created_by)->toBe($this->producer->id)
            ->and($event->pitch_id)->toBe($this->pitch->id)
            ->and($event->delivery_status)->toBe(PitchEvent::DELIVERY_DELIVERED)
            ->and($event->is_urgent)->toBeFalse();
    });

    it('creates an urgent producer message when flag is set', function () {
        $message = 'URGENT: Please review immediately!';

        $event = $this->communicationService->sendProducerMessage(
            $this->pitch,
            $this->producer,
            $message,
            isUrgent: true
        );

        expect($event->is_urgent)->toBeTrue();
    });

    it('includes visible_to_client metadata', function () {
        $event = $this->communicationService->sendProducerMessage(
            $this->pitch,
            $this->producer,
            'Test message'
        );

        expect($event->metadata)
            ->toBeArray()
            ->toHaveKey('visible_to_client')
            ->and($event->metadata['visible_to_client'])->toBeTrue()
            ->and($event->metadata['comment_type'])->toBe('producer_update');
    });

    it('persists the event to the database', function () {
        $event = $this->communicationService->sendProducerMessage(
            $this->pitch,
            $this->producer,
            'Database test message'
        );

        $this->assertDatabaseHas('pitch_events', [
            'id' => $event->id,
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'created_by' => $this->producer->id,
        ]);
    });
});

describe('sendClientMessage', function () {
    it('creates a client message event', function () {
        $message = 'Thanks for the update! Can you adjust the bass?';
        $clientEmail = 'client@example.com';

        $event = $this->communicationService->sendClientMessage(
            $this->pitch,
            $clientEmail,
            $message
        );

        expect($event)
            ->toBeInstanceOf(PitchEvent::class)
            ->and($event->event_type)->toBe(PitchEvent::TYPE_CLIENT_MESSAGE)
            ->and($event->comment)->toBe($message)
            ->and($event->created_by)->toBeNull() // Guest client has no user ID
            ->and($event->pitch_id)->toBe($this->pitch->id)
            ->and($event->delivery_status)->toBe(PitchEvent::DELIVERY_DELIVERED);
    });

    it('includes client_email in metadata', function () {
        $clientEmail = 'specific-client@example.com';

        $event = $this->communicationService->sendClientMessage(
            $this->pitch,
            $clientEmail,
            'Test message'
        );

        expect($event->metadata)
            ->toBeArray()
            ->toHaveKey('client_email')
            ->and($event->metadata['client_email'])->toBe($clientEmail)
            ->and($event->metadata['visible_to_client'])->toBeTrue();
    });

    it('includes client_name from parameter when provided', function () {
        $event = $this->communicationService->sendClientMessage(
            $this->pitch,
            'client@example.com',
            'Test message',
            clientName: 'Custom Client Name'
        );

        expect($event->metadata['client_name'])->toBe('Custom Client Name');
    });

    it('uses project client_name as fallback', function () {
        $event = $this->communicationService->sendClientMessage(
            $this->pitch,
            'client@example.com',
            'Test message'
        );

        expect($event->metadata['client_name'])->toBe('Test Client');
    });
});

describe('markAsRead', function () {
    it('marks event as read for a user', function () {
        $event = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Test message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $this->communicationService->markAsRead($event, $this->producer->id);

        $event->refresh();
        expect($event->read_at)->not->toBeNull()
            ->and($event->delivery_status)->toBe(PitchEvent::DELIVERY_READ)
            ->and($event->read_by)->toBeArray()
            ->and($event->isReadBy($this->producer->id))->toBeTrue();
    });

    it('marks event as read for a client', function () {
        $event = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Test message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);
        $clientEmail = 'client@example.com';

        $this->communicationService->markAsRead($event, null, true, $clientEmail);

        $event->refresh();
        expect($event->read_at)->not->toBeNull()
            ->and($event->delivery_status)->toBe(PitchEvent::DELIVERY_READ)
            ->and($event->isReadBy(clientEmail: $clientEmail))->toBeTrue();
    });
});

describe('markAllAsRead', function () {
    it('marks all unread messages as read for a user', function () {
        // Create multiple unread messages
        $messages = collect([
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
                'comment' => 'Message 1',
                'status' => $this->pitch->status,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]),
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
                'comment' => 'Message 2',
                'status' => $this->pitch->status,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]),
        ]);

        $count = $this->communicationService->markAllAsRead($this->pitch, $this->producer->id);

        expect($count)->toBe(2);

        foreach ($messages as $message) {
            $message->refresh();
            expect($message->read_at)->not->toBeNull();
        }
    });

    it('does not mark own messages as read', function () {
        // Create a message from the producer
        $ownMessage = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'My own message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $this->communicationService->markAllAsRead($this->pitch, $this->producer->id);

        $ownMessage->refresh();
        // Own message should still be unread (from producer's perspective)
        expect($ownMessage->isReadBy($this->producer->id))->toBeFalse();
    });
});

describe('getUnreadCount', function () {
    it('returns count of unread messages for a producer', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create 3 unread client messages
        for ($i = 0; $i < 3; $i++) {
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
                'comment' => "Message {$i}",
                'status' => $this->pitch->status,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]);
        }

        $count = $this->communicationService->getUnreadCount($this->pitch, $this->producer->id);

        expect($count)->toBe(3);
    });

    it('excludes own messages from unread count', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create 2 client messages
        for ($i = 0; $i < 2; $i++) {
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
                'comment' => "Client message {$i}",
                'status' => $this->pitch->status,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]);
        }

        // Create 1 producer message (own message)
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Producer message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $count = $this->communicationService->getUnreadCount($this->pitch, $this->producer->id);

        expect($count)->toBe(2); // Only client messages
    });

    it('returns count of producer messages for client', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create producer messages
        for ($i = 0; $i < 2; $i++) {
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
                'comment' => "Producer message {$i}",
                'status' => $this->pitch->status,
                'created_by' => $this->producer->id,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]);
        }

        // Create client message (should not count)
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Client message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $count = $this->communicationService->getUnreadCount(
            $this->pitch,
            userId: null,
            isClient: true,
            clientEmail: 'client@example.com'
        );

        expect($count)->toBe(2); // Only producer messages
    });
});

describe('getMessages', function () {
    it('returns messages ordered oldest to newest', function () {
        // Clear any existing events for clean test
        PitchEvent::where('pitch_id', $this->pitch->id)->delete();

        // Create messages at specific times - using Carbon to ensure correct timestamps
        $oldTime = now()->subMinutes(10);
        $newTime = now()->subMinutes(5);

        // Create older message first
        $oldMessage = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'OLDEST_MESSAGE_FOR_TEST',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);
        // Manually set the timestamp to avoid any automatic timestamp issues
        $oldMessage->created_at = $oldTime;
        $oldMessage->save();

        // Create newer message
        $newMessage = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'NEWEST_MESSAGE_FOR_TEST',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);
        $newMessage->created_at = $newTime;
        $newMessage->save();

        $this->pitch->refresh();
        $messages = $this->communicationService->getMessages($this->pitch);

        // Find our test messages by their unique comments
        $oldFound = $messages->firstWhere('comment', 'OLDEST_MESSAGE_FOR_TEST');
        $newFound = $messages->firstWhere('comment', 'NEWEST_MESSAGE_FOR_TEST');

        expect($oldFound)->not->toBeNull()
            ->and($newFound)->not->toBeNull();

        // Verify oldest message comes before newest in the collection (older created_at should have lower index)
        $oldIndex = $messages->search(fn ($m) => $m->comment === 'OLDEST_MESSAGE_FOR_TEST');
        $newIndex = $messages->search(fn ($m) => $m->comment === 'NEWEST_MESSAGE_FOR_TEST');

        // Oldest should come first (smaller index)
        expect($oldIndex)->toBeLessThan($newIndex);
    });

    it('respects the limit parameter', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create 5 messages
        for ($i = 0; $i < 5; $i++) {
            PitchEvent::create([
                'pitch_id' => $this->pitch->id,
                'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
                'comment' => "Message {$i}",
                'status' => $this->pitch->status,
                'created_by' => $this->producer->id,
                'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            ]);
        }

        $messages = $this->communicationService->getMessages($this->pitch, limit: 3);

        expect($messages)->toHaveCount(3);
    });

    it('only returns message-type events', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create a message
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'A message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        // Create a status change event (not a message)
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_STATUS_CHANGE,
            'comment' => 'Status changed',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
        ]);

        $messages = $this->communicationService->getMessages($this->pitch);

        expect($messages)->toHaveCount(1)
            ->and($messages->first()->event_type)->toBe(PitchEvent::TYPE_PRODUCER_MESSAGE);
    });
});

describe('getActivityTimeline', function () {
    it('returns communication hub events', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create various event types
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'A message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_STATUS_CHANGE,
            'comment' => 'Status changed',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
        ]);

        $timeline = $this->communicationService->getActivityTimeline($this->pitch);

        expect($timeline)->toHaveCount(2);
    });

    it('returns events ordered newest first', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        $oldEvent = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Old event',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'created_at' => now()->subMinutes(10),
        ]);

        $newEvent = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_STATUS_CHANGE,
            'comment' => 'New event',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'created_at' => now(),
        ]);

        $timeline = $this->communicationService->getActivityTimeline($this->pitch);

        expect($timeline->first()->id)->toBe($newEvent->id)
            ->and($timeline->last()->id)->toBe($oldEvent->id);
    });
});

describe('getPendingActions', function () {
    it('includes revision pending action when pitch has revisions requested', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();
        $this->pitch->update(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);

        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'revisions_requested',
            'comment' => 'Please adjust the mix',
            'status' => $this->pitch->status,
        ]);

        $actions = $this->communicationService->getPendingActions($this->pitch);

        expect($actions)->toHaveCount(1)
            ->and($actions->first()['type'])->toBe('revision_pending')
            ->and($actions->first()['priority'])->toBe('high');
    });

    it('includes unread messages action when unread count > 0', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();

        // Create an unread client message
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Unread message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $actions = $this->communicationService->getPendingActions($this->pitch, $this->producer->id);

        $unreadAction = $actions->firstWhere('type', 'unread_messages');
        expect($unreadAction)->not->toBeNull()
            ->and($unreadAction['priority'])->toBe('medium');
    });

    it('sorts actions by priority descending', function () {
        // Clear any existing events for clean test
        $this->pitch->events()->delete();
        $this->pitch->update(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);

        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'revisions_requested',
            'comment' => 'Revision request',
            'status' => $this->pitch->status,
        ]);

        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Unread message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $actions = $this->communicationService->getPendingActions($this->pitch, $this->producer->id);

        expect($actions->first()['priority'])->toBe('high')
            ->and($actions->last()['priority'])->toBe('medium');
    });
});

describe('deleteMessage', function () {
    it('deletes own message successfully', function () {
        $event = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'My message to delete',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $result = $this->communicationService->deleteMessage($event, $this->producer);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('pitch_events', ['id' => $event->id]);
    });

    it('fails to delete another user message', function () {
        $otherUser = User::factory()->create();

        $event = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Other user message',
            'status' => $this->pitch->status,
            'created_by' => $otherUser->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        $result = $this->communicationService->deleteMessage($event, $this->producer);

        expect($result)->toBeFalse();
        $this->assertDatabaseHas('pitch_events', ['id' => $event->id]);
    });

    it('fails to delete non-message events', function () {
        $event = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_STATUS_CHANGE,
            'comment' => 'Status changed',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
        ]);

        $result = $this->communicationService->deleteMessage($event, $this->producer);

        expect($result)->toBeFalse();
        $this->assertDatabaseHas('pitch_events', ['id' => $event->id]);
    });
});

describe('getConversationItems', function () {
    it('returns formatted conversation items', function () {
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Test message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'is_urgent' => true,
        ]);

        $items = $this->communicationService->getConversationItems($this->pitch);

        expect($items)->toHaveCount(1)
            ->and($items->first()['type'])->toBe('producer_message')
            ->and($items->first()['content'])->toBe('Test message')
            ->and($items->first()['is_urgent'])->toBeTrue()
            ->and($items->first())->toHaveKeys([
                'id', 'type', 'content', 'timestamp', 'user', 'metadata',
                'read_at', 'is_urgent', 'delivery_status', 'event',
            ]);
    });

    it('maps client_approved to approval type', function () {
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_APPROVED,
            'comment' => 'Approved!',
            'status' => $this->pitch->status,
        ]);

        $items = $this->communicationService->getConversationItems($this->pitch);

        expect($items->first()['type'])->toBe('approval');
    });

    it('maps client_revisions_requested to revision_request type', function () {
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
            'comment' => 'Please revise',
            'status' => $this->pitch->status,
        ]);

        $items = $this->communicationService->getConversationItems($this->pitch);

        expect($items->first()['type'])->toBe('revision_request');
    });
});
