<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use App\Models\ClientReminder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddClientReminderAction extends ZapierApiController
{
    /**
     * Create a new reminder for a client
     *
     * This action allows creating client reminders from external triggers
     * such as calendar events, CRM activities, or project milestones.
     */
    public function create(Request $request): JsonResponse
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
            'client_id' => ['required_without:client_email', 'integer'],
            'client_email' => ['required_without:client_id', 'email'], // Allow finding client by email

            'due_at' => ['required', 'date', 'after:now'],
            'note' => ['required', 'string', 'max:1000'],

            // Optional fields
            'due_in_hours' => ['nullable', 'integer', 'min:1', 'max:8760'], // Alternative to due_at
            'due_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],   // Alternative to due_at
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
        ]);

        // Find the client
        if (isset($validated['client_id'])) {
            $client = Client::where('user_id', $user->id)
                ->where('id', $validated['client_id'])
                ->first();
        } else {
            $client = Client::where('user_id', $user->id)
                ->where('email', $validated['client_email'])
                ->first();
        }

        if (! $client) {
            // If client doesn't exist by email, optionally create them
            if (isset($validated['client_email']) && $request->boolean('create_client_if_missing', false)) {
                $client = Client::create([
                    'user_id' => $user->id,
                    'email' => $validated['client_email'],
                    'name' => $request->input('client_name'),
                    'company' => $request->input('client_company'),
                    'status' => Client::STATUS_ACTIVE,
                ]);
            } else {
                return $this->errorResponse('Client not found', 404);
            }
        }

        // Calculate due date
        $dueAt = null;

        if (isset($validated['due_in_hours'])) {
            $dueAt = now()->addHours($validated['due_in_hours']);
        } elseif (isset($validated['due_in_days'])) {
            $dueAt = now()->addDays($validated['due_in_days']);
        } else {
            try {
                $dueAt = Carbon::parse($validated['due_at']);
            } catch (\Exception $e) {
                return $this->errorResponse('Invalid due_at format. Please use ISO 8601 format.', 400);
            }
        }

        // Create the reminder
        $reminder = ClientReminder::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'due_at' => $dueAt,
            'note' => $validated['note'],
            'status' => ClientReminder::STATUS_PENDING,
        ]);

        // Get latest project for context
        $latestProject = $client->projects()
            ->where('workflow_type', \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->orderBy('created_at', 'desc')
            ->first();

        // Log the action
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'actions.reminders.create',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => 1,
                'status_code' => 200,
            ]);
        }

        return $this->successResponse([
            'id' => $reminder->id,
            'due_at' => $reminder->due_at->toIso8601String(),
            'note' => $reminder->note,
            'status' => $reminder->status,
            'created_at' => $reminder->created_at->toIso8601String(),

            // Time-related fields
            'due_in_hours' => $reminder->due_at->diffInHours(now()),
            'due_in_days' => $reminder->due_at->diffInDays(now()),
            'due_in_words' => $reminder->due_at->diffForHumans(),

            // Include full client information
            'client' => [
                'id' => $client->id,
                'email' => $client->email,
                'name' => $client->name,
                'company' => $client->company,
                'phone' => $client->phone,
                'status' => $client->status,
                'tags' => $client->tags,
                'total_spent' => $client->total_spent,
                'total_projects' => $client->total_projects,
                'last_contacted_at' => $client->last_contacted_at?->toIso8601String(),
                'was_created' => $client->wasRecentlyCreated,
            ],

            // Include latest project if available
            'latest_project' => $latestProject ? [
                'id' => $latestProject->id,
                'name' => $latestProject->name,
                'status' => $latestProject->status,
                'created_at' => $latestProject->created_at->toIso8601String(),
            ] : null,

            // Success confirmation
            'was_created' => true,
        ], 'Reminder created successfully');
    }
}
