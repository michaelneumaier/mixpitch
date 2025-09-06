<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindClientProjectsSearch extends ZapierApiController
{
    /**
     * Search for client management projects
     *
     * This search action helps users find projects by client,
     * status, or other criteria.
     */
    public function search(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Validate search parameters
        $validated = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'client_email' => ['nullable', 'email'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'pitch_status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'is_prioritized' => ['nullable', 'boolean'],
            'is_private' => ['nullable', 'boolean'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 10;

        // Build the query
        $query = Project::with(['client', 'pitches'])
            ->where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);

        // Filter by client
        if (isset($validated['client_id'])) {
            $query->where('client_id', $validated['client_id']);
        } elseif (isset($validated['client_email'])) {
            // Support legacy projects and current ones
            $query->where(function ($q) use ($validated) {
                $q->where('client_email', $validated['client_email'])
                    ->orWhereHas('client', function ($clientQuery) use ($validated) {
                        $clientQuery->where('email', $validated['client_email']);
                    });
            });
        }

        // Filter by project name
        if (isset($validated['project_name'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%'.$validated['project_name'].'%')
                    ->orWhere('title', 'like', '%'.$validated['project_name'].'%');
            });
        }

        // Filter by project status
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by project flags
        if (isset($validated['is_prioritized'])) {
            $query->where('is_prioritized', $validated['is_prioritized']);
        }

        if (isset($validated['is_private'])) {
            $query->where('is_private', $validated['is_private']);
        }

        // Filter by date range
        if (isset($validated['created_after'])) {
            $query->where('created_at', '>=', $validated['created_after']);
        }

        if (isset($validated['created_before'])) {
            $query->where('created_at', '<=', $validated['created_before']);
        }

        // Filter by pitch status or payment status
        if (isset($validated['pitch_status']) || isset($validated['payment_status'])) {
            $query->whereHas('pitches', function ($pitchQuery) use ($validated) {
                if (isset($validated['pitch_status'])) {
                    $pitchQuery->where('status', $validated['pitch_status']);
                }
                if (isset($validated['payment_status'])) {
                    $pitchQuery->where('payment_status', $validated['payment_status']);
                }
            });
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        // Get results
        $projects = $query->limit($limit)->get();

        // Transform projects for Zapier
        $transformedProjects = $projects->map(function ($project) {
            $pitch = $project->pitches()->first();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'workflow_type' => $project->workflow_type,
                'project_type' => $project->project_type,
                'budget' => $project->budget,
                'payment_amount' => $project->payment_amount,
                'deadline' => $project->deadline?->toDateString(),
                'is_prioritized' => $project->is_prioritized,
                'is_private' => $project->is_private,
                'created_at' => $project->created_at->toIso8601String(),
                'updated_at' => $project->updated_at->toIso8601String(),

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
                    'last_contacted_at' => $project->client->last_contacted_at?->toIso8601String(),
                ] : [
                    // Legacy support
                    'email' => $project->client_email,
                    'name' => $project->client_name,
                ],

                // Pitch information
                'pitch' => $pitch ? [
                    'id' => $pitch->id,
                    'status' => $pitch->status,
                    'payment_status' => $pitch->payment_status,
                    'payment_amount' => $pitch->payment_amount,
                    'created_at' => $pitch->created_at->toIso8601String(),
                    'updated_at' => $pitch->updated_at->toIso8601String(),
                ] : null,

                // Project metrics
                'days_since_created' => $project->created_at->diffInDays(now()),
                'days_until_deadline' => $project->deadline
                    ? now()->diffInDays($project->deadline, false)
                    : null,
                'total_files' => $project->files()->count(),
                'total_pitch_files' => $pitch ? $pitch->files()->count() : 0,

                // URLs
                'producer_dashboard_url' => route('projects.show', $project),
                'client_portal_url' => $this->generateClientPortalUrl($project),

                // Status indicators
                'is_overdue' => $project->deadline && $project->deadline->isPast() && $project->status !== Project::STATUS_COMPLETED,
                'requires_attention' => $this->projectRequiresAttention($project, $pitch),
                'next_action' => $this->determineNextAction($project, $pitch),
            ];
        });

        // Log the search
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'searches.projects',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $transformedProjects->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($transformedProjects);
    }

    /**
     * Determine if a project requires attention
     */
    private function projectRequiresAttention(Project $project, $pitch): bool
    {
        // Project is overdue
        if ($project->deadline && $project->deadline->isPast() && $project->status !== Project::STATUS_COMPLETED) {
            return true;
        }

        // Pitch is ready for review
        if ($pitch && $pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW) {
            return true;
        }

        // Client has requested revisions
        if ($pitch && $pitch->status === \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED) {
            return true;
        }

        return false;
    }

    /**
     * Determine the next action needed for a project
     */
    private function determineNextAction(Project $project, $pitch): string
    {
        if (! $pitch) {
            return 'Create initial pitch';
        }

        switch ($pitch->status) {
            case \App\Models\Pitch::STATUS_PENDING:
                return 'Start working on project';

            case \App\Models\Pitch::STATUS_IN_PROGRESS:
                return 'Continue work and submit for review';

            case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
                return 'Waiting for client review';

            case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
                return 'Address client feedback';

            case \App\Models\Pitch::STATUS_APPROVED:
                return 'Finalize project completion';

            case \App\Models\Pitch::STATUS_COMPLETED:
                return 'Project complete';

            default:
                return 'Review project status';
        }
    }
}
