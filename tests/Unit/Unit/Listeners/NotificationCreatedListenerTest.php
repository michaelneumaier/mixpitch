<?php

namespace Tests\Unit\Unit\Listeners;

use App\Events\NotificationCreated;
use App\Jobs\SendNotificationEmailJob;
use App\Listeners\NotificationCreatedListener;
use App\Models\Notification;
use App\Models\NotificationChannelPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationCreatedListenerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private NotificationCreatedListener $listener;

    private string $notificationType = 'test_notification_type';

    private string $channel = 'email';

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->user = User::factory()->create();
        $this->listener = new NotificationCreatedListener;
    }

    private function createNotification(array $data = ['test_data' => 'value']): Notification
    {
        return Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => $this->notificationType,
            'data' => $data,
        ]);
    }

    /** @test */
    public function it_dispatches_email_job_when_preference_is_enabled(): void
    {
        NotificationChannelPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => $this->notificationType,
            'channel' => $this->channel,
            'is_enabled' => true,
        ]);

        $notification = $this->createNotification();
        $event = new NotificationCreated($notification);

        $this->listener->handle($event);

        Queue::assertPushed(SendNotificationEmailJob::class, function ($job) use ($notification) {
            return $job->user->is($this->user) &&
                   $job->notificationType === $notification->type &&
                   $job->data === $notification->data &&
                   $job->originalNotificationId === $notification->id;
        });
    }

    /** @test */
    public function it_does_not_dispatch_email_job_when_preference_is_disabled(): void
    {
        NotificationChannelPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => $this->notificationType,
            'channel' => $this->channel,
            'is_enabled' => false,
        ]);

        $notification = $this->createNotification();
        $event = new NotificationCreated($notification);

        $this->listener->handle($event);

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    }

    /** @test */
    public function it_dispatches_email_job_when_no_preference_is_set(): void
    {
        $notification = $this->createNotification();
        $event = new NotificationCreated($notification);

        $this->listener->handle($event);

        Queue::assertPushed(SendNotificationEmailJob::class, function ($job) use ($notification) {
            return $job->user->is($this->user) &&
                  $job->notificationType === $notification->type &&
                  $job->data === $notification->data &&
                  $job->originalNotificationId === $notification->id;
        });
    }
}
