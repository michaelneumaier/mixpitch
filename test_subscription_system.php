<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\SubscriptionLimit;
use App\Models\Project;
use App\Models\Pitch;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "==================================\n";
echo "MIXPITCH SUBSCRIPTION SYSTEM TEST\n";
echo "==================================\n\n";

// Test 1: Check Subscription Limits Seeding
echo "1. TESTING SUBSCRIPTION LIMITS:\n";
echo "--------------------------------\n";
$limits = SubscriptionLimit::all();
echo "Total subscription limits: " . $limits->count() . "\n";

foreach ($limits as $limit) {
    echo "- {$limit->plan_name}.{$limit->plan_tier}:\n";
    echo "  * Max Projects: " . ($limit->max_projects_owned ?? 'unlimited') . "\n";
    echo "  * Max Active Pitches: " . ($limit->max_active_pitches ?? 'unlimited') . "\n";
    echo "  * Monthly Pitches: " . ($limit->max_monthly_pitches ?? 'N/A') . "\n";
    echo "  * Storage per Project: {$limit->storage_per_project_mb}MB\n";
    echo "  * Priority Support: " . ($limit->priority_support ? 'Yes' : 'No') . "\n";
    echo "  * Custom Portfolio: " . ($limit->custom_portfolio ? 'Yes' : 'No') . "\n\n";
}

// Test 2: User Subscription Methods
echo "2. TESTING USER SUBSCRIPTION METHODS:\n";
echo "--------------------------------------\n";
$user = User::first();
if ($user) {
    echo "Test User: {$user->email}\n";
    echo "Current Plan: {$user->subscription_plan}.{$user->subscription_tier}\n";
    echo "Is Free Plan: " . ($user->isFreePlan() ? 'YES' : 'NO') . "\n";
    echo "Is Pro Plan: " . ($user->isProPlan() ? 'YES' : 'NO') . "\n";
    echo "Subscribed (Cashier): " . ($user->subscribed('default') ? 'YES' : 'NO') . "\n";
    
    $userLimits = $user->getSubscriptionLimits();
    if ($userLimits) {
        echo "User Limits:\n";
        echo "  * Max Projects: " . ($userLimits->max_projects_owned ?? 'unlimited') . "\n";
        echo "  * Max Active Pitches: " . ($userLimits->max_active_pitches ?? 'unlimited') . "\n";
        echo "  * Storage per Project: {$userLimits->storage_per_project_mb}MB\n";
    }
    
    echo "\nCurrent Usage:\n";
    echo "  * Projects Count: " . $user->projects()->count() . "\n";
    
    $activePitches = $user->pitches()->whereIn('status', [
        Pitch::STATUS_PENDING,
        Pitch::STATUS_IN_PROGRESS,
        Pitch::STATUS_READY_FOR_REVIEW,
        Pitch::STATUS_PENDING_REVIEW,
    ])->count();
    echo "  * Active Pitches: {$activePitches}\n";
    echo "  * Monthly Pitch Count: {$user->monthly_pitch_count}\n";
    
    echo "\nPermissions:\n";
    echo "  * Can Create Project: " . ($user->canCreateProject() ? 'YES' : 'NO') . "\n";
    
    // Test canCreatePitch with a project
    $project = $user->projects()->first();
    if ($project) {
        echo "  * Can Create Pitch (for project '{$project->title}'): " . ($user->canCreatePitch($project) ? 'YES' : 'NO') . "\n";
    } else {
        echo "  * Can Create Pitch: NO PROJECTS AVAILABLE TO TEST\n";
    }
    
    echo "  * Can Create Monthly Pitch: " . ($user->canCreateMonthlyPitch() ? 'YES' : 'NO') . "\n";
    
    // Show limit violations
    if (!$user->canCreateProject()) {
        echo "\n  🚨 LIMIT VIOLATION: User has " . $user->projects()->count() . " projects but limit is " . ($userLimits->max_projects_owned ?? 'unlimited') . "\n";
    }
    
} else {
    echo "No users found in database\n";
}

// Test 3: Project Storage Limits
echo "\n\n3. TESTING PROJECT STORAGE LIMITS:\n";
echo "-----------------------------------\n";
if ($user) {
    $project = $user->projects()->first();
    if ($project) {
        echo "Test Project: {$project->title}\n";
        echo "Storage Limit: " . number_format($project->getStorageLimit() / (1024 * 1024), 2) . "MB\n";
        echo "Storage Used: " . number_format($project->total_storage_used / (1024 * 1024), 2) . "MB\n";
        echo "Storage Used %: " . $project->getStorageUsedPercentage() . "%\n";
        echo "Has Storage Capacity: " . ($project->hasStorageCapacity(1024 * 1024) ? 'YES' : 'NO') . " (for 1MB file)\n";
        echo "Remaining Storage: " . number_format($project->getRemainingStorageBytes() / (1024 * 1024), 2) . "MB\n";
        echo "Storage Limit Message: " . $project->getStorageLimitMessage() . "\n";
    } else {
        echo "No projects found for test user\n";
    }
}

// Test 4: Configuration Check
echo "\n\n4. TESTING CONFIGURATION:\n";
echo "--------------------------\n";
echo "Stripe Prices Config:\n";
$proArtistPrice = config('subscription.stripe_prices.pro_artist');
$proEngineerPrice = config('subscription.stripe_prices.pro_engineer');
echo "  * Pro Artist Price ID: " . ($proArtistPrice ?: 'NOT SET') . "\n";
echo "  * Pro Engineer Price ID: " . ($proEngineerPrice ?: 'NOT SET') . "\n";

echo "\nPlans Config:\n";
$plans = config('subscription.plans', []);
foreach ($plans as $key => $plan) {
    echo "  * {$key}: {$plan['name']} - \${$plan['price']}/month\n";
}

// Test 5: Middleware Check
echo "\n\n5. TESTING MIDDLEWARE REGISTRATION:\n";
echo "-----------------------------------\n";
$middleware = app()->make('Illuminate\Contracts\Http\Kernel');
echo "SubscriptionCheck middleware exists: ";
try {
    $middlewareInstance = app()->make('App\Http\Middleware\SubscriptionCheck');
    echo "YES\n";
} catch (Exception $e) {
    echo "NO - " . $e->getMessage() . "\n";
}

// Test 6: Check Notifications
echo "\n\n6. TESTING NOTIFICATION CLASSES:\n";
echo "---------------------------------\n";
$notifications = [
    'SubscriptionUpgraded' => 'App\Notifications\SubscriptionUpgraded',
    'SubscriptionCancelled' => 'App\Notifications\SubscriptionCancelled',
    'LimitReached' => 'App\Notifications\LimitReached',
];

foreach ($notifications as $name => $class) {
    echo "  * {$name}: ";
    if (class_exists($class)) {
        echo "EXISTS\n";
    } else {
        echo "MISSING\n";
    }
}

echo "\n==================================\n";
echo "SUBSCRIPTION SYSTEM TEST COMPLETE\n";
echo "==================================\n"; 