<?php

namespace Tests\Feature\Livewire\Pitch\Snapshot;

use App\Livewire\Pitch\Snapshot\ShowSnapshot;
use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ShowSnapshotTest extends TestCase
{
    /** @test */
    public function renders_successfully()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create(['pitch_id' => $pitch->id, 'user_id' => $pitchCreator->id]);

        // Test as project owner
        Livewire::actingAs($projectOwner)
            ->test(ShowSnapshot::class, [
                'project' => $project,
                'pitch' => $pitch,
                'snapshot' => $snapshot,
            ])
            ->assertOk();

        // Test as pitch creator
        Livewire::actingAs($pitchCreator)
            ->test(ShowSnapshot::class, [
                'project' => $project,
                'pitch' => $pitch,
                'snapshot' => $snapshot,
            ])
            ->assertOk();
    }
}
