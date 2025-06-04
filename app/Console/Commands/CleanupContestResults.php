<?php

namespace App\Console\Commands;

use App\Models\ContestResult;
use App\Models\Pitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupContestResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contest:cleanup-results 
                            {--dry-run : Show what would be cleaned without making changes}
                            {--force : Skip confirmation prompts}
                            {--project= : Clean only specific project ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned pitch references in contest results';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $projectId = $this->option('project');
        
        $this->info('Contest Results Cleanup Tool');
        $this->info('============================');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Get contest results to check
        $query = ContestResult::with('project');
        
        if ($projectId) {
            $query->where('project_id', $projectId);
            $this->info("Filtering to project ID: {$projectId}");
        }
        
        $contestResults = $query->get();
        
        if ($contestResults->isEmpty()) {
            $this->info('No contest results found.');
            return 0;
        }
        
        $this->info("Found {$contestResults->count()} contest result(s) to check.");
        $this->newLine();
        
        $totalCleaned = 0;
        $summary = [
            'first_place' => 0,
            'second_place' => 0,
            'third_place' => 0,
            'runner_ups' => 0,
            'contest_results_affected' => 0
        ];
        
        foreach ($contestResults as $contestResult) {
            $projectTitle = $contestResult->project ? $contestResult->project->title : 'N/A';
            $this->info("Checking Contest Result ID: {$contestResult->id} (Project: {$projectTitle})");
            
            // Check for orphaned references
            $orphaned = $contestResult->hasOrphanedPitches();
            
            if (empty($orphaned)) {
                $this->line('  ✓ No orphaned references found');
                continue;
            }
            
            $this->warn("  Found orphaned references:");
            
            // Display what will be cleaned
            if (isset($orphaned['first_place'])) {
                $this->line("    - First place: Pitch ID {$orphaned['first_place']} (deleted)");
                $summary['first_place']++;
            }
            
            if (isset($orphaned['second_place'])) {
                $this->line("    - Second place: Pitch ID {$orphaned['second_place']} (deleted)");
                $summary['second_place']++;
            }
            
            if (isset($orphaned['third_place'])) {
                $this->line("    - Third place: Pitch ID {$orphaned['third_place']} (deleted)");
                $summary['third_place']++;
            }
            
            if (isset($orphaned['runner_ups'])) {
                $runnerUpCount = count($orphaned['runner_ups']);
                $this->line("    - Runner-ups: {$runnerUpCount} deleted pitch(es) - IDs: " . implode(', ', $orphaned['runner_ups']));
                $summary['runner_ups'] += $runnerUpCount;
            }
            
            // Show contest state
            $contestSummary = $contestResult->getContestSummary();
            $this->line("    - Contest state: " . ($contestSummary['is_finalized'] ? 'FINALIZED' : 'Not finalized'));
            $this->line("    - Total entries: {$contestSummary['total_entries']}, Placed: {$contestSummary['placed_entries']}");
            
            if (!$dryRun) {
                // Ask for confirmation unless force is used
                if (!$force && !$this->confirm("Clean up orphaned references for this contest result?", true)) {
                    $this->line("  Skipped");
                    continue;
                }
                
                // Perform cleanup
                try {
                    DB::beginTransaction();
                    
                    $cleaned = $contestResult->cleanupOrphanedPitches();
                    $contestResult->save();
                    
                    DB::commit();
                    
                    $this->info("  ✓ Cleaned up orphaned references");
                    $summary['contest_results_affected']++;
                    $totalCleaned++;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  ✗ Failed to clean up: " . $e->getMessage());
                }
            }
            
            $this->newLine();
        }
        
        // Display summary
        $this->info('Cleanup Summary');
        $this->info('===============');
        
        if ($dryRun) {
            $this->warn('DRY RUN - The following would be cleaned:');
        } else {
            $this->info('Successfully cleaned:');
        }
        
        $this->table(
            ['Type', 'Count'],
            [
                ['First Place References', $summary['first_place']],
                ['Second Place References', $summary['second_place']],
                ['Third Place References', $summary['third_place']],
                ['Runner-up References', $summary['runner_ups']],
                ['Contest Results Affected', $summary['contest_results_affected']],
            ]
        );
        
        if ($dryRun) {
            $this->info('Run without --dry-run to perform the cleanup.');
        } else {
            $this->info("Cleanup completed successfully!");
        }
        
        return 0;
    }
    
    /**
     * Display detailed information about a contest result
     */
    private function displayContestDetails(ContestResult $contestResult): void
    {
        $summary = $contestResult->getContestSummary();
        
        $this->table(
            ['Placement', 'Pitch ID', 'Status'],
            [
                ['First Place', $summary['placements']['first_place'] ?? 'None', $this->getPitchStatus($summary['placements']['first_place'] ?? null)],
                ['Second Place', $summary['placements']['second_place'] ?? 'None', $this->getPitchStatus($summary['placements']['second_place'] ?? null)],
                ['Third Place', $summary['placements']['third_place'] ?? 'None', $this->getPitchStatus($summary['placements']['third_place'] ?? null)],
                ['Runner-ups', implode(', ', $summary['placements']['runner_ups']), $this->getRunnerUpStatus($summary['placements']['runner_ups'])],
            ]
        );
    }
    
    /**
     * Get the status of a pitch ID
     */
    private function getPitchStatus(?int $pitchId): string
    {
        if (!$pitchId) {
            return 'N/A';
        }
        
        $pitch = Pitch::find($pitchId);
        return $pitch ? 'Valid' : 'DELETED';
    }
    
    /**
     * Get the status of runner-up pitch IDs
     */
    private function getRunnerUpStatus(array $pitchIds): string
    {
        if (empty($pitchIds)) {
            return 'N/A';
        }
        
        $validCount = Pitch::whereIn('id', $pitchIds)->count();
        $totalCount = count($pitchIds);
        
        if ($validCount === $totalCount) {
            return 'All Valid';
        } elseif ($validCount === 0) {
            return 'All DELETED';
        } else {
            return "{$validCount}/{$totalCount} Valid";
        }
    }
} 