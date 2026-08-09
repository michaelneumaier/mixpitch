<?php

namespace Tests\Feature\Security;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HttpLayerHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    /**
     * C1: The unauthenticated arbitrary-S3-read presigned route must no longer exist.
     */
    public function test_presigned_audio_route_is_removed(): void
    {
        $response = $this->get('/audio-file/presigned/anything/at/all.mp3');

        $response->assertNotFound();
    }

    /**
     * H1: changeStatus must reject a pitch that does not belong to the given project (IDOR guard).
     */
    public function test_change_status_rejects_pitch_from_another_project(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $projectA = Project::factory()->for($ownerA)->create();
        $projectB = Project::factory()->for($ownerB)->create();

        $pitchB = Pitch::factory()->create([
            'project_id' => $projectB->id,
            'user_id' => User::factory()->create()->id,
            'status' => Pitch::STATUS_PENDING,
        ]);

        $response = $this->actingAs($ownerA)->post(
            route('projects.pitches.change-status', ['project' => $projectA, 'pitch' => $pitchB]),
            [
                'direction' => 'forward',
                'newStatus' => Pitch::STATUS_IN_PROGRESS,
            ]
        );

        $response->assertNotFound();
    }

    /**
     * M1: The confirmed-delete route must reject GET (must be POST for CSRF protection).
     */
    public function test_delete_confirmed_route_rejects_get(): void
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($producer)->get(
            route('projects.pitches.destroyConfirmed', ['project' => $project, 'pitch' => $pitch])
        );

        $response->assertMethodNotAllowed();
    }

    /**
     * M1: A valid POST from the pitch owner deletes (soft-deletes) the pitch.
     */
    public function test_pitch_owner_can_delete_via_post(): void
    {
        $owner = User::factory()->create();
        $producer = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($producer)->post(
            route('projects.pitches.destroyConfirmed', ['project' => $project, 'pitch' => $pitch])
        );

        $response->assertRedirect(route('projects.show', $project));

        $this->assertSoftDeleted('pitches', ['id' => $pitch->id]);
    }

    /**
     * M2: A non-admin authenticated user must be forbidden from dev/test scaffolding routes.
     */
    public function test_non_admin_cannot_access_test_lambda_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/test-lambda-direct');

        $response->assertForbidden();
    }

    /**
     * M2: An admin is not forbidden from the dev/test scaffolding routes.
     */
    public function test_admin_is_not_forbidden_from_test_lambda_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/test-lambda-direct');

        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
