<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReputationService
{
    /**
     * Base reputation weights
     */
    const WEIGHT_COMPLETED_PROJECTS = 10;
    const WEIGHT_AVERAGE_RATING = 20;
    const WEIGHT_TOTAL_EARNINGS = 0.1; // 0.1 point per dollar earned
    const WEIGHT_CONSISTENCY = 5; // Bonus for consistent activity
    const WEIGHT_RESPONSE_TIME = 3; // Bonus for quick responses
    
    /**
     * Cache duration for reputation calculations (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Calculate the total reputation for a user
     *
     * @param User $user
     * @param bool $useCache
     * @return float
     */
    public function calculateUserReputation(User $user, bool $useCache = true): float
    {
        $cacheKey = "user_reputation_{$user->id}";
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $baseReputation = $this->calculateBaseReputation($user);
        $multiplier = $user->getReputationMultiplier();
        $totalReputation = $baseReputation * $multiplier;

        // Cache the result
        Cache::put($cacheKey, $totalReputation, self::CACHE_DURATION);

        Log::info('Reputation calculated', [
            'user_id' => $user->id,
            'base_reputation' => $baseReputation,
            'multiplier' => $multiplier,
            'total_reputation' => $totalReputation,
            'subscription_plan' => $user->subscription_plan,
            'subscription_tier' => $user->subscription_tier,
        ]);

        return $totalReputation;
    }

    /**
     * Calculate base reputation before subscription multiplier
     *
     * @param User $user
     * @return float
     */
    private function calculateBaseReputation(User $user): float
    {
        $components = [
            'completed_projects' => $this->calculateCompletedProjectsScore($user),
            'average_rating' => $this->calculateAverageRatingScore($user),
            'total_earnings' => $this->calculateEarningsScore($user),
            'consistency' => $this->calculateConsistencyScore($user),
            'response_time' => $this->calculateResponseTimeScore($user),
        ];

        $totalScore = array_sum($components);

        Log::debug('Base reputation components', [
            'user_id' => $user->id,
            'components' => $components,
            'total' => $totalScore,
        ]);

        return max(0, $totalScore); // Ensure non-negative
    }

    /**
     * Calculate score from completed projects
     *
     * @param User $user
     * @return float
     */
    private function calculateCompletedProjectsScore(User $user): float
    {
        $completedCount = $user->projects()
            ->where('status', Project::STATUS_COMPLETED)
            ->count();

        return $completedCount * self::WEIGHT_COMPLETED_PROJECTS;
    }

    /**
     * Calculate score from average ratings
     *
     * @param User $user
     * @return float
     */
    private function calculateAverageRatingScore(User $user): float
    {
        $ratingData = $user->calculateAverageRating();
        $averageRating = $ratingData['average'] ?? 0;
        $ratingCount = $ratingData['count'] ?? 0;

        if ($ratingCount === 0) {
            return 0;
        }

        // Scale rating score by number of ratings (diminishing returns)
        $ratingMultiplier = min(1.0, $ratingCount / 10); // Max multiplier at 10+ ratings
        
        return $averageRating * self::WEIGHT_AVERAGE_RATING * $ratingMultiplier;
    }

    /**
     * Calculate score from total earnings
     *
     * @param User $user
     * @return float
     */
    private function calculateEarningsScore(User $user): float
    {
        $totalEarnings = $user->getTotalEarnings();
        return $totalEarnings * self::WEIGHT_TOTAL_EARNINGS;
    }

    /**
     * Calculate consistency score based on recent activity
     *
     * @param User $user
     * @return float
     */
    private function calculateConsistencyScore(User $user): float
    {
        // Calculate activity over the last 3 months
        $monthsToCheck = 3;
        $activeMonths = 0;

        for ($i = 0; $i < $monthsToCheck; $i++) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $hasActivity = $user->projects()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->exists() ||
                $user->pitches()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->exists();

            if ($hasActivity) {
                $activeMonths++;
            }
        }

        $consistencyRatio = $activeMonths / $monthsToCheck;
        return $consistencyRatio * self::WEIGHT_CONSISTENCY;
    }

    /**
     * Calculate response time score (placeholder for future implementation)
     *
     * @param User $user
     * @return float
     */
    private function calculateResponseTimeScore(User $user): float
    {
        // TODO: Implement based on message response times, pitch submission speed, etc.
        // For now, return a base score
        return self::WEIGHT_RESPONSE_TIME;
    }

    /**
     * Get reputation breakdown for a user
     *
     * @param User $user
     * @return array
     */
    public function getReputationBreakdown(User $user): array
    {
        $baseComponents = [
            'completed_projects' => $this->calculateCompletedProjectsScore($user),
            'average_rating' => $this->calculateAverageRatingScore($user),
            'total_earnings' => $this->calculateEarningsScore($user),
            'consistency' => $this->calculateConsistencyScore($user),
            'response_time' => $this->calculateResponseTimeScore($user),
        ];

        $baseTotal = array_sum($baseComponents);
        $multiplier = $user->getReputationMultiplier();
        $finalTotal = $baseTotal * $multiplier;

        return [
            'base_components' => $baseComponents,
            'base_total' => $baseTotal,
            'subscription_multiplier' => $multiplier,
            'final_total' => $finalTotal,
            'subscription_plan' => $user->subscription_plan,
            'subscription_tier' => $user->subscription_tier,
            'multiplier_bonus' => $finalTotal - $baseTotal,
        ];
    }

    /**
     * Compare reputation between users
     *
     * @param User $user1
     * @param User $user2
     * @return array
     */
    public function compareUsers(User $user1, User $user2): array
    {
        $user1Reputation = $this->calculateUserReputation($user1);
        $user2Reputation = $this->calculateUserReputation($user2);

        return [
            'user1' => [
                'id' => $user1->id,
                'name' => $user1->name,
                'reputation' => $user1Reputation,
                'breakdown' => $this->getReputationBreakdown($user1),
            ],
            'user2' => [
                'id' => $user2->id,
                'name' => $user2->name,
                'reputation' => $user2Reputation,
                'breakdown' => $this->getReputationBreakdown($user2),
            ],
            'difference' => $user1Reputation - $user2Reputation,
            'percentage_difference' => $user2Reputation > 0 
                ? (($user1Reputation - $user2Reputation) / $user2Reputation) * 100 
                : 0,
        ];
    }

    /**
     * Get reputation rank for user
     *
     * @param User $user
     * @return array
     */
    public function getUserRank(User $user): array
    {
        $userReputation = $this->calculateUserReputation($user);
        
        // Count users with higher reputation
        $higherRanked = User::whereHas('projects') // Only users with projects
            ->get()
            ->filter(function ($otherUser) use ($userReputation) {
                return $this->calculateUserReputation($otherUser, false) > $userReputation;
            })
            ->count();

        $totalRankedUsers = User::whereHas('projects')->count();
        $rank = $higherRanked + 1;
        $percentile = $totalRankedUsers > 0 
            ? (($totalRankedUsers - $rank + 1) / $totalRankedUsers) * 100 
            : 0;

        return [
            'rank' => $rank,
            'total_users' => $totalRankedUsers,
            'percentile' => round($percentile, 1),
            'reputation' => $userReputation,
        ];
    }

    /**
     * Clear reputation cache for a user
     *
     * @param User $user
     * @return void
     */
    public function clearUserCache(User $user): void
    {
        $cacheKey = "user_reputation_{$user->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Update reputation after significant events
     *
     * @param User $user
     * @param string $event
     * @param array $context
     * @return float
     */
    public function updateAfterEvent(User $user, string $event, array $context = []): float
    {
        // Clear cache to force recalculation
        $this->clearUserCache($user);
        
        // Recalculate reputation
        $newReputation = $this->calculateUserReputation($user, false);

        Log::info('Reputation updated after event', [
            'user_id' => $user->id,
            'event' => $event,
            'context' => $context,
            'new_reputation' => $newReputation,
        ]);

        return $newReputation;
    }

    /**
     * Get reputation tier/badge based on score
     *
     * @param float $reputation
     * @return array
     */
    public function getReputationTier(float $reputation): array
    {
        if ($reputation >= 1000) {
            return ['tier' => 'legend', 'badge' => '👑', 'color' => 'text-yellow-500'];
        } elseif ($reputation >= 500) {
            return ['tier' => 'master', 'badge' => '⭐', 'color' => 'text-purple-500'];
        } elseif ($reputation >= 250) {
            return ['tier' => 'expert', 'badge' => '🔥', 'color' => 'text-red-500'];
        } elseif ($reputation >= 100) {
            return ['tier' => 'professional', 'badge' => '💎', 'color' => 'text-blue-500'];
        } elseif ($reputation >= 50) {
            return ['tier' => 'experienced', 'badge' => '⚡', 'color' => 'text-green-500'];
        } elseif ($reputation >= 25) {
            return ['tier' => 'developing', 'badge' => '🌟', 'color' => 'text-indigo-500'];
        } else {
            return ['tier' => 'newcomer', 'badge' => '🌱', 'color' => 'text-gray-500'];
        }
    }
} 