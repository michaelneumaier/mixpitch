<?php

namespace Tests\Feature\Notifications;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService; // We won't mock this
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pitch_submission_creates_notification_and_dispatches_event()
    {
        Event::fake([
            NotificationCreated::class, // Fake only the event we want to assert was dispatched
        ]);

        $client = User::factory()->create(['role' => 'client']);
        $producer = User::factory()->create(['role' => 'producer']);
        $project = Project::factory()->for($client, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($producer, 'user')->create();

        // Acting as the producer to submit the pitch
        $this->actingAs($producer);

        // Simulate the action that triggers the notification
        // Assuming there's an endpoint or method to submit a pitch.
        // Let's pretend we have a route like POST /projects/{project}/pitches/{pitch}/submit
        // We might need to adjust this based on the actual application structure.
        // For now, let's directly call the NotificationService method as if it were triggered by the submission logic.
        // NOTE: Ideally, we'd hit an actual route or call a controller method/action here.
        // This is a placeholder until we know the exact trigger point.

        /** @var NotificationService $notificationService */
        $notificationService = app(NotificationService::class);
        $notificationService->notifyPitchSubmitted($pitch);

        // Assert Notification was created in the database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'type' => Notification::TYPE_PITCH_SUBMITTED,
            'related_type' => Pitch::class,
            'related_id' => $pitch->id,
            'read_at' => null,
            // Updated payload check to match NotificationService::notifyPitchSubmitted
            'data->pitch_id' => $pitch->id, // Added pitch_id
            'data->pitch_slug' => $pitch->slug, // Changed from pitch_title to pitch_slug
            'data->project_id' => $project->id,
            'data->project_name' => $project->name,
            'data->producer_id' => $producer->id, // Changed from submitter_id to producer_id
            'data->producer_name' => $producer->name, // Changed from submitter_name to producer_name
        ]);

        // Retrieve the created notification to pass to the event assertion
        $notification = Notification::where('user_id', $client->id)
            ->where('related_type', Pitch::class)
            ->where('related_id', $pitch->id)
            ->where('type', Notification::TYPE_PITCH_SUBMITTED)
            ->first();

        $this->assertNotNull($notification, "Notification was not found in the database.");

        // Assert Event was dispatched with the correct notification data
        Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($notification) {
            return $event->notification->id === $notification->id &&
                   $event->notification->user_id === $notification->user_id;
        });
    }

    // Add more tests for other notification flows (e.g., comment added, status changed)
    // following the same pattern:
    // 1. Set up necessary models (users, project, pitch, etc.)
    // 2. Fake the NotificationCreated event
    // 3. Perform the action that should trigger the notification (ideally via route/controller, fallback to service call)
    // 4. Assert database record exists
    // 5. Assert NotificationCreated event was dispatched with the correct notification instance
} 