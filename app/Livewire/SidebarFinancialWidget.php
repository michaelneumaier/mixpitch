<?php

namespace App\Livewire;

use App\Models\PayoutSchedule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarFinancialWidget extends Component
{
    public $producerData;

    public function mount()
    {
        $user = Auth::user();

        if ($user && ($user->hasRole('producer') || $user->hasRole('admin'))) {
            $this->producerData = $this->getProducerAnalytics($user);
        }
    }

    private function getProducerAnalytics(User $user)
    {
        // Calculate earnings
        $totalEarnings = PayoutSchedule::where('producer_user_id', $user->id)
            ->where('status', 'completed')
            ->sum('net_amount');

        $pendingEarnings = PayoutSchedule::where('producer_user_id', $user->id)
            ->where('status', 'pending')
            ->sum('net_amount');

        $thisMonthEarnings = PayoutSchedule::where('producer_user_id', $user->id)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('net_amount');

        // Commission calculations
        $commissionRate = $user->getPlatformCommissionRate();
        $commissionSavings = $commissionRate < 10 ? ($totalEarnings * (10 - $commissionRate) / 100) : 0;

        // Client management stats
        $clientProjects = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->get();

        $clientManagement = [
            'total_projects' => $clientProjects->count(),
            'active_projects' => $clientProjects->whereIn('status', ['open', 'in_progress'])->count(),
            'completed_projects' => $clientProjects->where('status', 'completed')->count(),
            'total_revenue' => $clientProjects->sum('budget'),
        ];

        // Stripe Connect status
        $stripeConnect = [
            'account_exists' => ! empty($user->stripe_connect_account_id),
            'can_receive_payouts' => $user->stripe_connect_enabled && $user->stripe_connect_payouts_enabled,
            'status_display' => $this->getStripeConnectStatusDisplay($user),
        ];

        return [
            'earnings' => [
                'total' => $totalEarnings ?: 0,
                'pending' => $pendingEarnings ?: 0,
                'this_month' => $thisMonthEarnings ?: 0,
                'commission_rate' => $commissionRate,
                'commission_savings' => $commissionSavings,
            ],
            'client_management' => $clientManagement,
            'stripe_connect' => $stripeConnect,
        ];
    }

    private function getStripeConnectStatusDisplay(User $user)
    {
        if ($user->stripe_connect_enabled && $user->stripe_connect_payouts_enabled) {
            return 'Ready to receive payouts';
        } elseif (! empty($user->stripe_connect_account_id)) {
            return 'Setup in progress';
        } else {
            return 'Setup required';
        }
    }

    public function render()
    {
        return view('livewire.sidebar-financial-widget');
    }
}
