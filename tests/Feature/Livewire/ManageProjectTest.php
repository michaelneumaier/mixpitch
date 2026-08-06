<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Project\ManageStandardProject;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully_for_project_owner()
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Project Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'status' => 'draft',
            'is_published' => false,
            'preview_track' => null,
            'total_storage_used' => 0,
            'total_storage_limit_bytes' => 104857600,
        ]);

        Livewire::actingAs($user)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->assertOk();
    }

    /** @test */
    public function fails_to_render_for_unauthorized_user()
    {
        $projectOwner = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $projectOwner->id,
            'name' => 'Test Project',
            'description' => 'Test Project Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'status' => 'draft',
            'is_published' => false,
            'preview_track' => null,
        ]);

        $unauthorizedUser = User::factory()->create();

        Livewire::actingAs($unauthorizedUser)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->assertForbidden();
    }

    /** @test */
    public function can_publish_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'status' => 'draft',
            'is_published' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->call('publish')
            ->assertDispatched('project-updated');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_published' => true,
        ]);
    }

    /** @test */
    public function can_unpublish_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'status' => 'published',
            'is_published' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->call('unpublish')
            ->assertDispatched('project-updated');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_published' => false,
        ]);
    }

    /** @test */
    public function can_update_project_details_inline()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'description' => 'Original Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ]);

        Livewire::actingAs($user)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->call('updateProjectDetailsInline', [
                'description' => 'Updated Description',
                'genre' => 'Pop',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'description' => 'Updated Description',
            'genre' => 'Pop',
        ]);
    }

    /** @test */
    public function can_update_project_title()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'description' => 'Original Description',
            'project_type' => 'single',
            'genre' => 'Rock',
        ]);

        Livewire::actingAs($user)
            ->test(ManageStandardProject::class, ['project' => $project])
            ->call('updateProjectTitle', 'Updated Name');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }
}
