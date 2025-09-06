<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientProjectCreatedTrigger extends ZapierApiController
{
    /**
     * Poll for newly created client management projects
     *
     * This trigger helps users automate workflows when new projects
     * are created for clients.
     */
    public function poll(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Get the 'since' parameter
        $since = $request->input('since', now()->subMinutes(15)->toIso8601String());

        try {
            $sinceDate = \Carbon\Carbon::parse($since);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid since parameter. Please provide ISO 8601 timestamp.', 400);
        }

        // Query for new client management projects
        $projects = Project::with(['client', 'pitches'])
            ->where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('created_at', '>', $sinceDate)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Transform projects for Zapier
        $transformedProjects = $projects->map(function ($project) {
            // Get the initial pitch (automatically created for client management)
            $pitch = $project->pitches()->first();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'workflow_type' => $project->workflow_type,
                'created_at' => $project->created_at->toIso8601String(),
                'updated_at' => $project->updated_at->toIso8601String(),

                // Project details
                'project_type' => $project->project_type,
                'budget' => $project->budget,
                'payment_amount' => $project->payment_amount,
                'deadline' => $project->deadline?->toDateString(),
                'is_prioritized' => $project->is_prioritized,
                'is_private' => $project->is_private,

                // Client information
                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'email' => $project->client->email,
                    'name' => $project->client->name,
                    'company' => $project->client->company,
                    'phone' => $project->client->phone,
                    'status' => $project->client->status,
                    'tags' => $project->client->tags,
                    'total_spent' => $project->client->total_spent,
                    'total_projects' => $project->client->total_projects,
                ] : [
                    // Legacy support for projects without client_id
                    'email' => $project->client_email,
                    'name' => $project->client_name,
                ],

                // Pitch information
                'pitch' => $pitch ? [
                    'id' => $pitch->id,
                    'status' => $pitch->status,
                    'payment_status' => $pitch->payment_status,
                    'created_at' => $pitch->created_at->toIso8601String(),
                ] : null,

                // URLs
                'producer_dashboard_url' => route('projects.show', $project),
                'client_portal_url' => $this->generateClientPortalUrl($project),

                // Additional metadata
                'has_client_access' => $project->auto_allow_access,
                'requires_license' => $project->requires_license_agreement,
                'total_files' => $project->files()->count(),
            ];
        });

        // Log the request
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.projects.client_created',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $transformedProjects->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($transformedProjects);
    }
}
