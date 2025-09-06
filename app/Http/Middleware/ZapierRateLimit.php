<?php

namespace App\Http\Middleware;

use App\Models\ZapierUsageLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ZapierRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED',
            ], 401);
        }

        // Get rate limits from config
        $perMinuteLimit = config('zapier.rate_limits.per_minute', 60);
        $perHourLimit = config('zapier.rate_limits.per_hour', 1000);
        $perDayLimit = config('zapier.rate_limits.per_day', 10000);

        // Check subscription-based limits
        $subscriptionLimits = $this->getSubscriptionLimits($user);
        $perMinuteLimit = min($perMinuteLimit, $subscriptionLimits['per_minute']);
        $perHourLimit = min($perHourLimit, $subscriptionLimits['per_hour']);
        $perDayLimit = min($perDayLimit, $subscriptionLimits['per_day']);

        $userId = $user->id;
        $endpoint = $this->getEndpointIdentifier($request);

        // Rate limiting keys
        $perMinuteKey = "zapier_rate_limit:user:{$userId}:minute:".now()->format('Y-m-d-H-i');
        $perHourKey = "zapier_rate_limit:user:{$userId}:hour:".now()->format('Y-m-d-H');
        $perDayKey = "zapier_rate_limit:user:{$userId}:day:".now()->format('Y-m-d');
        $endpointKey = "zapier_rate_limit:user:{$userId}:endpoint:{$endpoint}:minute:".now()->format('Y-m-d-H-i');

        // Check per-minute limit
        $perMinuteCount = Cache::get($perMinuteKey, 0);
        if ($perMinuteCount >= $perMinuteLimit) {
            return $this->rateLimitResponse('Per-minute limit exceeded', $perMinuteLimit, $perMinuteCount, 60);
        }

        // Check per-hour limit
        $perHourCount = Cache::get($perHourKey, 0);
        if ($perHourCount >= $perHourLimit) {
            return $this->rateLimitResponse('Per-hour limit exceeded', $perHourLimit, $perHourCount, 3600);
        }

        // Check per-day limit
        $perDayCount = Cache::get($perDayKey, 0);
        if ($perDayCount >= $perDayLimit) {
            return $this->rateLimitResponse('Per-day limit exceeded', $perDayLimit, $perDayCount, 86400);
        }

        // Check endpoint-specific limits (20 requests per minute per endpoint)
        $endpointLimit = config('zapier.rate_limits.per_endpoint_per_minute', 20);
        $endpointCount = Cache::get($endpointKey, 0);
        if ($endpointCount >= $endpointLimit) {
            return $this->rateLimitResponse(
                "Endpoint rate limit exceeded for {$endpoint}",
                $endpointLimit,
                $endpointCount,
                60
            );
        }

        // Process the request
        $startTime = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Update rate limit counters
        $this->updateRateLimitCounters($perMinuteKey, $perHourKey, $perDayKey, $endpointKey);

        // Log usage
        $this->logUsage($user, $request, $response, $duration, $endpoint);

        // Add rate limit headers to response
        $response->headers->add([
            'X-RateLimit-Limit-Minute' => $perMinuteLimit,
            'X-RateLimit-Remaining-Minute' => max(0, $perMinuteLimit - $perMinuteCount - 1),
            'X-RateLimit-Limit-Hour' => $perHourLimit,
            'X-RateLimit-Remaining-Hour' => max(0, $perHourLimit - $perHourCount - 1),
            'X-RateLimit-Limit-Day' => $perDayLimit,
            'X-RateLimit-Remaining-Day' => max(0, $perDayLimit - $perDayCount - 1),
            'X-RateLimit-Reset-Minute' => now()->addMinute()->startOfMinute()->timestamp,
            'X-RateLimit-Reset-Hour' => now()->addHour()->startOfHour()->timestamp,
            'X-RateLimit-Reset-Day' => now()->addDay()->startOfDay()->timestamp,
        ]);

        return $response;
    }

    /**
     * Get subscription-based rate limits
     */
    private function getSubscriptionLimits($user): array
    {
        // Default limits for free users
        $limits = [
            'per_minute' => 30,
            'per_hour' => 500,
            'per_day' => 2000,
        ];

        // Check user subscription plan
        $plan = $user->subscription_plan ?? 'free';

        switch ($plan) {
            case 'pro':
            case 'professional':
                $limits = [
                    'per_minute' => 60,
                    'per_hour' => 2000,
                    'per_day' => 20000,
                ];
                break;
            case 'premium':
            case 'enterprise':
                $limits = [
                    'per_minute' => 120,
                    'per_hour' => 5000,
                    'per_day' => 50000,
                ];
                break;
        }

        return $limits;
    }

    /**
     * Generate rate limit exceeded response
     */
    private function rateLimitResponse(string $message, int $limit, int $current, int $resetSeconds): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'rate_limit' => [
                'limit' => $limit,
                'current' => $current,
                'reset_in_seconds' => $resetSeconds,
                'reset_at' => now()->addSeconds($resetSeconds)->toIso8601String(),
            ],
            'retry_after' => $resetSeconds,
        ], 429, [
            'Retry-After' => $resetSeconds,
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($resetSeconds)->timestamp,
        ]);
    }

    /**
     * Update rate limit counters
     */
    private function updateRateLimitCounters(string $perMinuteKey, string $perHourKey, string $perDayKey, string $endpointKey): void
    {
        // Increment counters with appropriate TTL
        Cache::put($perMinuteKey, Cache::get($perMinuteKey, 0) + 1, 60);
        Cache::put($perHourKey, Cache::get($perHourKey, 0) + 1, 3600);
        Cache::put($perDayKey, Cache::get($perDayKey, 0) + 1, 86400);
        Cache::put($endpointKey, Cache::get($endpointKey, 0) + 1, 60);
    }

    /**
     * Log API usage for analytics
     */
    private function logUsage($user, Request $request, $response, float $duration, string $endpoint): void
    {
        if (! config('zapier.log_usage', true)) {
            return;
        }

        // Only log if it's not already logged by the controller
        $shouldLog = ! $request->attributes->get('zapier_usage_logged', false);

        if ($shouldLog) {
            try {
                ZapierUsageLog::create([
                    'user_id' => $user->id,
                    'endpoint' => $endpoint,
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_data' => $this->sanitizeRequestData($request->all()),
                    'response_count' => $this->extractResponseCount($response),
                    'status_code' => $response->getStatusCode(),
                    'response_time_ms' => round($duration, 2),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to log Zapier usage', [
                    'user_id' => $user->id,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get endpoint identifier from request
     */
    private function getEndpointIdentifier(Request $request): string
    {
        $path = $request->path();

        // Map paths to friendly names
        $endpointMap = [
            'api/zapier/triggers/clients/new' => 'triggers.clients.new',
            'api/zapier/triggers/clients/updated' => 'triggers.clients.updated',
            'api/zapier/triggers/reminders/due' => 'triggers.reminders.due',
            'api/zapier/triggers/projects/client-created' => 'triggers.projects.created',
            'api/zapier/triggers/projects/status-changed' => 'triggers.projects.status',
            'api/zapier/triggers/revenue/analytics' => 'triggers.revenue.analytics',
            'api/zapier/triggers/communications/log' => 'triggers.communications.log',
            'api/zapier/actions/clients/create' => 'actions.clients.create',
            'api/zapier/actions/clients/update' => 'actions.clients.update',
            'api/zapier/actions/clients/bulk-update' => 'actions.clients.bulk_update',
            'api/zapier/actions/reminders/create' => 'actions.reminders.create',
            'api/zapier/actions/projects/create' => 'actions.projects.create',
            'api/zapier/searches/clients' => 'searches.clients',
            'api/zapier/searches/projects' => 'searches.projects',
            'api/zapier/webhooks/register' => 'webhooks.register',
            'api/zapier/webhooks/deregister' => 'webhooks.deregister',
            'api/zapier/webhooks/list' => 'webhooks.list',
            'api/zapier/webhooks/test' => 'webhooks.test',
            'api/zapier/webhooks/event-types' => 'webhooks.event_types',
        ];

        return $endpointMap[$path] ?? 'unknown';
    }

    /**
     * Sanitize request data for logging
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitive = ['password', 'token', 'api_key', 'secret'];

        return collect($data)->map(function ($value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive)) {
                return '[REDACTED]';
            }

            return is_string($value) && strlen($value) > 1000 ? '[TRUNCATED]' : $value;
        })->toArray();
    }

    /**
     * Extract response count from response
     */
    private function extractResponseCount($response): int
    {
        if (! $response instanceof Response) {
            return 1;
        }

        try {
            $content = json_decode($response->getContent(), true);

            if (isset($content['data']) && is_array($content['data'])) {
                return count($content['data']);
            }

            return 1;
        } catch (\Exception $e) {
            return 1;
        }
    }
}
