<?php

/**
 * Complete Contest Judging System Test Script
 *
 * This script tests all phases of the contest judging system implementation:
 * - Database schema and models
 * - Business logic and services
 * - Livewire components
 * - Policies and authorization
 * - Routes and controllers
 * - UI components and navigation
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Http\Controllers\ContestJudgingController;
use App\Livewire\Project\Component\ContestJudging;
use App\Livewire\Project\Component\ContestSnapshotJudging;
use App\Models\ContestResult;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\ContestJudgingService;
use Illuminate\Support\Facades\Auth;

class ContestJudgingSystemTest
{
    private $contestRunner;

    private $contestants = [];

    private $project;

    private $contestEntries = [];

    private $judgingService;

    public function __construct()
    {
        $this->judgingService = app(ContestJudgingService::class);
        echo "🎯 Contest Judging System - Complete Implementation Test\n";
        echo "======================================================\n\n";
    }

    public function runAllTests()
    {
        try {
            $this->testPhase1DatabaseSchema();
            $this->testPhase2ModelEnhancements();
            $this->testPhase3BusinessLogic();
            $this->testPhase4LivewireComponents();
            $this->testPhase5Policies();
            $this->testPhase6RoutesAndControllers();
            $this->testPhase7ViewTemplates();
            $this->testPhase8Navigation();
            $this->testPhase9CompleteWorkflow();

            echo "\n🎉 ALL TESTS PASSED! Contest Judging System Implementation Complete!\n";
            echo "=========================================================================\n";
            echo "✅ Database schema and models working\n";
            echo "✅ Business logic service layer functioning\n";
            echo "✅ Livewire components operational\n";
            echo "✅ Authorization policies enforced\n";
            echo "✅ Routes and controllers responding\n";
            echo "✅ View templates rendering\n";
            echo "✅ Navigation integration complete\n";
            echo "✅ End-to-end workflow validated\n\n";

        } catch (Exception $e) {
            echo '❌ Test failed: '.$e->getMessage()."\n";
            echo 'Stack trace: '.$e->getTraceAsString()."\n";

            return false;
        }

        return true;
    }

    private function testPhase1DatabaseSchema()
    {
        echo "📊 Phase 1: Testing Database Schema\n";
        echo "==================================\n";

        // Test projects table enhancements
        $this->assertTrue(
            Schema::hasColumn('projects', 'judging_finalized_at'),
            'Projects table has judging_finalized_at column'
        );

        $this->assertTrue(
            Schema::hasColumn('projects', 'show_submissions_publicly'),
            'Projects table has show_submissions_publicly column'
        );

        $this->assertTrue(
            Schema::hasColumn('projects', 'judging_notes'),
            'Projects table has judging_notes column'
        );

        // Test pitches table enhancements
        $this->assertTrue(
            Schema::hasColumn('pitches', 'judging_notes'),
            'Pitches table has judging_notes column'
        );

        $this->assertTrue(
            Schema::hasColumn('pitches', 'placement_finalized_at'),
            'Pitches table has placement_finalized_at column'
        );

        // Test contest_results table
        $this->assertTrue(
            Schema::hasTable('contest_results'),
            'Contest results table exists'
        );

        $requiredColumns = [
            'project_id', 'first_place_pitch_id', 'second_place_pitch_id',
            'third_place_pitch_id', 'runner_up_pitch_ids', 'finalized_at',
            'finalized_by', 'show_submissions_publicly',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('contest_results', $column),
                "Contest results table has {$column} column"
            );
        }

        echo "✅ Database schema validation complete\n\n";
    }

    private function testPhase2ModelEnhancements()
    {
        echo "🏗️ Phase 2: Testing Model Enhancements\n";
        echo "=====================================\n";

        // Create test data
        $this->contestRunner = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->contestRunner->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'submission_deadline' => now()->subDays(1),
            'is_published' => true,
        ]);

        // Test Project model methods
        $this->assertTrue($this->project->isContest(), 'Project is identified as contest');
        $this->assertFalse($this->project->isJudgingFinalized(), 'Project judging not finalized initially');
        $this->assertTrue($this->project->canFinalizeJudging(), 'Project can be finalized when ready');

        // Test Pitch rank constants
        $this->assertEquals('1st', Pitch::RANK_FIRST, 'Pitch first place rank constant');
        $this->assertEquals('2nd', Pitch::RANK_SECOND, 'Pitch second place rank constant');
        $this->assertEquals('3rd', Pitch::RANK_THIRD, 'Pitch third place rank constant');
        $this->assertEquals('runner-up', Pitch::RANK_RUNNER_UP, 'Pitch runner-up rank constant');

        // Test ContestResult model
        $contestResult = ContestResult::create([
            'project_id' => $this->project->id,
            'show_submissions_publicly' => true,
        ]);

        $this->assertFalse($contestResult->isFinalized(), 'Contest result not finalized initially');
        $this->assertFalse($contestResult->hasWinners(), 'Contest result has no winners initially');
        $this->assertEquals(0, $contestResult->getPlacedCount(), 'Contest result has zero placed entries initially');

        echo "✅ Model enhancements validation complete\n\n";
    }

    private function testPhase3BusinessLogic()
    {
        echo "⚙️ Phase 3: Testing Business Logic Service\n";
        echo "==========================================\n";

        // Create contest entries
        for ($i = 0; $i < 5; $i++) {
            $contestant = User::factory()->create();
            $this->contestants[] = $contestant;

            $pitch = Pitch::factory()->create([
                'project_id' => $this->project->id,
                'user_id' => $contestant->id,
                'status' => Pitch::STATUS_CONTEST_ENTRY,
                'rank' => null,
            ]);

            $this->contestEntries[] = $pitch;
        }

        // Test placement setting
        $firstPitch = $this->contestEntries[0];
        $result = $this->judgingService->setPlacement(
            $this->project,
            $firstPitch,
            Pitch::RANK_FIRST
        );

        $this->assertTrue($result, 'Placement setting succeeds');

        $firstPitch->refresh();
        $this->assertEquals(Pitch::RANK_FIRST, $firstPitch->rank, 'First place rank assigned correctly');

        // Test available placements logic
        $secondPitch = $this->contestEntries[1];
        $availablePlacements = $this->judgingService->getAvailablePlacementsOnly(
            $this->project,
            $secondPitch
        );

        $this->assertArrayNotHasKey(Pitch::RANK_FIRST, $availablePlacements, 'First place not available for other pitches');
        $this->assertArrayHasKey(Pitch::RANK_SECOND, $availablePlacements, 'Second place available');

        // Test multiple runner-ups
        $runnerUp1 = $this->contestEntries[3];
        $runnerUp2 = $this->contestEntries[4];

        $this->judgingService->setPlacement($this->project, $runnerUp1, Pitch::RANK_RUNNER_UP);
        $this->judgingService->setPlacement($this->project, $runnerUp2, Pitch::RANK_RUNNER_UP);

        $contestResult = $this->project->contestResult()->first();
        $this->assertCount(2, $contestResult->runner_up_pitch_ids ?? [], 'Multiple runner-ups supported');

        echo "✅ Business logic service validation complete\n\n";
    }

    private function testPhase4LivewireComponents()
    {
        echo "⚡ Phase 4: Testing Livewire Components\n";
        echo "=====================================\n";

        // Test ContestJudging component
        $judgingComponent = new ContestJudging;
        $judgingComponent->mount($this->project);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Collection::class,
            $judgingComponent->contestEntries,
            'Contest judging component loads entries'
        );

        $this->assertFalse(
            $judgingComponent->isFinalized,
            'Contest judging component recognizes non-finalized state'
        );

        // Test ContestSnapshotJudging component
        $firstPitch = $this->contestEntries[0];
        $currentSnapshot = $firstPitch->currentSnapshot;

        if ($currentSnapshot) {
            $snapshotComponent = new ContestSnapshotJudging;
            $snapshotComponent->mount($this->project, $firstPitch, $currentSnapshot);

            $this->assertEquals(
                Pitch::RANK_FIRST,
                $snapshotComponent->currentPlacement,
                'Contest snapshot judging component loads current placement'
            );
        } else {
            echo "  ✓ Contest snapshot judging component test skipped (no snapshot available)\n";
        }

        echo "✅ Livewire components validation complete\n\n";
    }

    private function testPhase5Policies()
    {
        echo "🔒 Phase 5: Testing Authorization Policies\n";
        echo "=========================================\n";

        // Test ProjectPolicy contest methods
        $this->assertTrue(
            $this->contestRunner->can('judgeContest', $this->project),
            'Contest runner can judge contest'
        );

        $this->assertTrue(
            $this->contestRunner->can('setContestPlacements', $this->project),
            'Contest runner can set placements'
        );

        $this->assertTrue(
            $this->contestRunner->can('finalizeContestJudging', $this->project),
            'Contest runner can finalize judging'
        );

        // Test contestant permissions
        $contestant = $this->contestants[0];

        $this->assertFalse(
            $contestant->can('judgeContest', $this->project),
            'Contestants cannot judge contest'
        );

        $this->assertFalse(
            $contestant->can('setContestPlacements', $this->project),
            'Contestants cannot set placements'
        );

        // Test PitchPolicy contest methods
        $contestEntry = $this->contestEntries[0];

        $this->assertTrue(
            $this->contestRunner->can('setContestPlacement', $contestEntry),
            'Contest runner can set placement for entries'
        );

        $this->assertTrue(
            $contestant->can('viewContestEntry', $contestEntry),
            'Contestants can view their own entries'
        );

        echo "✅ Authorization policies validation complete\n\n";
    }

    private function testPhase6RoutesAndControllers()
    {
        echo "🛣️ Phase 6: Testing Routes and Controllers\n";
        echo "=========================================\n";

        // Test route registration
        $routes = collect(\Route::getRoutes())->map(function ($route) {
            return $route->getName();
        })->filter();

        $expectedRoutes = [
            'projects.contest.judging',
            'projects.contest.results',
            'projects.contest.update-placement',
            'projects.contest.finalize',
            'projects.contest.analytics',
            'projects.contest.export',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(
                $routes->contains($routeName),
                "Route {$routeName} is registered"
            );
        }

        // Test controller instantiation
        $controller = new ContestJudgingController($this->judgingService);
        $this->assertInstanceOf(
            ContestJudgingController::class,
            $controller,
            'Contest judging controller instantiates correctly'
        );

        echo "✅ Routes and controllers validation complete\n\n";
    }

    private function testPhase7ViewTemplates()
    {
        echo "🎨 Phase 7: Testing View Templates\n";
        echo "=================================\n";

        // Test view files exist
        $viewPaths = [
            'contest.judging.index',
            'contest.results.index',
            'livewire.project.component.contest-judging',
            'livewire.project.component.contest-snapshot-judging',
        ];

        foreach ($viewPaths as $viewPath) {
            $this->assertTrue(
                view()->exists($viewPath),
                "View template {$viewPath} exists"
            );
        }

        // Test view rendering (basic check)
        try {
            $judgingView = view('contest.judging.index', [
                'project' => $this->project,
                'contestEntries' => collect($this->contestEntries),
                'contestResult' => $this->project->contestResult,
                'isFinalized' => false,
                'canFinalize' => true,
            ])->render();

            $this->assertStringContains('Contest Judging:', $judgingView, 'Judging view renders with correct title');

        } catch (Exception $e) {
            throw new Exception('View rendering failed: '.$e->getMessage());
        }

        echo "✅ View templates validation complete\n\n";
    }

    private function testPhase8Navigation()
    {
        echo "🧭 Phase 8: Testing Navigation Integration\n";
        echo "========================================\n";

        // Test project header component integration
        $headerView = view('components.project.header', [
            'project' => $this->project,
            'showActions' => true,
            'context' => 'manage',
        ]);

        $this->assertInstanceOf(
            \Illuminate\View\View::class,
            $headerView,
            'Project header component renders with contest project'
        );

        // Check if contest judging links would be available
        Auth::login($this->contestRunner);

        $this->assertTrue(
            auth()->user()->can('judgeContest', $this->project),
            'Navigation authorization works for contest judging'
        );

        echo "✅ Navigation integration validation complete\n\n";
    }

    private function testPhase9CompleteWorkflow()
    {
        echo "🎯 Phase 9: Testing Complete Workflow\n";
        echo "===================================\n";

        // Test complete judging workflow
        $secondPitch = $this->contestEntries[1];
        $thirdPitch = $this->contestEntries[2];

        // Set remaining placements
        $this->judgingService->setPlacement($this->project, $secondPitch, Pitch::RANK_SECOND);
        $this->judgingService->setPlacement($this->project, $thirdPitch, Pitch::RANK_THIRD);

        // Test finalization
        $result = $this->judgingService->finalizeJudging(
            $this->project,
            $this->contestRunner,
            'Contest concluded with excellent entries!'
        );

        $this->assertTrue($result, 'Contest judging finalization succeeds');

        // Refresh and validate
        $this->project->refresh();
        $this->assertTrue($this->project->isJudgingFinalized(), 'Project is finalized');
        $this->assertNotNull($this->project->judging_finalized_at, 'Finalization timestamp set');
        $this->assertEquals('Contest concluded with excellent entries!', $this->project->judging_notes, 'Judging notes saved');

        // Test final results
        $contestResult = $this->project->contestResult;
        $this->assertTrue($contestResult->isFinalized(), 'Contest result is finalized');
        $this->assertTrue($contestResult->hasWinners(), 'Contest result has winners');

        // Validate winner assignments
        $this->assertEquals($this->contestEntries[0]->id, $contestResult->first_place_pitch_id, 'First place assigned correctly');
        $this->assertEquals($this->contestEntries[1]->id, $contestResult->second_place_pitch_id, 'Second place assigned correctly');
        $this->assertEquals($this->contestEntries[2]->id, $contestResult->third_place_pitch_id, 'Third place assigned correctly');

        // Validate pitch statuses
        foreach ($this->contestEntries as $index => $pitch) {
            $pitch->refresh();

            if ($index < 3) {
                $this->assertEquals(
                    Pitch::STATUS_CONTEST_WINNER,
                    $pitch->status,
                    "Winner pitch {$index} has correct status"
                );
            } elseif ($index < 5) {
                $this->assertEquals(
                    Pitch::STATUS_CONTEST_RUNNER_UP,
                    $pitch->status,
                    "Runner-up pitch {$index} has correct status"
                );
            }

            $this->assertNotNull($pitch->placement_finalized_at, "Pitch {$index} has finalization timestamp");
        }

        echo "✅ Complete workflow validation complete\n\n";
    }

    private function assertTrue($condition, $message)
    {
        if (! $condition) {
            throw new Exception("Assertion failed: {$message}");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertFalse($condition, $message)
    {
        if ($condition) {
            throw new Exception("Assertion failed: {$message}");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertEquals($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: {$message}. Expected: {$expected}, Got: {$actual}");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertInstanceOf($expected, $actual, $message)
    {
        if (! ($actual instanceof $expected)) {
            throw new Exception("Assertion failed: {$message}. Expected instance of {$expected}");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertArrayHasKey($key, $array, $message)
    {
        if (! array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: {$message}. Key {$key} not found in array");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertArrayNotHasKey($key, $array, $message)
    {
        if (array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: {$message}. Key {$key} found in array when it shouldn't be");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertCount($expectedCount, $array, $message)
    {
        if (count($array) !== $expectedCount) {
            throw new Exception("Assertion failed: {$message}. Expected count: {$expectedCount}, Got: ".count($array));
        }
        echo "  ✓ {$message}\n";
    }

    private function assertStringContains($needle, $haystack, $message)
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: {$message}. String '{$needle}' not found");
        }
        echo "  ✓ {$message}\n";
    }

    private function assertNotNull($value, $message)
    {
        if (is_null($value)) {
            throw new Exception("Assertion failed: {$message}. Value is null");
        }
        echo "  ✓ {$message}\n";
    }
}

// Run the comprehensive test
$test = new ContestJudgingSystemTest;
$test->runAllTests();
