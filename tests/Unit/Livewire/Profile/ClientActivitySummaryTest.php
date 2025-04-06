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
            ->assertSeeHtml('Total Projects Posted:</span> 0')
            ->assertSeeHtml('Projects Hired:</span> 0')
            ->assertSeeHtml('No project activity to display yet.');
    }

    /** @test */
    public function component_shows_correct_stats_and_recent_projects()
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        // Create projects with different statuses
        Project::factory()->count(2)->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN]);
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_IN_PROGRESS]); // Hired
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now()]); // Hired & Completed
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_UNPUBLISHED]);
        
        // Create an older project that shouldn't appear in recent (limit 5)
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN, 'created_at' => now()->subYear()]);

        Livewire::test(ClientActivitySummary::class, ['client' => $client])
            ->assertSeeHtml('Total Projects Posted:</span> 6')
            ->assertSeeHtml('Projects Hired:</span> 2')
            ->assertViewHas('recentProjects', function ($projects) {
                return $projects->count() === 5; // Check limit
            })
            ->assertDontSeeHtml('No project activity to display yet.');
    }

    /** @test */
    public function component_shows_correct_completed_projects()
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        // Create completed projects
        $completedProject1 = Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now()->subDay()]);
        $completedProject2 = Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_COMPLETED, 'completed_at' => now()]);
        Project::factory()->create(['user_id' => $client->id, 'status' => Project::STATUS_OPEN]);

        Livewire::test(ClientActivitySummary::class, ['client' => $client])
            ->assertSeeHtml('Total Projects Posted:</span> 3')
            ->assertSeeHtml('Projects Hired:</span> 2')
            ->assertViewHas('completedProjects', function ($projects) use ($completedProject1, $completedProject2) {
                // Check count and order (latest completed first)
                return $projects->count() === 2 && 
                       $projects->first()->id === $completedProject2->id &&
                       $projects->last()->id === $completedProject1->id;
            })
            ->assertSeeHtml($completedProject1->name)
            ->assertSeeHtml($completedProject2->name);
    }

    /** @test */
    public function component_handles_users_with_no_role_or_producer_role()
    {
        // Test with a producer role (should still render, but likely show 0s)
        $producer = User::factory()->create(['role' => User::ROLE_PRODUCER]);
        Project::factory()->count(3)->create(['user_id' => $producer->id]); // Producers might own projects too

        Livewire::test(ClientActivitySummary::class, ['client' => $producer])
            ->assertSeeHtml('Client Activity')
            ->assertSeeHtml('Total Projects Posted:</span> 3')
            ->assertSeeHtml('Projects Hired:</span> 0')
            ->assertSeeHtml('Recent Projects')
            ->assertDontSeeHtml('No project activity to display yet.');
    }
}
