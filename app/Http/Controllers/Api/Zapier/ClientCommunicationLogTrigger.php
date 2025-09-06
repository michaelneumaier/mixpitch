<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use App\Models\ClientReminder;
use App\Models\EmailAudit;
use App\Models\EmailEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientCommunicationLogTrigger extends ZapierApiController
{
    /**
     * Client Communication Log Trigger
     *
     * This trigger detects various client communication events:
     * - Email communications (sent/delivered/opened/clicked)
     * - Client reminder completions
     * - Client status changes that indicate communication
     * - Manual contact updates (last_contacted_at changes)
     *
     * Useful for:
     * - CRM activity tracking
     * - Communication analytics
     * - Follow-up automation
     * - Client engagement monitoring
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
        $sinceParam = $request->get('since');

        // Default to 15 minutes ago for polling
        $sinceDate = $sinceParam
            ? Carbon::parse($sinceParam)
            : Carbon::now()->subMinutes(15);

        $communications = [];

        // 1. Email Communications
        $emailCommunications = $this->getEmailCommunications($user->id, $sinceDate);
        $communications = array_merge($communications, $emailCommunications);

        // 2. Reminder Completions
        $reminderCompletions = $this->getReminderCompletions($user->id, $sinceDate);
        $communications = array_merge($communications, $reminderCompletions);

        // 3. Manual Contact Updates
        $contactUpdates = $this->getContactUpdates($user->id, $sinceDate);
        $communications = array_merge($communications, $contactUpdates);

        // 4. Client Status Changes (that indicate communication)
        $statusChanges = $this->getCommunicationStatusChanges($user->id, $sinceDate);
        $communications = array_merge($communications, $statusChanges);

        // Sort by created_at descending
        usort($communications, function ($a, $b) {
            return strtotime($b['occurred_at']) - strtotime($a['occurred_at']);
        });

        // Log the request
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.communications.log',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => count($communications),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($communications);
    }

    /**
     * Get email communications from email_audits and email_events
     */
    private function getEmailCommunications(int $userId, Carbon $since): array
    {
        $communications = [];

        // Get email audits (sent emails) and match with client emails
        $emailAudits = EmailAudit::where('created_at', '>', $since)
            ->where('status', '!=', 'failed')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($emailAudits as $email) {
            // Try to match email to a client
            $client = Client::where('user_id', $userId)
                ->where('email', $email->email)
                ->first();

            if ($client) {
                $communications[] = [
                    'id' => "email_sent_{$email->id}",
                    'type' => 'email_sent',
                    'communication_method' => 'email',
                    'direction' => 'outbound',
                    'occurred_at' => $email->created_at->toIso8601String(),
                    'client' => [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'company' => $client->company,
                        'status' => $client->status,
                        'tags' => $client->tags ?? [],
                    ],
                    'communication_details' => [
                        'subject' => $email->subject,
                        'status' => $email->status,
                        'message_id' => $email->message_id,
                        'recipient_name' => $email->recipient_name,
                        'content_preview' => $this->getContentPreview($email->content),
                    ],
                    'producer_dashboard_url' => route('clients.show', $client),
                ];
            }
        }

        // Get email events (opens, clicks, etc.)
        $emailEvents = EmailEvent::where('created_at', '>', $since)
            ->whereIn('event_type', ['opened', 'clicked', 'delivered', 'bounced', 'complained'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($emailEvents as $event) {
            // Try to match email to a client
            $client = Client::where('user_id', $userId)
                ->where('email', $event->email)
                ->first();

            if ($client) {
                $communications[] = [
                    'id' => "email_event_{$event->id}",
                    'type' => "email_{$event->event_type}",
                    'communication_method' => 'email',
                    'direction' => 'engagement',
                    'occurred_at' => $event->created_at->toIso8601String(),
                    'client' => [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'company' => $client->company,
                        'status' => $client->status,
                        'tags' => $client->tags ?? [],
                    ],
                    'communication_details' => [
                        'event_type' => $event->event_type,
                        'email_type' => $event->email_type,
                        'message_id' => $event->message_id,
                        'engagement_score' => $this->calculateEngagementScore($event->event_type),
                        'metadata' => $event->metadata ? json_decode($event->metadata, true) : null,
                    ],
                    'is_positive_engagement' => in_array($event->event_type, ['opened', 'clicked', 'delivered']),
                    'producer_dashboard_url' => route('clients.show', $client),
                ];
            }
        }

        return $communications;
    }

    /**
     * Get completed client reminders
     */
    private function getReminderCompletions(int $userId, Carbon $since): array
    {
        $communications = [];

        // Get recently completed reminders
        $completedReminders = ClientReminder::with('client')
            ->where('user_id', $userId)
            ->where('status', ClientReminder::STATUS_COMPLETED)
            ->where('updated_at', '>', $since)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($completedReminders as $reminder) {
            if ($reminder->client) {
                $communications[] = [
                    'id' => "reminder_completed_{$reminder->id}",
                    'type' => 'reminder_completed',
                    'communication_method' => 'reminder',
                    'direction' => 'internal',
                    'occurred_at' => $reminder->updated_at->toIso8601String(),
                    'client' => [
                        'id' => $reminder->client->id,
                        'email' => $reminder->client->email,
                        'name' => $reminder->client->name,
                        'company' => $reminder->client->company,
                        'status' => $reminder->client->status,
                        'tags' => $reminder->client->tags ?? [],
                    ],
                    'communication_details' => [
                        'reminder_note' => $reminder->note,
                        'due_at' => $reminder->due_at->toIso8601String(),
                        'completed_at' => $reminder->updated_at->toIso8601String(),
                        'days_since_due' => $reminder->due_at->diffInDays(now(), false),
                    ],
                    'follow_up_suggested' => $reminder->due_at->isPast() && $reminder->due_at->diffInDays(now()) > 7,
                    'producer_dashboard_url' => route('clients.show', $reminder->client),
                ];
            }
        }

        return $communications;
    }

    /**
     * Get manual contact updates (last_contacted_at changes)
     */
    private function getContactUpdates(int $userId, Carbon $since): array
    {
        $communications = [];

        // Get clients with recently updated last_contacted_at
        $contactedClients = Client::where('user_id', $userId)
            ->where('last_contacted_at', '>', $since)
            ->where('updated_at', '>', $since)
            ->orderBy('last_contacted_at', 'desc')
            ->get();

        foreach ($contactedClients as $client) {
            // Only include if last_contacted_at was actually updated recently
            if ($client->last_contacted_at && $client->last_contacted_at->gt($since)) {
                $communications[] = [
                    'id' => "contact_update_{$client->id}_".$client->last_contacted_at->timestamp,
                    'type' => 'manual_contact_update',
                    'communication_method' => 'manual_update',
                    'direction' => 'internal',
                    'occurred_at' => $client->last_contacted_at->toIso8601String(),
                    'client' => [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'company' => $client->company,
                        'status' => $client->status,
                        'tags' => $client->tags ?? [],
                        'total_spent' => floatval($client->total_spent ?? 0),
                        'total_projects' => $client->total_projects ?? 0,
                    ],
                    'communication_details' => [
                        'contact_method' => 'manual', // Could be enhanced to detect method
                        'updated_at' => $client->updated_at->toIso8601String(),
                        'days_since_last_contact' => $this->calculateDaysSinceLastContact($client),
                    ],
                    'is_regular_contact' => $this->isRegularContact($client),
                    'producer_dashboard_url' => route('clients.show', $client),
                ];
            }
        }

        return $communications;
    }

    /**
     * Get client status changes that indicate communication
     */
    private function getCommunicationStatusChanges(int $userId, Carbon $since): array
    {
        $communications = [];

        // Get clients with status changes to active (often indicates contact)
        $reactivatedClients = Client::where('user_id', $userId)
            ->where('status', Client::STATUS_ACTIVE)
            ->where('updated_at', '>', $since)
            ->get();

        foreach ($reactivatedClients as $client) {
            // Check if this was a status change from inactive/prospect to active
            // This is a simplified check - in production you might want to track status history
            if ($client->updated_at->gt($since)) {
                $communications[] = [
                    'id' => "status_change_{$client->id}_active_".$client->updated_at->timestamp,
                    'type' => 'client_activated',
                    'communication_method' => 'status_change',
                    'direction' => 'internal',
                    'occurred_at' => $client->updated_at->toIso8601String(),
                    'client' => [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'company' => $client->company,
                        'status' => $client->status,
                        'tags' => $client->tags ?? [],
                        'total_spent' => floatval($client->total_spent ?? 0),
                        'total_projects' => $client->total_projects ?? 0,
                    ],
                    'communication_details' => [
                        'status_change' => 'activated',
                        'updated_at' => $client->updated_at->toIso8601String(),
                        'likely_communication' => true,
                    ],
                    'suggests_engagement' => true,
                    'producer_dashboard_url' => route('clients.show', $client),
                ];
            }
        }

        return $communications;
    }

    // Helper Methods

    /**
     * Get a preview of email content (first 100 characters)
     */
    private function getContentPreview(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        // Strip HTML tags and get first 100 characters
        $plainText = strip_tags($content);

        return strlen($plainText) > 100 ? substr($plainText, 0, 100).'...' : $plainText;
    }

    /**
     * Calculate engagement score for email events
     */
    private function calculateEngagementScore(string $eventType): int
    {
        $scores = [
            'delivered' => 10,
            'opened' => 25,
            'clicked' => 50,
            'bounced' => -10,
            'complained' => -50,
        ];

        return $scores[$eventType] ?? 0;
    }

    /**
     * Calculate days since last contact before this update
     */
    private function calculateDaysSinceLastContact(Client $client): ?int
    {
        // This would ideally look at previous contact history
        // For now, we'll use created_at as a baseline
        if ($client->last_contacted_at && $client->created_at) {
            return $client->created_at->diffInDays($client->last_contacted_at);
        }

        return null;
    }

    /**
     * Determine if this client has regular contact patterns
     */
    private function isRegularContact(Client $client): bool
    {
        // Simple heuristic: active clients with multiple projects likely have regular contact
        return $client->status === Client::STATUS_ACTIVE &&
               ($client->total_projects ?? 0) > 1;
    }
}
