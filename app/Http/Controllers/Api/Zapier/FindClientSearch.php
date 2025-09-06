<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindClientSearch extends ZapierApiController
{
    /**
     * Search for clients by email, name, or company
     *
     * This is a search action that helps users find existing clients
     * to use in subsequent actions or to check if a client exists.
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
            'query' => ['required_without_all:email,name,company', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'status' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 10;

        // Build the query
        $query = Client::where('user_id', $user->id);

        // Search by specific email (exact match)
        if (isset($validated['email'])) {
            $query->where('email', $validated['email']);
        }
        // Search by name (partial match)
        elseif (isset($validated['name'])) {
            $query->where('name', 'like', '%'.$validated['name'].'%');
        }
        // Search by company (partial match)
        elseif (isset($validated['company'])) {
            $query->where('company', 'like', '%'.$validated['company'].'%');
        }
        // General search across multiple fields
        elseif (isset($validated['query'])) {
            $searchTerm = $validated['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('company', 'like', '%'.$searchTerm.'%')
                    ->orWhere('notes', 'like', '%'.$searchTerm.'%');
            });
        }

        // Filter by status if provided
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by tags if provided
        if (isset($validated['tags']) && count($validated['tags']) > 0) {
            foreach ($validated['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Order by relevance (most recently contacted first)
        $query->orderBy('last_contacted_at', 'desc')
            ->orderBy('updated_at', 'desc');

        // Get the results
        $clients = $query->limit($limit)->get();

        // Transform clients for Zapier
        $transformedClients = $clients->map(function ($client) {
            // Get latest project for context
            $latestProject = $client->projects()
                ->where('workflow_type', \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
                ->orderBy('created_at', 'desc')
                ->first();

            // Get active reminders count
            $activeReminders = $client->reminders()
                ->where('status', \App\Models\ClientReminder::STATUS_PENDING)
                ->count();

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

                // Additional context
                'days_since_contact' => $client->last_contacted_at
                    ? $client->last_contacted_at->diffInDays(now())
                    : null,
                'active_reminders_count' => $activeReminders,

                // Include latest project if available
                'latest_project' => $latestProject ? [
                    'id' => $latestProject->id,
                    'name' => $latestProject->name,
                    'status' => $latestProject->status,
                    'created_at' => $latestProject->created_at->toIso8601String(),
                ] : null,

                // Search relevance indicator
                'search_score' => $this->calculateSearchScore($client, $request->all()),
            ];
        });

        // Sort by search score if doing a general search
        if (isset($validated['query'])) {
            $transformedClients = $transformedClients->sortByDesc('search_score')->values();
        }

        // Log the search
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'searches.clients',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $transformedClients->count(),
                'status_code' => 200,
            ]);
        }

        // Return results
        // Note: Zapier expects an array, even for single results
        return $this->successResponse($transformedClients);
    }

    /**
     * Calculate a relevance score for search results
     */
    private function calculateSearchScore($client, $searchParams): float
    {
        $score = 0.0;

        // Exact email match gets highest score
        if (isset($searchParams['email']) && $client->email === $searchParams['email']) {
            $score += 10.0;
        }

        // Recent activity increases score
        if ($client->last_contacted_at) {
            $daysSinceContact = $client->last_contacted_at->diffInDays(now());
            if ($daysSinceContact < 7) {
                $score += 3.0;
            } elseif ($daysSinceContact < 30) {
                $score += 1.0;
            }
        }

        // Active clients get higher score
        if ($client->status === Client::STATUS_ACTIVE) {
            $score += 2.0;
        }

        // Clients with projects get higher score
        $score += min($client->total_projects * 0.5, 5.0);

        // High-value clients get higher score
        if ($client->total_spent > 1000) {
            $score += 2.0;
        }

        return $score;
    }
}
