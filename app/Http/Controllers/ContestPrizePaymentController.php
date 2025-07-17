<?php

namespace App\Http\Controllers;

use App\Models\ContestPrize;
use App\Models\Project;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PayoutProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContestPrizePaymentController extends Controller
{
    protected InvoiceService $invoiceService;

    protected PayoutProcessingService $payoutService;

    public function __construct(InvoiceService $invoiceService, PayoutProcessingService $payoutService)
    {
        $this->invoiceService = $invoiceService;
        $this->payoutService = $payoutService;
        $this->middleware('auth');
    }

    /**
     * Show contest prize payment overview
     */
    public function overview(Project $project): View
    {
        // Authorization
        $this->authorize('manageProject', $project);

        // Validate this is a finalized contest with prizes
        if (! $project->isContest()) {
            abort(404, 'Not a contest project');
        }

        if (! $project->isJudgingFinalized()) {
            abort(403, 'Contest judging must be finalized before processing prize payments');
        }

        // Get contest results and prizes
        $contestResult = $project->contestResult;
        $allWinners = $this->getContestWinners($project);
        $totalPrizeAmount = $this->calculateTotalPrizeAmount($allWinners);

        // Separate valid and invalid winners
        $validationResult = $this->separateValidInvalidWinners($allWinners);
        $winners = $validationResult['valid'];
        $invalidWinners = $validationResult['invalid'];

        // Add detailed status information for each winner
        $winnersWithStatus = [];
        foreach ($allWinners as $winner) {
            $winner['stripe_status'] = $winner['user']->getStripeConnectStatus();
            $winnersWithStatus[] = $winner;
        }

        // Check if prizes have already been paid
        $prizesPaid = $this->areAllPrizesPaid($allWinners);

        // Get user's saved payment methods
        $user = auth()->user();
        $paymentMethods = collect();
        $defaultPaymentMethod = null;
        $setupIntent = null;

        // Create setup intent for new payment methods (only if we have valid winners to pay)
        if (! $prizesPaid && ! empty($winners)) {
            // Ensure user has a Stripe customer ID
            if (! $user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Now get payment methods after ensuring Stripe customer exists
            $paymentMethods = $user->paymentMethods();
            $defaultPaymentMethod = $user->defaultPaymentMethod();

            // Create setup intent for adding new payment methods
            $setupIntent = $user->createSetupIntent();
        } elseif ($user->stripe_id) {
            // If no valid winners but user has Stripe ID, still get existing payment methods
            $paymentMethods = $user->paymentMethods();
            $defaultPaymentMethod = $user->defaultPaymentMethod();
        }

        return view('contest.prizes.payment-overview', [
            'project' => $project,
            'contestResult' => $contestResult,
            'allWinners' => $winnersWithStatus, // Use winners with detailed status
            'winners' => $winners,
            'invalidWinners' => $invalidWinners,
            'totalPrizeAmount' => $totalPrizeAmount,
            'prizesPaid' => $prizesPaid,
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => $defaultPaymentMethod,
            'setupIntent' => $setupIntent,
        ]);
    }

    /**
     * Process contest prize payments
     */
    public function process(Request $request, Project $project): RedirectResponse
    {
        // Authorization
        $this->authorize('manageProject', $project);

        // Validate request
        $request->validate([
            'payment_method_id' => 'required|string',
            'confirm_payment' => 'required|accepted',
            'use_existing_method' => 'sometimes|boolean',
        ]);

        // Validate contest state
        if (! $project->isContest() || ! $project->isJudgingFinalized()) {
            return redirect()->back()
                ->withErrors(['error' => 'Contest must be finalized before processing payments']);
        }

        // Get winners and separate valid from invalid ones
        $allWinners = $this->getContestWinners($project);
        $validationResult = $this->separateValidInvalidWinners($allWinners);
        $winners = $validationResult['valid'];
        $invalidWinners = $validationResult['invalid'];

        if (empty($winners)) {
            return redirect()->back()
                ->withErrors(['error' => 'No winners have valid Stripe Connect accounts set up. Winners must complete their Stripe Connect setup before prizes can be paid.']);
        }

        // Check if already paid
        if ($this->areAllPrizesPaid($winners)) {
            return redirect()->back()
                ->with('info', 'Contest prizes have already been paid');
        }

        try {
            return DB::transaction(function () use ($request, $project, $winners) {
                $paymentMethodId = $request->payment_method_id;
                $totalAmount = $this->calculateTotalPrizeAmount($winners);

                // Create a single invoice for all contest prizes
                $invoice = $this->invoiceService->createContestPrizeInvoice(
                    $project,
                    $winners,
                    $totalAmount
                );

                // Process the payment
                $paymentResult = $this->invoiceService->processInvoicePayment(
                    $invoice,
                    $paymentMethodId
                );

                if (! $paymentResult['success']) {
                    throw new \Exception('Payment processing failed: '.($paymentResult['error'] ?? 'Unknown error'));
                }

                // Schedule payouts for all winners
                $payoutSchedules = $this->payoutService->schedulePayoutsForContest(
                    $project,
                    $winners,
                    $invoice->id
                );

                // Update winner pitches with payment information
                foreach ($winners as $winner) {
                    $pitch = $winner['pitch'];
                    $prize = $winner['prize'];

                    $pitch->update([
                        'payment_status' => 'paid',
                        'payment_amount' => $prize->cash_amount,
                        'payment_completed_at' => now(),
                        'final_invoice_id' => $invoice->id,
                    ]);
                }

                Log::info('Contest prizes paid successfully', [
                    'project_id' => $project->id,
                    'invoice_id' => $invoice->id,
                    'total_amount' => $totalAmount,
                    'winner_count' => count($winners),
                    'payout_schedules' => count($payoutSchedules),
                ]);

                $holdService = app(\App\Services\PayoutHoldService::class);
                $holdInfo = $holdService->getHoldPeriodInfo('contest');
                $successMessage = 'Contest prizes have been paid successfully! Payouts will be processed after '.$holdInfo['description'].'.';

                // Add note about partial payment if some winners were invalid
                if (! empty($invalidWinners)) {
                    $successMessage .= ' Note: Some winners still need to set up their Stripe Connect accounts: '.implode(', ', $invalidWinners);
                }

                return redirect()->route('contest.prizes.receipt', $project)
                    ->with('success', $successMessage);
            });

        } catch (\Exception $e) {
            Log::error('Contest prize payment failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Payment processing failed: '.$e->getMessage()]);
        }
    }

    /**
     * Show payment receipt
     */
    public function receipt(Project $project): View
    {
        // Authorization
        $this->authorize('manageProject', $project);

        // Validate contest state
        if (! $project->isContest() || ! $project->isJudgingFinalized()) {
            abort(403, 'Contest must be finalized to view receipt');
        }

        $winners = $this->getContestWinners($project);
        $totalPrizeAmount = $this->calculateTotalPrizeAmount($winners);

        // Get the invoice if prizes were paid
        $invoice = null;
        if (! empty($winners)) {
            $firstWinner = $winners[0];
            if ($firstWinner['pitch']->final_invoice_id) {
                $invoice = $this->invoiceService->getInvoice($firstWinner['pitch']->final_invoice_id);
            }
        }

        return view('contest.prizes.receipt', [
            'project' => $project,
            'winners' => $winners,
            'totalPrizeAmount' => $totalPrizeAmount,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Get contest winners with their prizes
     */
    protected function getContestWinners(Project $project): array
    {
        $contestResult = $project->contestResult;
        if (! $contestResult) {
            return [];
        }

        $winners = [];

        // Get cash prizes and their winners
        $cashPrizes = $project->contestPrizes()
            ->where('prize_type', 'cash')
            ->where('cash_amount', '>', 0)
            ->get();

        foreach ($cashPrizes as $prize) {
            $winnerPitch = $contestResult->getWinnerForPlacement($prize->placement);
            if ($winnerPitch) {
                $winners[] = [
                    'pitch' => $winnerPitch,
                    'prize' => $prize,
                    'user' => $winnerPitch->user,
                ];
            }
        }

        return $winners;
    }

    /**
     * Calculate total prize amount
     */
    protected function calculateTotalPrizeAmount(array $winners): float
    {
        return array_sum(array_map(function ($winner) {
            return $winner['prize']->cash_amount;
        }, $winners));
    }

    /**
     * Check if all prizes have been paid
     */
    protected function areAllPrizesPaid(array $winners): bool
    {
        if (empty($winners)) {
            return true; // No prizes to pay
        }

        foreach ($winners as $winner) {
            if ($winner['pitch']->payment_status !== 'paid') {
                return false;
            }
        }

        return true;
    }

    /**
     * Separate winners into valid and invalid based on Stripe Connect accounts
     */
    protected function separateValidInvalidWinners(array $winners): array
    {
        $validWinners = [];
        $invalidWinners = [];

        foreach ($winners as $winner) {
            $user = $winner['user'];
            if ($user->stripe_account_id && $user->hasValidStripeConnectAccount()) {
                $validWinners[] = $winner;
            } else {
                $invalidWinners[] = $user->name ?? $user->email;

                // Send notification to winner about setting up Stripe Connect
                $this->notifyWinnerToSetupStripeConnect($user, $winner['prize']);
            }
        }

        return [
            'valid' => $validWinners,
            'invalid' => $invalidWinners,
        ];
    }

    /**
     * Notify winner to set up Stripe Connect account
     */
    protected function notifyWinnerToSetupStripeConnect(User $winner, ContestPrize $prize): void
    {
        // You can implement email notification here
        // For now, we'll just log it
        Log::info('Winner needs Stripe Connect setup', [
            'winner_id' => $winner->id,
            'winner_email' => $winner->email,
            'prize_placement' => $prize->placement,
            'prize_amount' => $prize->cash_amount,
        ]);

        // TODO: Send email notification to winner
        // Mail::to($winner)->send(new StripeConnectSetupRequired($prize));
    }

    /**
     * Process individual prize payment
     */
    public function processIndividual(Request $request, Project $project, $prizeId): RedirectResponse
    {
        // Authorization
        $this->authorize('manageProject', $project);

        // Validate request
        $request->validate([
            'payment_method_id' => 'required|string',
            'confirm_payment' => 'required|accepted',
            'use_existing_method' => 'sometimes|boolean',
        ]);

        // Validate contest state
        if (! $project->isContest() || ! $project->isJudgingFinalized()) {
            return redirect()->back()
                ->withErrors(['error' => 'Contest must be finalized before processing payments']);
        }

        // Find the specific winner and prize
        $allWinners = $this->getContestWinners($project);
        $targetWinner = null;

        foreach ($allWinners as $winner) {
            if ($winner['prize']->id == $prizeId) {
                $targetWinner = $winner;
                break;
            }
        }

        if (! $targetWinner) {
            return redirect()->back()
                ->withErrors(['error' => 'Prize not found or invalid.']);
        }

        // Check if this specific prize has already been paid
        if ($targetWinner['pitch']->payment_status === 'paid') {
            return redirect()->back()
                ->with('info', "This prize has already been paid to {$targetWinner['user']->name}.");
        }

        // Validate the winner has a valid Stripe Connect account
        if (! $targetWinner['user']->stripe_account_id || ! $targetWinner['user']->hasValidStripeConnectAccount()) {
            return redirect()->back()
                ->withErrors(['error' => "Cannot process payment: {$targetWinner['user']->name} needs to complete their Stripe Connect account setup."]);
        }

        try {
            return DB::transaction(function () use ($request, $project, $targetWinner) {
                $paymentMethodId = $request->payment_method_id;
                $prizeAmount = $targetWinner['prize']->cash_amount;

                // Create invoice for this specific prize
                $invoice = $this->invoiceService->createContestPrizeInvoice(
                    $project,
                    [$targetWinner], // Single winner array
                    $prizeAmount
                );

                // Process the payment
                $paymentResult = $this->invoiceService->processInvoicePayment(
                    $invoice,
                    $paymentMethodId
                );

                if (! $paymentResult['success']) {
                    throw new \Exception('Payment processing failed: '.($paymentResult['error'] ?? 'Unknown error'));
                }

                // Schedule payout for this winner
                $payoutSchedules = $this->payoutService->schedulePayoutsForContest(
                    $project,
                    [$targetWinner], // Single winner array
                    $invoice->id
                );

                // Update the winner pitch with payment information
                $targetWinner['pitch']->update([
                    'payment_status' => 'paid',
                    'payment_amount' => $prizeAmount,
                    'payment_completed_at' => now(),
                    'final_invoice_id' => $invoice->id,
                ]);

                Log::info('Individual contest prize paid successfully', [
                    'project_id' => $project->id,
                    'prize_id' => $targetWinner['prize']->id,
                    'winner_user_id' => $targetWinner['user']->id,
                    'invoice_id' => $invoice->id,
                    'prize_amount' => $prizeAmount,
                    'placement' => $targetWinner['prize']->placement,
                ]);

                $successMessage = 'Prize payment of $'.number_format($prizeAmount, 2).' for '.
                                $targetWinner['prize']->getPlacementDisplayName().' place has been processed successfully! '.
                                "{$targetWinner['user']->name} will receive their payout after ".app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('contest')['description'].'.';

                return redirect()->route('contest.prizes.overview', $project)
                    ->with('success', $successMessage);
            });

        } catch (\Exception $e) {
            Log::error('Individual contest prize payment failed', [
                'project_id' => $project->id,
                'prize_id' => $prizeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Payment processing failed: '.$e->getMessage()]);
        }
    }

    /**
     * Process contest prize payments (bulk - keeping for backward compatibility)
     */
}
