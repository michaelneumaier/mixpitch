<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Pitch;

class CheckPitchFileAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the pitch from the route
        $pitch = $request->route('pitch');
        
        // If pitch exists and is in pending status, restrict access
        if ($pitch && $pitch->status === Pitch::STATUS_PENDING) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'File management is not available for pending pitches'
                ], 403);
            }
            
            return redirect()->route('projects.pitches.show', [
                'project' => $pitch->project,
                'pitch' => $pitch
            ])->with('error', 'File management is not available until the pitch is approved by the project owner.');
        }
        
        return $next($request);
    }
}
