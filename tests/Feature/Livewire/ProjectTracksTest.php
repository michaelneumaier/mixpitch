<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProjectTracks;
use App\Models\Project; // Assuming a Project model exists
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTracksTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ProjectTracks::class, ['project' => $project])
            ->assertOk();
    }
}
