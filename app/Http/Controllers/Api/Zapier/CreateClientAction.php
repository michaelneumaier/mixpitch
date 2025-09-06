<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateClientAction extends ZapierApiController
{
    /**
     * Create a new client (Zapier action endpoint)
     * 
     * POST /api/zapier/actions/clients/create
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (!$this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        try {
            // Use existing Client model validation rules
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'name' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string|max:1000',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'timezone' => 'nullable|string|max:50',
            ]);

            // Process tags if provided
            if (isset($validated['tags']) && is_array($validated['tags'])) {
                // Zapier might send tags as array or comma-separated string
                $validated['tags'] = array_filter($validated['tags']);
            }

            // Leverage existing Client::firstOrCreate pattern
            $client = Client::firstOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'email' => $validated['email']
                ],
                array_merge($validated, [
                    'status' => Client::STATUS_ACTIVE,
                    'timezone' => $validated['timezone'] ?? 'UTC',
                ])
            );

            // Format response for Zapier
            $responseData = [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'company' => $client->company,
                'phone' => $client->phone,
                'status' => $client->status,
                'timezone' => $client->timezone,
                'notes' => $client->notes,
                'tags' => $client->tags,
                'created_at' => $client->created_at->toISOString(),
                'updated_at' => $client->updated_at->toISOString(),
                'was_created' => $client->wasRecentlyCreated,
            ];

            $message = $client->wasRecentlyCreated 
                ? 'Client created successfully' 
                : 'Client already exists';

            return $this->successResponse($responseData, $message);

        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation failed: ' . implode(', ', array_flatten($e->errors())), 
                422
            );
        } catch (\Exception $e) {
            \Log::error('Zapier CreateClientAction error', [
                'user_id' => $request->user()->id,
                'request_data' => $this->sanitizeRequestData($request->all()),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to create client', 500);
        }
    }
}