<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Services\ZapierUsageTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageAnalyticsController extends ZapierApiController
{
    private ZapierUsageTrackingService $usageService;

    public function __construct(ZapierUsageTrackingService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Get current rate limit status for the authenticated user
     */
    public function rateLimitStatus(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();
        $status = $this->usageService->getCurrentRateLimitStatus($user);

        return $this->successResponse($status);
    }

    /**
     * Get usage statistics for the authenticated user
     */
    public function usageStats(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $user = $request->user();
        $days = $validated['days'] ?? 30;
        $stats = $this->usageService->getUserUsageStats($user, $days);

        return $this->successResponse($stats);
    }

    /**
     * Get usage quota status and warnings
     */
    public function quotaStatus(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();
        $quotaStatus = $this->usageService->getUsageQuotaStatus($user);

        return $this->successResponse($quotaStatus);
    }

    /**
     * Generate comprehensive usage report
     */
    public function usageReport(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'format' => ['nullable', 'string', 'in:json,summary'],
        ]);

        $user = $request->user();
        $days = $validated['days'] ?? 30;
        $format = $validated['format'] ?? 'json';

        $report = $this->usageService->generateUsageReport($user, $days);

        if ($format === 'summary') {
            // Return a condensed version
            $summary = [
                'user_id' => $user->id,
                'subscription_plan' => $user->subscription_plan ?? 'free',
                'report_period_days' => $days,
                'total_requests' => $report['usage_summary']['total_requests'],
                'success_rate' => $report['usage_summary']['success_rate'],
                'top_endpoint' => $report['endpoint_usage'][0]['endpoint'] ?? null,
                'current_quota_usage' => $report['current_quota_status']['percentage_used'],
                'has_warnings' => $report['current_quota_status']['is_approaching_limits'],
                'recommendations_count' => count($report['recommendations']),
                'generated_at' => $report['generated_at'],
            ];

            return $this->successResponse($summary, 'Usage summary generated');
        }

        return $this->successResponse($report, 'Usage report generated');
    }

    /**
     * Get health check information for the API
     */
    public function healthCheck(Request $request): JsonResponse
    {
        // No authentication required for health checks

        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.2.0', // Current Zapier integration version
            'services' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'webhooks' => $this->checkWebhookHealth(),
            ],
            'rate_limiting' => [
                'enabled' => config('zapier.rate_limiting.enabled', true),
                'default_limits' => [
                    'per_minute' => config('zapier.rate_limits.per_minute', 60),
                    'per_hour' => config('zapier.rate_limits.per_hour', 1000),
                    'per_day' => config('zapier.rate_limits.per_day', 10000),
                ],
            ],
        ];

        // Determine overall health
        $allHealthy = collect($status['services'])->every(function ($service) {
            return $service['status'] === 'healthy';
        });

        if (! $allHealthy) {
            $status['status'] = 'degraded';

            return $this->successResponse($status, 'System health check - some services degraded', 200);
        }

        return $this->successResponse($status, 'System health check - all services healthy');
    }

    /**
     * Get system-wide usage analytics (admin only)
     */
    public function systemAnalytics(Request $request): JsonResponse
    {
        if (! $this->checkZapierEnabled()) {
            return $this->errorResponse('Zapier integration is not enabled', 403);
        }

        if (! $this->checkZapierToken()) {
            return $this->errorResponse('Invalid Zapier token', 403);
        }

        $user = $request->user();

        // Check if user has admin privileges
        if (! $user->is_admin && ! $user->hasRole('admin')) {
            return $this->errorResponse('Admin access required', 403);
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $days = $validated['days'] ?? 30;
        $analytics = $this->usageService->getSystemUsageAnalytics($days);

        return $this->successResponse($analytics, 'System analytics generated');
    }

    // Private helper methods for health checks

    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            \App\Models\ZapierUsageLog::count(); // Test table access

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'last_checked' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
                'last_checked' => now()->toIso8601String(),
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'zapier_health_check_'.now()->timestamp;
            \Cache::put($testKey, 'test', 60);
            $retrieved = \Cache::get($testKey);
            \Cache::forget($testKey);

            if ($retrieved === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working properly',
                    'last_checked' => now()->toIso8601String(),
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write test failed',
                    'last_checked' => now()->toIso8601String(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system error',
                'error' => $e->getMessage(),
                'last_checked' => now()->toIso8601String(),
            ];
        }
    }

    private function checkWebhookHealth(): array
    {
        try {
            $activeWebhooks = \App\Models\ZapierWebhook::where('is_active', true)->count();
            $totalWebhooks = \App\Models\ZapierWebhook::count();

            return [
                'status' => 'healthy',
                'message' => 'Webhook system operational',
                'active_webhooks' => $activeWebhooks,
                'total_webhooks' => $totalWebhooks,
                'last_checked' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Webhook system error',
                'error' => $e->getMessage(),
                'last_checked' => now()->toIso8601String(),
            ];
        }
    }
}
