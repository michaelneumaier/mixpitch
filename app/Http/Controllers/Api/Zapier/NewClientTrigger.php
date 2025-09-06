<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewClientTrigger extends ZapierApiController
{
    /**
     * Poll for new clients (Zapier trigger endpoint)
     * 
     * GET /api/zapier/triggers/clients/new
     */
    public function poll(Request $request): JsonResponse
    {
        if (!$this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (!$this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        try {
            // Get 'since' parameter for polling (defaults to 15 minutes ago)
            $since = $request->get('since', now()->subMinutes(15));
            
            // Validate and parse the since parameter
            if (is_string($since)) {
                try {
                    $since = \Carbon\Carbon::parse($since);
                } catch (\Exception $e) {
                    return $this->errorResponse('Invalid since parameter format', 400);
                }
            }

            // Leverage existing Client model with all relationships
            $clients = Client::where('user_id', $request->user()->id)
                ->where('created_at', '>', $since)
                ->with(['projects']) // Existing relationship
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            // Format response data for Zapier
            $data = $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'company' => $client->company,
                    'phone' => $client->phone,
                    'status' => $client->status, // Existing status field
                    'timezone' => $client->timezone,
                    'notes' => $client->notes,
                    'tags' => $client->tags, // Existing tags support
                    'created_at' => $client->created_at->toISOString(),
                    'updated_at' => $client->updated_at->toISOString(),
                    'total_projects' => $client->total_projects, // Existing computed field
                    'last_contacted_at' => $client->last_contacted_at?->toISOString(),
                ];
            });

            return $this->successResponse($data->toArray());

        } catch (\Exception $e) {
            \Log::error('Zapier NewClientTrigger error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to retrieve clients', 500);
        }
    }
}