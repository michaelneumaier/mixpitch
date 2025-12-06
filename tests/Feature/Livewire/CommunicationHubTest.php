<?php

use App\Livewire\Project\Component\CommunicationHub;
use App\Livewire\Project\Component\CommunicationHubFab;
use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
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
});

describe('CommunicationHubFab', function () {
    it('renders correctly', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHubFab::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->assertStatus(200)
            ->assertSee('Open Communication Hub');
    });

    it('shows unread count when there are unread messages', function () {
        $this->actingAs($this->producer);

        // Create unread client message
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Test client message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => ['client_email' => 'client@example.com'],
        ]);

        Livewire::test(CommunicationHubFab::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->assertSee('1');
    });

    it('dispatches open modal event when clicked', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHubFab::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('openHub')
            ->assertDispatched('open-modal', name: 'communication-hub')
            ->assertDispatched('communication-hub-opened');
    });
});

describe('CommunicationHub', function () {
    it('renders correctly', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->assertStatus(200)
            ->assertSee('Communication Hub')
            ->assertSee($this->project->name);
    });

    it('shows messages tab by default', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->assertSet('activeTab', 'messages');
    });

    it('can switch between tabs', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('setActiveTab', 'activity')
            ->assertSet('activeTab', 'activity')
            ->call('setActiveTab', 'actions')
            ->assertSet('activeTab', 'actions')
            ->call('setActiveTab', 'messages')
            ->assertSet('activeTab', 'messages');
    });

    it('can send a message to client', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->set('newMessage', 'Hello client!')
            ->call('sendMessage')
            ->assertSet('newMessage', '')
            ->assertDispatched('message-sent');

        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Hello client!',
            'created_by' => $this->producer->id,
        ]);
    });

    it('can send an urgent message', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->set('newMessage', 'Urgent message!')
            ->set('isUrgent', true)
            ->call('sendMessage');

        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Urgent message!',
            'is_urgent' => true,
        ]);
    });

    it('validates message is required', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->set('newMessage', '')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'required']);
    });

    it('validates message max length', function () {
        $this->actingAs($this->producer);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->set('newMessage', str_repeat('a', 2001))
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'max']);
    });

    it('displays messages in chronological order', function () {
        $this->actingAs($this->producer);

        // Create messages
        $oldMessage = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'First message',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);
        $oldMessage->created_at = now()->subMinutes(10);
        $oldMessage->save();

        $newMessage = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Second message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => ['client_email' => 'client@example.com'],
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->assertSeeInOrder(['First message', 'Second message']);
    });

    it('can delete own messages', function () {
        $this->actingAs($this->producer);

        $message = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Message to delete',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('deleteMessage', $message->id);

        $this->assertDatabaseMissing('pitch_events', ['id' => $message->id]);
    });

    it('cannot delete other users messages', function () {
        $this->actingAs($this->producer);

        $otherUser = User::factory()->create();
        $message = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_PRODUCER_MESSAGE,
            'comment' => 'Other message',
            'status' => $this->pitch->status,
            'created_by' => $otherUser->id,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('deleteMessage', $message->id);

        $this->assertDatabaseHas('pitch_events', ['id' => $message->id]);
    });

    it('marks messages as read when hub is opened', function () {
        $this->actingAs($this->producer);

        // Create unread message
        $message = PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_CLIENT_MESSAGE,
            'comment' => 'Unread message',
            'status' => $this->pitch->status,
            'delivery_status' => PitchEvent::DELIVERY_DELIVERED,
            'metadata' => ['client_email' => 'client@example.com'],
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('markMessagesAsRead');

        $message->refresh();
        expect($message->read_at)->not->toBeNull()
            ->and($message->delivery_status)->toBe(PitchEvent::DELIVERY_READ);
    });

    it('shows pending actions in actions tab', function () {
        $this->actingAs($this->producer);

        // Clear events and update pitch status
        $this->pitch->events()->delete();
        $this->pitch->update(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);

        // Create revision request event
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'revisions_requested',
            'comment' => 'Please fix the mix',
            'status' => $this->pitch->status,
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('setActiveTab', 'actions')
            ->assertSee('Respond to Revision Request');
    });

    it('shows activity timeline in activity tab', function () {
        $this->actingAs($this->producer);

        // Create status change event
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => PitchEvent::TYPE_STATUS_CHANGE,
            'comment' => 'Status changed',
            'status' => Pitch::STATUS_IN_PROGRESS,
            'created_by' => $this->producer->id,
        ]);

        Livewire::test(CommunicationHub::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
        ])->call('setActiveTab', 'activity')
            ->assertSee('Status changed');
    });
});
