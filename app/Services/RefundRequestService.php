<?php

namespace App\Services;

use App\Models\RefundRequest;
use App\Models\PayoutSchedule;
use App\Models\Pitch;
use App\Models\User;
use App\Models\Project;
use App\Services\StripeConnectService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RefundRequestService
{
    protected PayoutProcessingService $payoutService;
    protected NotificationService $notificationService;
    protected StripeConnectService $stripeConnectService;

    public function __construct(
        PayoutProcessingService $payoutService,
        NotificationService $notificationService,
        StripeConnectService $stripeConnectService
    ) {
        $this->payoutService = $payoutService;
        $this->notificationService = $notificationService;
        $this->stripeConnectService = $stripeConnectService;
    }

    /**
     * Create a refund request from a client
     * Only allowed within 3 days of payment completion
     *
     * @param Pitch $pitch
     * @param string $clientEmail
     * @param string $reason
     * @param array $additionalData
     * @return RefundRequest
     */
    public function createRefundRequest(
        Pitch $pitch, 
        string $clientEmail, 
        string $reason, 
        array $additionalData = []
    ): RefundRequest {
        // Validate timing - must be within 3 days of payment
        if (!$pitch->payment_completed_at) {
            throw new \InvalidArgumentException('Cannot request refund for unpaid pitch');
        }

        $daysSincePayment = $pitch->payment_completed_at->diffInDays(now());
        if ($daysSincePayment > 3) {
            throw new \InvalidArgumentException('Refund requests must be made within 3 days of payment completion');
        }

        // Check if refund request already exists
        $existingRequest = RefundRequest::where('pitch_id', $pitch->id)
            ->whereIn('status', [RefundRequest::STATUS_REQUESTED, RefundRequest::STATUS_APPROVED])
            ->first();

        if ($existingRequest) {
            throw new \InvalidArgumentException('A refund request already exists for this pitch');
        }

        Log::info('Creating refund request', [
            'pitch_id' => $pitch->id,
            'client_email' => $clientEmail,
            'days_since_payment' => $daysSincePayment
        ]);

        return DB::transaction(function () use ($pitch, $clientEmail, $reason, $additionalData) {
            // Find associated payout schedule
            $payoutSchedule = PayoutSchedule::where('pitch_id', $pitch->id)
                ->whereIn('status', [PayoutSchedule::STATUS_SCHEDULED, PayoutSchedule::STATUS_PROCESSING])
                ->first();

            // Calculate response deadline (2 days from now)
            $responseDeadline = now()->addDays(2);

            $refundRequest = RefundRequest::create([
                'pitch_id' => $pitch->id,
                'payout_schedule_id' => $payoutSchedule?->id,
                'client_email' => $clientEmail,
                'producer_user_id' => $pitch->user_id,
                'refund_amount' => $pitch->payment_amount ?? $pitch->project->budget,
                'currency' => $pitch->project->prize_currency ?? 'USD',
                'reason' => $reason,
                'status' => RefundRequest::STATUS_REQUESTED,
                'requested_at' => now(),
                'response_deadline' => $responseDeadline,
                'metadata' => array_merge([
                    'project_name' => $pitch->project->name,
                    'pitch_title' => $pitch->title,
                    'payment_completed_at' => $pitch->payment_completed_at->toISOString(),
                    'days_since_payment' => $pitch->payment_completed_at->diffInDays(now())
                ], $additionalData)
            ]);

            Log::info('Refund request created', [
                'refund_request_id' => $refundRequest->id,
                'response_deadline' => $responseDeadline->toISOString()
            ]);

            // Send notifications
            $this->notificationService->notifyProducerRefundRequest($pitch->user, $refundRequest);
            $this->notificationService->notifyClientRefundRequestReceived($clientEmail, $refundRequest);

            return $refundRequest;
        });
    }

    /**
     * Producer approves the refund request
     *
     * @param RefundRequest $refundRequest
     * @param User $producer
     * @param string|null $notes
     * @return RefundRequest
     */
    public function approveRefund(RefundRequest $refundRequest, User $producer, ?string $notes = null): RefundRequest
    {
        if ($refundRequest->status !== RefundRequest::STATUS_REQUESTED) {
            throw new \InvalidArgumentException('Refund request cannot be approved in current status: ' . $refundRequest->status);
        }

        if ($refundRequest->producer_user_id !== $producer->id) {
            throw new \InvalidArgumentException('Only the assigned producer can approve this refund request');
        }

        Log::info('Producer approving refund request', [
            'refund_request_id' => $refundRequest->id,
            'producer_id' => $producer->id
        ]);

        return DB::transaction(function () use ($refundRequest, $producer, $notes) {
            // Update refund request
            $refundRequest->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'approved_by' => $producer->id,
                'approved_at' => now(),
                'producer_notes' => $notes,
                'metadata' => array_merge($refundRequest->metadata ?? [], [
                    'approved_at' => now()->toISOString(),
                    'producer_notes' => $notes
                ])
            ]);

            // Handle payout cancellation or reversal
            $payoutSchedule = $refundRequest->payoutSchedule;
            $transferReversalResult = null;

            if ($payoutSchedule) {
                if (in_array($payoutSchedule->status, [PayoutSchedule::STATUS_SCHEDULED, PayoutSchedule::STATUS_PROCESSING])) {
                    // Cancel payout if not yet processed
                    $this->payoutService->cancelPayout(
                        $payoutSchedule, 
                        "Refund approved by producer"
                    );
                } elseif ($payoutSchedule->status === PayoutSchedule::STATUS_COMPLETED && $payoutSchedule->stripe_transfer_id) {
                    // Reverse the completed transfer
                    $transferReversalResult = $this->stripeConnectService->reverseTransfer(
                        $payoutSchedule->stripe_transfer_id,
                        $payoutSchedule->net_amount,
                        [
                            'refund_request_id' => $refundRequest->id,
                            'reason' => 'Producer approved refund request',
                            'original_payout_id' => $payoutSchedule->id,
                        ]
                    );

                    if ($transferReversalResult['success']) {
                        // Update payout schedule to reflect reversal
                        $payoutSchedule->update([
                            'status' => PayoutSchedule::STATUS_REVERSED,
                            'reversed_at' => now(),
                            'stripe_reversal_id' => $transferReversalResult['reversal_id'],
                            'metadata' => array_merge($payoutSchedule->metadata ?? [], [
                                'reversed_for_refund' => true,
                                'refund_request_id' => $refundRequest->id,
                                'reversal_id' => $transferReversalResult['reversal_id'],
                            ])
                        ]);

                        Log::info('Stripe transfer reversed for refund', [
                            'payout_schedule_id' => $payoutSchedule->id,
                            'reversal_id' => $transferReversalResult['reversal_id'],
                            'refund_request_id' => $refundRequest->id
                        ]);
                    } else {
                        Log::error('Failed to reverse Stripe transfer for refund', [
                            'payout_schedule_id' => $payoutSchedule->id,
                            'transfer_id' => $payoutSchedule->stripe_transfer_id,
                            'error' => $transferReversalResult['error'],
                            'refund_request_id' => $refundRequest->id
                        ]);

                        throw new \Exception('Failed to reverse producer payout: ' . $transferReversalResult['error']);
                    }
                }
            }

            // Process the client refund (this would integrate with existing invoice refund logic)
            // TODO: Integrate with existing InvoiceService refund processing
            $stripeRefundId = 're_processed_' . uniqid(); // Placeholder for actual refund processing

            // Update refund request as processed
            $refundRequest->update([
                'status' => RefundRequest::STATUS_PROCESSED,
                'processed_at' => now(),
                'stripe_refund_id' => $stripeRefundId,
                'metadata' => array_merge($refundRequest->metadata ?? [], [
                    'processed_at' => now()->toISOString(),
                    'transfer_reversed' => $transferReversalResult !== null,
                    'reversal_id' => $transferReversalResult['reversal_id'] ?? null,
                    'stripe_refund_id' => $stripeRefundId,
                ])
            ]);

            Log::info('Refund request approved and processed', [
                'refund_request_id' => $refundRequest->id
            ]);

            // Send notifications
            $this->notificationService->notifyClientRefundApproved($refundRequest->client_email, $refundRequest);
            
            return $refundRequest;
        });
    }

    /**
     * Producer denies the refund request
     *
     * @param RefundRequest $refundRequest
     * @param User $producer
     * @param string $reason
     * @return RefundRequest
     */
    public function denyRefund(RefundRequest $refundRequest, User $producer, string $reason): RefundRequest
    {
        if ($refundRequest->status !== RefundRequest::STATUS_REQUESTED) {
            throw new \InvalidArgumentException('Refund request cannot be denied in current status: ' . $refundRequest->status);
        }

        if ($refundRequest->producer_user_id !== $producer->id) {
            throw new \InvalidArgumentException('Only the assigned producer can deny this refund request');
        }

        Log::info('Producer denying refund request', [
            'refund_request_id' => $refundRequest->id,
            'producer_id' => $producer->id,
            'reason' => $reason
        ]);

        return DB::transaction(function () use ($refundRequest, $producer, $reason) {
            $refundRequest->update([
                'status' => RefundRequest::STATUS_DENIED,
                'denied_by' => $producer->id,
                'denied_at' => now(),
                'denial_reason' => $reason,
                'metadata' => array_merge($refundRequest->metadata ?? [], [
                    'denied_at' => now()->toISOString(),
                    'denial_reason' => $reason
                ])
            ]);

            Log::info('Refund request denied', [
                'refund_request_id' => $refundRequest->id
            ]);

            // Send notifications
            $this->notificationService->notifyClientRefundDenied($refundRequest->client_email, $refundRequest);
            
            // Escalate to platform mediation
            $this->escalateToMediation($refundRequest);

            return $refundRequest;
        });
    }

    /**
     * Handle expired refund requests (producer didn't respond within 2 days)
     * Called by scheduled job
     *
     * @return array Processing results
     */
    public function processExpiredRequests(): array
    {
        $results = [
            'expired' => 0,
            'escalated' => 0
        ];

        $expiredRequests = RefundRequest::where('status', RefundRequest::STATUS_REQUESTED)
            ->where('response_deadline', '<', now())
            ->with(['pitch', 'producer'])
            ->get();

        Log::info('Processing expired refund requests', ['count' => $expiredRequests->count()]);

        foreach ($expiredRequests as $request) {
            try {
                $this->handleExpiredRequest($request);
                $results['expired']++;
                $results['escalated']++;
            } catch (\Exception $e) {
                Log::error('Failed to process expired refund request', [
                    'refund_request_id' => $request->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Handle a single expired refund request
     *
     * @param RefundRequest $refundRequest
     * @return void
     */
    protected function handleExpiredRequest(RefundRequest $refundRequest): void
    {
        DB::transaction(function () use ($refundRequest) {
            $refundRequest->update([
                'status' => RefundRequest::STATUS_EXPIRED,
                'expired_at' => now(),
                'metadata' => array_merge($refundRequest->metadata ?? [], [
                    'expired_at' => now()->toISOString(),
                    'expiration_reason' => 'Producer did not respond within 2 days'
                ])
            ]);

            Log::info('Refund request expired', [
                'refund_request_id' => $refundRequest->id
            ]);

            // Escalate to platform mediation
            $this->escalateToMediation($refundRequest);

            // Send notifications
            $this->notificationService->notifyClientRefundExpired($refundRequest->client_email, $refundRequest);
            $this->notificationService->notifyProducerRefundExpired($refundRequest->producer, $refundRequest);
        });
    }

    /**
     * Escalate refund request to platform mediation
     *
     * @param RefundRequest $refundRequest
     * @return void
     */
    protected function escalateToMediation(RefundRequest $refundRequest): void
    {
        Log::info('Escalating refund request to mediation', [
            'refund_request_id' => $refundRequest->id,
            'current_status' => $refundRequest->status
        ]);

        // Update metadata to indicate mediation
        $refundRequest->update([
            'metadata' => array_merge($refundRequest->metadata ?? [], [
                'escalated_to_mediation' => true,
                'escalated_at' => now()->toISOString(),
                'mediation_required' => true
            ])
        ]);

        // Send notification to admin team
        $this->notificationService->notifyAdminMediationRequired($refundRequest);
    }

    /**
     * Admin resolves a refund request through mediation
     *
     * @param RefundRequest $refundRequest
     * @param User $admin
     * @param string $decision 'approve' or 'deny'
     * @param string $reasoning
     * @return RefundRequest
     */
    public function adminResolveRefund(
        RefundRequest $refundRequest, 
        User $admin, 
        string $decision, 
        string $reasoning
    ): RefundRequest {
        if (!in_array($decision, ['approve', 'deny'])) {
            throw new \InvalidArgumentException('Decision must be either "approve" or "deny"');
        }

        Log::info('Admin resolving refund request', [
            'refund_request_id' => $refundRequest->id,
            'admin_id' => $admin->id,
            'decision' => $decision
        ]);

        return DB::transaction(function () use ($refundRequest, $admin, $decision, $reasoning) {
            if ($decision === 'approve') {
                $refundRequest->update([
                    'status' => RefundRequest::STATUS_APPROVED,
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'admin_notes' => $reasoning,
                    'metadata' => array_merge($refundRequest->metadata ?? [], [
                        'admin_resolved' => true,
                        'admin_decision' => 'approve',
                        'admin_reasoning' => $reasoning,
                        'resolved_at' => now()->toISOString()
                    ])
                ]);

                // Cancel payout if still scheduled
                if ($refundRequest->payoutSchedule && 
                    in_array($refundRequest->payoutSchedule->status, [PayoutSchedule::STATUS_SCHEDULED, PayoutSchedule::STATUS_PROCESSING])) {
                    
                    $this->payoutService->cancelPayout(
                        $refundRequest->payoutSchedule, 
                        "Refund approved by admin mediation"
                    );
                }

                // TODO: Process actual Stripe refund in Phase 3
                $refundRequest->update([
                    'status' => RefundRequest::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'stripe_refund_id' => 're_admin_' . uniqid()
                ]);

                // Send approval notifications
                $this->notificationService->notifyClientRefundApproved($refundRequest->client_email, $refundRequest);
                $this->notificationService->notifyProducerRefundApproved($refundRequest->producer, $refundRequest);

            } else {
                $refundRequest->update([
                    'status' => RefundRequest::STATUS_DENIED,
                    'denied_by' => $admin->id,
                    'denied_at' => now(),
                    'denial_reason' => $reasoning,
                    'admin_notes' => $reasoning,
                    'metadata' => array_merge($refundRequest->metadata ?? [], [
                        'admin_resolved' => true,
                        'admin_decision' => 'deny',
                        'admin_reasoning' => $reasoning,
                        'resolved_at' => now()->toISOString()
                    ])
                ]);

                // Send denial notifications
                $this->notificationService->notifyClientRefundDenied($refundRequest->client_email, $refundRequest);
                $this->notificationService->notifyProducerRefundDenied($refundRequest->producer, $refundRequest);
            }

            Log::info('Admin refund resolution completed', [
                'refund_request_id' => $refundRequest->id,
                'decision' => $decision
            ]);

            return $refundRequest;
        });
    }

    /**
     * Get refund request statistics for admin dashboard
     *
     * @param array $filters
     * @return array
     */
    public function getRefundStatistics(array $filters = []): array
    {
        $query = RefundRequest::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('requested_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('requested_at', '<=', $filters['date_to']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $stats = [
            'total_requests' => $query->count(),
            'total_refund_amount' => $query->sum('refund_amount'),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count, sum(refund_amount) as total_amount')
                ->get()
                ->keyBy('status'),
            'pending_response' => RefundRequest::where('status', RefundRequest::STATUS_REQUESTED)
                ->where('response_deadline', '>', now())
                ->count(),
            'overdue_response' => RefundRequest::where('status', RefundRequest::STATUS_REQUESTED)
                ->where('response_deadline', '<', now())
                ->count(),
            'requiring_mediation' => RefundRequest::whereIn('status', [RefundRequest::STATUS_DENIED, RefundRequest::STATUS_EXPIRED])
                ->whereJsonContains('metadata->mediation_required', true)
                ->count()
        ];

        return $stats;
    }

    /**
     * Get producer's refund request history
     *
     * @param User $producer
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProducerRefundHistory(User $producer, int $limit = 20)
    {
        return RefundRequest::where('producer_user_id', $producer->id)
            ->with(['pitch.project', 'payoutSchedule'])
            ->orderBy('requested_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Check if a pitch is eligible for refund request
     *
     * @param Pitch $pitch
     * @return array ['eligible' => bool, 'reason' => string|null]
     */
    public function checkRefundEligibility(Pitch $pitch): array
    {
        // Must be paid
        if (!$pitch->payment_completed_at) {
            return ['eligible' => false, 'reason' => 'Pitch has not been paid'];
        }

        // Must be within 3 days
        $daysSincePayment = $pitch->payment_completed_at->diffInDays(now());
        if ($daysSincePayment > 3) {
            return ['eligible' => false, 'reason' => 'Refund window has expired (3 days maximum)'];
        }

        // Cannot have existing active refund request
        $existingRequest = RefundRequest::where('pitch_id', $pitch->id)
            ->whereIn('status', [RefundRequest::STATUS_REQUESTED, RefundRequest::STATUS_APPROVED])
            ->exists();

        if ($existingRequest) {
            return ['eligible' => false, 'reason' => 'A refund request already exists for this pitch'];
        }

        return ['eligible' => true, 'reason' => null];
    }
} 