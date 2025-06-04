<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Http\Middleware\SubscriptionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "==========================================\n";
echo "TESTING SUBSCRIPTION MIDDLEWARE ENFORCEMENT\n";
echo "==========================================\n\n";

// Get test user
$user = User::first();
if (!$user) {
    echo "No users found in database. Please create a user first.\n";
    exit(1);
}

echo "Testing with user: {$user->email}\n";
echo "Current plan: {$user->subscription_plan}.{$user->subscription_tier}\n";
echo "Current projects: " . $user->projects()->count() . "\n";
echo "Project limit: " . ($user->getSubscriptionLimits()->max_projects_owned ?? 'unlimited') . "\n\n";

// Test 1: Project Creation Enforcement
echo "TEST 1: PROJECT CREATION ENFORCEMENT\n";
echo "=====================================\n";

try {
    $middleware = new SubscriptionCheck();
    
    // Create a mock request
    $request = Request::create('/projects/store', 'POST');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    echo "Simulating project creation request...\n";
    
    $next = function ($request) {
        return new Response('Success: Project would be created');
    };
    
    $response = $middleware->handle($request, $next, 'create_project');
    
    if ($response->getStatusCode() === 302) {
        // Redirection means blocked
        echo "✅ BLOCKED: User correctly blocked from creating project\n";
        echo "Redirect to: " . $response->headers->get('location') . "\n";
    } else {
        // Successful means allowed
        echo "✅ ALLOWED: User can create project\n";
        echo "Response: " . $response->getContent() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Pitch Creation Enforcement
echo "TEST 2: PITCH CREATION ENFORCEMENT\n";
echo "===================================\n";

$project = $user->projects()->first();
if ($project) {
    try {
        $request = Request::create("/projects/{$project->id}/pitches", 'POST');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        echo "Simulating pitch creation request for project: {$project->title}\n";
        
        $response = $middleware->handle($request, $next, 'create_pitch');
        
        if ($response->getStatusCode() === 302) {
            echo "✅ BLOCKED: User correctly blocked from creating pitch\n";
            echo "Redirect to: " . $response->headers->get('location') . "\n";
        } else {
            echo "✅ ALLOWED: User can create pitch\n";
            echo "Response: " . $response->getContent() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "No projects available to test pitch creation\n";
}

echo "\n";

// Test 3: Test what happens if we upgrade the user
echo "TEST 3: SIMULATING PRO UPGRADE\n";
echo "===============================\n";

echo "Temporarily upgrading user to Pro Artist...\n";
$originalPlan = $user->subscription_plan;
$originalTier = $user->subscription_tier;

$user->update([
    'subscription_plan' => 'pro',
    'subscription_tier' => 'artist'
]);

echo "User is now on: {$user->subscription_plan}.{$user->subscription_tier}\n";
echo "New limits:\n";
$proLimits = $user->getSubscriptionLimits();
echo "- Max Projects: " . ($proLimits->max_projects_owned ?? 'unlimited') . "\n";
echo "- Max Active Pitches: " . ($proLimits->max_active_pitches ?? 'unlimited') . "\n";
echo "- Storage per Project: {$proLimits->storage_per_project_mb}MB\n\n";

// Test project creation with Pro plan
try {
    $request = Request::create('/projects/store', 'POST');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    echo "Testing project creation with Pro plan...\n";
    $response = $middleware->handle($request, $next, 'create_project');
    
    if ($response->getStatusCode() === 302) {
        echo "❌ UNEXPECTED: Pro user blocked from creating project\n";
    } else {
        echo "✅ SUCCESS: Pro user can create unlimited projects\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Restore original plan
echo "\nRestoring original plan...\n";
$user->update([
    'subscription_plan' => $originalPlan,
    'subscription_tier' => $originalTier
]);

echo "\n";

// Test 4: Storage Limit Testing
echo "TEST 4: STORAGE LIMIT TESTING\n";
echo "==============================\n";

if ($project) {
    echo "Testing storage limits for project: {$project->title}\n";
    echo "Current storage limit: " . number_format($project->getStorageLimit() / (1024 * 1024), 2) . "MB\n";
    echo "Current storage used: " . number_format($project->total_storage_used / (1024 * 1024), 2) . "MB\n";
    echo "Storage limit is based on user's subscription: " . ($project->getStorageLimit() === $user->getProjectStorageLimit() ? 'YES' : 'NO') . "\n";
    
    // Test capacity checks
    $testSizes = [1024 * 1024, 10 * 1024 * 1024, 50 * 1024 * 1024, 200 * 1024 * 1024]; // 1MB, 10MB, 50MB, 200MB
    foreach ($testSizes as $size) {
        $sizeMB = number_format($size / (1024 * 1024), 0);
        $canUpload = $project->hasStorageCapacity($size);
        echo "Can upload {$sizeMB}MB file: " . ($canUpload ? 'YES' : 'NO') . "\n";
    }
}

echo "\n==========================================\n";
echo "MIDDLEWARE ENFORCEMENT TEST COMPLETE\n";
echo "==========================================\n"; 