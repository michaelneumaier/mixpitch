<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Note: Removed separate client dashboard - client projects now show in regular dashboard

        // Phase 3: Enhance producer dashboard with earnings and analytics
        $producerData = $this->getProducerAnalytics($user);

        // --- Subscription Information ---
        $subscriptionData = [
            'plan' => $user->subscription_plan,
            'tier' => $user->subscription_tier,
            'display_name' => $user->getSubscriptionDisplayName(),
            'limits' => $user->getSubscriptionLimits(),
            'usage' => [],
            'alerts' => [],
        ];

        // Calculate usage for the current user
        $limits = $user->getSubscriptionLimits();
        $subscriptionData['limits'] = $limits;

        // Project counts using new methods
        $totalProjects = $user->projects->count();
        $activeProjects = $user->getActiveProjectsCount();
        $completedProjects = $user->getCompletedProjectsCount();

        // Pitch counts (excluding client management)
        $activePitches = $user->getActivePitchesCount();

        $subscriptionData['usage'] = [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'active_pitches_count' => $activePitches,
            'monthly_pitches_used' => $user->getMonthlyPitchCount(),
        ];

        if ($limits) {

            // Generate alerts based on usage (now using active projects)
            if ($limits->max_projects_owned && $activeProjects >= $limits->max_projects_owned) {
                $subscriptionData['alerts'][] = [
                    'type' => 'projects',
                    'message' => 'You have reached your active project limit. Upgrade to Pro for unlimited projects or complete existing projects.',
                    'level' => 'error',
                ];
            } elseif ($limits->max_projects_owned && $activeProjects >= ($limits->max_projects_owned * 0.8)) {
                $subscriptionData['alerts'][] = [
                    'type' => 'projects',
                    'message' => 'You are approaching your active project limit.',
                    'level' => 'warning',
                ];
            }

            // Active pitch limit alerts
            if ($limits->max_active_pitches && $subscriptionData['usage']['active_pitches_count'] >= $limits->max_active_pitches) {
                $subscriptionData['alerts'][] = [
                    'type' => 'pitches',
                    'message' => 'You have reached your active pitch limit. Upgrade to Pro for unlimited pitches.',
                    'level' => 'error',
                ];
            } elseif ($limits->max_active_pitches && $subscriptionData['usage']['active_pitches_count'] >= ($limits->max_active_pitches * 0.8)) {
                $subscriptionData['alerts'][] = [
                    'type' => 'pitches',
                    'message' => 'You are approaching your active pitch limit.',
                    'level' => 'warning',
                ];
            }

            // Monthly pitch limit alerts (Pro Engineer)
            if ($limits->max_monthly_pitches) {
                if ($subscriptionData['usage']['monthly_pitches_used'] >= $limits->max_monthly_pitches) {
                    $subscriptionData['alerts'][] = [
                        'type' => 'monthly_pitches',
                        'message' => 'You have reached your monthly pitch limit.',
                        'level' => 'error',
                    ];
                } elseif ($subscriptionData['usage']['monthly_pitches_used'] >= ($limits->max_monthly_pitches * 0.8)) {
                    $subscriptionData['alerts'][] = [
                        'type' => 'monthly_pitches',
                        'message' => 'You are approaching your monthly pitch limit.',
                        'level' => 'warning',
                    ];
                }
            }
        }

        // --- Storage Information ---
        $userStorageService = app(UserStorageService::class);
        $storageInfo = [
            'percentage' => round($userStorageService->getUserStoragePercentage($user), 1),
            'used_gb' => round($userStorageService->getUserStorageUsed($user) / (1024 ** 3), 2),
            'total_gb' => round($userStorageService->getUserStorageLimit($user) / (1024 ** 3), 1),
            'remaining_gb' => round($userStorageService->getUserStorageRemaining($user) / (1024 ** 3), 2),
        ];

        // Pass subscription data to the view
        return view('dashboard', [
            'subscription' => $subscriptionData,
            'producerData' => $producerData,
            'storage_info' => $storageInfo,
        ]);
    }

    private function clientDashboard($user)
    {
        // Get all projects associated with this client
        $projects = Project::where(function ($query) use ($user) {
            $query->where('client_user_id', $user->id)
                ->orWhere('client_email', $user->email);
        })
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->with(['pitches' => function ($q) {
                $q->with(['user', 'files', 'events']);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate client statistics
        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->whereIn('status', [
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])->count(),
            'completed_projects' => $projects->where('status', Project::STATUS_COMPLETED)->count(),
            'total_spent' => $projects->sum(function ($project) {
                return $project->pitches
                    ->where('payment_status', Pitch::PAYMENT_STATUS_PAID)
                    ->sum('payment_amount');
            }),
            'pending_payments' => $projects->sum(function ($project) {
                return $project->pitches
                    ->where('payment_status', Pitch::PAYMENT_STATUS_PENDING)
                    ->sum('payment_amount');
            }),
        ];

        // Get recent activity from pitch events
        $recentActivity = collect();
        foreach ($projects as $project) {
            foreach ($project->pitches as $pitch) {
                $recentEvents = $pitch->events()
                    ->whereIn('event_type', [
                        'pitch_submitted',
                        'pitch_approved',
                        'pitch_completed',
                        'payment_completed',
                        'revisions_requested',
                        'client_comment',
                    ])
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($recentEvents as $event) {
                    $event->project = $project;
                    $event->pitch = $pitch;
                    $recentActivity->push($event);
                }
            }
        }

        // Sort recent activity by date and limit to 10 most recent
        $recentActivity = $recentActivity->sortByDesc('created_at')->take(10);

        return view('dashboard.client', compact('projects', 'stats', 'recentActivity'));
    }

    /**
     * Phase 3: Get producer analytics and earnings data
     */
    private function getProducerAnalytics($user)
    {
        // Get payout statistics
        $payoutStats = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(net_amount) as total_amount,
                SUM(gross_amount) as total_gross,
                SUM(commission_amount) as total_commission
            ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Calculate earnings metrics
        $totalEarnings = $payoutStats->get('completed')->total_amount ?? 0;
        $pendingEarnings = ($payoutStats->get('scheduled')->total_amount ?? 0) +
                          ($payoutStats->get('processing')->total_amount ?? 0);

        // This month earnings
        $thisMonthEarnings = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->startOfMonth())
            ->sum('net_amount');

        // Commission savings calculation using User model method
        $currentRate = $user->getPlatformCommissionRate();
        $commissionSavings = $user->getCommissionSavings();

        // Client management projects statistics (pivot by client_id for unique clients)
        $clientProjects = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->get();

        $distinctClientIds = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNotNull('client_id')
            ->distinct()
            ->count('client_id');

        $distinctEmailsWithoutClientId = Project::where('user_id', $user->id)
            ->where('workflow_type', Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT)
            ->whereNull('client_id')
            ->whereNotNull('client_email')
            ->distinct()
            ->count('client_email');

        $clientStats = [
            'total_projects' => $clientProjects->count(),
            'active_projects' => $clientProjects->whereIn('status', [
                Project::STATUS_OPEN,
                Project::STATUS_IN_PROGRESS,
            ])->count(),
            'completed_projects' => $clientProjects->where('status', Project::STATUS_COMPLETED)->count(),
            'unique_clients' => $distinctClientIds + $distinctEmailsWithoutClientId,
            'total_revenue' => $clientProjects->sum(function ($project) {
                return $project->pitches->sum(function ($pitch) {
                    $base = $pitch->payment_status === Pitch::PAYMENT_STATUS_PAID ? ($pitch->payment_amount ?? 0) : 0;
                    $milestones = method_exists($pitch, 'milestones') ? $pitch->milestones->where('payment_status', Pitch::PAYMENT_STATUS_PAID)->sum('amount') : 0;

                    return $base + $milestones;
                });
            }),
            'milestone_adoption_rate' => (function () use ($clientProjects) {
                $totalCM = $clientProjects->count();
                if ($totalCM === 0) {
                    return 0;
                }
                $withMilestones = $clientProjects->sum(function ($project) {
                    return $project->pitches->filter(function ($p) {
                        return method_exists($p, 'milestones') && $p->milestones->count() > 0;
                    })->count() > 0 ? 1 : 0;
                });

                return round(($withMilestones / $totalCM) * 100, 1);
            })(),
        ];

        // Stripe Connect status with error handling
        try {
            $stripeConnectService = app(\App\Services\StripeConnectService::class);
            $stripeStatus = $stripeConnectService->getDetailedAccountStatus($user);

            // Ensure all required keys exist with defaults
            $stripeStatus = array_merge([
                'account_exists' => false,
                'can_receive_payouts' => false,
                'status_display' => 'Setup Required',
                'next_steps' => [],
            ], $stripeStatus ?? []);
        } catch (\Exception $e) {
            // Fallback to default values if Stripe service fails
            $stripeStatus = [
                'account_exists' => false,
                'can_receive_payouts' => false,
                'status_display' => 'Setup Required',
                'next_steps' => [],
            ];
        }

        // Recent payouts (last 5)
        $recentPayouts = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
            ->with(['project', 'pitch'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'earnings' => [
                'total' => $totalEarnings,
                'pending' => $pendingEarnings,
                'this_month' => $thisMonthEarnings,
                'commission_savings' => $commissionSavings,
                'commission_rate' => $currentRate,
            ],
            'client_management' => $clientStats,
            'stripe_connect' => $stripeStatus,
            'recent_payouts' => $recentPayouts,
            'payout_counts' => [
                'completed' => $payoutStats->get('completed')->count ?? 0,
                'pending' => ($payoutStats->get('scheduled')->count ?? 0) +
                           ($payoutStats->get('processing')->count ?? 0),
                'failed' => $payoutStats->get('failed')->count ?? 0,
            ],
        ];
    }

    /**
     * Show the full client management dashboard for all registered users.
     */
    public function clientManagement()
    {
        $user = Auth::user();

        // Client management is now available to all registered users
        return view('producer.client-management');
    }

    /**
     * Show individual client detail dashboard.
     */
    public function clientDetail(\App\Models\Client $client)
    {
        $user = Auth::user();

        // Ensure the client belongs to the current user
        if ($client->user_id !== $user->id) {
            abort(403, 'Access denied. You can only view your own clients.');
        }

        return view('producer.client-detail', compact('client'));
    }
}
