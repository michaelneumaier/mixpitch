<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Models\ZapierWebhook;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZapierWebhookService
{
    /**
     * Event type constants
     */
    const EVENT_CLIENT_CREATED = 'client.created';

    const EVENT_CLIENT_UPDATED = 'client.updated';

    const EVENT_CLIENT_REMINDER_DUE = 'client.reminder_due';

    const EVENT_PROJECT_CREATED = 'project.created';

    const EVENT_PROJECT_STATUS_CHANGED = 'project.status_changed';

    const EVENT_PITCH_STATUS_CHANGED = 'pitch.status_changed';

    const EVENT_REVENUE_MILESTONE = 'revenue.milestone';

    const EVENT_COMMUNICATION_LOG = 'communication.logged';

    /**
     * Register a webhook for a specific event type
     */
    public function registerWebhook(User $user, string $eventType, string $webhookUrl, array $metadata = []): ZapierWebhook
    {
        // Deactivate any existing webhooks for this event type and user
        ZapierWebhook::where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->update(['is_active' => false]);

        // Create new webhook
        return ZapierWebhook::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'webhook_url' => $webhookUrl,
            'is_active' => true,
            'metadata' => $metadata,
            'trigger_count' => 0,
        ]);
    }

    /**
     * Deregister a webhook
     */
    public function deregisterWebhook(User $user, string $eventType): bool
    {
        return ZapierWebhook::where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * Trigger client created webhook
     */
    public function triggerClientCreated(Client $client): void
    {
        $data = [
            'id' => $client->id,
            'email' => $client->email,
            'name' => $client->name,
            'company' => $client->company,
            'phone' => $client->phone,
            'status' => $client->status,
            'tags' => $client->tags ?? [],
            'created_at' => $client->created_at->toIso8601String(),
            'total_projects' => $client->total_projects ?? 0,
            'total_spent' => floatval($client->total_spent ?? 0),
            'producer_dashboard_url' => route('clients.show', $client),
        ];

        $this->sendWebhook($client->user_id, self::EVENT_CLIENT_CREATED, $data);
    }

    /**
     * Trigger client updated webhook
     */
    public function triggerClientUpdated(Client $client): void
    {
        $data = [
            'id' => $client->id,
            'email' => $client->email,
            'name' => $client->name,
            'company' => $client->company,
            'phone' => $client->phone,
            'status' => $client->status,
            'tags' => $client->tags ?? [],
            'updated_at' => $client->updated_at->toIso8601String(),
            'total_projects' => $client->total_projects ?? 0,
            'total_spent' => floatval($client->total_spent ?? 0),
            'last_contacted_at' => $client->last_contacted_at?->toIso8601String(),
            'producer_dashboard_url' => route('clients.show', $client),
            'was_status_changed' => $client->wasChanged('status'),
            'was_contacted' => $client->wasChanged('last_contacted_at'),
        ];

        $this->sendWebhook($client->user_id, self::EVENT_CLIENT_UPDATED, $data);
    }

    /**
     * Trigger project created webhook
     */
    public function triggerProjectCreated(Project $project): void
    {
        $project->load(['client', 'pitches']);

        $data = [
            'id' => $project->id,
            'name' => $project->name,
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status,
            'workflow_type' => $project->workflow_type,
            'project_type' => $project->project_type,
            'budget' => $project->budget,
            'payment_amount' => floatval($project->payment_amount ?? 0),
            'deadline' => $project->deadline?->toDateString(),
            'is_prioritized' => $project->is_prioritized,
            'is_private' => $project->is_private,
            'created_at' => $project->created_at->toIso8601String(),
            'client' => $project->client ? [
                'id' => $project->client->id,
                'email' => $project->client->email,
                'name' => $project->client->name,
                'company' => $project->client->company,
                'status' => $project->client->status,
            ] : null,
            'pitch' => $project->pitches->first() ? [
                'id' => $project->pitches->first()->id,
                'status' => $project->pitches->first()->status,
                'payment_status' => $project->pitches->first()->payment_status,
            ] : null,
            'producer_dashboard_url' => route('projects.show', $project),
            'client_portal_url' => $this->generateClientPortalUrl($project),
        ];

        $this->sendWebhook($project->user_id, self::EVENT_PROJECT_CREATED, $data);
    }

    /**
     * Trigger project status changed webhook
     */
    public function triggerProjectStatusChanged(Project $project, string $previousStatus): void
    {
        $project->load(['client', 'pitches']);

        $data = [
            'id' => "project_{$project->id}",
            'type' => 'project_status_change',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'status' => $project->status,
            'previous_status' => $previousStatus,
            'updated_at' => $project->updated_at->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'workflow_type' => $project->workflow_type,
                'budget' => $project->budget,
                'payment_amount' => floatval($project->payment_amount ?? 0),
                'deadline' => $project->deadline?->toDateString(),
            ],
            'client' => $project->client ? [
                'id' => $project->client->id,
                'email' => $project->client->email,
                'name' => $project->client->name,
                'company' => $project->client->company,
                'status' => $project->client->status,
            ] : null,
            'producer_dashboard_url' => route('projects.show', $project),
            'client_portal_url' => $this->generateClientPortalUrl($project),
        ];

        $this->sendWebhook($project->user_id, self::EVENT_PROJECT_STATUS_CHANGED, $data);
    }

    /**
     * Trigger pitch status changed webhook
     */
    public function triggerPitchStatusChanged(Pitch $pitch, string $previousStatus): void
    {
        $pitch->load(['project.client']);

        $data = [
            'id' => "pitch_{$pitch->id}",
            'type' => 'pitch_status_change',
            'project_id' => $pitch->project->id,
            'project_name' => $pitch->project->name,
            'pitch_id' => $pitch->id,
            'pitch_status' => $pitch->status,
            'previous_pitch_status' => $previousStatus,
            'payment_status' => $pitch->payment_status,
            'updated_at' => $pitch->updated_at->toIso8601String(),
            'project' => [
                'id' => $pitch->project->id,
                'name' => $pitch->project->name,
                'status' => $pitch->project->status,
                'workflow_type' => $pitch->project->workflow_type,
                'budget' => $pitch->project->budget,
                'payment_amount' => floatval($pitch->project->payment_amount ?? 0),
            ],
            'client' => $pitch->project->client ? [
                'id' => $pitch->project->client->id,
                'email' => $pitch->project->client->email,
                'name' => $pitch->project->client->name,
                'company' => $pitch->project->client->company,
                'status' => $pitch->project->client->status,
            ] : null,
            'pitch' => [
                'id' => $pitch->id,
                'status' => $pitch->status,
                'payment_status' => $pitch->payment_status,
                'payment_amount' => floatval($pitch->payment_amount ?? 0),
                'created_at' => $pitch->created_at->toIso8601String(),
                'updated_at' => $pitch->updated_at->toIso8601String(),
            ],
            'is_completion' => $pitch->status === Pitch::STATUS_COMPLETED,
            'is_client_approval' => $pitch->status === Pitch::STATUS_APPROVED,
            'is_ready_for_review' => $pitch->status === Pitch::STATUS_READY_FOR_REVIEW,
            'requires_client_action' => in_array($pitch->status, [
                Pitch::STATUS_READY_FOR_REVIEW,
                Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            ]),
            'producer_dashboard_url' => route('projects.show', $pitch->project),
            'client_portal_url' => $this->generateClientPortalUrl($pitch->project),
        ];

        $this->sendWebhook($pitch->project->user_id, self::EVENT_PITCH_STATUS_CHANGED, $data);
    }

    /**
     * Trigger revenue milestone webhook
     */
    public function triggerRevenueMilestone(User $user, array $milestoneData): void
    {
        $this->sendWebhook($user->id, self::EVENT_REVENUE_MILESTONE, $milestoneData);
    }

    /**
     * Trigger communication log webhook
     */
    public function triggerCommunicationLog(User $user, array $communicationData): void
    {
        $this->sendWebhook($user->id, self::EVENT_COMMUNICATION_LOG, $communicationData);
    }

    /**
     * Send webhook to all registered URLs for an event type
     */
    private function sendWebhook(int $userId, string $eventType, array $data): void
    {
        $webhooks = ZapierWebhook::where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->sendWebhookRequest($webhook, $data);
        }
    }

    /**
     * Send individual webhook request
     */
    private function sendWebhookRequest(ZapierWebhook $webhook, array $data): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Zapier-Event' => $webhook->event_type,
                    'X-Zapier-Webhook-Id' => $webhook->id,
                ])
                ->post($webhook->webhook_url, $data);

            if ($response->successful()) {
                $webhook->markTriggered();

                Log::info('Zapier webhook sent successfully', [
                    'webhook_id' => $webhook->id,
                    'event_type' => $webhook->event_type,
                    'user_id' => $webhook->user_id,
                    'status_code' => $response->status(),
                ]);
            } else {
                Log::warning('Zapier webhook failed', [
                    'webhook_id' => $webhook->id,
                    'event_type' => $webhook->event_type,
                    'user_id' => $webhook->user_id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                // Deactivate webhook after multiple failures
                $this->handleWebhookFailure($webhook, $response->status());
            }

        } catch (Exception $e) {
            Log::error('Zapier webhook exception', [
                'webhook_id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'user_id' => $webhook->user_id,
                'error' => $e->getMessage(),
            ]);

            $this->handleWebhookFailure($webhook, 0);
        }
    }

    /**
     * Handle webhook failures
     */
    private function handleWebhookFailure(ZapierWebhook $webhook, int $statusCode): void
    {
        $metadata = $webhook->metadata ?? [];
        $failureCount = ($metadata['failure_count'] ?? 0) + 1;
        $metadata['failure_count'] = $failureCount;
        $metadata['last_failure_at'] = now()->toIso8601String();
        $metadata['last_failure_status'] = $statusCode;

        // Deactivate after 5 consecutive failures
        if ($failureCount >= 5) {
            $webhook->update([
                'is_active' => false,
                'metadata' => $metadata,
            ]);

            Log::warning('Zapier webhook deactivated due to failures', [
                'webhook_id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'user_id' => $webhook->user_id,
                'failure_count' => $failureCount,
            ]);
        } else {
            $webhook->update(['metadata' => $metadata]);
        }
    }

    /**
     * Generate client portal URL for a project
     */
    private function generateClientPortalUrl(Project $project): ?string
    {
        if ($project->workflow_type !== Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT) {
            return null;
        }

        // This would generate a signed URL for the client portal
        // For now, returning a placeholder structure
        return route('client.portal', ['project' => $project->id, 'token' => 'secure_token']);
    }

    /**
     * Get all available event types
     */
    public static function getAvailableEventTypes(): array
    {
        return [
            self::EVENT_CLIENT_CREATED => 'New Client Created',
            self::EVENT_CLIENT_UPDATED => 'Client Updated',
            self::EVENT_CLIENT_REMINDER_DUE => 'Client Reminder Due',
            self::EVENT_PROJECT_CREATED => 'New Project Created',
            self::EVENT_PROJECT_STATUS_CHANGED => 'Project Status Changed',
            self::EVENT_PITCH_STATUS_CHANGED => 'Pitch Status Changed',
            self::EVENT_REVENUE_MILESTONE => 'Revenue Milestone Reached',
            self::EVENT_COMMUNICATION_LOG => 'Communication Logged',
        ];
    }

    /**
     * Get webhook statistics for a user
     */
    public function getWebhookStats(User $user): array
    {
        $webhooks = ZapierWebhook::where('user_id', $user->id)->get();

        return [
            'total_webhooks' => $webhooks->count(),
            'active_webhooks' => $webhooks->where('is_active', true)->count(),
            'inactive_webhooks' => $webhooks->where('is_active', false)->count(),
            'total_triggers' => $webhooks->sum('trigger_count'),
            'event_types' => $webhooks->groupBy('event_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'active' => $group->where('is_active', true)->count(),
                    'total_triggers' => $group->sum('trigger_count'),
                    'last_triggered' => $group->max('last_triggered_at'),
                ];
            }),
        ];
    }

    /**
     * Test a webhook URL
     */
    public function testWebhook(string $webhookUrl, string $eventType): bool
    {
        $testData = [
            'test' => true,
            'event_type' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test webhook from MixPitch Zapier integration',
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Zapier-Event' => $eventType,
                    'X-Zapier-Test' => 'true',
                ])
                ->post($webhookUrl, $testData);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Webhook test failed', [
                'url' => $webhookUrl,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
