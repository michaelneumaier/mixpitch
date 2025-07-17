<?php

namespace App\Jobs;

use App\Services\RefundRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExpiredRefundRequests implements ShouldQueue
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
        // No parameters needed - processes all expired requests
    }

    /**
     * Execute the job.
     */
    public function handle(RefundRequestService $refundService): void
    {
        Log::info('Starting expired refund request processing job');

        try {
            $results = $refundService->processExpiredRequests();

            Log::info('Expired refund request processing completed', [
                'expired' => $results['expired'],
                'escalated' => $results['escalated'],
            ]);

            // Send admin notification if requests were escalated to mediation
            if ($results['escalated'] > 0) {
                Log::info('Refund requests escalated to mediation', [
                    'escalated_count' => $results['escalated'],
                ]);

                // TODO: Send notification to admin team about mediation required
                // This is normal business process, not an error
            }

        } catch (\Exception $e) {
            Log::error('Expired refund request processing job failed', [
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
        Log::error('ProcessExpiredRefundRequests job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send alert to admin team
        // Failed refund processing could leave customers in limbo
    }
}
