<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\User;

class UserBadge extends Component
{
    public User $user;
    public ?string $badge;
    public string $planName;
    
    /**
     * Create a new component instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->badge = $user->getUserBadge();
        $this->planName = ucfirst($user->subscription_plan) . 
                         ($user->subscription_tier !== 'basic' ? ' ' . ucfirst($user->subscription_tier) : '');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.user-badge');
    }
}
