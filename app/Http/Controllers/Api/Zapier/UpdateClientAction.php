<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateClientAction extends ZapierApiController
{
    /**
     * Update an existing client
     *
     * This action allows updating client information from external systems
     */
    public function update(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Validate input
        $validated = $request->validate([
            'id' => ['required_without:email', 'integer'],
            'email' => ['required_without:id', 'email'], // Allow finding by email as fallback

            // Fields that can be updated
            'name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['nullable', Rule::in([Client::STATUS_ACTIVE, Client::STATUS_INACTIVE, Client::STATUS_BLOCKED])],
            'timezone' => ['nullable', 'timezone'],

            // Special actions
            'mark_as_contacted' => ['nullable', 'boolean'],
            'append_tags' => ['nullable', 'boolean'], // If true, append tags instead of replacing
        ]);

        // Find the client by ID or email
        if (isset($validated['id'])) {
            $client = Client::where('user_id', $user->id)
                ->where('id', $validated['id'])
                ->first();
        } else {
            $client = Client::where('user_id', $user->id)
                ->where('email', $validated['email'])
                ->first();
        }

        if (! $client) {
            return $this->errorResponse('Client not found', 404);
        }

        // Build update data
        $updateData = [];

        // Handle each field that can be updated
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }

        if (array_key_exists('company', $validated)) {
            $updateData['company'] = $validated['company'];
        }

        if (array_key_exists('phone', $validated)) {
            $updateData['phone'] = $validated['phone'];
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (isset($validated['timezone'])) {
            $updateData['timezone'] = $validated['timezone'];
        }

        // Handle tags (append or replace)
        if (isset($validated['tags'])) {
            if ($request->boolean('append_tags', false) && is_array($client->tags)) {
                // Append tags to existing ones
                $existingTags = $client->tags ?: [];
                $newTags = array_unique(array_merge($existingTags, $validated['tags']));
                $updateData['tags'] = array_values($newTags);
            } else {
                // Replace tags
                $updateData['tags'] = $validated['tags'];
            }
        }

        // Update the client
        $client->update($updateData);

        // Handle special actions
        if ($request->boolean('mark_as_contacted', false)) {
            $client->markAsContacted();
        }

        // Refresh client data to get updated values
        $client->refresh();

        // Get latest project for context
        $latestProject = $client->projects()
            ->where('workflow_type', \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->orderBy('created_at', 'desc')
            ->first();

        // Log the action
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'actions.clients.update',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => 1,
                'status_code' => 200,
            ]);
        }

        return $this->successResponse([
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

            // Indicate what was updated
            'fields_updated' => array_keys($updateData),
            'was_contacted' => $request->boolean('mark_as_contacted', false),

            // Include latest project if available
            'latest_project' => $latestProject ? [
                'id' => $latestProject->id,
                'name' => $latestProject->name,
                'status' => $latestProject->status,
                'created_at' => $latestProject->created_at->toIso8601String(),
            ] : null,
        ], 'Client updated successfully');
    }
}
