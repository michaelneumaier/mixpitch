<?php

namespace App\Services;

use App\Models\ContestPrize;
use App\Models\PayoutSchedule;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutProcessingService
{
    protected NotificationService $notificationService;

    protected StripeConnectService $stripeConnectService;

    public function __construct(NotificationService $notificationService, StripeConnectService $stripeConnectService)
    {
        $this->notificationService = $notificationService;
        $this->stripeConnectService = $stripeConnectService;
    }

    /**
     * Schedule payout for a completed pitch payment
     * Called from PitchWorkflowService::markPitchAsPaid() and webhook handlers
     */
    public function schedulePayoutForPitch(Pitch $pitch, string $stripeInvoiceId): PayoutSchedule
    {
        Log::info('Scheduling payout for pitch', [
            'pitch_id' => $pitch->id,
            'project_id' => $pitch->project_id,
            'stripe_invoice_id' => $stripeInvoiceId,
            'workflow_type' => $pitch->project->workflow_type,
        ]);

        return DB::transaction(function () use ($pitch, $stripeInvoiceId) {
            // Determine workflow type and producer
            $project = $pitch->project;
            $producer = $pitch->user; // Pitch creator is the producer
            $workflowType = $project->workflow_type;

            // Calculate payout details
            $payoutAmount = $pitch->payment_amount ?? $project->budget;
            $commissionRate = $producer->getPlatformCommissionRate();
            $commissionAmount = $payoutAmount * ($commissionRate / 100);
            $netAmount = $payoutAmount - $commissionAmount;

            // Calculate hold release date using dynamic configuration
            $holdReleaseDate = $this->calculateHoldReleaseDate($workflowType);

            // Create transaction record
            $transaction = Transaction::createForPitch(
                $producer, // Producer receives the payout
                $project,
                $pitch,
                $payoutAmount,
                [
                    'type' => Transaction::TYPE_PAYMENT,
                    'status' => Transaction::STATUS_PENDING,
                    'external_transaction_id' => $stripeInvoiceId,
                    'workflow_type' => $workflowType,
                    'description' => "Payout for pitch: {$pitch->title}",
                    'metadata' => [
                        'stripe_invoice_id' => $stripeInvoiceId,
                        'hold_release_date' => $holdReleaseDate->toISOString(),
                        'workflow_type' => $workflowType,
                    ],
                ]
            );

            // Create payout schedule
            $payoutSchedule = PayoutSchedule::create([
                'producer_user_id' => $producer->id,
                'producer_stripe_account_id' => $producer->stripe_account_id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'transaction_id' => $transaction->id,
                'workflow_type' => $workflowType,
                'gross_amount' => $payoutAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'currency' => $project->prize_currency ?? 'USD',
                'status' => PayoutSchedule::STATUS_SCHEDULED,
                'hold_release_date' => $holdReleaseDate,
                'stripe_payment_intent_id' => $stripeInvoiceId,
                'metadata' => [
                    'pitch_title' => $pitch->title,
                    'project_name' => $project->name,
                    'client_email' => $project->client_email,
                    'original_budget' => $project->budget,
                ],
            ]);

            // Link transaction to payout schedule
            $transaction->update(['payout_schedule_id' => $payoutSchedule->id]);

            Log::info('Payout scheduled successfully', [
                'payout_schedule_id' => $payoutSchedule->id,
                'transaction_id' => $transaction->id,
                'net_amount' => $netAmount,
                'hold_release_date' => $holdReleaseDate->toISOString(),
            ]);

            // Send notification to producer
            $this->notificationService->notifyPayoutScheduled($producer, $payoutSchedule);

            return $payoutSchedule;
        });
    }

    /**
     * Schedule payouts for contest winners
     * Called when contest prizes are awarded
     *
     * @param  array  $winners  Array of ['pitch' => Pitch, 'prize' => ContestPrize]
     * @return array Array of PayoutSchedule objects
     */
    public function schedulePayoutsForContest(Project $project, array $winners, string $stripeInvoiceId): array
    {
        Log::info('Scheduling contest payouts', [
            'project_id' => $project->id,
            'winner_count' => count($winners),
            'stripe_invoice_id' => $stripeInvoiceId,
        ]);

        return DB::transaction(function () use ($project, $winners, $stripeInvoiceId) {
            $payoutSchedules = [];
            $holdReleaseDate = $this->calculateHoldReleaseDate('contest');

            foreach ($winners as $winner) {
                $pitch = $winner['pitch'];
                $prize = $winner['prize'];
                $producer = $pitch->user;

                // Skip non-cash prizes
                if ($prize->prize_type !== 'cash' || $prize->cash_amount <= 0) {
                    continue;
                }

                // Calculate payout details based on producer's subscription
                $prizeAmount = $prize->cash_amount;
                $commissionRate = $producer->getPlatformCommissionRate();
                $commissionAmount = $prizeAmount * ($commissionRate / 100);
                $netAmount = $prizeAmount - $commissionAmount;

                // Create transaction record
                $transaction = Transaction::createForPitch(
                    $producer,
                    $project,
                    $pitch,
                    $prizeAmount,
                    [
                        'type' => Transaction::TYPE_PAYMENT,
                        'status' => Transaction::STATUS_PENDING,
                        'external_transaction_id' => $stripeInvoiceId,
                        'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
                        'description' => "Contest prize payout: {$prize->placement} place - {$pitch->title}",
                        'metadata' => [
                            'contest_prize_id' => $prize->id,
                            'prize_position' => $prize->placement,
                            'stripe_invoice_id' => $stripeInvoiceId,
                            'hold_release_date' => $holdReleaseDate->toISOString(),
                        ],
                    ]
                );

                // Create payout schedule
                $payoutSchedule = PayoutSchedule::create([
                    'producer_user_id' => $producer->id,
                    'producer_stripe_account_id' => $producer->stripe_account_id,
                    'project_id' => $project->id,
                    'pitch_id' => $pitch->id,
                    'transaction_id' => $transaction->id,
                    'contest_prize_id' => $prize->id,
                    'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
                    'gross_amount' => $prizeAmount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'currency' => $project->prize_currency ?? 'USD',
                    'status' => PayoutSchedule::STATUS_SCHEDULED,
                    'hold_release_date' => $holdReleaseDate,
                    'stripe_payment_intent_id' => $stripeInvoiceId,
                    'metadata' => [
                        'prize_position' => $prize->placement,
                        'prize_title' => $prize->title,
                        'pitch_title' => $pitch->title,
                        'project_name' => $project->name,
                    ],
                ]);

                // Link transaction to payout schedule
                $transaction->update(['payout_schedule_id' => $payoutSchedule->id]);

                $payoutSchedules[] = $payoutSchedule;

                // Send notification to producer
                $this->notificationService->notifyContestPayoutScheduled($producer, $payoutSchedule, $prize);
            }

            Log::info('Contest payouts scheduled successfully', [
                'project_id' => $project->id,
                'payout_count' => count($payoutSchedules),
                'total_net_amount' => array_sum(array_column($payoutSchedules, 'net_amount')),
            ]);

            return $payoutSchedules;
        });
    }

    /**
     * Process scheduled payouts that are ready for release
     * Called by scheduled job
     *
     * @return array Processing results
     */
    public function processScheduledPayouts(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get payouts ready for processing
        $readyPayouts = PayoutSchedule::where('status', PayoutSchedule::STATUS_SCHEDULED)
            ->where('hold_release_date', '<=', now())
            ->with(['producer', 'project', 'pitch', 'transaction'])
            ->get();

        Log::info('Processing scheduled payouts', ['count' => $readyPayouts->count()]);

        foreach ($readyPayouts as $payout) {
            try {
                $this->processSinglePayout($payout);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to process payout', [
                    'payout_schedule_id' => $payout->id,
                    'producer_id' => $payout->producer_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Process a single payout via Stripe Connect
     */
    public function processSinglePayout(PayoutSchedule $payoutSchedule): void
    {
        Log::info('Processing single payout', [
            'payout_schedule_id' => $payoutSchedule->id,
            'producer_id' => $payoutSchedule->producer_user_id,
            'net_amount' => $payoutSchedule->net_amount,
        ]);

        DB::transaction(function () use ($payoutSchedule) {
            // Update status to processing
            $payoutSchedule->update([
                'status' => PayoutSchedule::STATUS_PROCESSING,
                'processed_at' => now(),
            ]);

            $producer = $payoutSchedule->producer;

            // Check if producer has a Stripe Connect account ready for payouts
            if (! $this->stripeConnectService->isAccountReadyForPayouts($producer)) {
                Log::warning('Producer account not ready for payouts', [
                    'payout_schedule_id' => $payoutSchedule->id,
                    'producer_id' => $producer->id,
                    'stripe_account_id' => $producer->stripe_account_id,
                ]);

                // Update status to failed with reason
                $payoutSchedule->update([
                    'status' => PayoutSchedule::STATUS_FAILED,
                    'failed_at' => now(),
                    'failure_reason' => 'Producer Stripe Connect account not ready for payouts',
                    'metadata' => array_merge($payoutSchedule->metadata ?? [], [
                        'failure_details' => 'Account setup incomplete or restricted',
                    ]),
                ]);

                // Send notification to producer about account setup needed
                $this->notificationService->notifyPayoutFailed($producer, $payoutSchedule, 'Account setup required');

                return;
            }

            // Process the Stripe transfer
            $transferResult = $this->stripeConnectService->processTransfer(
                $producer,
                $payoutSchedule->net_amount,
                [
                    'description' => $this->buildTransferDescription($payoutSchedule),
                    'payout_schedule_id' => $payoutSchedule->id,
                    'project_id' => $payoutSchedule->project_id,
                    'pitch_id' => $payoutSchedule->pitch_id,
                    'workflow_type' => $payoutSchedule->workflow_type,
                    'commission_rate' => $payoutSchedule->commission_rate,
                    'gross_amount' => $payoutSchedule->gross_amount,
                ]
            );

            if ($transferResult['success']) {
                // Update payout as completed
                $payoutSchedule->update([
                    'status' => PayoutSchedule::STATUS_COMPLETED,
                    'stripe_transfer_id' => $transferResult['transfer_id'],
                    'completed_at' => now(),
                    'metadata' => array_merge($payoutSchedule->metadata ?? [], [
                        'stripe_transfer_created' => now()->toISOString(),
                        'transfer_amount_cents' => round($payoutSchedule->net_amount * 100),
                    ]),
                ]);

                // Update transaction status if it exists
                if ($payoutSchedule->transaction) {
                    $payoutSchedule->transaction->markAsCompleted($transferResult['transfer_id']);
                }

                Log::info('Payout processed successfully via Stripe Connect', [
                    'payout_schedule_id' => $payoutSchedule->id,
                    'stripe_transfer_id' => $transferResult['transfer_id'],
                    'net_amount' => $payoutSchedule->net_amount,
                ]);

                // Send success notification to producer
                $this->notificationService->notifyPayoutCompleted($payoutSchedule->producer, $payoutSchedule);

            } else {
                // Handle transfer failure
                $payoutSchedule->update([
                    'status' => PayoutSchedule::STATUS_FAILED,
                    'failed_at' => now(),
                    'failure_reason' => $transferResult['error'],
                    'metadata' => array_merge($payoutSchedule->metadata ?? [], [
                        'stripe_error' => $transferResult['error'],
                        'failed_at' => now()->toISOString(),
                    ]),
                ]);

                // Update transaction status if it exists
                if ($payoutSchedule->transaction) {
                    $payoutSchedule->transaction->update(['status' => Transaction::STATUS_FAILED]);
                }

                Log::error('Stripe transfer failed', [
                    'payout_schedule_id' => $payoutSchedule->id,
                    'error' => $transferResult['error'],
                ]);

                // Send failure notification to producer
                $this->notificationService->notifyPayoutFailed($producer, $payoutSchedule, $transferResult['error']);

                throw new \Exception('Stripe transfer failed: '.$transferResult['error']);
            }
        });
    }

    /**
     * Build a descriptive transfer description for Stripe
     */
    private function buildTransferDescription(PayoutSchedule $payoutSchedule): string
    {
        $project = $payoutSchedule->project;
        $pitch = $payoutSchedule->pitch;

        if ($payoutSchedule->workflow_type === Project::WORKFLOW_TYPE_CONTEST && $payoutSchedule->contestPrize) {
            $prize = $payoutSchedule->contestPrize;

            return "Contest Prize: {$prize->placement} place - {$project->name}";
        }

        return "Project Payout: {$project->name} - {$pitch->title}";
    }

    /**
     * Cancel a scheduled payout (e.g., due to refund request)
     */
    public function cancelPayout(PayoutSchedule $payoutSchedule, string $reason): void
    {
        if (! in_array($payoutSchedule->status, [PayoutSchedule::STATUS_SCHEDULED, PayoutSchedule::STATUS_PROCESSING])) {
            throw new \InvalidArgumentException('Cannot cancel payout in current status: '.$payoutSchedule->status);
        }

        DB::transaction(function () use ($payoutSchedule, $reason) {
            $payoutSchedule->update([
                'status' => PayoutSchedule::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'metadata' => array_merge($payoutSchedule->metadata ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now()->toISOString(),
                ]),
            ]);

            // Update transaction status if it exists
            if ($payoutSchedule->transaction) {
                $payoutSchedule->transaction->update(['status' => Transaction::STATUS_CANCELLED]);
            }

            Log::info('Payout cancelled', [
                'payout_schedule_id' => $payoutSchedule->id,
                'reason' => $reason,
            ]);

            // Send notification to producer
            $this->notificationService->notifyPayoutCancelled($payoutSchedule->producer, $payoutSchedule, $reason);
        });
    }

    /**
     * Calculate hold release date using dynamic configuration
     */
    protected function calculateHoldReleaseDate(string $workflowType = 'standard'): Carbon
    {
        $holdService = app(\App\Services\PayoutHoldService::class);

        return $holdService->calculateHoldReleaseDate($workflowType);
    }

    /**
     * Get payout statistics for admin dashboard
     */
    public function getPayoutStatistics(array $filters = []): array
    {
        $query = PayoutSchedule::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['workflow_type'])) {
            $query->where('workflow_type', $filters['workflow_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $stats = [
            'total_payouts' => $query->count(),
            'total_gross_amount' => $query->sum('gross_amount'),
            'total_commission' => $query->sum('commission_amount'),
            'total_net_amount' => $query->sum('net_amount'),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count, sum(net_amount) as total_amount')
                ->get()
                ->keyBy('status'),
            'by_workflow' => $query->groupBy('workflow_type')
                ->selectRaw('workflow_type, count(*) as count, sum(net_amount) as total_amount')
                ->get()
                ->keyBy('workflow_type'),
            'pending_release' => PayoutSchedule::where('status', PayoutSchedule::STATUS_SCHEDULED)
                ->where('hold_release_date', '<=', now())
                ->count(),
        ];

        return $stats;
    }

    /**
     * Get producer payout history
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProducerPayoutHistory(User $producer, int $limit = 20)
    {
        return PayoutSchedule::where('producer_user_id', $producer->id)
            ->with(['project', 'pitch', 'transaction', 'contestPrize'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}
