<?php

namespace Tests\Unit\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\EmailService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Mockery;
use Tests\TestCase;

class ContestLifecycleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;

    protected User $entrant;

    protected Project $project;

    protected Pitch $pitch;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->create();
        $this->entrant = User::factory()->create();

        $this->project = Project::factory()->create([
            'user_id' => $this->organizer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'title' => 'Best Beat Contest',
        ]);

        $this->pitch = Pitch::factory()
            ->for($this->project)
            ->for($this->entrant, 'user')
            ->create([
                'status' => Pitch::STATUS_CONTEST_ENTRY,
            ]);

        $emailServiceMock = Mockery::mock(EmailService::class);
        $this->notificationService = new NotificationService($emailServiceMock);

        Event::fake([NotificationCreated::class]);
        NotificationFacade::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function notify_contest_closed_early_creates_notification_with_reason(): void
    {
        $reason = 'Insufficient entries received.';

        $notification = $this->notificationService->notifyContestClosedEarly($this->pitch, $reason);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->entrant->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_CONTEST_CLOSED_EARLY, $notification->type);
        $this->assertEquals($this->pitch->id, $notification->related_id);
        $this->assertEquals(get_class($this->pitch), $notification->related_type);
        $this->assertEquals($this->project->id, $notification->data['project_id']);
        $this->assertEquals($this->project->title, $notification->data['project_name']);
        $this->assertEquals($reason, $notification->data['reason']);

        Event::assertDispatched(NotificationCreated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }

    /** @test */
    public function notify_contest_closed_early_defaults_reason_to_empty_string(): void
    {
        $notification = $this->notificationService->notifyContestClosedEarly($this->pitch);

        $this->assertNotNull($notification);
        $this->assertSame('', $notification->data['reason']);
    }

    /** @test */
    public function notify_contest_submissions_reopened_creates_notification(): void
    {
        $notification = $this->notificationService->notifyContestSubmissionsReopened($this->pitch);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->entrant->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_CONTEST_SUBMISSIONS_REOPENED, $notification->type);
        $this->assertEquals($this->pitch->id, $notification->related_id);
        $this->assertEquals($this->project->id, $notification->data['project_id']);
        $this->assertEquals($this->project->title, $notification->data['project_name']);

        Event::assertDispatched(NotificationCreated::class, function ($event) use ($notification) {
            return $event->notification->id === $notification->id;
        });
    }

    /** @test */
    public function notify_contest_results_announced_creates_notification_with_placement(): void
    {
        $notification = $this->notificationService->notifyContestResultsAnnounced($this->pitch, 'Winner');

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->entrant->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_CONTEST_RESULTS_ANNOUNCED, $notification->type);
        $this->assertEquals($this->project->id, $notification->data['project_id']);
        $this->assertEquals($this->project->title, $notification->data['project_name']);
        $this->assertEquals('Winner', $notification->data['placement']);

        Event::assertDispatched(NotificationCreated::class);
    }

    /** @test */
    public function notify_contest_results_announced_organizer_targets_project_owner(): void
    {
        $notification = $this->notificationService->notifyContestResultsAnnouncedOrganizer($this->project);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->organizer->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_CONTEST_RESULTS_ANNOUNCED_ORGANIZER, $notification->type);
        $this->assertEquals($this->project->id, $notification->related_id);
        $this->assertEquals(get_class($this->project), $notification->related_type);
        $this->assertEquals($this->project->id, $notification->data['project_id']);
        $this->assertEquals($this->project->title, $notification->data['project_name']);

        Event::assertDispatched(NotificationCreated::class);
    }

    /** @test */
    public function it_respects_user_preferences_for_contest_closed_early(): void
    {
        NotificationPreference::create([
            'user_id' => $this->entrant->id,
            'notification_type' => Notification::TYPE_CONTEST_CLOSED_EARLY,
            'is_enabled' => false,
        ]);

        $notification = $this->notificationService->notifyContestClosedEarly($this->pitch, 'reason');

        $this->assertNull($notification);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->entrant->id,
            'type' => Notification::TYPE_CONTEST_CLOSED_EARLY,
        ]);
        Event::assertNotDispatched(NotificationCreated::class);
    }

    /** @test */
    public function it_returns_null_when_pitch_user_is_missing(): void
    {
        // Hard-delete the entrant so the pitch->user relationship resolves to null.
        // Using forceDelete + unsetRelation avoids the NOT NULL FK constraint on pitches.user_id.
        $this->entrant->forceDelete();
        $this->pitch->unsetRelation('user');

        $this->assertNull($this->notificationService->notifyContestClosedEarly($this->pitch));
        $this->assertNull($this->notificationService->notifyContestSubmissionsReopened($this->pitch));
        $this->assertNull($this->notificationService->notifyContestResultsAnnounced($this->pitch, 'Winner'));
    }

    /** @test */
    public function it_returns_null_when_project_owner_is_missing_for_organizer_notification(): void
    {
        // Hard-delete the organizer so project->user resolves to null.
        $this->organizer->forceDelete();
        $this->project->unsetRelation('user');

        $this->assertNull($this->notificationService->notifyContestResultsAnnouncedOrganizer($this->project));
    }
}
