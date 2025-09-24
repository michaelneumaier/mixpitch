<?php

require_once __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "TESTING STRIPE CONNECTION & SETUP\n";
echo "========================================\n\n";

// Test 1: Check Environment Variables
echo "1. CHECKING ENVIRONMENT VARIABLES:\n";
echo "-----------------------------------\n";

$stripeKey = config('cashier.key');
$stripeSecret = config('cashier.secret');
$webhookSecret = config('cashier.webhook.secret');
$proArtistPrice = config('subscription.stripe_prices.pro_artist');
$proEngineerPrice = config('subscription.stripe_prices.pro_engineer');

echo 'Stripe Publishable Key: '.($stripeKey ? 'SET (starts with: '.substr($stripeKey, 0, 8).'...)' : 'NOT SET')."\n";
echo 'Stripe Secret Key: '.($stripeSecret ? 'SET (starts with: '.substr($stripeSecret, 0, 8).'...)' : 'NOT SET')."\n";
echo 'Webhook Secret: '.($webhookSecret ? 'SET (starts with: '.substr($webhookSecret, 0, 8).'...)' : 'NOT SET')."\n";
echo 'Pro Artist Price ID: '.($proArtistPrice ?: 'NOT SET')."\n";
echo 'Pro Engineer Price ID: '.($proEngineerPrice ?: 'NOT SET')."\n\n";

if (! $stripeSecret) {
    echo "❌ CANNOT CONTINUE: Stripe Secret Key not configured\n";
    echo "Please add STRIPE_SECRET to your .env file\n";
    exit(1);
}

// Test 2: Test Stripe Connection
echo "2. TESTING STRIPE CONNECTION:\n";
echo "------------------------------\n";

try {
    \Stripe\Stripe::setApiKey($stripeSecret);

    // Test basic connection
    $account = \Stripe\Account::retrieve();
    echo "✅ Stripe Connection: SUCCESS\n";
    echo 'Account ID: '.$account->id."\n";
    echo 'Account Name: '.($account->business_profile->name ?? 'Not set')."\n";
    echo 'Test Mode: '.($account->livemode ? 'NO (Live mode)' : 'YES (Test mode)')."\n\n";

} catch (Exception $e) {
    echo "❌ Stripe Connection: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n\n";
    exit(1);
}

// Test 3: Check Products
echo "3. CHECKING STRIPE PRODUCTS:\n";
echo "-----------------------------\n";

try {
    $products = \Stripe\Product::all(['limit' => 10]);
    echo 'Total Products: '.count($products->data)."\n";

    if (count($products->data) > 0) {
        echo "Products found:\n";
        foreach ($products->data as $product) {
            echo '- '.$product->name.' (ID: '.$product->id.")\n";
        }
    } else {
        echo "⚠️  No products found. You'll need to create them in Stripe Dashboard.\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo '❌ Error fetching products: '.$e->getMessage()."\n\n";
}

// Test 4: Check Prices
echo "4. CHECKING STRIPE PRICES:\n";
echo "---------------------------\n";

if ($proArtistPrice && $proArtistPrice !== 'price_1234567890') {
    try {
        $price = \Stripe\Price::retrieve($proArtistPrice);
        echo "✅ Pro Artist Price: FOUND\n";
        echo '   - Price: $'.number_format($price->unit_amount / 100, 2)."\n";
        echo '   - Currency: '.strtoupper($price->currency)."\n";
        echo '   - Interval: '.$price->recurring->interval."\n";
    } catch (Exception $e) {
        echo '❌ Pro Artist Price: ERROR - '.$e->getMessage()."\n";
    }
} else {
    echo "⚠️  Pro Artist Price: NOT CONFIGURED\n";
}

if ($proEngineerPrice && $proEngineerPrice !== 'price_0987654321') {
    try {
        $price = \Stripe\Price::retrieve($proEngineerPrice);
        echo "✅ Pro Engineer Price: FOUND\n";
        echo '   - Price: $'.number_format($price->unit_amount / 100, 2)."\n";
        echo '   - Currency: '.strtoupper($price->currency)."\n";
        echo '   - Interval: '.$price->recurring->interval."\n";
    } catch (Exception $e) {
        echo '❌ Pro Engineer Price: ERROR - '.$e->getMessage()."\n";
    }
} else {
    echo "⚠️  Pro Engineer Price: NOT CONFIGURED\n";
}

echo "\n";

// Test 5: Test Customer Creation
echo "5. TESTING CUSTOMER CREATION:\n";
echo "------------------------------\n";

try {
    $testCustomer = \Stripe\Customer::create([
        'email' => 'test@example.com',
        'name' => 'Test Customer',
        'description' => 'Test customer for MixPitch setup verification',
    ]);

    echo "✅ Customer Creation: SUCCESS\n";
    echo 'Customer ID: '.$testCustomer->id."\n";

    // Clean up test customer
    $testCustomer->delete();
    echo "✅ Customer Cleanup: SUCCESS\n\n";

} catch (Exception $e) {
    echo "❌ Customer Creation: FAILED\n";
    echo 'Error: '.$e->getMessage()."\n\n";
}

// Test 6: Webhook Endpoint Check
echo "6. WEBHOOK CONFIGURATION:\n";
echo "--------------------------\n";

try {
    $webhooks = \Stripe\WebhookEndpoint::all();

    if (count($webhooks->data) > 0) {
        echo "Webhook endpoints found:\n";
        foreach ($webhooks->data as $webhook) {
            echo '- URL: '.$webhook->url."\n";
            echo '  Status: '.($webhook->status === 'enabled' ? '✅ Enabled' : '❌ Disabled')."\n";
            echo '  Events: '.count($webhook->enabled_events)." events configured\n";
        }
    } else {
        echo "⚠️  No webhook endpoints found. You'll need to create one.\n";
    }

} catch (Exception $e) {
    echo '❌ Error checking webhooks: '.$e->getMessage()."\n";
}

echo "\n========================================\n";
echo "STRIPE CONNECTION TEST COMPLETE\n";
echo "========================================\n\n";

// Summary
echo "SETUP SUMMARY:\n";
echo "---------------\n";

$checks = [
    'Environment Variables' => $stripeKey && $stripeSecret,
    'Stripe Connection' => true, // We got here, so connection worked
    'Webhook Secret' => (bool) $webhookSecret,
    'Pro Artist Price' => $proArtistPrice && $proArtistPrice !== 'price_1234567890',
    'Pro Engineer Price' => $proEngineerPrice && $proEngineerPrice !== 'price_0987654321',
];

foreach ($checks as $check => $status) {
    echo ($status ? '✅' : '❌')." {$check}\n";
}

echo "\nNEXT STEPS:\n";
echo "-----------\n";

if (! $proArtistPrice || $proArtistPrice === 'price_1234567890') {
    echo "1. Create Pro Artist product and price in Stripe Dashboard\n";
    echo "2. Update STRIPE_PRICE_PRO_ARTIST in your .env file\n";
}

if (! $proEngineerPrice || $proEngineerPrice === 'price_0987654321') {
    echo "3. Create Pro Engineer product and price in Stripe Dashboard\n";
    echo "4. Update STRIPE_PRICE_PRO_ENGINEER in your .env file\n";
}

if (! $webhookSecret) {
    echo "5. Set up webhook endpoint in Stripe Dashboard\n";
    echo "6. Update STRIPE_WEBHOOK_SECRET in your .env file\n";
}

echo "\nSee docs/STRIPE_SETUP_GUIDE.md for detailed instructions.\n";
