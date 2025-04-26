<?php

namespace Tests\Unit\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Models\NotificationPreference;
use App\Services\EmailService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->pitch = Pitch::factory()->for($this->project)->for($this->user, 'user')->create();

        // Mock EmailService as we are not testing email sending here
        $emailServiceMock = Mockery::mock(EmailService::class);
        $this->notificationService = new NotificationService($emailServiceMock);

        // Fake the event dispatcher to assert events are dispatched
        Event::fake([NotificationCreated::class]);
    }

    /** @test */
    public function it_creates_a_notification_successfully()
    {
        $notification = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_SUBMITTED,
            $this->pitch,
            ['test_data' => 'value']
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
            'related_id' => $this->pitch->id,
            'related_type' => get_class($this->pitch),
            // Check JSON data contains expected key
            'data' => json_encode(['test_data' => 'value']),
        ]);

        // Assert the event was dispatched
        Event::assertDispatched(NotificationCreated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }

    /** @test */
    public function it_prevents_duplicate_notifications_within_5_minutes()
    {
        $notification1 = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_APPROVED,
            $this->pitch,
            ['approver_id' => 99]
        );

        // Travel forward 4 minutes
        $this->travel(4)->minutes();

        $notification2 = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_APPROVED, // Same type
            $this->pitch, // Same related object
            ['approver_id' => 99] // Same data (though data isn't checked by current logic)
        );

        // Assert that the second call returned the *first* notification
        $this->assertSame($notification1->id, $notification2->id);

        // Assert only one notification was actually created in the DB
        $this->assertDatabaseCount('notifications', 1);

        // Assert the event was only dispatched once
        Event::assertDispatchedTimes(NotificationCreated::class, 1);
    }

    /** @test */
    public function it_creates_a_new_notification_after_5_minutes()
    {
        $notification1 = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_SUBMISSION_DENIED,
            $this->pitch,
            ['reason' => 'Initial reason']
        );

        // Travel forward 6 minutes (past the 5-minute window)
        $this->travel(6)->minutes();

        $notification2 = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_SUBMISSION_DENIED,
            $this->pitch,
            ['reason' => 'Updated reason']
        );

        // Assert the second notification is different from the first
        $this->assertNotSame($notification1->id, $notification2->id);

        // Assert two notifications exist in the DB
        $this->assertDatabaseCount('notifications', 2);

        // Assert the event was dispatched twice
        Event::assertDispatchedTimes(NotificationCreated::class, 2);
    }

    /** @test */
    public function notify_pitch_submitted_creates_correct_notification()
    {
        $projectOwner = User::factory()->create();
        $this->project->user_id = $projectOwner->id; // Assign project owner
        $this->project->save();
        $submitter = User::factory()->create();
        $pitch = Pitch::factory()->for($this->project)->for($submitter, 'user')->create();

        $notification = $this->notificationService->notifyPitchSubmitted($pitch);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $projectOwner->id,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
            'related_id' => $pitch->id,
            'related_type' => get_class($pitch),
            'data' => json_encode([
                'pitch_id' => $pitch->id,
                'pitch_slug' => $pitch->slug,
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
                'producer_id' => $submitter->id,
                'producer_name' => $submitter->name,
            ])
        ]);

        $this->assertEquals($projectOwner->id, $notification->user_id);
        $this->assertEquals($pitch->id, $notification->related_id);
        $this->assertEquals(Notification::TYPE_PITCH_SUBMITTED, $notification->type);
        $this->assertEquals($this->project->id, $notification->data['project_id']);
        $this->assertEquals($submitter->name, $notification->data['producer_name']);

        Event::assertDispatched(NotificationCreated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }

    /** @test */
    public function notify_pitch_status_change_creates_correct_notification()
    {
        $pitchCreator = $this->user; // User from setUp is the pitch creator

        $notification = $this->notificationService->notifyPitchStatusChange($this->pitch, Pitch::STATUS_APPROVED);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $pitchCreator->id,
            'type' => Notification::TYPE_PITCH_STATUS_CHANGE,
            'related_id' => $this->pitch->id,
            'related_type' => get_class($this->pitch),
            'data' => json_encode([
                'status' => Pitch::STATUS_APPROVED,
                'project_id' => $this->pitch->project_id,
                'project_name' => $this->pitch->project->name,
            ])
        ]);

        $this->assertEquals($pitchCreator->id, $notification->user_id);
        $this->assertEquals(Pitch::STATUS_APPROVED, $notification->data['status']);

        Event::assertDispatched(NotificationCreated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }

    /** @test */
    public function it_does_not_create_notification_if_preference_is_disabled()
    {
        // Create a preference disabling this type for the user
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => Notification::TYPE_PITCH_COMMENT,
            'is_enabled' => false,
        ]);

        // Attempt to create the notification
        $notification = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_COMMENT,
            $this->pitch,
            ['comment' => 'Test comment']
        );

        // Assert notification was NOT created (method returns null)
        $this->assertNull($notification);

        // Assert notification does not exist in the database
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_PITCH_COMMENT,
            'related_id' => $this->pitch->id,
        ]);

        // Assert the event was NOT dispatched
        Event::assertNotDispatched(NotificationCreated::class);
    }

    /** @test */
    public function it_creates_notification_if_preference_is_enabled()
    {
        // Create a preference explicitly enabling this type
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => Notification::TYPE_PITCH_APPROVED,
            'is_enabled' => true,
        ]);

        $notification = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_APPROVED,
            $this->pitch,
            ['approver_id' => 123]
        );

        // Assert notification WAS created
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_PITCH_APPROVED,
            'related_id' => $this->pitch->id,
        ]);

        // Assert the event WAS dispatched
        Event::assertDispatched(NotificationCreated::class);
    }

    /** @test */
    public function it_creates_notification_if_no_preference_exists_for_type()
    {
        // Ensure no preference exists for this user/type
        NotificationPreference::where('user_id', $this->user->id)
            ->where('notification_type', Notification::TYPE_PITCH_CANCELLED)
            ->delete();

        $notification = $this->notificationService->createNotification(
            $this->user,
            Notification::TYPE_PITCH_CANCELLED,
            $this->pitch,
            ['canceller_id' => 456]
        );

        // Assert notification WAS created (default behavior is enabled)
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_PITCH_CANCELLED,
            'related_id' => $this->pitch->id,
        ]);

        // Assert the event WAS dispatched
        Event::assertDispatched(NotificationCreated::class);
    }

    /** @test */
    public function notify_pitch_submitted_respects_disabled_preference()
    {
        $projectOwner = User::factory()->create();
        $this->project->user_id = $projectOwner->id;
        $this->project->save();
        $submitter = User::factory()->create();
        $pitch = Pitch::factory()->for($this->project)->for($submitter, 'user')->create();

        // Disable the specific notification type for the project owner
        NotificationPreference::create([
            'user_id' => $projectOwner->id,
            'notification_type' => Notification::TYPE_PITCH_SUBMITTED,
            'is_enabled' => false,
        ]);

        $notification = $this->notificationService->notifyPitchSubmitted($pitch);

        // Assert notification was not created
        $this->assertNull($notification);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $projectOwner->id,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
            'related_id' => $pitch->id,
        ]);
        Event::assertNotDispatched(NotificationCreated::class);
    }

    // TODO: Add tests for other representative notify... methods
    // - notifyPitchComment (check recipient, check commenter isn't notified)
    // - notifySnapshotApproved (check recipient)
    // - notifyPaymentProcessed (check recipient)

} 