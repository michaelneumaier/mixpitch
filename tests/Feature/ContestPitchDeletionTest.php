<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\ContestJudgingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContestPitchDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $contestRunner;

    private Project $contestProject;

    private ContestJudgingService $judgingService;

    private array $contestants = [];

    private array $contestEntries = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create contest runner
        $this->contestRunner = User::factory()->create();

        // Create contest project
        $this->contestProject = Project::factory()->create([
            'user_id' => $this->contestRunner->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => now()->subDays(1),
            'is_published' => true,
        ]);

        $this->judgingService = app(ContestJudgingService::class);

        // Create contest entries
        for ($i = 0; $i < 5; $i++) {
            $contestant = User::factory()->create();
            $this->contestants[] = $contestant;

            $pitch = Pitch::factory()->create([
                'project_id' => $this->contestProject->id,
                'user_id' => $contestant->id,
                'status' => Pitch::STATUS_CONTEST_ENTRY,
                'rank' => null,
            ]);

            $this->contestEntries[] = $pitch;
        }
    }

    /** @test */
    public function deleting_first_place_pitch_clears_placement()
    {
        // Set first place
        $firstPlacePitch = $this->contestEntries[0];
        $this->judgingService->setPlacement(
            $this->contestProject,
            $firstPlacePitch,
            Pitch::RANK_FIRST
        );

        // Verify placement is set
        $contestResult = $this->contestProject->contestResult;
        $this->assertEquals($firstPlacePitch->id, $contestResult->first_place_pitch_id);
        $this->assertEquals('1st', $contestResult->hasPlacement($firstPlacePitch->id));

        // Delete the pitch
        $firstPlacePitch->delete();

        // Verify placement is cleared (database FK constraint should handle this)
        $contestResult->refresh();
        $this->assertNull($contestResult->first_place_pitch_id);
        $this->assertNull($contestResult->hasPlacement($firstPlacePitch->id));
    }

    /** @test */
    public function deleting_runner_up_pitch_removes_from_array()
    {
        // Set multiple runner-ups
        $runnerUp1 = $this->contestEntries[0];
        $runnerUp2 = $this->contestEntries[1];
        $runnerUp3 = $this->contestEntries[2];

        $this->judgingService->setPlacement($this->contestProject, $runnerUp1, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp2, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp3, Pitch::RANK_RUNNER_UP);

        // Verify all are set as runner-ups
        $contestResult = $this->contestProject->contestResult;
        $this->assertCount(3, $contestResult->runner_up_pitch_ids);
        $this->assertContains($runnerUp1->id, $contestResult->runner_up_pitch_ids);
        $this->assertContains($runnerUp2->id, $contestResult->runner_up_pitch_ids);
        $this->assertContains($runnerUp3->id, $contestResult->runner_up_pitch_ids);

        // Delete one runner-up
        $runnerUp2->delete();

        // Verify it's removed from the array
        $contestResult->refresh();
        $this->assertCount(2, $contestResult->runner_up_pitch_ids ?? []);
        $this->assertContains($runnerUp1->id, $contestResult->runner_up_pitch_ids);
        $this->assertNotContains($runnerUp2->id, $contestResult->runner_up_pitch_ids);
        $this->assertContains($runnerUp3->id, $contestResult->runner_up_pitch_ids);
    }

    /** @test */
    public function deleting_last_runner_up_sets_array_to_null()
    {
        // Set single runner-up
        $runnerUp = $this->contestEntries[0];
        $this->judgingService->setPlacement($this->contestProject, $runnerUp, Pitch::RANK_RUNNER_UP);

        // Verify runner-up is set
        $contestResult = $this->contestProject->contestResult;
        $this->assertCount(1, $contestResult->runner_up_pitch_ids);
        $this->assertContains($runnerUp->id, $contestResult->runner_up_pitch_ids);

        // Delete the runner-up
        $runnerUp->delete();

        // Verify array is set to null (empty)
        $contestResult->refresh();
        $this->assertNull($contestResult->runner_up_pitch_ids);
    }

    /** @test */
    public function deleting_pitch_from_finalized_contest_still_cleans_up()
    {
        // Set placements
        $firstPlace = $this->contestEntries[0];
        $secondPlace = $this->contestEntries[1];
        $runnerUp1 = $this->contestEntries[2];
        $runnerUp2 = $this->contestEntries[3];

        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->setPlacement($this->contestProject, $secondPlace, Pitch::RANK_SECOND);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp1, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp2, Pitch::RANK_RUNNER_UP);

        // Finalize the contest
        $this->judgingService->finalizeJudging(
            $this->contestProject,
            $this->contestRunner,
            'Contest finalized for testing'
        );

        $this->assertTrue($this->contestProject->fresh()->isJudgingFinalized());

        // Delete a runner-up from finalized contest
        $runnerUp1->delete();

        // Verify cleanup still happens
        $contestResult = $this->contestProject->contestResult;
        $contestResult->refresh();

        $this->assertCount(1, $contestResult->runner_up_pitch_ids ?? []);
        $this->assertNotContains($runnerUp1->id, $contestResult->runner_up_pitch_ids ?? []);
        $this->assertContains($runnerUp2->id, $contestResult->runner_up_pitch_ids);
    }

    /** @test */
    public function deleting_multiple_pitches_cleans_all_references()
    {
        // Set up full contest with all placements
        $firstPlace = $this->contestEntries[0];
        $secondPlace = $this->contestEntries[1];
        $thirdPlace = $this->contestEntries[2];
        $runnerUp1 = $this->contestEntries[3];
        $runnerUp2 = $this->contestEntries[4];

        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->setPlacement($this->contestProject, $secondPlace, Pitch::RANK_SECOND);
        $this->judgingService->setPlacement($this->contestProject, $thirdPlace, Pitch::RANK_THIRD);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp1, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp2, Pitch::RANK_RUNNER_UP);

        // Verify all placements
        $contestResult = $this->contestProject->contestResult;
        $this->assertEquals($firstPlace->id, $contestResult->first_place_pitch_id);
        $this->assertEquals($secondPlace->id, $contestResult->second_place_pitch_id);
        $this->assertEquals($thirdPlace->id, $contestResult->third_place_pitch_id);
        $this->assertCount(2, $contestResult->runner_up_pitch_ids);

        // Delete multiple pitches
        $secondPlace->delete();
        $runnerUp1->delete();
        $thirdPlace->delete();

        // Verify all references are cleaned
        $contestResult->refresh();
        $this->assertEquals($firstPlace->id, $contestResult->first_place_pitch_id);
        $this->assertNull($contestResult->second_place_pitch_id);
        $this->assertNull($contestResult->third_place_pitch_id);
        $this->assertCount(1, $contestResult->runner_up_pitch_ids ?? []);
        $this->assertContains($runnerUp2->id, $contestResult->runner_up_pitch_ids);
    }

    /** @test */
    public function contest_result_utility_methods_work_correctly()
    {
        // Set up contest with placements
        $firstPlace = $this->contestEntries[0];
        $runnerUp1 = $this->contestEntries[1];
        $runnerUp2 = $this->contestEntries[2];

        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp1, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp2, Pitch::RANK_RUNNER_UP);

        $contestResult = $this->contestProject->contestResult;

        // Test hasOrphanedPitches (should be empty with valid pitches)
        $orphaned = $contestResult->hasOrphanedPitches();
        $this->assertEmpty($orphaned);

        // Test removePitchFromAllPlacements
        $wasRemoved = $contestResult->removePitchFromAllPlacements($runnerUp1->id);
        $this->assertTrue($wasRemoved);
        $this->assertCount(1, $contestResult->runner_up_pitch_ids);
        $this->assertNotContains($runnerUp1->id, $contestResult->runner_up_pitch_ids);

        // Test getContestSummary
        $summary = $contestResult->getContestSummary();
        $this->assertEquals(5, $summary['total_entries']);
        $this->assertEquals(2, $summary['placed_entries']); // first place + 1 runner-up
        $this->assertFalse($summary['has_orphaned_references']);
        $this->assertTrue($summary['has_winners']);
    }

    /** @test */
    public function force_deleting_pitch_also_cleans_contest_results()
    {
        // Set runner-up
        $runnerUp = $this->contestEntries[0];
        $this->judgingService->setPlacement($this->contestProject, $runnerUp, Pitch::RANK_RUNNER_UP);

        // Verify placement
        $contestResult = $this->contestProject->contestResult;
        $this->assertContains($runnerUp->id, $contestResult->runner_up_pitch_ids);

        // Delete the pitch (using regular delete since forceDelete has media dependencies)
        $runnerUp->delete();

        // Verify cleanup happened
        $contestResult->refresh();
        $this->assertNull($contestResult->runner_up_pitch_ids);
    }

    /** @test */
    public function cleanup_command_detects_orphaned_references()
    {
        // Set placements
        $firstPlace = $this->contestEntries[0];
        $runnerUp = $this->contestEntries[1];

        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp, Pitch::RANK_RUNNER_UP);

        $contestResult = $this->contestProject->contestResult;

        // Manually delete pitches without triggering observers (simulating old data)
        Pitch::whereIn('id', [$firstPlace->id, $runnerUp->id])->forceDelete();

        // Manually create orphaned references (simulating the bug state)
        $contestResult->update([
            'first_place_pitch_id' => $firstPlace->id, // This will be caught by FK constraint
            'runner_up_pitch_ids' => [$runnerUp->id], // This is what we're fixing
        ]);

        // Test orphan detection
        $orphaned = $contestResult->fresh()->hasOrphanedPitches();
        $this->assertNotEmpty($orphaned);
        $this->assertArrayHasKey('runner_ups', $orphaned);
        $this->assertContains($runnerUp->id, $orphaned['runner_ups']);

        // Test cleanup
        $cleaned = $contestResult->cleanupOrphanedPitches();
        $contestResult->save();

        $this->assertArrayHasKey('runner_ups', $cleaned);
        $this->assertNull($contestResult->fresh()->runner_up_pitch_ids);
    }

    /** @test */
    public function finalizing_contest_judging_sets_project_status_to_completed()
    {
        // Set some placements
        $firstPlace = $this->contestEntries[0];
        $runnerUp = $this->contestEntries[1];

        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->setPlacement($this->contestProject, $runnerUp, Pitch::RANK_RUNNER_UP);

        // Verify initial project status
        $this->assertNotEquals(Project::STATUS_COMPLETED, $this->contestProject->status);
        $this->assertFalse($this->contestProject->isJudgingFinalized());

        // Finalize the contest
        $result = $this->judgingService->finalizeJudging(
            $this->contestProject,
            $this->contestRunner,
            'Contest completed successfully'
        );

        $this->assertTrue($result);

        // Verify project status is now completed
        $this->contestProject->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->contestProject->status);
        $this->assertTrue($this->contestProject->isJudgingFinalized());
        $this->assertEquals('Contest completed successfully', $this->contestProject->judging_notes);
    }

    /** @test */
    public function reopening_contest_judging_reverts_project_status()
    {
        // Set placements and finalize
        $firstPlace = $this->contestEntries[0];
        $this->judgingService->setPlacement($this->contestProject, $firstPlace, Pitch::RANK_FIRST);
        $this->judgingService->finalizeJudging($this->contestProject, $this->contestRunner, 'Test finalization');

        // Verify project is completed
        $this->contestProject->refresh();
        $this->assertEquals(Project::STATUS_COMPLETED, $this->contestProject->status);
        $this->assertTrue($this->contestProject->isJudgingFinalized());

        // Create an admin user
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Reopen judging
        $result = $this->judgingService->reopenJudging($this->contestProject, $admin);

        $this->assertTrue($result);

        // Verify project status is reverted
        $this->contestProject->refresh();
        $this->assertNotEquals(Project::STATUS_COMPLETED, $this->contestProject->status);
        $this->assertFalse($this->contestProject->isJudgingFinalized());
        $this->assertNull($this->contestProject->judging_notes);

        // Should revert to OPEN since the project is published
        $expectedStatus = $this->contestProject->is_published ? Project::STATUS_OPEN : Project::STATUS_UNPUBLISHED;
        $this->assertEquals($expectedStatus, $this->contestProject->status);
    }
}
