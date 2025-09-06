<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\ClientReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientReminderDueTrigger extends ZapierApiController
{
    /**
     * Poll for client reminders that are due or overdue
     *
     * This trigger helps users automate follow-ups with clients
     * by notifying when reminders become due.
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

        // Get the 'since' parameter to avoid duplicates
        $since = $request->input('since', now()->subMinutes(15)->toIso8601String());

        try {
            $sinceDate = \Carbon\Carbon::parse($since);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid since parameter. Please provide ISO 8601 timestamp.', 400);
        }

        // Query for reminders that are:
        // 1. Due (due_at is in the past)
        // 2. Not yet completed
        // 3. Created or updated since last poll (to avoid duplicates)
        $reminders = ClientReminder::with('client')
            ->where('user_id', $user->id)
            ->where('status', ClientReminder::STATUS_PENDING)
            ->where('due_at', '<=', now())
            ->where(function ($query) use ($sinceDate) {
                $query->where('due_at', '>', $sinceDate)
                    ->orWhere('updated_at', '>', $sinceDate);
            })
            ->orderBy('due_at', 'asc')
            ->limit(100)
            ->get();

        // Transform reminders for Zapier
        $transformedReminders = $reminders->map(function ($reminder) {
            return [
                'id' => $reminder->id,
                'due_at' => $reminder->due_at->toIso8601String(),
                'note' => $reminder->note,
                'status' => $reminder->status,
                'snooze_until' => $reminder->snooze_until?->toIso8601String(),
                'created_at' => $reminder->created_at->toIso8601String(),

                // Time-related fields
                'is_overdue' => $reminder->due_at->isPast(),
                'hours_overdue' => max(0, $reminder->due_at->diffInHours(now())),
                'due_in_words' => $reminder->due_at->diffForHumans(),

                // Include full client information
                'client' => $reminder->client ? [
                    'id' => $reminder->client->id,
                    'email' => $reminder->client->email,
                    'name' => $reminder->client->name,
                    'company' => $reminder->client->company,
                    'phone' => $reminder->client->phone,
                    'status' => $reminder->client->status,
                    'tags' => $reminder->client->tags,
                    'total_spent' => $reminder->client->total_spent,
                    'total_projects' => $reminder->client->total_projects,
                    'last_contacted_at' => $reminder->client->last_contacted_at?->toIso8601String(),
                ] : null,

                // Include latest project for context
                'latest_project' => $this->getLatestClientProject($reminder->client),
            ];
        });

        // Also check for snoozed reminders that are now due
        $snoozedReminders = ClientReminder::with('client')
            ->where('user_id', $user->id)
            ->where('status', ClientReminder::STATUS_SNOOZED)
            ->where('snooze_until', '<=', now())
            ->where('snooze_until', '>', $sinceDate)
            ->orderBy('snooze_until', 'asc')
            ->limit(100 - $transformedReminders->count())
            ->get();

        // Transform snoozed reminders
        $transformedSnoozed = $snoozedReminders->map(function ($reminder) {
            $data = [
                'id' => $reminder->id,
                'due_at' => $reminder->due_at->toIso8601String(),
                'note' => $reminder->note,
                'status' => 'snoozed_now_due', // Special status to indicate it was snoozed but now due
                'snooze_until' => $reminder->snooze_until?->toIso8601String(),
                'created_at' => $reminder->created_at->toIso8601String(),

                'is_overdue' => true,
                'hours_overdue' => max(0, $reminder->snooze_until->diffInHours(now())),
                'due_in_words' => 'Snooze period ended '.$reminder->snooze_until->diffForHumans(),

                'client' => $reminder->client ? [
                    'id' => $reminder->client->id,
                    'email' => $reminder->client->email,
                    'name' => $reminder->client->name,
                    'company' => $reminder->client->company,
                    'phone' => $reminder->client->phone,
                    'status' => $reminder->client->status,
                    'tags' => $reminder->client->tags,
                    'total_spent' => $reminder->client->total_spent,
                    'total_projects' => $reminder->client->total_projects,
                    'last_contacted_at' => $reminder->client->last_contacted_at?->toIso8601String(),
                ] : null,

                'latest_project' => $this->getLatestClientProject($reminder->client),
            ];

            return $data;
        });

        // Combine and sort all reminders
        $allReminders = $transformedReminders->concat($transformedSnoozed)
            ->sortBy('due_at')
            ->values();

        // Log the request for usage tracking
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.reminders.due',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $allReminders->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($allReminders);
    }

    /**
     * Get the latest project for a client
     */
    private function getLatestClientProject($client): ?array
    {
        if (! $client) {
            return null;
        }

        $project = $client->projects()
            ->where('workflow_type', \App\Models\Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
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
