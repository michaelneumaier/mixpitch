<?php

namespace Tests\Unit\Services;

use App\Exceptions\Project\ProjectCreationException;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase; // May not be needed for pure unit tests if mocking DB
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectManagementServiceTest extends TestCase
{
    use RefreshDatabase; // Add this trait to handle migrations

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3'); // Fake the S3 disk for tests
    }

    /** @test */
    public function it_can_create_a_project_successfully()
    {
        $user = User::factory()->create();
        // Get factory data without saving. Use only fields expected in validatedData
        $data = Project::factory()->make([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'genre' => 'Pop',
            'artist_name' => 'Test Artist',
            'project_type' => 'Mixing',
            'collaboration_type' => ['Vocalist'],
            'budget' => 500,
            'deadline' => now()->addMonth(),
            // Add other necessary fields based on StoreProjectRequest validation
        ])->toArray();

        $service = new ProjectManagementService;

        // Mock DB transaction to just execute the callback
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Mock save method on the Project model if not using RefreshDatabase or real DB interaction
        // Project::shouldReceive('save')->andReturn(true);

        $project = $service->createProject($user, $data, null);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals($user->id, $project->user_id);
        $this->assertEquals($data['name'], $project->name);
        $this->assertEquals(Project::STATUS_UNPUBLISHED, $project->status); // Verify default status

        // If not mocking the save/DB, assert against the database
        // $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $data['name']]);
    }

    /** @test */
    public function it_can_create_a_project_with_an_image()
    {
        $user = User::factory()->create();
        $data = Project::factory()->make([
            'name' => 'Image Project',
            // ... other required fields ...
        ])->toArray();
        $file = UploadedFile::fake()->image('project.jpg');

        $service = new ProjectManagementService;
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $project = $service->createProject($user, $data, $file);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertNotNull($project->image_path);
        Storage::disk('s3')->assertExists($project->image_path);
        // If not mocking DB: $this->assertDatabaseHas('projects', ['id' => $project->id, 'image_path' => $project->image_path]);
    }

    /** @test */
    public function it_throws_exception_on_create_project_db_error()
    {
        $user = User::factory()->create();
        $data = Project::factory()->make([
            'name' => 'DB Error Project',
            // ... other required fields ...
        ])->toArray();

        $service = new ProjectManagementService;

        // Mock DB transaction to throw an exception
        DB::shouldReceive('transaction')->once()->andThrow(new \Exception('DB Error'));

        $this->expectException(ProjectCreationException::class);

        $service->createProject($user, $data, null);
    }

    // TODO: Add more tests for update, publish, unpublish, complete, image handling, edge cases etc.
}
