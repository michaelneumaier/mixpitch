<?php

namespace App\Listeners;

use App\Models\Project;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LinkClientProjectsOnLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        // Check if there are any client management projects with this user's email
        // that aren't already linked to a user account
        $projects = Project::where('client_email', $user->email)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNull('client_user_id')
            ->get();

        if ($projects->count() > 0) {
            // Link all matching projects to this user
            Project::where('client_email', $user->email)
                ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                ->whereNull('client_user_id')
                ->update(['client_user_id' => $user->id]);

            // Note: Not assigning CLIENT role automatically to preserve regular dashboard access
            // Users will see client projects in their regular "My Work" section

            Log::info('Linked client management projects on login', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'projects_linked' => $projects->count(),
                'project_ids' => $projects->pluck('id')->toArray(),
                'assigned_client_role' => !$user->hasRole(\App\Models\User::ROLE_CLIENT),
            ]);
        }
    }
}