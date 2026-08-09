<?php

namespace Tests\Feature\Security;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectUpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_non_owner_cannot_update_project(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $project = Project::factory()->for($owner)->create([
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($attacker)->put(route('projects.update', $project), [
            'description' => 'Hacked description',
        ]);

        $response->assertForbidden();

        $this->assertSame('Original description', $project->fresh()->description);
    }

    public function test_owner_can_update_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner)->create([
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($owner)->put(route('projects.update', $project), [
            'description' => 'Updated by owner',
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $this->assertSame('Updated by owner', $project->fresh()->description);
    }

    public function test_guest_cannot_update_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner)->create([
            'description' => 'Original description',
        ]);

        $response = $this->put(route('projects.update', $project), [
            'description' => 'Hacked description',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertSame('Original description', $project->fresh()->description);
    }

    public function test_non_owner_cannot_publish_project(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $project = Project::factory()->for($owner)->create([
            'is_published' => false,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);

        $response = $this->actingAs($attacker)->put(route('projects.update', $project), [
            'description' => 'Original description',
            'is_published' => true,
        ]);

        $response->assertForbidden();

        $this->assertFalse((bool) $project->fresh()->is_published);
    }

    public function test_non_owner_cannot_unpublish_project(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $project = Project::factory()->for($owner)->published()->create();

        $response = $this->actingAs($attacker)->put(route('projects.update', $project), [
            'description' => 'Original description',
            'is_published' => false,
        ]);

        $response->assertForbidden();

        $this->assertTrue((bool) $project->fresh()->is_published);
    }
}
