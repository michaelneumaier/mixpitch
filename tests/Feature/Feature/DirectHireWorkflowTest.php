<?php

namespace Tests\Feature\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Illuminate\Support\Facades\Auth;
use App\Observers\ProjectObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DirectHireWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure migrations are run and workflow_type column exists
        if (!Schema::hasColumn('projects', 'workflow_type')) {
            $this->markTestSkipped('The workflow_type column does not exist in the projects table.');
        }
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test that creating a Direct Hire project automatically creates a pitch
     * and triggers the correct notification.
     */
    public function test_direct_hire_project_creation_creates_pitch_and_notifies_producer(): void
    {
        // Arrange: Create owner and target producer
        $owner = User::factory()->create();
        $producer = User::factory()->create();

        // Mock the facade used for broadcasting events if necessary
        Notification::fake(); // Use Laravel's built-in fake

        // Log test diagnostic information
        \Illuminate\Support\Facades\Log::info('Starting direct hire project creation test', [
            'owner_id' => $owner->id,
            'producer_id' => $producer->id
        ]);

        // Verify that the workflow_type column exists
        if (!Schema::hasColumn('projects', 'workflow_type')) {
            $this->fail('workflow_type column does not exist in projects table');
        }

        // Act: Create the Direct Hire project using the factory
        $project = Project::factory()
            ->configureWorkflow(Project::WORKFLOW_TYPE_DIRECT_HIRE, [
                'target_producer_id' => $producer->id
            ])
            ->create([
                'user_id' => $owner->id,
                 // Ensure required fields not covered by definition/state are set
                'name' => 'Test Direct Hire Project',
                'artist_name' => 'Test Artist',
                'project_type' => 'single', 
                'collaboration_type' => ['Mixing'],
                'budget' => 100, 
                'deadline' => now()->addDays(30),
            ]);

        // Log created project
        \Illuminate\Support\Facades\Log::info('Project created', [
            'project_id' => $project->id,
            'workflow_type' => $project->workflow_type,
            'has_target_producer' => !is_null($project->target_producer_id)
        ]);
            
        // Refresh from database to ensure we have all fields
        $project = $project->fresh();

        // Manually trigger the observer logic
        $observer = app(ProjectObserver::class); // Resolve observer from container
        $observer->created($project);

        // Assert: Check if the project was created
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'workflow_type' => Project::WORKFLOW_TYPE_DIRECT_HIRE,
            'target_producer_id' => $producer->id,
        ]);

        // Assert: Check if a pitch was automatically created for the producer
        $this->assertDatabaseHas('pitches', [
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS, // Verify implicit flow status
        ]);

        // Assert: Check if the initiation event was created
        $pitch = Pitch::where('project_id', $project->id)->where('user_id', $producer->id)->first();
        $this->assertNotNull($pitch, 'Pitch was not created.');
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'direct_hire_initiated',
            'created_by' => $owner->id,
        ]);

        // Mockery assertions (like shouldReceive) are checked automatically at the end of the test.
        // Assert Notification was created using database check
        $pitch = Pitch::where('project_id', $project->id)->where('user_id', $producer->id)->first(); // Re-fetch pitch
        $this->assertNotNull($pitch, 'Pitch should exist before checking notification.');
        $this->assertDatabaseHas('notifications', [
             'user_id' => $producer->id,
             'type' => \App\Models\Notification::TYPE_DIRECT_HIRE_ASSIGNMENT,
             'related_type' => Pitch::class,
             'related_id' => $pitch->id,
         ]);
    }

    /**
     * Test permissions for a Direct Hire project/pitch.
     */
    public function test_direct_hire_permissions(): void
    {
        // Arrange: Create owner, producer, and a random user
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $randomUser = User::factory()->create();

        // Verify that the workflow_type column exists
        if (!Schema::hasColumn('projects', 'workflow_type')) {
            $this->fail('workflow_type column does not exist in projects table');
        }

        // Create a Direct Hire project assigned to the producer
        $project = Project::factory()
            ->configureWorkflow(Project::WORKFLOW_TYPE_DIRECT_HIRE, ['target_producer_id' => $producer->id])
            ->create(['user_id' => $owner->id]);
        
        // Manually trigger observer logic
        $observer = app(ProjectObserver::class);
        $observer->created($project);

        // Retrieve the automatically created pitch
        $pitch = Pitch::where('project_id', $project->id)->where('user_id', $producer->id)->first();
        $this->assertNotNull($pitch, 'Pitch was not automatically created for Direct Hire project.');
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $pitch->status);

        // Act & Assert: Producer permissions
        // Example: Check if producer can submit for review (Policy needs to allow this)
        $this->actingAs($producer);
        $this->assertTrue($producer->can('submitForReview', $pitch), 'Producer should be able to submit for review.');
        // $response = $this->post(route('pitches.submitReview', $pitch->id)); // Assuming a route exists
        // $response->assertStatus(200); // or appropriate status/redirect

        // Act & Assert: Owner permissions
        // Example: Check if owner can view the pitch
        $this->actingAs($owner);
        $this->assertTrue($owner->can('view', $pitch), 'Owner should be able to view the pitch.');
        // Example: Owner cannot submit for review
        $this->assertFalse($owner->can('submitForReview', $pitch), 'Owner should not be able to submit for review.');

        // Act & Assert: Random user permissions
        // Example: Check if random user can view the pitch
        $this->actingAs($randomUser);
        $this->assertFalse($randomUser->can('view', $pitch), 'Random user should not be able to view the pitch.');
        $this->assertFalse($randomUser->can('submitForReview', $pitch), 'Random user should not be able to submit for review.');
    }

    /**
     * Test visibility of Direct Hire projects in listings.
     */
    public function test_direct_hire_project_visibility(): void
    {
        // Arrange: Create owner, producer, random user, and projects
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $randomUser = User::factory()->create();

        // Verify that the workflow_type column exists
        if (!Schema::hasColumn('projects', 'workflow_type')) {
            $this->fail('workflow_type column does not exist in projects table');
        }

        // Create a Direct Hire project (should be visible to owner & producer)
        $directHireProject = Project::factory()
            ->configureWorkflow(Project::WORKFLOW_TYPE_DIRECT_HIRE, ['target_producer_id' => $producer->id])
            ->published() // Ensure it's published/visible otherwise
            ->create(['user_id' => $owner->id]);

        // Create a standard project (should be visible to all)
        $standardProject = Project::factory()->published()->create();

        // Act & Assert: Owner's view
        $this->actingAs($owner);
        $response = $this->get(route('projects.index')); // Assuming a route for project listing
        $response->assertStatus(200);
        $response->assertSee($directHireProject->name); // Use 'name'
        $response->assertSee($standardProject->name);

        // Act & Assert: Producer's view
        $this->actingAs($producer);
        $response = $this->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertSee($directHireProject->name); // Use 'name'
        $response->assertSee($standardProject->name);

        // Act & Assert: Random user's view
        $this->actingAs($randomUser);
        $response = $this->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertDontSee($directHireProject->name); // Use 'name'
        $response->assertSee($standardProject->name);

         // Act & Assert: Guest's view (not logged in)
        Auth::logout();
        $response = $this->get(route('projects.index'));
        $response->assertStatus(200);
        $response->assertDontSee($directHireProject->name); // Use 'name'
        $response->assertSee($standardProject->name);
    }
}
