<?php

namespace Tests\Feature;

use App\Events\Pitch\PitchCancelled;
use App\Models\Notification;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Project\ProjectManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProjectCancellationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an owner can cancel their standard project mid-workflow,
     * and associated active pitches are appropriately closed/cancelled.
     *
     * @test
     */
    public function owner_can_cancel_standard_project_and_active_pitches_are_closed(): void
    {
        // Arrange
        Event::fake([PitchCancelled::class]); // Fake specific event if needed
        $notificationMock = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationMock);

        $projectOwner = User::factory()->create();
        $producer1 = User::factory()->create();
        $producer2 = User::factory()->create();

        $project = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'status' => Project::STATUS_OPEN,
            'is_published' => true,
        ]);

        // Pitch 1: Still pending initial approval
        $pitch1 = Pitch::factory()->for($project)->for($producer1, 'user')->create([
            'status' => Pitch::STATUS_PENDING,
        ]);

        // Pitch 2: Approved and in progress
        $pitch2 = Pitch::factory()->for($project)->for($producer2, 'user')->create([
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        // Define expected notification calls
        $notificationMock->shouldReceive('notifyPitchCancelled')
            ->once()
            ->with(Mockery::on(fn($p) => $p->id === $pitch1->id));
        $notificationMock->shouldReceive('notifyPitchCancelled')
            ->once()
            ->with(Mockery::on(fn($p) => $p->id === $pitch2->id));

        // Assuming cancellation is handled by ProjectManagementService::cancelProject
        // We might need to mock this or call it directly, depending on how the UI triggers it.
        // For now, let's assume a Livewire component `ManageProject` calls the service.
        $projectManagementServiceMock = Mockery::mock(ProjectManagementService::class);
        $projectManagementServiceMock->shouldReceive('cancelProject')
            ->once()
            ->withArgs(function (Project $proj, User $user, ?string $reason) use ($project, $projectOwner) {
                return $proj->id === $project->id && $user->id === $projectOwner->id;
            })
            ->andReturnUsing(function (Project $proj) {
                // Simulate the service's action
                $proj->status = Project::STATUS_CANCELLED;
                $proj->save();
                foreach ($proj->pitches as $pitch) {
                    if (in_array($pitch->status, [Pitch::STATUS_PENDING, Pitch::STATUS_IN_PROGRESS])) {
                        $pitch->status = Pitch::STATUS_CLOSED; // Or CANCELLED if that status exists
                        $pitch->save();
                        // Assume service triggers notifications internally
                    }
                }
                return $proj;
            });
        $this->app->instance(ProjectManagementService::class, $projectManagementServiceMock);


        // Act: Simulate owner cancelling via a hypothetical Livewire component
        // Replace 'App\Livewire\Project\ManageProject' with the actual component name if different
        Livewire::actingAs($projectOwner)
            ->test('App\Livewire\Project\ManageProject', ['project' => $project])
            ->call('cancelProject', 'No longer needed'); // Assuming method exists and takes optional reason


        // Assert
        $project->refresh();
        $pitch1->refresh();
        $pitch2->refresh();

        $this->assertEquals(Project::STATUS_CANCELLED, $project->status);
        $this->assertEquals(Pitch::STATUS_CLOSED, $pitch1->status); // Check final pitch status
        $this->assertEquals(Pitch::STATUS_CLOSED, $pitch2->status); // Check final pitch status

        // Notification mock expectations are checked automatically by Mockery

        // Optional: Assert events if ProjectCancelled or PitchCancelled events are dispatched
        // Event::assertDispatched(ProjectCancelled::class, fn ($e) => $e->project->id === $project->id);
        // Event::assertDispatched(PitchCancelled::class, fn ($e) => $e->pitch->id === $pitch1->id);
        // Event::assertDispatched(PitchCancelled::class, fn ($e) => $e->pitch->id === $pitch2->id);

        // Optional: Assert DB records for notifications if not relying on mocks
        $this->assertDatabaseHas('notifications', [
            'user_id' => $producer1->id,
            'type' => Notification::TYPE_PITCH_CANCELLED,
            'related_id' => $pitch1->id
        ]);
         $this->assertDatabaseHas('notifications', [
            'user_id' => $producer2->id,
            'type' => Notification::TYPE_PITCH_CANCELLED,
            'related_id' => $pitch2->id
        ]);
    }
} 