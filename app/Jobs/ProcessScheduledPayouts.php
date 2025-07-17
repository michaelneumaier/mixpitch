<?php

namespace App\Jobs;

use App\Services\PayoutProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPayouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // No parameters needed - processes all ready payouts
    }

    /**
     * Execute the job.
     */
    public function handle(PayoutProcessingService $payoutService): void
    {
        Log::info('Starting scheduled payout processing job');

        try {
            $results = $payoutService->processScheduledPayouts();

            Log::info('Scheduled payout processing completed', [
                'processed' => $results['processed'],
                'failed' => $results['failed'],
                'error_count' => count($results['errors']),
            ]);

            // Log individual errors if any
            if (! empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    Log::error('Payout processing error', $error);
                }
            }

            // Send admin notification if there were failures
            if ($results['failed'] > 0) {
                // TODO: Implement admin notification for failed payouts
                Log::warning('Some payouts failed processing', [
                    'failed_count' => $results['failed'],
                    'errors' => $results['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Scheduled payout processing job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessScheduledPayouts job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send critical alert to admin team
        // This indicates a system-level issue that needs immediate attention
    }
}
