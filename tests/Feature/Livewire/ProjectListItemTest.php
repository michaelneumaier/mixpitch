<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProjectListItem;
use App\Models\Project;
use App\Models\User; // Assuming a Project model exists
use Livewire\Livewire;
use Tests\TestCase;

class ProjectListItemTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]); // Create a project for the user

        Livewire::actingAs($user)
            ->test(ProjectListItem::class, ['project' => $project])
            ->assertOk();
    }
}
