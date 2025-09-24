<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Models\User;
use App\Notifications\LimitReached;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\SubscriptionUpgraded;
use Illuminate\Support\Facades\Notification;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=======================================\n";
echo "TESTING SUBSCRIPTION VIEWS & NOTIFICATIONS\n";
echo "=======================================\n\n";

// Test 1: Check that views exist
echo "TEST 1: CHECKING VIEW FILES\n";
echo "============================\n";

$viewsToCheck = [
    'subscription.index' => 'resources/views/subscription/index.blade.php',
    'subscription.success' => 'resources/views/subscription/success.blade.php',
    'pricing' => 'resources/views/pricing.blade.php',
    'dashboard' => 'resources/views/dashboard.blade.php',
];

foreach ($viewsToCheck as $viewName => $filePath) {
    if (file_exists($filePath)) {
        echo "✅ {$viewName}: EXISTS\n";

        // Check if file has content
        $content = file_get_contents($filePath);
        if (strlen($content) > 100) {
            echo '   - Content length: '.number_format(strlen($content))." characters\n";
        } else {
            echo '   ⚠️  WARNING: File seems too small ('.strlen($content)." characters)\n";
        }
    } else {
        echo "❌ {$viewName}: MISSING at {$filePath}\n";
    }
}

echo "\n";

// Test 2: Test Subscription Controller Methods
echo "TEST 2: TESTING SUBSCRIPTION CONTROLLER\n";
echo "========================================\n";

try {
    $controller = new \App\Http\Controllers\SubscriptionController;
    echo "✅ SubscriptionController: Can be instantiated\n";

    // Check if controller methods exist
    $methods = ['index', 'upgrade', 'success', 'cancel', 'downgrade', 'resume'];
    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "✅ Method {$method}: EXISTS\n";
        } else {
            echo "❌ Method {$method}: MISSING\n";
        }
    }
} catch (Exception $e) {
    echo '❌ SubscriptionController: ERROR - '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Test Notification Classes
echo "TEST 3: TESTING NOTIFICATION SYSTEM\n";
echo "====================================\n";

$user = User::first();
if ($user) {
    echo "Testing notifications with user: {$user->email}\n\n";

    // Test SubscriptionUpgraded notification
    try {
        $notification = new SubscriptionUpgraded('pro', 'artist');
        echo "✅ SubscriptionUpgraded: Can be created\n";

        // Test if it can generate mail message
        $mailMessage = $notification->toMail($user);
        echo '   - Subject: '.$mailMessage->subject."\n";
        echo '   - Lines count: '.count($mailMessage->lines)."\n";

    } catch (Exception $e) {
        echo '❌ SubscriptionUpgraded: ERROR - '.$e->getMessage()."\n";
    }

    // Test SubscriptionCancelled notification
    try {
        $notification = new SubscriptionCancelled('Pro Artist', now()->addMonth());
        echo "✅ SubscriptionCancelled: Can be created\n";

        $mailMessage = $notification->toMail($user);
        echo '   - Subject: '.$mailMessage->subject."\n";

    } catch (Exception $e) {
        echo '❌ SubscriptionCancelled: ERROR - '.$e->getMessage()."\n";
    }

    // Test LimitReached notification
    try {
        $notification = new LimitReached('projects', 3, 1);
        echo "✅ LimitReached: Can be created\n";

        $mailMessage = $notification->toMail($user);
        echo '   - Subject: '.$mailMessage->subject."\n";

    } catch (Exception $e) {
        echo '❌ LimitReached: ERROR - '.$e->getMessage()."\n";
    }

} else {
    echo "No users found to test notifications\n";
}

echo "\n";

// Test 4: Test Configuration Values
echo "TEST 4: TESTING CONFIGURATION\n";
echo "==============================\n";

$requiredConfigs = [
    'subscription.stripe_prices.pro_artist',
    'subscription.stripe_prices.pro_engineer',
    'subscription.plans.free.name',
    'subscription.plans.pro_artist.name',
    'subscription.plans.pro_engineer.name',
];

foreach ($requiredConfigs as $configKey) {
    $value = config($configKey);
    if ($value) {
        echo "✅ {$configKey}: '{$value}'\n";
    } else {
        echo "❌ {$configKey}: NOT SET\n";
    }
}

echo "\n";

// Test 5: Test Dashboard Data Preparation
echo "TEST 5: TESTING DASHBOARD DATA\n";
echo "===============================\n";

if ($user) {
    try {
        $limits = $user->getSubscriptionLimits();

        // Test usage data calculation (like what dashboard would show)
        $usage = [
            'projects_count' => $user->projects()->count(),
            'active_pitches_count' => $user->pitches()->whereIn('status', [
                \App\Models\Pitch::STATUS_PENDING,
                \App\Models\Pitch::STATUS_IN_PROGRESS,
                \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                \App\Models\Pitch::STATUS_PENDING_REVIEW,
            ])->count(),
            'monthly_pitches_used' => $user->monthly_pitch_count,
        ];

        echo "✅ Usage data calculation: SUCCESS\n";
        echo "   - Projects: {$usage['projects_count']} / ".($limits->max_projects_owned ?? 'unlimited')."\n";
        echo "   - Active Pitches: {$usage['active_pitches_count']} / ".($limits->max_active_pitches ?? 'unlimited')."\n";
        echo "   - Monthly Pitches: {$usage['monthly_pitches_used']} / ".($limits->max_monthly_pitches ?? 'N/A')."\n";

        // Test alert conditions
        $alerts = [];
        if ($limits && $limits->max_projects_owned && $usage['projects_count'] >= $limits->max_projects_owned) {
            $alerts[] = 'Project limit reached';
        }
        if ($limits && $limits->max_active_pitches && $usage['active_pitches_count'] >= $limits->max_active_pitches) {
            $alerts[] = 'Active pitch limit reached';
        }
        if ($limits && $limits->max_monthly_pitches && $usage['monthly_pitches_used'] >= $limits->max_monthly_pitches) {
            $alerts[] = 'Monthly pitch limit reached';
        }

        if (! empty($alerts)) {
            echo "⚠️  Active alerts:\n";
            foreach ($alerts as $alert) {
                echo "   - {$alert}\n";
            }
        } else {
            echo "✅ No limit alerts\n";
        }

    } catch (Exception $e) {
        echo '❌ Dashboard data: ERROR - '.$e->getMessage()."\n";
    }
}

echo "\n=======================================\n";
echo "SUBSCRIPTION VIEWS & NOTIFICATIONS TEST COMPLETE\n";
echo "=======================================\n";
