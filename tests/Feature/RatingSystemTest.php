<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PitchCompletionService;
use App\Services\ProjectManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class RatingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $projectOwner;

    protected $pitchCreator;

    protected $project;

    protected $pitch;

    protected $pitchCompletionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectOwner = User::factory()->create(['role' => 'client']);
        $this->pitchCreator = User::factory()->create(['role' => 'producer']); // Role needed for existing logic, adjust if role system changes

        $this->project = Project::factory()->create(['user_id' => $this->projectOwner->id, 'budget' => 100]);
        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->pitchCreator->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_APPROVED, // Assume pitch is approved before completion
        ]);

        // Mock services
        $projectManagementServiceMock = Mockery::mock(ProjectManagementService::class);
        $projectManagementServiceMock->shouldReceive('completeProject')->andReturnSelf();

        $notificationServiceMock = Mockery::mock(NotificationService::class);
        $notificationServiceMock->shouldReceive('notifyPitchCompleted')->andReturnNull(); // Return null to match type hint
        $notificationServiceMock->shouldReceive('notifyPitchClosed')->andReturnNull();    // Mock the closed notification too

        Notification::fake(); // Still needed if any default Laravel notifications are used elsewhere

        // Use app() to resolve the service with mocked dependencies
        $this->instance(ProjectManagementService::class, $projectManagementServiceMock);
        $this->instance(NotificationService::class, $notificationServiceMock);

        $this->pitchCompletionService = $this->app->make(PitchCompletionService::class);
    }

    /** @test */
    public function a_pitch_can_be_completed_with_a_rating()
    {
        $rating = 4;
        $feedback = 'Great work!';

        $completedPitch = $this->pitchCompletionService->completePitch($this->pitch, $this->projectOwner, $feedback, $rating);

        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertEquals($feedback, $completedPitch->completion_feedback);

        // Check if the completion event was created with the correct rating
        $completionEvent = PitchEvent::where('pitch_id', $this->pitch->id)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($completionEvent);
        $this->assertEquals($rating, $completionEvent->rating);
        $this->assertEquals($this->projectOwner->id, $completionEvent->created_by);

        // Use the specific method to get the rating
        $this->assertEquals($rating, $completedPitch->getCompletionRating());
    }

    /** @test */
    public function completing_a_pitch_without_a_rating_stores_null()
    {
        $completedPitch = $this->pitchCompletionService->completePitch($this->pitch, $this->projectOwner);

        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);

        $completionEvent = PitchEvent::where('pitch_id', $this->pitch->id)
            ->where('event_type', 'status_change')
            ->where('status', Pitch::STATUS_COMPLETED)
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($completionEvent);
        $this->assertNull($completionEvent->rating);
        $this->assertNull($completedPitch->getCompletionRating());
    }

    /** @test */
    public function user_average_rating_is_calculated_correctly()
    {
        // Complete multiple pitches with different ratings
        $pitch1 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);
        $pitch2 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);
        $pitch3 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);

        $this->pitchCompletionService->completePitch($pitch1, $this->projectOwner, null, 5);
        $this->pitchCompletionService->completePitch($pitch2, $this->projectOwner, null, 4);
        $this->pitchCompletionService->completePitch($pitch3, $this->projectOwner, null, 3);

        $ratingData = $this->pitchCreator->calculateAverageRating();

        $this->assertEquals(4.0, $ratingData['average']);
        $this->assertEquals(3, $ratingData['count']);
    }

    /** @test */
    public function user_average_rating_handles_null_ratings()
    {
        // Complete pitches, some with ratings, some without
        $pitch1 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);
        $pitch2 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);
        $pitch3 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);

        $this->pitchCompletionService->completePitch($pitch1, $this->projectOwner, null, 5); // Rating 5
        $this->pitchCompletionService->completePitch($pitch2, $this->projectOwner);        // No rating (null)
        $this->pitchCompletionService->completePitch($pitch3, $this->projectOwner, null, 3); // Rating 3

        $ratingData = $this->pitchCreator->calculateAverageRating();

        // Average should only consider the rated pitches (5 + 3) / 2 = 4
        $this->assertEquals(4.0, $ratingData['average']);
        $this->assertEquals(2, $ratingData['count']); // Count should only include rated pitches
    }

    /** @test */
    public function user_average_rating_is_null_if_no_ratings_exist()
    {
        // Complete a pitch without a rating
        $pitch1 = Pitch::factory()->create(['user_id' => $this->pitchCreator->id, 'project_id' => $this->project->id, 'status' => Pitch::STATUS_APPROVED]);
        $this->pitchCompletionService->completePitch($pitch1, $this->projectOwner);

        $ratingData = $this->pitchCreator->calculateAverageRating();

        $this->assertNull($ratingData['average']);
        $this->assertEquals(0, $ratingData['count']);
    }

    /** @test */
    public function get_completion_rating_returns_correct_value_for_pitch()
    {
        $rating = 5;
        $completedPitch = $this->pitchCompletionService->completePitch($this->pitch, $this->projectOwner, null, $rating);

        $this->assertEquals($rating, $completedPitch->getCompletionRating());
    }

    /** @test */
    public function get_completion_rating_returns_null_for_unrated_pitch()
    {
        $completedPitch = $this->pitchCompletionService->completePitch($this->pitch, $this->projectOwner);

        $this->assertNull($completedPitch->getCompletionRating());
    }

    /** @test */
    public function get_completion_rating_returns_null_for_non_completed_pitch()
    {
        $this->assertNull($this->pitch->getCompletionRating()); // Pitch is initially APPROVED
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
