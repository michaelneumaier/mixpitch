<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientUpdatedTrigger extends ZapierApiController
{
    /**
     * Poll for recently updated clients
     *
     * This is a polling trigger that returns clients updated since the last poll.
     * Zapier will typically poll every 15 minutes.
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

        // Get the 'since' parameter (ISO 8601 timestamp)
        // If not provided, default to 15 minutes ago (typical Zapier polling interval)
        $since = $request->input('since', now()->subMinutes(15)->toIso8601String());

        try {
            $sinceDate = \Carbon\Carbon::parse($since);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid since parameter. Please provide ISO 8601 timestamp.', 400);
        }

        // Query for clients updated since the given timestamp
        $clients = Client::where('user_id', $user->id)
            ->where('updated_at', '>', $sinceDate)
            ->where('created_at', '<', $sinceDate) // Exclude newly created clients (handled by NewClientTrigger)
            ->orderBy('updated_at', 'desc')
            ->limit(100) // Zapier recommends limiting to 100 items
            ->get();

        // Transform clients for Zapier
        $transformedClients = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
                'company' => $client->company,
                'phone' => $client->phone,
                'status' => $client->status,
                'tags' => $client->tags,
                'timezone' => $client->timezone ?: 'UTC',
                'notes' => $client->notes,
                'total_spent' => $client->total_spent,
                'total_projects' => $client->total_projects,
                'last_contacted_at' => $client->last_contacted_at?->toIso8601String(),
                'created_at' => $client->created_at->toIso8601String(),
                'updated_at' => $client->updated_at->toIso8601String(),

                // Include what changed if we can detect it
                'changes_detected' => $this->detectChanges($client),

                // Include latest project if available
                'latest_project' => $this->getLatestProject($client),
            ];
        });

        // Log the request for usage tracking
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.clients.updated',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $transformedClients->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($transformedClients);
    }

    /**
     * Detect what might have changed on the client
     * This is a best-effort detection based on common update patterns
     */
    private function detectChanges(Client $client): array
    {
        $changes = [];

        // If last_contacted_at was updated in the last minute, it was likely just marked as contacted
        if ($client->last_contacted_at &&
            $client->last_contacted_at->diffInMinutes($client->updated_at) < 1) {
            $changes[] = 'contacted';
        }

        // If total_spent or total_projects changed, it's likely a project completion
        if ($client->wasChanged(['total_spent', 'total_projects'])) {
            $changes[] = 'project_activity';
        }

        return $changes;
    }

    /**
     * Get the latest project for a client
     */
    private function getLatestProject(Client $client): ?array
    {
        $project = $client->projects()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $project) {
            return null;
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'created_at' => $project->created_at->toIso8601String(),
        ];
    }
}
