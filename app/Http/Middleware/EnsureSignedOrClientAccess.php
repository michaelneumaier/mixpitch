<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureSignedOrClientAccess
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Project|null $project */
        $project = $request->route('project');

        // When using implicit binding with :id, Laravel may pass the id; resolve to model
        if ($project && ! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project || ! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Allow if signed URL is valid
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        // Allow if authenticated client matches this project
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();

            $isClient = method_exists($user, 'hasRole')
                ? $user->hasRole(User::ROLE_CLIENT)
                : ($user->role ?? null) === User::ROLE_CLIENT;

            if ($isClient && ($project->client_user_id === $user->id || $project->client_email === $user->email)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}


