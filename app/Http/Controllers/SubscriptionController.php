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
    
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:pro',
            'tier' => 'required|in:artist,engineer'
        ]);
        
        $plan = $request->input('plan'); // 'pro'
        $tier = $request->input('tier'); // 'artist' or 'engineer'
        
        $user = $request->user();
        
        // Check if user already has a subscription
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.index')
                ->with('warning', 'You already have an active subscription. Manage it through your billing portal.');
        }
        
        try {
            $priceId = $this->getPriceIdForPlan($plan, $tier);
            
            // Create Stripe checkout session
            $checkoutSession = $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('subscription.cancel'),
                    'metadata' => [
                        'plan' => $plan,
                        'tier' => $tier,
                        'user_id' => $user->id,
                    ],
                ]);
            
            Log::info('Stripe checkout session created', [
                'user_id' => $user->id,
                'plan' => $plan,
                'tier' => $tier,
                'session_id' => $checkoutSession->id
            ]);
            
            return $checkoutSession;
            
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe checkout session', [
                'user_id' => $user->id,
                'plan' => $plan,
                'tier' => $tier,
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
    
    private function getPriceIdForPlan(string $plan, string $tier): string
    {
        $priceIds = [
            'pro.artist' => config('subscription.stripe_prices.pro_artist'),
            'pro.engineer' => config('subscription.stripe_prices.pro_engineer'),
        ];
        
        $priceId = $priceIds["$plan.$tier"] ?? null;
        
        if (!$priceId) {
            throw new \Exception("Invalid plan/tier combination: $plan.$tier");
        }
        
        return $priceId;
    }
}
