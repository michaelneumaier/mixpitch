<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientProjectStatusTrigger extends ZapierApiController
{
    /**
     * Poll for client management projects with status changes
     *
     * This trigger fires when client project status changes or
     * when pitch status changes within client projects.
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

        // Get projects that were updated (but not newly created)
        $updatedProjects = Project::with(['client', 'pitches.events' => function ($query) use ($sinceDate) {
            $query->where('created_at', '>', $sinceDate)
                ->where('event_type', 'status_change')
                ->orderBy('created_at', 'desc');
        }])
            ->where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('updated_at', '>', $sinceDate)
            ->where('created_at', '<', $sinceDate) // Exclude newly created projects
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        // Also get pitches with recent status changes
        $pitchStatusChanges = Pitch::with(['project.client', 'events' => function ($query) use ($sinceDate) {
            $query->where('created_at', '>', $sinceDate)
                ->where('event_type', 'status_change')
                ->orderBy('created_at', 'desc');
        }])
            ->whereHas('project', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT);
            })
            ->whereHas('events', function ($query) use ($sinceDate) {
                $query->where('created_at', '>', $sinceDate)
                    ->where('event_type', 'status_change');
            })
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $statusUpdates = collect();

        // Process project updates
        foreach ($updatedProjects as $project) {
            $pitch = $project->pitches()->first();

            $statusUpdates->push([
                'id' => "project_{$project->id}",
                'type' => 'project_status_change',
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_status' => $project->status,
                'previous_status' => $this->detectPreviousProjectStatus($project),
                'updated_at' => $project->updated_at->toIso8601String(),

                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'title' => $project->title,
                    'status' => $project->status,
                    'workflow_type' => $project->workflow_type,
                    'budget' => $project->budget,
                    'payment_amount' => $project->payment_amount,
                    'deadline' => $project->deadline?->toDateString(),
                    'created_at' => $project->created_at->toIso8601String(),
                    'updated_at' => $project->updated_at->toIso8601String(),
                ],

                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'email' => $project->client->email,
                    'name' => $project->client->name,
                    'company' => $project->client->company,
                    'status' => $project->client->status,
                    'tags' => $project->client->tags,
                ] : [
                    'email' => $project->client_email,
                    'name' => $project->client_name,
                ],

                'pitch' => $pitch ? [
                    'id' => $pitch->id,
                    'status' => $pitch->status,
                    'payment_status' => $pitch->payment_status,
                    'updated_at' => $pitch->updated_at->toIso8601String(),
                ] : null,

                'producer_dashboard_url' => route('projects.show', $project),
                'client_portal_url' => $this->generateClientPortalUrl($project),
            ]);
        }

        // Process pitch status changes
        foreach ($pitchStatusChanges as $pitch) {
            $recentEvent = $pitch->events->first();

            $statusUpdates->push([
                'id' => "pitch_{$pitch->id}",
                'type' => 'pitch_status_change',
                'project_id' => $pitch->project->id,
                'project_name' => $pitch->project->name,
                'pitch_id' => $pitch->id,
                'pitch_status' => $pitch->status,
                'previous_pitch_status' => $recentEvent?->metadata['old_status'] ?? null,
                'payment_status' => $pitch->payment_status,
                'updated_at' => $pitch->updated_at->toIso8601String(),

                'project' => [
                    'id' => $pitch->project->id,
                    'name' => $pitch->project->name,
                    'title' => $pitch->project->title,
                    'status' => $pitch->project->status,
                    'workflow_type' => $pitch->project->workflow_type,
                    'budget' => $pitch->project->budget,
                    'payment_amount' => $pitch->project->payment_amount,
                    'deadline' => $pitch->project->deadline?->toDateString(),
                    'created_at' => $pitch->project->created_at->toIso8601String(),
                ],

                'client' => $pitch->project->client ? [
                    'id' => $pitch->project->client->id,
                    'email' => $pitch->project->client->email,
                    'name' => $pitch->project->client->name,
                    'company' => $pitch->project->client->company,
                    'status' => $pitch->project->client->status,
                    'tags' => $pitch->project->client->tags,
                ] : [
                    'email' => $pitch->project->client_email,
                    'name' => $pitch->project->client_name,
                ],

                'pitch' => [
                    'id' => $pitch->id,
                    'status' => $pitch->status,
                    'payment_status' => $pitch->payment_status,
                    'payment_amount' => $pitch->payment_amount,
                    'created_at' => $pitch->created_at->toIso8601String(),
                    'updated_at' => $pitch->updated_at->toIso8601String(),
                ],

                // Status-specific information
                'is_completion' => $pitch->status === Pitch::STATUS_COMPLETED,
                'is_client_approval' => $pitch->status === Pitch::STATUS_APPROVED,
                'is_ready_for_review' => $pitch->status === Pitch::STATUS_READY_FOR_REVIEW,
                'requires_client_action' => in_array($pitch->status, [
                    Pitch::STATUS_READY_FOR_REVIEW,
                    Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
                ]),

                'producer_dashboard_url' => route('projects.show', $pitch->project),
                'client_portal_url' => $this->generateClientPortalUrl($pitch->project),
            ]);
        }

        // Sort by updated_at and limit
        $finalUpdates = $statusUpdates
            ->sortByDesc('updated_at')
            ->take(100)
            ->values();

        // Log the request
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.projects.status_changed',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $finalUpdates->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($finalUpdates);
    }

    /**
     * Try to detect the previous project status based on common patterns
     */
    private function detectPreviousProjectStatus(Project $project): ?string
    {
        // This is a best-effort detection
        // In a real implementation, you might want to track status changes explicitly

        if ($project->status === Project::STATUS_COMPLETED && $project->completed_at) {
            // If recently completed, likely came from in_progress or ready_for_review
            return Project::STATUS_IN_PROGRESS;
        }

        return null; // Unknown previous status
    }
}
