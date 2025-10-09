<?php

namespace App\Livewire;

use App\Models\PayoutSchedule;
use App\Models\Transaction;
use App\Models\UserPayoutAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BillingPaymentsSection extends Component
{
    public $user;

    public function mount()
    {
        $this->user = Auth::user();
    }

    /**
     * Check if user is a producer (has earnings/payouts)
     */
    public function getIsProducerProperty(): bool
    {
        return $this->user->payoutSchedules()->exists()
            || $this->user->payoutAccounts()->exists()
            || $this->user->pitches()
                ->whereIn('status', [
                    \App\Models\Pitch::STATUS_APPROVED,
                    \App\Models\Pitch::STATUS_COMPLETED,
                    \App\Models\Pitch::STATUS_CONTEST_WINNER,
                ])
                ->exists();
    }

    /**
     * Get recent transactions (last 30 days)
     */
    public function getRecentTransactionsProperty()
    {
        return Transaction::where('user_id', $this->user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get transaction summary stats
     */
    public function getTransactionSummaryProperty(): array
    {
        $transactions = $this->recentTransactions;

        return [
            'count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'completed_count' => $transactions->where('status', Transaction::STATUS_COMPLETED)->count(),
        ];
    }

    /**
     * Get payment method status
     */
    public function getPaymentMethodProperty(): array
    {
        try {
            $hasPaymentMethod = $this->user->hasDefaultPaymentMethod();
            $defaultPaymentMethod = $hasPaymentMethod ? $this->user->defaultPaymentMethod() : null;

            return [
                'exists' => $hasPaymentMethod,
                'last_four' => $defaultPaymentMethod?->card?->last4 ?? null,
                'brand' => $defaultPaymentMethod?->card?->brand ?? null,
                'is_default' => true,
            ];
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'last_four' => null,
                'brand' => null,
                'is_default' => false,
            ];
        }
    }

    /**
     * Get next billing information
     */
    public function getNextBillingProperty(): ?array
    {
        if (! $this->user->subscribed('default')) {
            return null;
        }

        $subscription = $this->user->subscription('default');

        if (! $subscription) {
            return null;
        }

        // Get upcoming invoice from Stripe
        try {
            $upcomingInvoice = $this->user->upcomingInvoice();

            return [
                'date' => $subscription->asStripeSubscription()->current_period_end
                    ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)
                    : null,
                'amount' => $upcomingInvoice ? $upcomingInvoice->total / 100 : null,
            ];
        } catch (\Exception $e) {
            return [
                'date' => null,
                'amount' => null,
            ];
        }
    }

    /**
     * Get payout account status for producers
     */
    public function getPayoutAccountStatusProperty(): array
    {
        if (! $this->isProducer) {
            return [
                'exists' => false,
                'is_active' => false,
                'needs_setup' => true,
                'status_text' => 'Not Set Up',
                'status_color' => 'gray',
            ];
        }

        // Check UserPayoutAccount first
        $account = $this->user->payoutAccounts()
            ->where('is_primary', true)
            ->first();

        if ($account) {
            return [
                'exists' => true,
                'is_active' => $account->isReadyForPayouts(),
                'needs_setup' => $account->needsSetup(),
                'is_restricted' => $account->isRestricted(),
                'status_text' => $account->status_display_name,
                'status_color' => $account->status_color,
                'provider' => $account->provider_display_name,
            ];
        }

        // Fallback to checking Stripe Connect (legacy)
        try {
            $stripeConnectService = app(\App\Services\StripeConnectService::class);
            $stripeStatus = $stripeConnectService->getDetailedAccountStatus($this->user);

            return [
                'exists' => $stripeStatus['account_exists'] ?? false,
                'is_active' => $stripeStatus['can_receive_payouts'] ?? false,
                'needs_setup' => ! ($stripeStatus['can_receive_payouts'] ?? false),
                'is_restricted' => false,
                'status_text' => $stripeStatus['status_display'] ?? 'Setup Required',
                'status_color' => ($stripeStatus['can_receive_payouts'] ?? false) ? 'green' : 'amber',
                'provider' => 'Stripe Connect',
            ];
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'is_active' => false,
                'needs_setup' => true,
                'is_restricted' => false,
                'status_text' => 'Setup Required',
                'status_color' => 'amber',
                'provider' => null,
            ];
        }
    }

    /**
     * Get earnings summary for producers
     */
    public function getEarningsSummaryProperty(): array
    {
        if (! $this->isProducer) {
            return [
                'total_earnings' => 0,
                'pending_payouts' => 0,
                'this_month_earnings' => 0,
                'completed_count' => 0,
                'pending_count' => 0,
            ];
        }

        // Optimized: Single query with conditional aggregation
        $stats = PayoutSchedule::where('producer_user_id', $this->user->id)
            ->selectRaw('
                SUM(CASE WHEN status = ? THEN net_amount ELSE 0 END) as total_earnings,
                SUM(CASE WHEN status IN (?, ?) THEN net_amount ELSE 0 END) as pending_payouts,
                COUNT(CASE WHEN status = ? THEN 1 END) as completed_count,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending_count
            ', [
                PayoutSchedule::STATUS_COMPLETED,
                PayoutSchedule::STATUS_SCHEDULED,
                PayoutSchedule::STATUS_PROCESSING,
                PayoutSchedule::STATUS_COMPLETED,
                PayoutSchedule::STATUS_SCHEDULED,
                PayoutSchedule::STATUS_PROCESSING,
            ])
            ->first();

        // Separate query for this month (different WHERE clause requirement)
        $thisMonthEarnings = PayoutSchedule::where('producer_user_id', $this->user->id)
            ->where('status', PayoutSchedule::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->startOfMonth())
            ->sum('net_amount');

        return [
            'total_earnings' => $stats->total_earnings ?? 0,
            'pending_payouts' => $stats->pending_payouts ?? 0,
            'this_month_earnings' => $thisMonthEarnings,
            'completed_count' => $stats->completed_count ?? 0,
            'pending_count' => $stats->pending_count ?? 0,
        ];
    }

    /**
     * Get recent payouts for producers
     */
    public function getRecentPayoutsProperty()
    {
        if (! $this->isProducer) {
            return collect();
        }

        return PayoutSchedule::where('producer_user_id', $this->user->id)
            ->with(['project', 'pitch'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.billing-payments-section');
    }
}
