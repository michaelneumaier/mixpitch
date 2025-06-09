<?php

namespace Tests\Unit\Livewire\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Livewire\Profile\ClientActivitySummary;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientActivitySummaryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function component_renders_correctly_with_no_activity()
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

        Livewire::test(ClientActivitySummary::class, ['client' => $client])
            ->assertSeeHtml('Client Activity')
            ->assertSeeHtml('Total Projects Posted')
            ->assertSeeHtml('Projects Hired')
            ->assertSeeHtml('No project activity to display yet.');
    }

    /** @test */
    public function component_shows_correct_stats_and_recent_projects()
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        // Create projects with different statuses
        Project::factory()->count(2)->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN, 'is_published' => true]);
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_IN_PROGRESS, 'is_published' => true]); // Hired
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now(), 'is_published' => true]); // Hired & Completed
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_UNPUBLISHED, 'is_published' => false]);
        
        // Create an older project that shouldn't appear in recent (limit 5)
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN, 'created_at' => now()->subYear(), 'is_published' => true]);

        Livewire::test(ClientActivitySummary::class, ['client' => $client])
            ->assertSeeHtml('Total Projects Posted')
            ->assertSeeHtml('Projects Hired')
            ->assertSet('totalProjects', 6) // All projects including unpublished
            ->assertSet('hiredProjectsCount', 2) // In progress + completed
            ->call('loadClientActivity') // Ensure data is loaded
            ->assertDontSeeHtml('No project activity to display yet.');
    }

    /** @test */
    public function component_shows_correct_completed_projects()
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        // Create completed projects
        $completedProject1 = Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now()->subDay(), 'is_published' => true]);
        $completedProject2 = Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now(), 'is_published' => true]);
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN, 'is_published' => true]);

        $component = Livewire::test(ClientActivitySummary::class, ['client' => $client])
            ->assertSeeHtml('Total Projects Posted')
            ->assertSeeHtml('Projects Hired')
            ->assertSet('totalProjects', 3)
            ->assertSet('hiredProjectsCount', 2) // 2 completed projects
            ->assertSeeHtml($completedProject1->name)
            ->assertSeeHtml($completedProject2->name);
            
        // Check that completed projects were loaded correctly
        $this->assertCount(2, $component->get('completedProjects'));
    }

    /** @test */
    public function component_handles_users_with_no_role_or_producer_role()
    {
        // Test with a producer role (should still render, but likely show 0s)
        $producer = User::factory()->create(['role' => User::ROLE_PRODUCER]);
        Project::factory()->count(3)->create(['user_id' => $producer->id, 'is_published' => true]); // Producers might own projects too

        Livewire::test(ClientActivitySummary::class, ['client' => $producer])
            ->assertSeeHtml('Client Activity')
            ->assertSeeHtml('Total Projects Posted')
            ->assertSeeHtml('Projects Hired')
            ->assertSet('totalProjects', 3)
            ->assertSet('hiredProjectsCount', 0) // No hired projects as they're all open by default
            ->assertDontSeeHtml('No project activity to display yet.');
    }
}
