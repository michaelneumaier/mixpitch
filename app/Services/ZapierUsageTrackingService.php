<?php

namespace App\Services;

use App\Models\User;
use App\Models\ZapierUsageLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZapierUsageTrackingService
{
    /**
     * Get usage statistics for a user
     */
    public function getUserUsageStats(User $user, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $logs = ZapierUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'total_requests' => $logs->count(),
            'successful_requests' => $logs->where('status_code', '<', 400)->count(),
            'failed_requests' => $logs->where('status_code', '>=', 400)->count(),
            'average_response_time' => round($logs->avg('response_time_ms'), 2),
            'total_response_time' => round($logs->sum('response_time_ms'), 2),
            'most_used_endpoints' => $this->getMostUsedEndpoints($logs),
            'daily_usage' => $this->getDailyUsage($logs, $days),
            'hourly_distribution' => $this->getHourlyDistribution($logs),
            'error_breakdown' => $this->getErrorBreakdown($logs),
            'period_start' => $since->toIso8601String(),
            'period_end' => now()->toIso8601String(),
        ];
    }

    /**
     * Get current rate limit status for a user
     */
    public function getCurrentRateLimitStatus(User $user): array
    {
        $limits = $this->getSubscriptionLimits($user);
        $now = now();

        // Get current usage counts
        $perMinuteCount = $this->getUsageCount($user->id, 'minute', $now);
        $perHourCount = $this->getUsageCount($user->id, 'hour', $now);
        $perDayCount = $this->getUsageCount($user->id, 'day', $now);

        return [
            'limits' => $limits,
            'current_usage' => [
                'per_minute' => $perMinuteCount,
                'per_hour' => $perHourCount,
                'per_day' => $perDayCount,
            ],
            'remaining' => [
                'per_minute' => max(0, $limits['per_minute'] - $perMinuteCount),
                'per_hour' => max(0, $limits['per_hour'] - $perHourCount),
                'per_day' => max(0, $limits['per_day'] - $perDayCount),
            ],
            'percentage_used' => [
                'per_minute' => $limits['per_minute'] > 0 ? round(($perMinuteCount / $limits['per_minute']) * 100, 1) : 0,
                'per_hour' => $limits['per_hour'] > 0 ? round(($perHourCount / $limits['per_hour']) * 100, 1) : 0,
                'per_day' => $limits['per_day'] > 0 ? round(($perDayCount / $limits['per_day']) * 100, 1) : 0,
            ],
            'reset_times' => [
                'per_minute' => $now->copy()->addMinute()->startOfMinute()->toIso8601String(),
                'per_hour' => $now->copy()->addHour()->startOfHour()->toIso8601String(),
                'per_day' => $now->copy()->addDay()->startOfDay()->toIso8601String(),
            ],
        ];
    }

    /**
     * Get usage analytics for admin dashboard
     */
    public function getSystemUsageAnalytics(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        // Total system usage
        $totalRequests = ZapierUsageLog::where('created_at', '>=', $since)->count();
        $successfulRequests = ZapierUsageLog::where('created_at', '>=', $since)
            ->where('status_code', '<', 400)
            ->count();
        $failedRequests = ZapierUsageLog::where('created_at', '>=', $since)
            ->where('status_code', '>=', 400)
            ->count();

        // Active users
        $activeUsers = ZapierUsageLog::where('created_at', '>=', $since)
            ->distinct('user_id')
            ->count();

        // Top users by usage
        $topUsers = ZapierUsageLog::where('created_at', '>=', $since)
            ->select('user_id', DB::raw('COUNT(*) as request_count'))
            ->groupBy('user_id')
            ->orderBy('request_count', 'desc')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        // Endpoint usage
        $endpointStats = ZapierUsageLog::where('created_at', '>=', $since)
            ->select('endpoint', DB::raw('COUNT(*) as request_count'))
            ->groupBy('endpoint')
            ->orderBy('request_count', 'desc')
            ->get();

        // Daily usage trend
        $dailyUsage = ZapierUsageLog::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as requests'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Average response times by endpoint
        $responseTimeStats = ZapierUsageLog::where('created_at', '>=', $since)
            ->select('endpoint', DB::raw('AVG(response_time_ms) as avg_response_time'))
            ->groupBy('endpoint')
            ->orderBy('avg_response_time', 'desc')
            ->get();

        return [
            'summary' => [
                'total_requests' => $totalRequests,
                'successful_requests' => $successfulRequests,
                'failed_requests' => $failedRequests,
                'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
                'active_users' => $activeUsers,
                'period_start' => $since->toIso8601String(),
                'period_end' => now()->toIso8601String(),
            ],
            'top_users' => $topUsers,
            'endpoint_stats' => $endpointStats,
            'daily_usage' => $dailyUsage,
            'response_time_stats' => $responseTimeStats,
        ];
    }

    /**
     * Get usage quota status for a user
     */
    public function getUsageQuotaStatus(User $user): array
    {
        $limits = $this->getSubscriptionLimits($user);
        $currentUsage = $this->getCurrentRateLimitStatus($user);

        // Calculate quota warnings
        $warnings = [];
        foreach (['per_minute', 'per_hour', 'per_day'] as $period) {
            $percentage = $currentUsage['percentage_used'][$period];

            if ($percentage >= 90) {
                $warnings[] = [
                    'level' => 'critical',
                    'period' => $period,
                    'message' => "API usage is at {$percentage}% of {$period} limit",
                    'suggestion' => $period === 'per_day' ? 'Consider upgrading your plan' : 'Please slow down your API calls',
                ];
            } elseif ($percentage >= 75) {
                $warnings[] = [
                    'level' => 'warning',
                    'period' => $period,
                    'message' => "API usage is at {$percentage}% of {$period} limit",
                    'suggestion' => 'Monitor your usage to avoid hitting limits',
                ];
            }
        }

        return [
            'subscription_plan' => $user->subscription_plan ?? 'free',
            'limits' => $limits,
            'current_usage' => $currentUsage['current_usage'],
            'remaining' => $currentUsage['remaining'],
            'percentage_used' => $currentUsage['percentage_used'],
            'warnings' => $warnings,
            'is_approaching_limits' => count($warnings) > 0,
            'next_reset' => $currentUsage['reset_times'],
        ];
    }

    /**
     * Clean up old usage logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoff = Carbon::now()->subDays($daysToKeep);

        return ZapierUsageLog::where('created_at', '<', $cutoff)->delete();
    }

    /**
     * Generate usage report for a user
     */
    public function generateUsageReport(User $user, int $days = 30): array
    {
        $stats = $this->getUserUsageStats($user, $days);
        $quotaStatus = $this->getUsageQuotaStatus($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_plan' => $user->subscription_plan ?? 'free',
            ],
            'report_period' => [
                'days' => $days,
                'start_date' => $stats['period_start'],
                'end_date' => $stats['period_end'],
            ],
            'usage_summary' => [
                'total_requests' => $stats['total_requests'],
                'successful_requests' => $stats['successful_requests'],
                'failed_requests' => $stats['failed_requests'],
                'success_rate' => $stats['total_requests'] > 0 ? round(($stats['successful_requests'] / $stats['total_requests']) * 100, 2) : 0,
                'average_response_time' => $stats['average_response_time'],
            ],
            'endpoint_usage' => $stats['most_used_endpoints'],
            'daily_usage_trend' => $stats['daily_usage'],
            'current_quota_status' => $quotaStatus,
            'recommendations' => $this->generateRecommendations($stats, $quotaStatus),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    // Private helper methods

    private function getSubscriptionLimits(User $user): array
    {
        $plan = $user->subscription_plan ?? 'free';

        switch ($plan) {
            case 'pro':
            case 'professional':
                return [
                    'per_minute' => 60,
                    'per_hour' => 2000,
                    'per_day' => 20000,
                ];
            case 'premium':
            case 'enterprise':
                return [
                    'per_minute' => 120,
                    'per_hour' => 5000,
                    'per_day' => 50000,
                ];
            default:
                return [
                    'per_minute' => 30,
                    'per_hour' => 500,
                    'per_day' => 2000,
                ];
        }
    }

    private function getUsageCount(int $userId, string $period, Carbon $time): int
    {
        // This would typically query cache, but for reporting we'll query the database
        $query = ZapierUsageLog::where('user_id', $userId);

        switch ($period) {
            case 'minute':
                $query->where('created_at', '>=', $time->copy()->startOfMinute());
                break;
            case 'hour':
                $query->where('created_at', '>=', $time->copy()->startOfHour());
                break;
            case 'day':
                $query->where('created_at', '>=', $time->copy()->startOfDay());
                break;
        }

        return $query->count();
    }

    private function getMostUsedEndpoints(Collection $logs): array
    {
        return $logs->groupBy('endpoint')
            ->map(function ($group) {
                return [
                    'endpoint' => $group->first()->endpoint,
                    'requests' => $group->count(),
                    'success_rate' => round(($group->where('status_code', '<', 400)->count() / $group->count()) * 100, 2),
                    'avg_response_time' => round($group->avg('response_time_ms'), 2),
                ];
            })
            ->sortByDesc('requests')
            ->values()
            ->toArray();
    }

    private function getDailyUsage(Collection $logs, int $days): array
    {
        $dailyUsage = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'requests' => $group->count(),
                'successful' => $group->where('status_code', '<', 400)->count(),
                'failed' => $group->where('status_code', '>=', 400)->count(),
            ];
        });

        // Fill in missing days with zero counts
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            if (! $dailyUsage->has($date)) {
                $dailyUsage[$date] = [
                    'date' => $date,
                    'requests' => 0,
                    'successful' => 0,
                    'failed' => 0,
                ];
            }
        }

        return $dailyUsage->sortBy('date')->values()->toArray();
    }

    private function getHourlyDistribution(Collection $logs): array
    {
        $hourlyUsage = $logs->groupBy(function ($log) {
            return $log->created_at->format('H');
        })->map(function ($group, $hour) {
            return [
                'hour' => intval($hour),
                'requests' => $group->count(),
            ];
        });

        // Fill in missing hours
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = sprintf('%02d', $hour);
            if (! $hourlyUsage->has($hourStr)) {
                $hourlyUsage[$hourStr] = [
                    'hour' => $hour,
                    'requests' => 0,
                ];
            }
        }

        return $hourlyUsage->sortBy('hour')->values()->toArray();
    }

    private function getErrorBreakdown(Collection $logs): array
    {
        return $logs->where('status_code', '>=', 400)
            ->groupBy('status_code')
            ->map(function ($group, $statusCode) {
                return [
                    'status_code' => intval($statusCode),
                    'count' => $group->count(),
                    'percentage' => 0, // Will be calculated later
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    private function generateRecommendations(array $stats, array $quotaStatus): array
    {
        $recommendations = [];

        // High usage recommendations
        if ($stats['total_requests'] > 1000) {
            $recommendations[] = [
                'type' => 'optimization',
                'message' => 'Consider using webhooks instead of polling for better performance',
                'action' => 'Review webhook documentation',
            ];
        }

        // Error rate recommendations
        $errorRate = $stats['total_requests'] > 0 ? ($stats['failed_requests'] / $stats['total_requests']) * 100 : 0;
        if ($errorRate > 10) {
            $recommendations[] = [
                'type' => 'error_reduction',
                'message' => 'High error rate detected. Review failed requests and implement proper error handling',
                'action' => 'Check API documentation for proper request formats',
            ];
        }

        // Quota recommendations
        if ($quotaStatus['is_approaching_limits']) {
            $recommendations[] = [
                'type' => 'quota_management',
                'message' => 'You are approaching your API limits. Consider upgrading your plan or optimizing usage',
                'action' => 'Review subscription plans or implement request batching',
            ];
        }

        return $recommendations;
    }
}
