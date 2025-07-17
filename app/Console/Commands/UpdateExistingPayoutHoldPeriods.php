<?php

namespace App\Console\Commands;

use App\Models\PayoutSchedule;
use App\Services\PayoutHoldService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExistingPayoutHoldPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:update-hold-periods 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if hold period has passed}
                            {--workflow= : Only update specific workflow type (standard, contest, client_management)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing payout schedules to use new dynamic hold period calculations';

    protected PayoutHoldService $holdService;

    public function __construct(PayoutHoldService $holdService)
    {
        parent::__construct();
        $this->holdService = $holdService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting payout hold period migration...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $workflowFilter = $this->option('workflow');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get payouts that need updating
        $query = PayoutSchedule::where('status', PayoutSchedule::STATUS_SCHEDULED);

        if (! $force) {
            // Only update payouts that haven't been released yet
            $query->where('hold_release_date', '>', now());
        }

        if ($workflowFilter) {
            $query->where('workflow_type', $workflowFilter);
        }

        $payouts = $query->with(['project', 'pitch'])->get();

        if ($payouts->isEmpty()) {
            $this->info('✅ No payouts found that need updating.');

            return 0;
        }

        $this->info("📋 Found {$payouts->count()} payouts to process");

        // Show summary of what will be updated
        $this->table(
            ['Workflow Type', 'Count', 'Current Avg Days', 'New Avg Days'],
            $this->getUpdateSummary($payouts)
        );

        if (! $dryRun && ! $this->confirm('Do you want to proceed with updating these payouts?')) {
            $this->info('❌ Migration cancelled by user');

            return 0;
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($payouts->count());
        $progressBar->start();

        foreach ($payouts as $payout) {
            try {
                $result = $this->updatePayoutHoldPeriod($payout, $dryRun, $force);

                if ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to update payout hold period', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);

                if (! $dryRun) {
                    $this->newLine();
                    $this->error("Error updating payout {$payout->id}: ".$e->getMessage());
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info('📊 Migration Results:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $payouts->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('🔍 This was a dry run - no actual changes were made');
            $this->info('💡 Run without --dry-run to apply changes');
        } else {
            $this->info('✅ Migration completed successfully!');

            if ($updated > 0) {
                Log::info('Payout hold periods updated via migration', [
                    'updated_count' => $updated,
                    'skipped_count' => $skipped,
                    'error_count' => $errors,
                    'total_processed' => $payouts->count(),
                ]);
            }
        }

        return 0;
    }

    /**
     * Update a single payout's hold period
     */
    protected function updatePayoutHoldPeriod(PayoutSchedule $payout, bool $dryRun, bool $force): array
    {
        $workflowType = $payout->workflow_type ?? $this->inferWorkflowType($payout);
        $currentReleaseDate = $payout->hold_release_date;
        $newReleaseDate = $this->holdService->calculateHoldReleaseDate($workflowType);

        // Check if update is needed
        if ($currentReleaseDate->equalTo($newReleaseDate)) {
            return ['updated' => false, 'reason' => 'No change needed'];
        }

        // Check if we should skip due to timing
        if (! $force && $currentReleaseDate <= now()) {
            return ['updated' => false, 'reason' => 'Hold period already passed'];
        }

        if ($dryRun) {
            return [
                'updated' => true,
                'reason' => 'Would update',
                'old_date' => $currentReleaseDate,
                'new_date' => $newReleaseDate,
                'workflow' => $workflowType,
            ];
        }

        // Perform the update
        DB::transaction(function () use ($payout, $newReleaseDate, $workflowType, $currentReleaseDate) {
            $payout->update([
                'hold_release_date' => $newReleaseDate,
                'workflow_type' => $workflowType, // Ensure workflow type is set
                'metadata' => array_merge($payout->metadata ?? [], [
                    'hold_period_migrated' => true,
                    'migration_date' => now()->toISOString(),
                    'original_release_date' => $currentReleaseDate->toISOString(),
                    'new_release_date' => $newReleaseDate->toISOString(),
                    'detected_workflow' => $workflowType,
                ]),
            ]);
        });

        return [
            'updated' => true,
            'reason' => 'Updated successfully',
            'old_date' => $currentReleaseDate,
            'new_date' => $newReleaseDate,
            'workflow' => $workflowType,
        ];
    }

    /**
     * Infer workflow type from payout data
     */
    protected function inferWorkflowType(PayoutSchedule $payout): string
    {
        // Check if it's a contest payout
        if ($payout->contest_prize_id) {
            return 'contest';
        }

        // Check project type if available
        if ($payout->project) {
            if ($payout->project->type === 'contest') {
                return 'contest';
            }
            if ($payout->project->type === 'client_management') {
                return 'client_management';
            }
        }

        // Check metadata for workflow hints
        if (isset($payout->metadata['type']) && $payout->metadata['type'] === 'client_management_completion') {
            return 'client_management';
        }

        // Default to standard
        return 'standard';
    }

    /**
     * Get summary of what will be updated
     */
    protected function getUpdateSummary($payouts): array
    {
        $summary = [];
        $grouped = $payouts->groupBy(function ($payout) {
            return $payout->workflow_type ?? $this->inferWorkflowType($payout);
        });

        foreach ($grouped as $workflowType => $workflowPayouts) {
            $currentAvgDays = $workflowPayouts->avg(function ($payout) {
                return $payout->hold_release_date->diffInDays(now());
            });

            $newReleaseDate = $this->holdService->calculateHoldReleaseDate($workflowType);
            $newAvgDays = $newReleaseDate->diffInDays(now());

            $summary[] = [
                $workflowType,
                $workflowPayouts->count(),
                round($currentAvgDays, 1),
                round($newAvgDays, 1),
            ];
        }

        return $summary;
    }
}
