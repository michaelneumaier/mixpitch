# MixPitch Stripe Subscription Setup Guide

## Overview

This guide walks you through setting up Stripe subscriptions for MixPitch's three-tier subscription system:
- **Free Plan**: $0/month (no Stripe product needed)
- **Pro Artist**: $29/month 
- **Pro Engineer**: $19/month

## Step 1: Stripe Dashboard Setup

### 1.1 Create Products in Stripe Dashboard

1. **Log into your Stripe Dashboard** at https://dashboard.stripe.com
2. **Go to Products** (in the left sidebar)
3. **Create Pro Artist Product**:
   - Click "Add product"
   - Name: `MixPitch Pro Artist`
   - Description: `Unlimited projects, priority support, and custom portfolio features for artists`
   - Click "Save product"

4. **Create Pro Engineer Product**:
   - Click "Add product" 
   - Name: `MixPitch Pro Engineer`
   - Description: `Unlimited projects with monthly pitch limits, priority support for engineers`
   - Click "Save product"

### 1.2 Create Prices for Each Product

For **Pro Artist Product**:
1. Click on the Pro Artist product
2. Click "Add price"
3. Configure:
   - **Pricing model**: Standard pricing
   - **Price**: $29.00
   - **Billing period**: Monthly
   - **Currency**: USD
4. Click "Save price"
5. **Copy the Price ID** (starts with `price_`) - you'll need this!

For **Pro Engineer Product**:
1. Click on the Pro Engineer product  
2. Click "Add price"
3. Configure:
   - **Pricing model**: Standard pricing
   - **Price**: $19.00
   - **Billing period**: Monthly
   - **Currency**: USD
4. Click "Save price"
5. **Copy the Price ID** (starts with `price_`) - you'll need this!

### 1.3 Set Up Webhooks

1. **Go to Developers > Webhooks** in Stripe Dashboard
2. **Click "Add endpoint"**
3. **Configure**:
   - **Endpoint URL**: `https://yourdomain.com/stripe/webhook`
   - **Description**: `MixPitch Subscription Events`
   - **Events to send**: Select these events:
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `customer.subscription.trial_will_end`
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
4. **Click "Add endpoint"**
5. **Copy the Webhook Signing Secret** - you'll need this!

## Step 2: Environment Configuration

### 2.1 Add Stripe Keys to .env

Add these variables to your `.env` file:

```env
# Stripe Configuration
STRIPE_KEY=pk_test_... # Your Stripe Publishable Key
STRIPE_SECRET=sk_test_... # Your Stripe Secret Key
STRIPE_WEBHOOK_SECRET=whsec_... # Your Webhook Signing Secret

# Stripe Price IDs (replace with your actual Price IDs)
STRIPE_PRICE_PRO_ARTIST=price_1ABC123... # Pro Artist Price ID
STRIPE_PRICE_PRO_ENGINEER=price_1DEF456... # Pro Engineer Price ID
```

### 2.2 Where to Find Your Stripe Keys

**Publishable Key & Secret Key**:
1. Go to **Developers > API keys** in Stripe Dashboard
2. Copy the **Publishable key** (starts with `pk_test_` or `pk_live_`)
3. Click "Reveal" for **Secret key** (starts with `sk_test_` or `sk_live_`)

**Webhook Secret**:
1. Go to **Developers > Webhooks**
2. Click on your webhook endpoint
3. In the "Signing secret" section, click "Reveal"
4. Copy the secret (starts with `whsec_`)

## Step 3: Test the Integration

### 3.1 Verify Configuration

Run this command to test your Stripe connection:

```bash
php artisan tinker
```

Then in tinker:
```php
// Test Stripe connection
\Stripe\Stripe::setApiKey(config('cashier.secret'));
\Stripe\Product::all(['limit' => 3]);

// Test price retrieval
\Stripe\Price::retrieve(config('subscription.stripe_prices.pro_artist'));
\Stripe\Price::retrieve(config('subscription.stripe_prices.pro_engineer'));
```

### 3.2 Test Subscription Creation

Create a test subscription to verify everything works:

```bash
php artisan tinker
```

```php
// Get a test user
$user = App\Models\User::first();

// Create a test subscription (Pro Artist)
$user->newSubscription('default', config('subscription.stripe_prices.pro_artist'))
    ->create('pm_card_visa'); // Test payment method

// Check subscription status
$user->fresh()->subscribed('default');
```

## Step 4: Webhook Testing

### 4.1 Test Webhook Locally

For local development, use Stripe CLI:

1. **Install Stripe CLI**: https://stripe.com/docs/stripe-cli
2. **Login to Stripe CLI**:
   ```bash
   stripe login
   ```
3. **Forward events to local app**:
   ```bash
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```
4. **Copy the webhook signing secret** from the CLI output to your `.env`

### 4.2 Trigger Test Events

```bash
# Test subscription creation
stripe trigger customer.subscription.created

# Test subscription update
stripe trigger customer.subscription.updated

# Test payment success
stripe trigger invoice.payment_succeeded
```

## Step 5: Production Deployment

### 5.1 Switch to Live Mode

1. **Toggle to "Live" mode** in Stripe Dashboard (top right)
2. **Create live products and prices** (same process as test mode)
3. **Update webhook endpoint** to production URL
4. **Update .env with live keys**:
   ```env
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_live_...
   ```

### 5.2 Verify Live Configuration

```bash
# Clear config cache
php artisan config:clear

# Verify live prices exist
php artisan tinker
\Stripe\Stripe::setApiKey(config('cashier.secret'));
\Stripe\Price::retrieve(config('subscription.stripe_prices.pro_artist'));
```

## Step 6: Advanced Configuration

### 6.1 Tax Configuration (Optional)

If you need to collect taxes:

1. **Go to Settings > Tax** in Stripe Dashboard
2. **Enable tax collection**
3. **Configure tax rates** for your regions
4. **Update subscription creation** to include tax:

```php
$user->newSubscription('default', $priceId)
    ->withTaxRates(['txr_1ABC123...']) // Tax rate ID
    ->create($paymentMethod);
```

### 6.2 Proration Settings

Configure how plan changes are handled:

```php
$user->subscription('default')
    ->prorate()
    ->swapAndInvoice(config('subscription.stripe_prices.pro_engineer'));
```

## Testing Checklist

Before going live, verify:

- [ ] Products created in Stripe Dashboard
- [ ] Prices configured correctly ($29 and $19)
- [ ] Webhook endpoint configured and receiving events
- [ ] Environment variables set correctly
- [ ] Test subscription creation works
- [ ] Test subscription updates work
- [ ] Test subscription cancellation works
- [ ] Webhook events are being processed
- [ ] User subscription status updates correctly

## Troubleshooting

### Common Issues

**"No such price" error**:
- Verify Price ID is correct in `.env`
- Ensure you're using the right mode (test vs live)

**Webhook not receiving events**:
- Check webhook URL is accessible
- Verify webhook secret is correct
- Check webhook event types are selected

**Payment method required**:
- Use test payment methods: https://stripe.com/docs/testing
- For production, implement proper payment method collection

### Stripe Test Cards

For testing:
- **Visa**: `4242424242424242`
- **Visa (declined)**: `4000000000000002`
- **Mastercard**: `5555555555554444`

## Support

- **Stripe Documentation**: https://stripe.com/docs
- **Laravel Cashier**: https://laravel.com/docs/billing
- **Stripe CLI**: https://stripe.com/docs/stripe-cli 