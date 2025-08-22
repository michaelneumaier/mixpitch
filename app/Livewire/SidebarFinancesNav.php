<?php

namespace App\Livewire;

use App\Models\PayoutSchedule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarFinancesNav extends Component
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

        // Client management stats
        $clientProjects = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->get();

        $clientManagement = [
            'total_projects' => $clientProjects->count(),
            'active_projects' => $clientProjects->whereIn('status', ['open', 'in_progress'])->count(),
        ];

        // Stripe Connect status
        $stripeConnect = [
            'account_exists' => ! empty($user->stripe_connect_account_id),
            'can_receive_payouts' => $user->stripe_connect_enabled && $user->stripe_connect_payouts_enabled,
        ];

        return [
            'earnings' => [
                'total' => $totalEarnings ?: 0,
            ],
            'client_management' => $clientManagement,
            'stripe_connect' => $stripeConnect,
        ];
    }

    public function render()
    {
        return view('livewire.sidebar-finances-nav');
    }
}
