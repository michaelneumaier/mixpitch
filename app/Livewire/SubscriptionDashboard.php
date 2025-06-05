<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\UserMonthlyLimit;
use App\Models\VisibilityBoost;
use App\Models\LicenseTemplate;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class SubscriptionDashboard extends Component
{
    public $user;
    public $showFullDetails = false;
    
    public function mount()
    {
        $this->user = Auth::user();
    }

    public function toggleDetails()
    {
        $this->showFullDetails = !$this->showFullDetails;
    }

    public function createVisibilityBoost($type = 'profile', $duration = 72)
    {
        if (!$this->user->canCreateVisibilityBoost()) {
            $this->addError('boost', 'You cannot create more visibility boosts this month.');
            return;
        }

        try {
            $boost = VisibilityBoost::create([
                'user_id' => $this->user->id,
                'boost_type' => $type,
                'started_at' => now(),
                'expires_at' => now()->addHours($duration),
                'status' => 'active',
                'ranking_multiplier' => 2.0,
                'month_year' => now()->format('Y-m'),
            ]);

            // Increment monthly usage
            VisibilityBoost::incrementMonthlyUsage($this->user);

            session()->flash('success', "Visibility boost activated! Your {$type} will have increased visibility for {$duration} hours.");
            
        } catch (\Exception $e) {
            $this->addError('boost', 'Failed to create visibility boost. Please try again.');
        }
    }

    public function refreshReputationCache()
    {
        $this->user->clearReputationCache();
        session()->flash('success', 'Reputation score refreshed!');
    }

    public function getSubscriptionDataProperty()
    {
        $limits = $this->user->getSubscriptionLimits();
        $monthlyLimits = $this->user->currentMonthLimits();
        
        return [
            'limits' => $limits,
            'monthly_limits' => $monthlyLimits,
            'usage_summary' => $monthlyLimits->getUsageSummary(),
            'reputation' => $this->user->getReputationWithMultiplier(),
            'earnings' => [
                'total' => $this->user->getTotalEarnings(),
                'commission_paid' => $this->user->getTotalCommissionPaid(),
                'commission_savings' => $this->user->getCommissionSavings(),
            ],
            'remaining' => [
                'private_projects' => $this->user->getRemainingPrivateProjects(),
                'visibility_boosts' => $this->user->getRemainingVisibilityBoosts(),
            ],
            'active_boosts' => $this->user->activeVisibilityBoosts()->count(),
            'plan_name' => $this->getPlanName(),
        ];
    }

    public function getRecentActivityProperty()
    {
        return [
            'transactions' => $this->user->transactions()
                ->latest()
                ->limit(5)
                ->get(),
            'visibility_boosts' => $this->user->visibilityBoosts()
                ->latest()
                ->limit(3)
                ->get(),
            'license_templates' => $this->user->licenseTemplates()
                ->latest()
                ->limit(3)
                ->get(),
        ];
    }

    private function getPlanName()
    {
        $planName = ucfirst($this->user->subscription_plan);
        if ($this->user->subscription_tier !== 'basic') {
            $planName .= ' ' . ucfirst($this->user->subscription_tier);
        }
        return $planName;
    }

    public function render()
    {
        return view('livewire.subscription-dashboard', [
            'subscriptionData' => $this->subscriptionData,
            'recentActivity' => $this->recentActivity,
        ]);
    }
}
