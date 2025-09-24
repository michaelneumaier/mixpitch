<?php

namespace Tests\Feature;

use App\Helpers\RouteHelpers;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase; // To mock
use Illuminate\Support\Facades\Log; // Use Laravel's built-in facade for faking
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class PitchCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $projectOwner;

    protected $producer;

    protected $otherUser;

    protected $openProject;

    protected $closedProject;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->projectOwner = User::factory()->create();
        $this->producer = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create projects with explicit slugs to ensure proper route resolution
        $this->openProject = Project::factory()->for($this->projectOwner)->create([
            'status' => Project::STATUS_OPEN,
            'slug' => 'open-test-project',
        ]);

        $this->closedProject = Project::factory()->for($this->projectOwner)->create([
            'status' => Project::STATUS_COMPLETED,
            'slug' => 'closed-test-project',
        ]);

        // Make sure projects are properly saved with slugs
        $this->openProject->refresh();
        $this->closedProject->refresh();

        // Fake notifications
        Notification::fake();

        // We might need to mock NotificationService if it's directly injected somewhere unexpected,
        // but usually Notification::fake() is sufficient for testing dispatched notifications.
        // $notificationServiceMock = Mockery::mock(NotificationService::class);
        // $this->app->instance(NotificationService::class, $notificationServiceMock);
        // $notificationServiceMock->shouldReceive('notifyPitchSubmitted')->zeroOrMoreTimes(); // Allow calls

    }

    // --- Create Form View Tests ---

    protected function makePitchesCreateUrl($project)
    {
        return '/projects/'.$project->slug.'/pitches/create';
    }

    /** @test */
    public function authenticated_producer_can_view_create_pitch_form_for_open_project()
    {
        // In setUp we explicitly set the slug to 'open-test-project'
        $url = '/projects/open-test-project/pitches/create';

        // Log the URL and project details to help debug
        Log::info('DEBUG test URL', [
            'url' => $url,
            'direct_url' => true,
            'project_id' => $this->openProject->id,
            'project_slug' => $this->openProject->slug,
            'exists' => $this->openProject->exists,
        ]);

        $response = $this->actingAs($this->producer)
            ->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('pitches.create');
        $response->assertSee('Start Your Pitch'); // The button actually says "Start Your Pitch"
    }

    /** @test */
    public function producer_is_redirected_from_create_form_if_already_pitched()
    {
        // Create an existing pitch for this producer
        $existingPitch = Pitch::factory()->for($this->openProject)->for($this->producer)->create();
        $existingPitch->refresh(); // Make sure we have the slug

        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.create', $this->openProject));

        // Should redirect to the existing pitch show page
        $response->assertRedirect(RouteHelpers::pitchUrl($existingPitch));
        $response->assertSessionHas('info'); // Check for the info message
    }

    /** @test */
    public function producer_cannot_view_create_form_for_closed_project()
    {
        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.create', $this->closedProject));

        // Should redirect back to project show page with an error
        $response->assertRedirect(route('projects.show', $this->closedProject));
        // Check for the error message set by the controller's catch block
        $response->assertSessionHas('error', 'You cannot submit a pitch for this project. This action is unauthorized.');
    }

    /** @test */
    public function project_owner_cannot_view_create_pitch_form_for_their_own_project()
    {
        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.create', $this->openProject));

        // Redirect back to project show page with error
        $response->assertRedirect(route('projects.show', $this->openProject));
        // Check for the error message set by the controller's catch block
        $response->assertSessionHas('error', 'You cannot submit a pitch for this project. This action is unauthorized.');
        // Note: This requires the 'createPitch' policy check in PitchController@create or ProjectPolicy
    }

    /** @test */
    public function guest_cannot_view_create_pitch_form()
    {
        $response = $this->get(route('projects.pitches.create', ['project' => $this->openProject]));

        $response->assertRedirect(route('login'));
    }

    // --- Store Action Tests ---

    /** @test */
    public function pitch_creation_fails_validation_if_terms_not_accepted()
    {
        $response = $this->actingAs($this->producer)
            ->post(route('projects.pitches.store', ['project' => $this->openProject]), [
                // 'agree_terms' => '1' // Missing or not 'accepted'
            ]);

        // Laravel returns a redirect with validation errors
        $response->assertStatus(302); // Expecting a redirect
        $response->assertSessionHasErrors('agree_terms'); // Should have validation error for agree_terms
        $this->assertDatabaseMissing('pitches', [ // Ensure no pitch was created
            'project_id' => $this->openProject->id,
            'user_id' => $this->producer->id,
        ]);
    }

    /** @test */
    public function producer_can_successfully_create_a_pitch()
    {
        $response = $this->actingAs($this->producer)
            ->post(route('projects.pitches.store', $this->openProject), [
                'agree_terms' => '1', // Represents checked checkbox
            ]);

        // Verify pitch was created in the database
        $this->assertDatabaseHas('pitches', [
            'project_id' => $this->openProject->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_PENDING,
        ]);

        // Get the created pitch from the database
        $pitch = Pitch::where('project_id', $this->openProject->id)
            ->where('user_id', $this->producer->id)
            ->first();

        // Ensure the pitch was saved
        $this->assertNotNull($pitch);

        // Just check for a redirect without verifying the specific session key
        $response->assertRedirect();
    }

    /** @test */
    public function pitch_creation_fails_if_project_is_closed_during_submit()
    {
        // Simulate project closing between viewing form and submitting
        $this->openProject->status = Project::STATUS_COMPLETED;
        $this->openProject->save();

        $response = $this->actingAs($this->producer)
            ->post(route('projects.pitches.store', ['project' => $this->openProject]), [
                'agree_terms' => '1',
            ]);

        // Expecting a 403 Forbidden because the policy should block creation on a closed project
        $response->assertStatus(403);
    }

    /** @test */
    public function pitch_creation_fails_if_user_already_pitched_during_submit()
    {
        // Simulate another pitch getting created just before this user submits
        Pitch::factory()->for($this->openProject)->for($this->producer)->create();

        // Attempt to submit the form again
        $response = $this->actingAs($this->producer)
            ->post(route('projects.pitches.store', $this->openProject), [
                'agree_terms' => '1',
            ]);

        // Expecting a redirect back to the project page with an error, because the service throws PitchCreationException
        // $response->assertStatus(403); // Old expectation based on policy blocking
        $response->assertRedirect(route('projects.show', $this->openProject));
        $response->assertSessionHas('error', 'Failed to create pitch: You have already submitted a pitch for this project.');
    }

    /** @test */
    public function guest_cannot_submit_pitch_creation_form()
    {
        $response = $this->post(route('projects.pitches.store', ['project' => $this->openProject]), [
            'agree_terms' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('pitches', ['project_id' => $this->openProject->id]);
    }

    // --- Show Pitch Authorization Tests --- (Based on refactored showProjectPitch)

    /** @test */
    public function project_owner_is_redirected_from_pitch_show_page_to_manage_page()
    {
        $pitch = Pitch::factory()->for($this->openProject)->for($this->producer)->create();
        $pitch->refresh();

        $response = $this->actingAs($this->projectOwner)
            ->get(route('projects.pitches.show', ['project' => $this->openProject, 'pitch' => $pitch]));

        $response->assertRedirect(route('projects.manage', $this->openProject));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function pitch_owner_can_view_their_own_pitch_show_page()
    {
        $pitch = Pitch::factory()->for($this->openProject)->for($this->producer)->create();
        $pitch->refresh();

        $response = $this->actingAs($this->producer)
            ->get(route('projects.pitches.show', ['project' => $this->openProject, 'pitch' => $pitch]));

        $response->assertStatus(200);
        $response->assertViewIs('pitches.show');

        // Assert seeing the linked project title text
        $response->assertSeeText($this->openProject->name);
        // Keep a general check for 'Pitch' as well
        $response->assertSee('Pitch', false);
    }

    /** @test */
    public function unauthorized_user_cannot_view_pitch_show_page()
    {
        $pitch = Pitch::factory()->for($this->openProject)->for($this->producer)->create();
        $pitch->refresh();

        $response = $this->actingAs($this->otherUser)
            ->get(route('projects.pitches.show', ['project' => $this->openProject, 'pitch' => $pitch]));

        // Expecting 403 Forbidden based on PitchPolicy@view
        $response->assertStatus(403);
        // Note: This requires PitchPolicy::view() to be implemented correctly.
    }

    /** @test */
    public function test_contest_pitch_creation_fails_if_deadline_passed()
    {
        // Create a project owner
        $projectOwner = User::factory()->create();

        // Create a contest project with a passed deadline
        $contestProject = Project::factory()->for($projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => now()->subDays(1), // Deadline was yesterday
            'status' => Project::STATUS_OPEN,
            'is_published' => true,
        ]);

        // Create a producer user who will try to submit a pitch
        $producer = User::factory()->create();

        // Attempt to submit a pitch
        $response = $this->actingAs($producer)
            ->post(route('projects.pitches.store', ['project' => $contestProject]), [
                'agree_terms' => '1',
            ]);

        // Expect a redirect with error message
        $response->assertRedirect();
        $response->assertSessionHas('error'); // Verify there's an error message

        // Verify no pitch was created
        $this->assertDatabaseMissing('pitches', [
            'project_id' => $contestProject->id,
            'user_id' => $producer->id,
        ]);
    }
}
