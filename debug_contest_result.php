<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\ContestResult;
use App\Services\ContestJudgingService;

// Create test data exactly like the test
$contestRunner = User::factory()->create();
$project = Project::factory()->create([
    'user_id' => $contestRunner->id,
    'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
    'submission_deadline' => now()->subDays(1),
    'is_published' => true
]);

$contestEntries = [];
for ($i = 0; $i < 5; $i++) {
    $contestant = User::factory()->create();
    $pitch = Pitch::factory()->create([
        'project_id' => $project->id,
        'user_id' => $contestant->id,
        'status' => Pitch::STATUS_CONTEST_ENTRY,
        'rank' => null
    ]);
    $contestEntries[] = $pitch;
}

$judgingService = app(ContestJudgingService::class);

// Set placements exactly like the test
// Phase 3: Set first place and runner-ups
$judgingService->setPlacement($project, $contestEntries[0], Pitch::RANK_FIRST);
$judgingService->setPlacement($project, $contestEntries[3], Pitch::RANK_RUNNER_UP);
$judgingService->setPlacement($project, $contestEntries[4], Pitch::RANK_RUNNER_UP);

// Phase 9: Set second and third place
$judgingService->setPlacement($project, $contestEntries[1], Pitch::RANK_SECOND);
$judgingService->setPlacement($project, $contestEntries[2], Pitch::RANK_THIRD);

// Check contest result before finalization
$contestResult = $project->contestResult;
echo "Before finalization:\n";
echo "First place: " . $contestResult->first_place_pitch_id . " (expected: " . $contestEntries[0]->id . ")\n";
echo "Second place: " . $contestResult->second_place_pitch_id . " (expected: " . $contestEntries[1]->id . ")\n";
echo "Third place: " . $contestResult->third_place_pitch_id . " (expected: " . $contestEntries[2]->id . ")\n";
echo "Runner-ups: " . json_encode($contestResult->runner_up_pitch_ids) . "\n";

// Check hasPlacement for each pitch
foreach ($contestEntries as $index => $pitch) {
    $placement = $contestResult->hasPlacement($pitch->id);
    echo "Pitch {$index} (ID: {$pitch->id}) placement: " . ($placement ?? 'none') . "\n";
}

// Check getContestEntries
$allEntries = $project->getContestEntries();
echo "\ngetContestEntries() returns " . $allEntries->count() . " entries\n";

// Finalize judging
$result = $judgingService->finalizeJudging($project, $contestRunner, "Test finalization");
echo "\nFinalization result: " . ($result ? 'success' : 'failed') . "\n";

// Check pitch statuses after finalization
echo "\nAfter finalization:\n";
foreach ($contestEntries as $index => $pitch) {
    $pitch->refresh();
    echo "Pitch {$index} status: {$pitch->status}\n";
} 