<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Services\ZapierWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebhookController extends ZapierApiController
{
    private ZapierWebhookService $webhookService;

    public function __construct(ZapierWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Register a webhook for real-time notifications
     *
     * This endpoint allows Zapier to register webhook URLs for instant triggers
     * instead of polling. When events occur, MixPitch will POST to the webhook URL.
     */
    public function register(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        $validated = $request->validate([
            'event_type' => ['required', 'string', Rule::in(array_keys(ZapierWebhookService::getAvailableEventTypes()))],
            'webhook_url' => ['required', 'url', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Test the webhook URL first
        if (! $this->webhookService->testWebhook($validated['webhook_url'], $validated['event_type'])) {
            return $this->errorResponse('Webhook URL test failed. Please ensure the URL is accessible and returns a 2xx status code.', 400);
        }

        try {
            $webhook = $this->webhookService->registerWebhook(
                $user,
                $validated['event_type'],
                $validated['webhook_url'],
                $validated['metadata'] ?? []
            );

            // Log the action
            if (config('zapier.log_usage')) {
                \App\Models\ZapierUsageLog::create([
                    'user_id' => $user->id,
                    'endpoint' => 'webhooks.register',
                    'request_data' => $this->sanitizeRequestData($request->all()),
                    'response_count' => 1,
                    'status_code' => 201,
                ]);
            }

            return $this->successResponse([
                'id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'webhook_url' => $webhook->webhook_url,
                'is_active' => $webhook->is_active,
                'created_at' => $webhook->created_at->toIso8601String(),
                'trigger_count' => $webhook->trigger_count,
                'metadata' => $webhook->metadata,
            ], 'Webhook registered successfully', 201);

        } catch (\Exception $e) {
            \Log::error('Failed to register Zapier webhook', [
                'user_id' => $user->id,
                'event_type' => $validated['event_type'],
                'webhook_url' => $validated['webhook_url'],
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to register webhook: '.$e->getMessage(), 500);
        }
    }

    /**
     * Deregister a webhook
     */
    public function deregister(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        $validated = $request->validate([
            'event_type' => ['required', 'string', Rule::in(array_keys(ZapierWebhookService::getAvailableEventTypes()))],
        ]);

        $success = $this->webhookService->deregisterWebhook($user, $validated['event_type']);

        if ($success) {
            // Log the action
            if (config('zapier.log_usage')) {
                \App\Models\ZapierUsageLog::create([
                    'user_id' => $user->id,
                    'endpoint' => 'webhooks.deregister',
                    'request_data' => $this->sanitizeRequestData($request->all()),
                    'response_count' => 1,
                    'status_code' => 200,
                ]);
            }

            return $this->successResponse([
                'event_type' => $validated['event_type'],
                'deregistered' => true,
                'deregistered_at' => now()->toIso8601String(),
            ], 'Webhook deregistered successfully');
        }

        return $this->errorResponse('No active webhook found for this event type', 404);
    }

    /**
     * List all webhooks for the authenticated user
     */
    public function list(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        $webhooks = \App\Models\ZapierWebhook::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'event_type' => $webhook->event_type,
                    'event_name' => ZapierWebhookService::getAvailableEventTypes()[$webhook->event_type] ?? $webhook->event_type,
                    'webhook_url' => $webhook->webhook_url,
                    'is_active' => $webhook->is_active,
                    'trigger_count' => $webhook->trigger_count,
                    'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                    'created_at' => $webhook->created_at->toIso8601String(),
                    'metadata' => $webhook->metadata,
                ];
            });

        $stats = $this->webhookService->getWebhookStats($user);

        // Log the request
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'webhooks.list',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => $webhooks->count(),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse([
            'webhooks' => $webhooks,
            'stats' => $stats,
            'available_event_types' => ZapierWebhookService::getAvailableEventTypes(),
        ]);
    }

    /**
     * Test a webhook URL without registering it
     */
    public function test(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $validated = $request->validate([
            'webhook_url' => ['required', 'url', 'max:255'],
            'event_type' => ['required', 'string', Rule::in(array_keys(ZapierWebhookService::getAvailableEventTypes()))],
        ]);

        $success = $this->webhookService->testWebhook(
            $validated['webhook_url'],
            $validated['event_type']
        );

        return $this->successResponse([
            'webhook_url' => $validated['webhook_url'],
            'event_type' => $validated['event_type'],
            'test_successful' => $success,
            'tested_at' => now()->toIso8601String(),
            'message' => $success
                ? 'Webhook URL is accessible and responding correctly'
                : 'Webhook URL failed to respond or returned an error status',
        ], $success ? 'Webhook test successful' : 'Webhook test failed', $success ? 200 : 400);
    }

    /**
     * Get available event types for webhook registration
     */
    public function eventTypes(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $eventTypes = collect(ZapierWebhookService::getAvailableEventTypes())
            ->map(function ($name, $key) {
                return [
                    'key' => $key,
                    'name' => $name,
                    'description' => $this->getEventTypeDescription($key),
                    'frequency' => $this->getEventTypeFrequency($key),
                ];
            })
            ->values();

        return $this->successResponse([
            'event_types' => $eventTypes,
            'total_count' => $eventTypes->count(),
            'webhook_documentation_url' => 'https://docs.mixpitch.com/zapier/webhooks',
        ]);
    }

    /**
     * Get description for an event type
     */
    private function getEventTypeDescription(string $eventType): string
    {
        $descriptions = [
            ZapierWebhookService::EVENT_CLIENT_CREATED => 'Triggered when a new client is added to your account',
            ZapierWebhookService::EVENT_CLIENT_UPDATED => 'Triggered when client information is updated',
            ZapierWebhookService::EVENT_CLIENT_REMINDER_DUE => 'Triggered when a client reminder becomes due',
            ZapierWebhookService::EVENT_PROJECT_CREATED => 'Triggered when a new client project is created',
            ZapierWebhookService::EVENT_PROJECT_STATUS_CHANGED => 'Triggered when a project status changes',
            ZapierWebhookService::EVENT_PITCH_STATUS_CHANGED => 'Triggered when a pitch status changes',
            ZapierWebhookService::EVENT_REVENUE_MILESTONE => 'Triggered when revenue milestones are reached',
            ZapierWebhookService::EVENT_COMMUNICATION_LOG => 'Triggered when client communication is logged',
        ];

        return $descriptions[$eventType] ?? 'Event description not available';
    }

    /**
     * Get typical frequency for an event type
     */
    private function getEventTypeFrequency(string $eventType): string
    {
        $frequencies = [
            ZapierWebhookService::EVENT_CLIENT_CREATED => 'low',
            ZapierWebhookService::EVENT_CLIENT_UPDATED => 'medium',
            ZapierWebhookService::EVENT_CLIENT_REMINDER_DUE => 'daily',
            ZapierWebhookService::EVENT_PROJECT_CREATED => 'medium',
            ZapierWebhookService::EVENT_PROJECT_STATUS_CHANGED => 'high',
            ZapierWebhookService::EVENT_PITCH_STATUS_CHANGED => 'high',
            ZapierWebhookService::EVENT_REVENUE_MILESTONE => 'low',
            ZapierWebhookService::EVENT_COMMUNICATION_LOG => 'high',
        ];

        return $frequencies[$eventType] ?? 'unknown';
    }
}
