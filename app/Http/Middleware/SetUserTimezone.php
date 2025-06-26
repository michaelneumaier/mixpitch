<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $timezone = $user->timezone ?? config('timezone.default');
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
        
        return $next($request);
    }
} 