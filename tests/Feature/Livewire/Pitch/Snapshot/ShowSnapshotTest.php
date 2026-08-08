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

    /** @test */
    public function project_owner_can_view_snapshot_route(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->actingAs($projectOwner)
            ->get($url)
            ->assertOk()
            ->assertSeeLivewire(ShowSnapshot::class);
    }

    /** @test */
    public function pitch_owner_can_view_own_snapshot_route(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->actingAs($pitchCreator)
            ->get($url)
            ->assertOk()
            ->assertSeeLivewire(ShowSnapshot::class);
    }

    /** @test */
    public function unrelated_user_receives_forbidden_response(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->actingAs($unrelatedUser)
            ->get($url)
            ->assertForbidden();
    }

    /** @test */
    public function guest_is_redirected_to_login(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->get($url)->assertRedirect(route('login'));
    }

    /** @test */
    public function snapshot_belonging_to_a_different_pitch_returns_404(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        $otherPitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);
        // Snapshot actually belongs to $otherPitch
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $otherPitch->id,
            'project_id' => $project->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->actingAs($projectOwner)
            ->get($url)
            ->assertNotFound();
    }

    /** @test */
    public function pitch_belonging_to_a_different_project_returns_404(): void
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $projectOwner->id]);
        $otherProject = Project::factory()->create(['user_id' => $projectOwner->id]);
        // Pitch is on $otherProject
        $pitch = Pitch::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $pitchCreator->id,
        ]);
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $otherProject->id,
            'user_id' => $pitchCreator->id,
        ]);

        $url = route('projects.pitches.snapshots.show', [
            'project' => $project->slug,
            'pitch' => $pitch->slug,
            'snapshot' => $snapshot->id,
        ]);

        $this->actingAs($projectOwner)
            ->get($url)
            ->assertNotFound();
    }
}
