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

            // IMPORTANT: Only set config for display purposes
            // DO NOT call date_default_timezone_set() as it affects database storage
            // All timestamps should be stored in UTC per Laravel best practices
            config(['app.timezone' => $timezone]);
        }

        return $next($request);
    }
}
