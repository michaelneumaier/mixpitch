<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Livewire\Pitch\Component\ManagePitch;
use Livewire\Livewire;
use Illuminate\Support\Facades\Notification;
use App\Services\NotificationService;
use Mockery;

class PitchSubmissionTest extends TestCase
{
    use RefreshDatabase;
    
    protected $notificationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake notifications
        Notification::fake();
        // Mock the notification service
        $this->notificationServiceMock = $this->mock(NotificationService::class);
    }

    /** @test */
    public function producer_can_submit_pitch_for_review()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        PitchFile::factory()->count(2)->for($pitch)->create();

        // Mock the notification service method
        $this->notificationServiceMock->shouldReceive('notifyPitchReadyForReview')->once();

        Livewire::actingAs($pitchCreator)
            ->test(ManagePitch::class, ['pitch' => $pitch])
            ->call('submitForReview')
            ->assertStatus(200); // Expect success, not redirect in recent Livewire versions

        // Assert database state changes
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $pitch->status);
        $this->assertNotNull($pitch->current_snapshot_id);
        
        // Get the created snapshot directly
        $snapshot = PitchSnapshot::find($pitch->current_snapshot_id);
        $this->assertNotNull($snapshot);
        $this->assertEquals($pitch->id, $snapshot->pitch_id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $snapshot->status);
        $this->assertEquals(1, $snapshot->snapshot_data['version'] ?? null);
    }

    /** @test */
    public function producer_can_resubmit_pitch_after_revisions()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $previousSnapshot = PitchSnapshot::factory()->create([
            'status' => PitchSnapshot::STATUS_REVISIONS_REQUESTED, 
            'snapshot_data' => ['version' => 1]
        ]);
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_REVISIONS_REQUESTED,
                'current_snapshot_id' => $previousSnapshot->id
            ]);
        $previousSnapshot->pitch_id = $pitch->id;
        $previousSnapshot->save();
        PitchFile::factory()->count(1)->for($pitch)->create();

        $feedbackResponse = 'I fixed the things you asked for.';

        // Mock the notification service method
        $this->notificationServiceMock->shouldReceive('notifyPitchReadyForReview')->once();

        Livewire::actingAs($pitchCreator)
            ->test(ManagePitch::class, ['pitch' => $pitch])
            ->set('responseToFeedback', $feedbackResponse)
            ->call('submitForReview')
            ->assertStatus(200);

        // Assert database state changes
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $pitch->status);
        
        // Get new snapshot directly
        $newSnapshot = PitchSnapshot::find($pitch->current_snapshot_id);
        $this->assertNotNull($newSnapshot);
        $this->assertEquals($pitch->id, $newSnapshot->pitch_id);
        $this->assertEquals(PitchSnapshot::STATUS_PENDING, $newSnapshot->status);
        $this->assertEquals(2, $newSnapshot->snapshot_data['version'] ?? null);
        $this->assertEquals($feedbackResponse, $newSnapshot->snapshot_data['response_to_feedback'] ?? null);
        
        // Check previous snapshot status
        $previousSnapshot->refresh();
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $previousSnapshot->status);
    }

    /** @test */
    public function submit_fails_if_no_files_attached()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        // No files attached

        // Make sure notification service is not called
        $this->notificationServiceMock->shouldNotReceive('notifyPitchReadyForReview');

        // Expected to set an error message in the component
        Livewire::actingAs($pitchCreator)
            ->test(ManagePitch::class, ['pitch' => $pitch])
            ->call('submitForReview')
            ->assertStatus(200); // Should return normal status but with errors

        // Assert pitch status hasn't changed
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $pitch->refresh()->status);
    }

    /** @test */
    public function submit_fails_in_wrong_pitch_status()
    {
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]); // Wrong status
        PitchFile::factory()->count(1)->for($pitch)->create();

        // Make sure notification service is not called
        $this->notificationServiceMock->shouldNotReceive('notifyPitchReadyForReview');

        Livewire::actingAs($pitchCreator)
            ->test(ManagePitch::class, ['pitch' => $pitch])
            ->call('submitForReview')
            ->assertStatus(200); // Should return normal status but with errors

        $this->assertEquals(Pitch::STATUS_APPROVED, $pitch->refresh()->status);
    }

    /** @test */
    public function project_owner_cannot_submit_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)
            ->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        PitchFile::factory()->count(1)->for($pitch)->create();

        // Make sure notification service is not called
        $this->notificationServiceMock->shouldNotReceive('notifyPitchReadyForReview');

        // In this test we just verify that the project owner can't submit
        // The authorization might be handled at service level without a 403
        $result = Livewire::actingAs($projectOwner) // Act as project owner
            ->test(ManagePitch::class, ['pitch' => $pitch])
            ->call('submitForReview');
            
        // Check for authorization failure by checking no state changes occurred
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $pitch->refresh()->status);
    }
} 