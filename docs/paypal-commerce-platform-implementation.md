# PayPal Commerce Platform Implementation Guide

## Overview
PayPal Commerce Platform is PayPal's marketplace solution, equivalent to Stripe Connect. It enables direct payments from customers to sellers with platform fee capabilities.

## Setup Requirements

### 1. Platform Approval
- Apply for PayPal Commerce Platform access
- Fill out the partnership application form
- Requires business verification
- Approval process takes 1-2 weeks

### 2. API Credentials Needed
```env
# PayPal Commerce Platform
PAYPAL_PARTNER_ID=your_partner_id
PAYPAL_PARTNER_CLIENT_ID=your_client_id
PAYPAL_PARTNER_CLIENT_SECRET=your_client_secret
PAYPAL_PARTNER_BN_CODE=your_bn_code
PAYPAL_ENVIRONMENT=sandbox # or production
```

### 3. Key Features
- **Connected Path**: Direct payments to seller accounts
- **Platform Fees**: Commission on each transaction
- **OAuth Onboarding**: Sellers connect existing PayPal accounts
- **Webhook Support**: Real-time event notifications

## Implementation Architecture

### Database Changes Needed
```sql
-- Update user_payout_accounts table
ALTER TABLE user_payout_accounts 
ADD COLUMN paypal_merchant_id VARCHAR(255),
ADD COLUMN paypal_onboarding_status VARCHAR(50),
ADD COLUMN paypal_permissions JSON,
ADD COLUMN paypal_primary_email VARCHAR(255);

-- Add PayPal-specific tracking
CREATE TABLE paypal_onboarding_links (
    id BIGINT PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    action_url TEXT,
    expires_at TIMESTAMP,
    completed_at TIMESTAMP,
    auth_code VARCHAR(255),
    shared_id VARCHAR(255)
);
```

### Service Architecture
```
PayPalCommerceProvider (implements PayoutProviderInterface)
├── OAuth Onboarding Flow
├── Account Status Management  
├── Payment Processing with Fees
├── Webhook Handling
└── Seller Management
```

## Implementation Steps

### Step 1: Create PayPalCommerceProvider
```php
class PayPalCommerceProvider extends AbstractPayoutProvider
{
    // Partner Referrals API for onboarding
    public function createOnboardingLink(User $user): array
    {
        // Generate signup link with Partner Referrals API
        // Store tracking URL in database
        // Return action_url for redirect
    }
    
    // Complete onboarding with authCode
    public function completeOnboarding(string $authCode, string $sharedId): array
    {
        // Exchange authCode for access token
        // Store merchant details
        // Update account status
    }
    
    // Process payment with platform fee
    public function processPaymentWithFee($order, $fee): array
    {
        // Create order with PLATFORM_FEES
        // Direct payment to seller
        // Platform receives commission
    }
}
```

### Step 2: Update Livewire Component
```php
// PayoutProviderSelector.php additions
public function getPayPalOnboardingLink()
{
    $provider = $this->registry->get('paypal_commerce');
    $result = $provider->createOnboardingLink($this->user);
    
    if ($result['success']) {
        // Open PayPal signup in popup/redirect
        $this->dispatchBrowserEvent('open-paypal-onboarding', [
            'url' => $result['action_url']
        ]);
    }
}
```

### Step 3: Webhook Controller
```php
class PayPalWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->all();
        
        switch($event['event_type']) {
            case 'MERCHANT.ONBOARDING.COMPLETED':
                // Mark account as connected
                break;
            case 'MERCHANT.PARTNER-CONSENT.REVOKED':
                // Handle disconnection
                break;
            case 'PAYMENT.CAPTURE.COMPLETED':
                // Handle successful payment
                break;
        }
    }
}
```

## UI/UX Flow

### Seller Onboarding
1. Seller clicks "Connect PayPal" button
2. Redirect to PayPal signup/login
3. Grant permissions to platform
4. Return to platform with success
5. Account marked as connected

### Payment Flow
1. Customer selects PayPal at checkout
2. Payment goes directly to seller's PayPal
3. Platform fee automatically deducted
4. Both parties receive notifications

## Advantages Over Simple Payouts

1. **Direct Payment Flow**: Money goes directly to sellers
2. **Lower Risk**: No money held by platform
3. **Automatic Fees**: Commission handled by PayPal
4. **Professional**: Sellers see your platform branding
5. **Compliance**: PayPal handles regulatory requirements

## Migration Strategy

### Phase 1: Add Commerce Platform
- Keep existing Stripe Connect
- Add PayPal Commerce as new option
- Existing PayPal Payouts users unaffected

### Phase 2: Migration Options
- Offer users choice to upgrade
- Provide comparison of features
- Gradual migration with incentives

### Phase 3: Deprecate Simple Payouts
- After majority migrated
- Keep for legacy users
- Eventually sunset simple version

## Testing Approach

### Sandbox Testing
1. Create sandbox partner account
2. Test seller onboarding flow
3. Process test transactions
4. Verify webhook handling
5. Test fee calculations

### Integration Tests
```php
public function test_paypal_commerce_onboarding()
{
    $user = User::factory()->create();
    $provider = new PayPalCommerceProvider(config('payouts.providers.paypal_commerce'));
    
    $result = $provider->createOnboardingLink($user);
    
    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['action_url']);
}
```

## Configuration Updates

```php
// config/payouts.php
'paypal_commerce' => [
    'enabled' => env('PAYPAL_COMMERCE_ENABLED', false),
    'partner_id' => env('PAYPAL_PARTNER_ID'),
    'client_id' => env('PAYPAL_PARTNER_CLIENT_ID'),
    'client_secret' => env('PAYPAL_PARTNER_CLIENT_SECRET'),
    'bn_code' => env('PAYPAL_PARTNER_BN_CODE'),
    'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
],
```

## Timeline Estimate

1. **Week 1**: Apply for partner access, setup sandbox
2. **Week 2**: Implement PayPalCommerceProvider
3. **Week 3**: Update UI, add onboarding flow
4. **Week 4**: Testing and refinement
5. **Week 5**: Production deployment

## Next Steps

1. Apply for PayPal Commerce Platform partnership
2. Decide on migration strategy for existing users
3. Update database schema for new fields
4. Implement PayPalCommerceProvider service
5. Add webhook endpoints
6. Update UI for dual PayPal options