<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        switch ($feature) {
            case 'create_project':
                if (!$user->canCreateProject()) {
                    return redirect()->route('pricing')
                        ->with('error', 'You have reached your project limit. Upgrade to Pro to create unlimited projects.');
                }
                break;
                
            case 'create_pitch':
                $project = $request->route('project');
                if ($project && !$user->canCreatePitch($project)) {
                    return redirect()->back()
                        ->with('error', 'You have reached your active pitch limit. Upgrade to Pro for unlimited pitches.');
                }
                
                if (!$user->canCreateMonthlyPitch()) {
                    return redirect()->back()
                        ->with('error', 'You have reached your monthly pitch limit. Upgrade to Pro for unlimited monthly pitches.');
                }
                break;
        }

        return $next($request);
    }
}
