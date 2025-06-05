<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionLimit;
use App\Models\Pitch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    
    /**
     * Handle subscription upgrade with plan selection
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:pro',
            'tier' => 'required|in:artist,engineer',
            'billing_period' => 'required|in:monthly,yearly'
        ]);
        
        $plan = $request->input('plan'); // 'pro'
        $tier = $request->input('tier'); // 'artist' or 'engineer'
        $billingPeriod = $request->input('billing_period'); // 'monthly' or 'yearly'
        
        $user = $request->user();
        
        // Check if user already has a subscription
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.index')
                ->with('warning', 'You already have an active subscription. Manage it through your billing portal.');
        }
        
        try {
            $priceId = $this->getPriceIdForPlan($plan, $tier, $billingPeriod);
            $planConfig = $this->getPlanConfig($plan, $tier, $billingPeriod);
            
            // Create Stripe checkout session
            $checkoutSession = $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('subscription.cancel'),
                    'metadata' => [
                        'plan' => $plan,
                        'tier' => $tier,
                        'billing_period' => $billingPeriod,
                        'user_id' => $user->id,
                        'price' => $planConfig['price'],
                        'currency' => 'USD',
                    ],
                ]);
            
            Log::info('Stripe checkout session created', [
                'user_id' => $user->id,
                'plan' => $plan,
                'tier' => $tier,
                'billing_period' => $billingPeriod,
                'price_id' => $priceId,
                'session_id' => $checkoutSession->id
            ]);
            
            return $checkoutSession;
            
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe checkout session', [
                'user_id' => $user->id,
                'plan' => $plan,
                'tier' => $tier,
                'billing_period' => $billingPeriod,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('subscription.index')
                ->with('error', 'Unable to process upgrade. Please try again later.');
        }
    }
    
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if ($sessionId) {
            Log::info('User returned from successful Stripe checkout', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId
            ]);
        }
        
        return view('subscription.success');
    }
    
    public function cancel()
    {
        Log::info('User cancelled Stripe checkout', [
            'user_id' => Auth::id()
        ]);
        
        return redirect()->route('subscription.index')
            ->with('info', 'Subscription upgrade was cancelled.');
    }
    
    /**
     * Handle downgrade to free plan
     */
    public function downgrade(Request $request)
    {
        $user = $request->user();
        
        if (!$user->subscribed('default')) {
            return redirect()->route('subscription.index')
                ->with('warning', 'You do not have an active subscription.');
        }
        
        try {
            // Cancel the subscription at period end
            $user->subscription('default')->cancelAtPeriodEnd();
            
            Log::info('User downgraded subscription', [
                'user_id' => $user->id
            ]);
            
            return redirect()->route('subscription.index')
                ->with('success', 'Your subscription will be cancelled at the end of the current billing period.');
                
        } catch (\Exception $e) {
            Log::error('Failed to downgrade subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('subscription.index')
                ->with('error', 'Unable to process downgrade. Please contact support.');
        }
    }
    
    /**
     * Resume a cancelled subscription
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        
        if (!$user->subscribed('default') || !$user->subscription('default')->onGracePeriod()) {
            return redirect()->route('subscription.index')
                ->with('warning', 'No subscription to resume.');
        }
        
        try {
            $user->subscription('default')->resume();
            
            Log::info('User resumed subscription', [
                'user_id' => $user->id
            ]);
            
            return redirect()->route('subscription.index')
                ->with('success', 'Your subscription has been resumed.');
                
        } catch (\Exception $e) {
            Log::error('Failed to resume subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('subscription.index')
                ->with('error', 'Unable to resume subscription. Please contact support.');
        }
    }
    
    private function getPriceIdForPlan(string $plan, string $tier, string $billingPeriod): string
    {
        $priceIds = [
            'pro.artist.monthly' => config('subscription.stripe_prices.pro_artist_monthly'),
            'pro.artist.yearly' => config('subscription.stripe_prices.pro_artist_yearly'),
            'pro.engineer.monthly' => config('subscription.stripe_prices.pro_engineer_monthly'),
            'pro.engineer.yearly' => config('subscription.stripe_prices.pro_engineer_yearly'),
        ];
        
        $priceId = $priceIds["$plan.$tier.$billingPeriod"] ?? null;
        
        if (!$priceId) {
            throw new \Exception("Invalid plan/tier/billing_period combination: $plan.$tier.$billingPeriod");
        }
        
        return $priceId;
    }

    private function getPlanConfig(string $plan, string $tier, string $billingPeriod)
    {
        $planConfigs = [
            'pro.artist.monthly' => [
                'price' => config('subscription.stripe_prices.pro_artist_monthly'),
            ],
            'pro.artist.yearly' => [
                'price' => config('subscription.stripe_prices.pro_artist_yearly'),
            ],
            'pro.engineer.monthly' => [
                'price' => config('subscription.stripe_prices.pro_engineer_monthly'),
            ],
            'pro.engineer.yearly' => [
                'price' => config('subscription.stripe_prices.pro_engineer_yearly'),
            ],
        ];
        
        $planConfig = $planConfigs["$plan.$tier.$billingPeriod"] ?? null;
        
        if (!$planConfig) {
            throw new \Exception("Invalid plan/tier/billing_period combination: $plan.$tier.$billingPeriod");
        }
        
        return $planConfig;
    }
}
