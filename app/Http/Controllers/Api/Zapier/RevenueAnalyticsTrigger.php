<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Models\Client;
use App\Models\Project;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueAnalyticsTrigger extends ZapierApiController
{
    /**
     * Revenue Analytics Trigger - detects revenue milestones and spending patterns
     *
     * This trigger helps producers track:
     * - Client spending milestones ($500, $1000, $5000, etc.)
     * - Monthly revenue achievements
     * - High-value client identification
     * - Revenue trend changes
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

        $analytics = [];

        // 1. Client Spending Milestones
        $milestones = $this->detectClientSpendingMilestones($user->id, $sinceDate);
        $analytics = array_merge($analytics, $milestones);

        // 2. Monthly Revenue Achievements
        $monthlyAchievements = $this->detectMonthlyRevenueAchievements($user->id, $sinceDate);
        $analytics = array_merge($analytics, $monthlyAchievements);

        // 3. High-Value Client Detection
        $highValueClients = $this->detectHighValueClients($user->id, $sinceDate);
        $analytics = array_merge($analytics, $highValueClients);

        // 4. Revenue Trend Changes
        $trendChanges = $this->detectRevenueTrendChanges($user->id, $sinceDate);
        $analytics = array_merge($analytics, $trendChanges);

        // Sort by created_at descending
        usort($analytics, function ($a, $b) {
            return strtotime($b['triggered_at']) - strtotime($a['triggered_at']);
        });

        // Log the request
        if (config('zapier.log_usage')) {
            \App\Models\ZapierUsageLog::create([
                'user_id' => $user->id,
                'endpoint' => 'triggers.revenue.analytics',
                'request_data' => $this->sanitizeRequestData($request->all()),
                'response_count' => count($analytics),
                'status_code' => 200,
            ]);
        }

        return $this->successResponse($analytics);
    }

    /**
     * Detect when clients hit spending milestones ($500, $1000, $2500, $5000, $10000+)
     */
    private function detectClientSpendingMilestones(int $userId, Carbon $since): array
    {
        $milestoneThresholds = [500, 1000, 2500, 5000, 10000, 25000, 50000];
        $milestones = [];

        // Get recent transactions that might have pushed clients over milestones
        $recentTransactions = Transaction::with(['project.client', 'user'])
            ->where('producer_user_id', $userId)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('processed_at', '>', $since)
            ->orderBy('processed_at', 'desc')
            ->get();

        foreach ($recentTransactions as $transaction) {
            if (! $transaction->project?->client) {
                continue;
            }

            $client = $transaction->project->client;

            // Calculate total client spending
            $totalSpent = Transaction::whereHas('project', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })
                ->where('producer_user_id', $userId)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->where('type', Transaction::TYPE_PAYMENT)
                ->sum('amount');

            // Check which milestone was crossed
            foreach ($milestoneThresholds as $threshold) {
                $previousTotal = $totalSpent - $transaction->amount;

                if ($previousTotal < $threshold && $totalSpent >= $threshold) {
                    $milestones[] = [
                        'id' => "milestone_client_{$client->id}_{$threshold}",
                        'type' => 'client_spending_milestone',
                        'milestone_amount' => $threshold,
                        'client_total_spent' => floatval($totalSpent),
                        'triggering_transaction_amount' => floatval($transaction->amount),
                        'triggered_at' => $transaction->processed_at->toIso8601String(),
                        'client' => [
                            'id' => $client->id,
                            'email' => $client->email,
                            'name' => $client->name,
                            'company' => $client->company,
                            'status' => $client->status,
                            'total_spent' => floatval($totalSpent),
                            'total_projects' => $client->total_projects,
                            'tags' => $client->tags ?? [],
                        ],
                        'project' => [
                            'id' => $transaction->project->id,
                            'name' => $transaction->project->name,
                            'workflow_type' => $transaction->project->workflow_type,
                            'status' => $transaction->project->status,
                        ],
                        'transaction' => [
                            'id' => $transaction->id,
                            'amount' => floatval($transaction->amount),
                            'net_amount' => floatval($transaction->net_amount),
                            'commission_rate' => floatval($transaction->commission_rate),
                            'processed_at' => $transaction->processed_at->toIso8601String(),
                        ],
                        'milestone_tier' => $this->getMilestoneTier($threshold),
                        'is_vip_client' => $totalSpent >= 10000,
                        'producer_dashboard_url' => route('clients.show', $client),
                    ];
                    break; // Only trigger one milestone per transaction
                }
            }
        }

        return $milestones;
    }

    /**
     * Detect monthly revenue achievements ($1000, $2500, $5000, $10000+ per month)
     */
    private function detectMonthlyRevenueAchievements(int $userId, Carbon $since): array
    {
        $monthlyThresholds = [1000, 2500, 5000, 10000, 15000, 25000];
        $achievements = [];

        $currentMonth = Carbon::now()->startOfMonth();

        // Get current month's revenue
        $monthlyRevenue = Transaction::where('producer_user_id', $userId)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('processed_at', '>=', $currentMonth)
            ->sum('net_amount');

        // Get recent transactions from this month that might have triggered milestones
        $recentTransactions = Transaction::with(['project.client'])
            ->where('producer_user_id', $userId)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('processed_at', '>', $since)
            ->where('processed_at', '>=', $currentMonth)
            ->orderBy('processed_at', 'desc')
            ->get();

        foreach ($recentTransactions as $transaction) {
            foreach ($monthlyThresholds as $threshold) {
                $previousRevenue = $monthlyRevenue - $transaction->net_amount;

                if ($previousRevenue < $threshold && $monthlyRevenue >= $threshold) {
                    $achievements[] = [
                        'id' => "monthly_{$currentMonth->format('Y_m')}_{$threshold}",
                        'type' => 'monthly_revenue_achievement',
                        'milestone_amount' => $threshold,
                        'month' => $currentMonth->format('Y-m'),
                        'month_display' => $currentMonth->format('F Y'),
                        'monthly_revenue' => floatval($monthlyRevenue),
                        'triggering_transaction_amount' => floatval($transaction->net_amount),
                        'triggered_at' => $transaction->processed_at->toIso8601String(),
                        'client' => $transaction->project?->client ? [
                            'id' => $transaction->project->client->id,
                            'name' => $transaction->project->client->name,
                            'email' => $transaction->project->client->email,
                        ] : null,
                        'project' => [
                            'id' => $transaction->project->id,
                            'name' => $transaction->project->name,
                            'workflow_type' => $transaction->project->workflow_type,
                        ],
                        'achievement_tier' => $this->getAchievementTier($threshold),
                        'is_record_month' => $this->isRecordMonth($userId, $monthlyRevenue, $currentMonth),
                        'progress_to_next_milestone' => $this->getProgressToNextMilestone($monthlyRevenue, $monthlyThresholds),
                    ];
                    break;
                }
            }
        }

        return $achievements;
    }

    /**
     * Detect when clients become high-value (multiple projects, consistent spending)
     */
    private function detectHighValueClients(int $userId, Carbon $since): array
    {
        $highValueClients = [];

        // Find clients who recently completed their 3rd or 5th project
        $recentProjects = Project::with(['client', 'pitches.transactions'])
            ->where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('updated_at', '>', $since)
            ->whereIn('status', [Project::STATUS_COMPLETED, Project::STATUS_APPROVED])
            ->get();

        foreach ($recentProjects as $project) {
            if (! $project->client) {
                continue;
            }

            $client = $project->client;
            $clientProjectCount = Project::where('user_id', $userId)
                ->where('client_id', $client->id)
                ->whereIn('status', [Project::STATUS_COMPLETED, Project::STATUS_APPROVED])
                ->count();

            // Trigger on 3rd and 5th completed projects (high-value thresholds)
            if (in_array($clientProjectCount, [3, 5])) {
                $totalSpent = Transaction::whereHas('project', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                    ->where('producer_user_id', $userId)
                    ->where('status', Transaction::STATUS_COMPLETED)
                    ->sum('amount');

                $avgProjectValue = $clientProjectCount > 0 ? $totalSpent / $clientProjectCount : 0;

                $highValueClients[] = [
                    'id' => "high_value_client_{$client->id}_{$clientProjectCount}",
                    'type' => 'high_value_client_detected',
                    'client_project_count' => $clientProjectCount,
                    'total_client_value' => floatval($totalSpent),
                    'average_project_value' => floatval($avgProjectValue),
                    'triggered_at' => $project->updated_at->toIso8601String(),
                    'client' => [
                        'id' => $client->id,
                        'email' => $client->email,
                        'name' => $client->name,
                        'company' => $client->company,
                        'status' => $client->status,
                        'total_spent' => floatval($totalSpent),
                        'total_projects' => $clientProjectCount,
                        'tags' => $client->tags ?? [],
                        'last_contacted_at' => $client->last_contacted_at?->toIso8601String(),
                    ],
                    'latest_project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status,
                        'completed_at' => $project->updated_at->toIso8601String(),
                    ],
                    'value_tier' => $this->getClientValueTier($totalSpent, $clientProjectCount),
                    'is_vip_client' => $totalSpent >= 5000 && $clientProjectCount >= 3,
                    'retention_score' => $this->calculateRetentionScore($client, $clientProjectCount, $totalSpent),
                    'producer_dashboard_url' => route('clients.show', $client),
                    'recommended_actions' => $this->getRecommendedActions($totalSpent, $clientProjectCount),
                ];
            }
        }

        return $highValueClients;
    }

    /**
     * Detect significant revenue trend changes (week-over-week growth/decline)
     */
    private function detectRevenueTrendChanges(int $userId, Carbon $since): array
    {
        $trendChanges = [];

        // Only check for trend changes once per day to avoid spam
        if (Carbon::now()->hour < 9 || $since->gt(Carbon::now()->subHours(6))) {
            return $trendChanges;
        }

        $currentWeek = Carbon::now()->startOfWeek();
        $lastWeek = $currentWeek->copy()->subWeek();
        $twoWeeksAgo = $lastWeek->copy()->subWeek();

        $thisWeekRevenue = $this->getWeeklyRevenue($userId, $currentWeek);
        $lastWeekRevenue = $this->getWeeklyRevenue($userId, $lastWeek);
        $twoWeeksAgoRevenue = $this->getWeeklyRevenue($userId, $twoWeeksAgo);

        if ($lastWeekRevenue > 0) {
            $changePercent = (($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100;

            // Trigger on significant changes (>50% increase or >30% decrease)
            if (abs($changePercent) >= 30 && ($changePercent >= 50 || $changePercent <= -30)) {
                $trendChanges[] = [
                    'id' => "trend_{$currentWeek->format('Y_W')}_".($changePercent > 0 ? 'growth' : 'decline'),
                    'type' => 'revenue_trend_change',
                    'trend_direction' => $changePercent > 0 ? 'growth' : 'decline',
                    'change_percent' => round($changePercent, 1),
                    'current_week_revenue' => floatval($thisWeekRevenue),
                    'previous_week_revenue' => floatval($lastWeekRevenue),
                    'two_weeks_ago_revenue' => floatval($twoWeeksAgoRevenue),
                    'week_start' => $currentWeek->toDateString(),
                    'week_display' => $currentWeek->format('M j').' - '.$currentWeek->copy()->endOfWeek()->format('M j, Y'),
                    'triggered_at' => Carbon::now()->toIso8601String(),
                    'is_significant_change' => abs($changePercent) >= 75,
                    'trend_consistency' => $this->analyzeTrendConsistency($userId, $changePercent),
                    'contributing_factors' => $this->analyzeContributingFactors($userId, $currentWeek, $lastWeek),
                ];
            }
        }

        return $trendChanges;
    }

    // Helper Methods

    private function getMilestoneTier(int $amount): string
    {
        if ($amount >= 25000) {
            return 'enterprise';
        }
        if ($amount >= 10000) {
            return 'premium';
        }
        if ($amount >= 2500) {
            return 'professional';
        }

        return 'standard';
    }

    private function getAchievementTier(int $amount): string
    {
        if ($amount >= 15000) {
            return 'exceptional';
        }
        if ($amount >= 10000) {
            return 'outstanding';
        }
        if ($amount >= 5000) {
            return 'excellent';
        }
        if ($amount >= 2500) {
            return 'strong';
        }

        return 'good';
    }

    private function isRecordMonth(int $userId, float $currentRevenue, Carbon $month): bool
    {
        $maxPreviousMonth = Transaction::where('producer_user_id', $userId)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('processed_at', '<', $month)
            ->selectRaw('YEAR(processed_at) as year, MONTH(processed_at) as month, SUM(net_amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('total', 'desc')
            ->first();

        return ! $maxPreviousMonth || $currentRevenue > $maxPreviousMonth->total;
    }

    private function getProgressToNextMilestone(float $revenue, array $thresholds): ?array
    {
        foreach ($thresholds as $threshold) {
            if ($revenue < $threshold) {
                return [
                    'next_milestone' => $threshold,
                    'remaining_amount' => $threshold - $revenue,
                    'progress_percent' => round(($revenue / $threshold) * 100, 1),
                ];
            }
        }

        return null;
    }

    private function getClientValueTier(float $totalSpent, int $projectCount): string
    {
        if ($totalSpent >= 10000 && $projectCount >= 5) {
            return 'platinum';
        }
        if ($totalSpent >= 5000 && $projectCount >= 3) {
            return 'gold';
        }
        if ($totalSpent >= 2500 || $projectCount >= 2) {
            return 'silver';
        }

        return 'bronze';
    }

    private function calculateRetentionScore(Client $client, int $projectCount, float $totalSpent): int
    {
        $score = 0;
        $score += min($projectCount * 20, 60); // Max 60 for project count
        $score += min(($totalSpent / 1000) * 5, 30); // Max 30 for spending
        $score += $client->status === 'active' ? 10 : 0; // Active bonus

        return min($score, 100);
    }

    private function getRecommendedActions(float $totalSpent, int $projectCount): array
    {
        $actions = [];

        if ($totalSpent >= 5000) {
            $actions[] = 'Consider offering VIP client perks';
            $actions[] = 'Schedule regular check-in calls';
        }

        if ($projectCount >= 3) {
            $actions[] = 'Explore retainer agreement opportunities';
            $actions[] = 'Request testimonial or case study';
        }

        $actions[] = 'Update client tags with high-value status';

        return $actions;
    }

    private function getWeeklyRevenue(int $userId, Carbon $weekStart): float
    {
        return Transaction::where('producer_user_id', $userId)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->whereBetween('processed_at', [
                $weekStart,
                $weekStart->copy()->endOfWeek(),
            ])
            ->sum('net_amount');
    }

    private function analyzeTrendConsistency(int $userId, float $changePercent): string
    {
        // Simple trend analysis - could be expanded with more historical data
        if (abs($changePercent) >= 100) {
            return 'volatile';
        }
        if (abs($changePercent) >= 50) {
            return 'moderate';
        }

        return 'stable';
    }

    private function analyzeContributingFactors(int $userId, Carbon $currentWeek, Carbon $lastWeek): array
    {
        $factors = [];

        // Count projects completed in each week
        $currentWeekProjects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('status', Project::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$currentWeek, $currentWeek->copy()->endOfWeek()])
            ->count();

        $lastWeekProjects = Project::where('user_id', $userId)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->where('status', Project::STATUS_COMPLETED)
            ->whereBetween('updated_at', [$lastWeek, $lastWeek->copy()->endOfWeek()])
            ->count();

        if ($currentWeekProjects > $lastWeekProjects) {
            $factors[] = 'More projects completed this week';
        } elseif ($currentWeekProjects < $lastWeekProjects) {
            $factors[] = 'Fewer projects completed this week';
        }

        // Could add more factors like client acquisition, average project value, etc.
        return $factors;
    }
}
