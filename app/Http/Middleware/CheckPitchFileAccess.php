<?php

namespace App\Http\Middleware;

use App\Helpers\RouteHelpers;
use App\Models\Pitch;
use App\Models\PitchFile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPitchFileAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $file = $request->route('file');

        if (! $file instanceof PitchFile) {
            Log::warning('Pitch file not found in middleware.', ['file_param' => $request->route('file')]);
            abort(404, 'File not found.');
        }

        $pitch = $file->pitch; // Eager load might be better

        if (! $pitch instanceof Pitch) {
            Log::error('Pitch relationship missing for PitchFile in middleware.', ['file_id' => $file->id]);

            // Redirect to dashboard or a generic error page?
            // Use RouteHelpers for URL generation if redirecting to pitch
            return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('error', 'Could not verify file access due to missing pitch data.');
        }

        // Allow access if user is the pitch owner OR the project owner
        if (Auth::id() === $pitch->user_id || Auth::id() === $pitch->project->user_id) {
            return $next($request);
        }

        Log::warning('Unauthorized access attempt to pitch file.', [
            'file_id' => $file->id,
            'pitch_id' => $pitch->id,
            'user_id' => Auth::id(),
            'pitch_owner_id' => $pitch->user_id,
            'project_owner_id' => $pitch->project->user_id,
        ]);
        abort(403, 'You do not have permission to access this file.');
    }
}
