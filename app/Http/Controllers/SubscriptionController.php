<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLimit;
use App\Models\Pitch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $limits = $user->getSubscriptionLimits();
        
        $usage = [
            'projects_count' => $user->projects()->count(),
            'active_pitches_count' => $user->pitches()->whereIn('status', [
                Pitch::STATUS_PENDING,
                Pitch::STATUS_IN_PROGRESS,
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_PENDING_REVIEW,
            ])->count(),
            'monthly_pitches_used' => $user->monthly_pitch_count,
        ];
        
        return view('subscription.index', compact('user', 'limits', 'usage'));
    }
    
    public function upgrade(Request $request)
    {
        $plan = $request->input('plan'); // 'pro'
        $tier = $request->input('tier'); // 'artist' or 'engineer'
        
        $priceId = $this->getPriceIdForPlan($plan, $tier);
        
        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
            ]);
    }
    
    public function success()
    {
        return view('subscription.success');
    }
    
    public function cancel()
    {
        return redirect()->route('subscription.index')
            ->with('info', 'Subscription upgrade was cancelled.');
    }
    
    private function getPriceIdForPlan(string $plan, string $tier): string
    {
        $priceIds = [
            'pro.artist' => config('subscription.stripe_prices.pro_artist'),
            'pro.engineer' => config('subscription.stripe_prices.pro_engineer'),
        ];
        
        return $priceIds["$plan.$tier"] ?? throw new \Exception('Invalid plan/tier combination');
    }
}
