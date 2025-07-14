<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SubscriptionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $limits = $user->getSubscriptionLimits();
        
        switch ($action) {
            case 'create_project':
                if (!$user->canCreateProject()) {
                    $activeProjectsCount = $user->getActiveProjectsCount();
                    
                    Log::info('User blocked from creating project due to subscription limits', [
                        'user_id' => $user->id,
                        'plan' => $user->subscription_plan,
                        'tier' => $user->subscription_tier,
                        'active_projects' => $activeProjectsCount,
                        'total_projects' => $user->projects()->count(),
                        'limit' => $limits?->max_projects_owned
                    ]);
                    
                    // Send limit reached notification if not already sent recently
                    $this->sendLimitNotificationIfNeeded($user, 'projects', $activeProjectsCount, $limits?->max_projects_owned);
                    
                    return redirect()->route('subscription.index')
                        ->with('error', 'You have reached your active project limit. Upgrade to Pro for unlimited projects or complete existing projects.');
                }
                break;
                
            case 'create_pitch':
                if (!$user->canCreatePitch()) {
                    $activePitchesCount = $user->pitches()->whereIn('status', [
                        \App\Models\Pitch::STATUS_PENDING,
                        \App\Models\Pitch::STATUS_IN_PROGRESS,
                        \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                        \App\Models\Pitch::STATUS_PENDING_REVIEW,
                    ])->count();
                    
                    Log::info('User blocked from creating pitch due to subscription limits', [
                        'user_id' => $user->id,
                        'plan' => $user->subscription_plan,
                        'tier' => $user->subscription_tier,
                        'active_pitches' => $activePitchesCount,
                        'limit' => $limits?->max_active_pitches
                    ]);
                    
                    // Send limit reached notification if not already sent recently
                    $this->sendLimitNotificationIfNeeded($user, 'pitches', $activePitchesCount, $limits?->max_active_pitches);
                    
                    return redirect()->route('subscription.index')
                        ->with('error', 'You have reached your active pitch limit. Upgrade to Pro for unlimited pitches.');
                }
                
                // Check monthly pitch limit for Pro Engineer
                if (!$user->canCreateMonthlyPitch()) {
                    Log::info('User blocked from creating monthly pitch due to subscription limits', [
                        'user_id' => $user->id,
                        'monthly_pitches_used' => $user->monthly_pitch_count,
                        'limit' => $limits?->max_monthly_pitches
                    ]);
                    
                    // Send limit reached notification if not already sent recently
                    $this->sendLimitNotificationIfNeeded($user, 'monthly_pitches', $user->monthly_pitch_count, $limits?->max_monthly_pitches);
                    
                    return redirect()->route('subscription.index')
                        ->with('error', 'You have reached your monthly pitch limit. This limit resets next month.');
                }
                break;
        }
        
        return $next($request);
    }

    /**
     * Send limit reached notification if not already sent recently
     */
    private function sendLimitNotificationIfNeeded($user, $limitType, $currentCount, $limit)
    {
        if (!$limit) {
            return; // No limit set, so don't send notification
        }
        
        // Create a cache key to prevent spamming notifications
        $cacheKey = "limit_notification_{$user->id}_{$limitType}";
        
        // Only send notification if we haven't sent one in the last 24 hours
        if (!Cache::has($cacheKey)) {
            $user->notify(new \App\Notifications\LimitReached($limitType, $currentCount, $limit));
            
            // Cache for 24 hours to prevent spam
            Cache::put($cacheKey, true, now()->addHours(24));
            
            Log::info('Sent limit reached notification', [
                'user_id' => $user->id,
                'limit_type' => $limitType,
                'current_count' => $currentCount,
                'limit' => $limit
            ]);
        }
    }
}
