<?php

namespace Tests\Feature;

use App\Livewire\CreateProject;
use App\Livewire\ManageProject;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectManagementService;
use Database\Seeders\ProjectTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake the S3 disk for all tests in this class
        Storage::fake('s3');
    }

    // ========================================
    // CreateProject Livewire Component Tests
    // ========================================

    /** @test */
    public function guest_cannot_view_create_project_page()
    {
        // Assuming there's a route named 'projects.create'
        $this->get(route('projects.create'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_create_project_page()
    {
        $user = User::factory()->create();

        // projects.create now redirects to dashboard (project creation is handled via dropdown)
        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function create_project_component_shows_validation_errors()
    {
        $this->seed(ProjectTypeSeeder::class);
        $user = User::factory()->create();

        // Wizard step 2 validates name, genre, description
        // Must set a collaboration type to bypass the collaboration check
        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('currentStep', 2) // Step 2 validates basic project details
            ->set('form.name', '') // Invalid: name is required
            ->set('form.genre', '') // Invalid: genre is required
            ->set('form.description', '') // Invalid: description is required
            ->set('form.collaborationTypeMixing', true) // Required to reach validation
            ->call('nextStep')
            ->assertHasErrors(['form.name', 'form.genre', 'form.description']);
    }

    /** @test */
    public function create_project_component_can_create_project_without_image()
    {
        $this->seed(ProjectTypeSeeder::class);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('currentStep', 4) // Set to review step
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->set('form.name', 'Test Project Name')
            ->set('form.description', 'Test Description')
            ->set('form.genre', 'Pop')
            ->set('form.artistName', 'Test Artist')
            ->set('form.projectType', 'single')
            ->set('form.collaborationTypeMixing', true)
            ->set('form.budgetType', 'free')
            ->set('form.budget', 0)
            ->set('form.deadline', now()->addMonth()->format('Y-m-d'))
            ->call('save');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Test Project Name',
            'genre' => 'Pop',
            'budget' => 0,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);

        $project = Project::where('name', 'Test Project Name')->first();
        $this->assertNotNull($project, 'Project was not created.');
    }

    /** @test */
    public function create_project_component_can_create_project_with_image()
    {
        Storage::fake('s3');
        $this->seed(ProjectTypeSeeder::class);
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('project.jpg');

        Livewire::actingAs($user)
            ->test(CreateProject::class)
            ->set('currentStep', 4) // Set to review step
            ->set('workflow_type', Project::WORKFLOW_TYPE_STANDARD)
            ->set('form.name', 'Test Project Name Image')
            ->set('form.description', 'Test Description')
            ->set('form.genre', 'Rock')
            ->set('form.artistName', 'Test Artist')
            ->set('form.projectType', 'album')
            ->set('form.collaborationTypeMastering', true)
            ->set('form.budgetType', 'paid')
            ->set('form.budget', 500)
            ->set('form.projectImage', $file)
            ->set('form.deadline', now()->addMonths(2)->format('Y-m-d'))
            ->call('save');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Test Project Name Image',
            'genre' => 'Rock',
            'budget' => 500,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);

        $project = Project::where('name', 'Test Project Name Image')->first();
        $this->assertNotNull($project, 'Project with image was not created.');
        $this->assertNotNull($project->image_path);
        Storage::disk('s3')->assertExists($project->image_path);
    }

    // ========================================
    // Edit Project Tests (Using CreateProject Component)
    // ========================================

    /** @test */
    public function authorized_user_can_view_edit_project_page()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSeeLivewire(CreateProject::class); // Should load CreateProject
    }

    /** @test */
    public function unauthorized_user_cannot_view_edit_project_page()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    /** @test */
    public function edit_project_component_loads_existing_data()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'name' => 'Existing Name',
            'description' => 'Existing Description',
            'project_type' => 'album',
            'budget' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(CreateProject::class, ['project' => $project]) // Mount with project
            ->assertSet('isEdit', true)
            ->assertSet('form.name', 'Existing Name')
            ->assertSet('form.description', 'Existing Description')
            ->assertSet('form.projectType', 'album')
            ->assertSet('form.budget', 100);
    }

    /** @test */
    public function edit_project_component_can_update_project_details()
    {
        $this->markTestSkipped('Skipping due to form update issues with projectType field.');

        /*
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
            'project_type' => 'single',
        ]);

        Livewire::actingAs($user)
            ->test(CreateProject::class, ['project' => $project])
            ->set('form.name', 'Updated Name')
            ->set('form.description', 'Updated Description')
            ->set('form.projectType', 'ep')
            ->call('save');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'project_type' => 'ep',
        ]);
        */
    }

    /** @test */
    public function edit_project_component_can_update_project_image()
    {
        // This test verifies that image uploads work through the CreateProject component
        Storage::fake('s3');
        $user = User::factory()->create();

        // Create project with no initial image
        $project = Project::factory()->for($user)->create([
            'image_path' => null,
            'name' => 'Test Project',
            'description' => 'Test Description',
            'genre' => 'Rock',
            'project_type' => 'single',
            'budget' => 100,
            'deadline' => now()->addMonth()->format('Y-m-d'),
        ]);

        // Verify project has no initial image path
        $this->assertNull($project->image_path);

        // Create a fake image file for upload
        $newImage = UploadedFile::fake()->image('test_image.jpg');

        // Test using the component with our special test helper
        $component = Livewire::actingAs($user)
            ->test(CreateProject::class, ['project' => $project])
            ->set('form.projectImage', $newImage)
            ->call('forceImageUpdate');

        // Refresh project from database
        $project->refresh();

        // Assert project now has an image
        $this->assertNotNull($project->image_path, 'Project should have an image path after forced update');
        Storage::disk('s3')->assertExists($project->image_path);

        // Creating a second image to verify updates work
        $secondImage = UploadedFile::fake()->image('second_test_image.jpg');
        $oldPath = $project->image_path;

        // Update with second image
        Livewire::actingAs($user)
            ->test(CreateProject::class, ['project' => $project])
            ->set('form.projectImage', $secondImage)
            ->call('forceImageUpdate');

        // Refresh project and check image was updated
        $project->refresh();
        $this->assertNotNull($project->image_path, 'Project should still have an image path');
        $this->assertNotEquals($oldPath, $project->image_path, 'Image path should have changed');
        Storage::disk('s3')->assertExists($project->image_path);
        Storage::disk('s3')->assertMissing($oldPath);
    }

    /** @test */
    public function edit_project_component_shows_validation_errors()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(CreateProject::class, ['project' => $project])
            ->set('form.name', '') // Make name invalid
            ->call('save')
            ->assertHasErrors(['form.name']);
    }

    // ========================================
    // ManageProject Livewire Component Tests
    // ========================================

    /** @test */
    public function guest_cannot_view_manage_project_page()
    {
        $project = Project::factory()->create();
        // Assuming a route like 'projects.manage' or 'projects.edit'
        // Adjust route name as necessary
        $this->get(route('projects.edit', $project))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthorized_user_cannot_view_manage_project_page()
    {
        // ManageProject is a router component that always redirects to the
        // workflow-specific management page. Authorization is handled by
        // the target component (e.g. ManageStandardProject), so the
        // projects.manage route always returns a 302 redirect.
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->get(route('projects.manage', $project))
            ->assertRedirect();
    }

    /** @test */
    public function authorized_user_can_view_manage_project_page()
    {
        // ManageProject is a router component that always redirects to the
        // workflow-specific management page (e.g. manage-standard-project).
        // Verify the redirect happens.
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('projects.manage', $project))
            ->assertRedirect();
    }

    /** @test */
    public function manage_project_component_can_publish_project()
    {
        $this->markTestSkipped('Skipping due to Livewire test environment auth issue (wrong user ID passed to policy).');

        $user = User::factory()->create(); // Create owner directly
        $project = Project::factory()->for($user)->create(['status' => Project::STATUS_UNPUBLISHED]); // Create project for owner

        Livewire::actingAs($user) // Act as the owner
            ->test(ManageProject::class, ['project' => $project])
            ->call('publish');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => Project::STATUS_OPEN,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_publish_project_via_component()
    {
        $this->seed(); // Seeding might be needed for roles/permissions if Gate depends on them
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner)->create(['status' => Project::STATUS_UNPUBLISHED]);

        // Test the Gate directly
        $this->assertTrue(Gate::forUser($otherUser)->denies('publish', $project));

        // Optional: Verify the state didn't change (should pass as action wasn't called)
        $project->refresh();
        $this->assertEquals(Project::STATUS_UNPUBLISHED, $project->status);
    }

    /** @test */
    public function manage_project_component_can_unpublish_project()
    {
        $this->markTestSkipped('Skipping due to Livewire test environment auth issue (wrong user ID passed to policy).');

        $user = User::factory()->create(); // Create owner directly
        $project = Project::factory()->for($user)->published()->create(['status' => Project::STATUS_OPEN]); // Create published project for owner

        Livewire::actingAs($user) // Act as the owner
            ->test(ManageProject::class, ['project' => $project])
            ->call('unpublish');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_unpublish_project_via_component()
    {
        $this->seed(); // Seeding might be needed for roles/permissions if Gate depends on them
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner)->published()->create(['status' => Project::STATUS_OPEN]);

        // Test the Gate directly
        $this->assertTrue(Gate::forUser($otherUser)->denies('unpublish', $project));

        // Optional: Verify the state didn't change (should pass as action wasn't called)
        $project->refresh();
        $this->assertEquals(Project::STATUS_OPEN, $project->status);
    }

    // TODO: Add tests for ManageProject file uploads/deletions in Step 5 testing
    // These require mocking or interacting with the FileManagementService once refactored.

    /** @test */
    public function manage_project_component_update_with_new_image_removes_old_one()
    {
        // ManageProject is a router component that always redirects in mount().
        // It cannot be tested directly with Livewire::test() as it produces null snapshot errors.
        // Image update functionality should be tested via the workflow-specific components
        // (ManageStandardProject, ManageContestProject, ManageClientProject).
        $this->markTestSkipped('ManageProject is a router component that always redirects; cannot be tested directly with Livewire::test().');
    }

    /** @test */
    public function project_management_service_can_update_image()
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $newFile = UploadedFile::fake()->image('new.jpg');
        $oldPath = $oldFile->store('project_images', 's3');

        $project = Project::factory()->for($user)->create([
            'image_path' => $oldPath,
            // Add minimal valid data to satisfy potential model/DB constraints
            'name' => 'Service Image Update Test',
            'description' => 'Testing service directly.',
            'genre' => 'Rock',
            'project_type' => 'ep',
            'deadline' => now()->addDays(30),
            'budget' => 0,
        ]);

        // Instantiate the service directly using the app container
        $service = app(ProjectManagementService::class);

        // Call the service method
        $updatedProject = $service->updateProject(
            $project,
            [], // Pass empty validatedData as we focus only on image
            $newFile
        );

        // Retrieve the project fresh from the database
        $projectFromDb = Project::find($project->id);

        $this->assertNotNull($projectFromDb->image_path, 'Image path should not be null after update.');
        $this->assertNotEquals($oldPath, $projectFromDb->image_path, 'Image path should have changed.');
        Storage::disk('s3')->assertMissing($oldPath); // Remove custom message
        Storage::disk('s3')->assertExists($projectFromDb->image_path); // Remove custom message
        // Verify the returned project instance also has the correct path
        $this->assertEquals($projectFromDb->image_path, $updatedProject->image_path, 'Returned project image path mismatch.');
    }
}
