<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "TESTING SUBSCRIPTION SUCCESS PAGE\n";
echo "========================================\n\n";

// Test 1: Check if routes exist
echo "1. CHECKING ROUTE EXISTENCE:\n";
echo "-----------------------------\n";

$routes = [
    'subscription.success' => 'Subscription success page',
    'billing' => 'Billing dashboard',
    'dashboard' => 'Main dashboard',
    'projects.create' => 'Create project page',
    'subscription.index' => 'Subscription management'
];

foreach ($routes as $routeName => $description) {
    try {
        $url = route($routeName);
        echo "✅ {$routeName}: {$url}\n";
    } catch (Exception $e) {
        echo "❌ {$routeName}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 2: Try to render the success view
echo "2. TESTING SUCCESS VIEW RENDERING:\n";
echo "-----------------------------------\n";

try {
    // Create a mock request to simulate the success page
    $app = app();
    $user = App\Models\User::first();
    
    if ($user) {
        // Simulate authenticated user
        auth()->login($user);
        
        // Try to render the view
        $view = view('subscription.success');
        $content = $view->render();
        
        echo "✅ Success view renders without errors\n";
        echo "Content length: " . number_format(strlen($content)) . " characters\n";
        
        // Check for specific content
        if (strpos($content, 'Welcome to Pro!') !== false) {
            echo "✅ Contains expected success message\n";
        } else {
            echo "⚠️  Missing expected success message\n";
        }
        
        if (strpos($content, 'billing dashboard') !== false) {
            echo "✅ Contains billing dashboard link\n";
        } else {
            echo "⚠️  Missing billing dashboard link\n";
        }
        
    } else {
        echo "⚠️  No user found to test authentication\n";
    }
    
} catch (Exception $e) {
    echo "❌ View rendering failed: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// Test 3: Check subscription controller method
echo "3. TESTING SUBSCRIPTION CONTROLLER:\n";
echo "------------------------------------\n";

try {
    $controller = new App\Http\Controllers\SubscriptionController();
    echo "✅ SubscriptionController instantiated successfully\n";
    
    if (method_exists($controller, 'success')) {
        echo "✅ success() method exists\n";
    } else {
        echo "❌ success() method missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "SUCCESS PAGE TEST COMPLETE\n";
echo "========================================\n"; 